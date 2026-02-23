<?php

declare(strict_types=1);

namespace App\Application\UseCases\Inventory;

use App\Application\DTO\Inventory\StockLedgerEntry;
use App\Application\Ports\Repositories\InventoryStockRepositoryPort;
use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Repositories\StockLedgerRepositoryPort;
use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Application\UseCases\Notifications\NotifyLowStockForProductRequest;
use App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase;
use App\Domain\Audit\AuditEntry;

final readonly class AdjustStockUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private ProductRepositoryPort $products,
        private InventoryStockRepositoryPort $stocks,
        private StockLedgerRepositoryPort $ledger,
        private AuditLoggerPort $audit,
        private ?NotifyLowStockForProductUseCase $lowStock = null,
    ) {}

    public function handle(AdjustStockRequest $req): void
    {
        if ($req->qtyDelta === 0) {
            throw new \InvalidArgumentException('qtyDelta must not be 0');
        }

        // POLICY: stok masuk (qtyDelta > 0) hanya lewat Purchases
        if ($req->qtyDelta > 0) {
            throw new \InvalidArgumentException('stock in is not allowed via adjustment; use purchases');
        }

        $note = trim($req->note);
        if ($note === '') {
            throw new \InvalidArgumentException('note is required');
        }

        $product = $this->products->findById($req->productId);
        if ($product === null) {
            throw new \InvalidArgumentException('product not found');
        }

        $this->tx->run(function () use ($req, $note): void {
            $stock = $this->stocks->lockOrCreateByProductId($req->productId);

            $before = [
                'product_id' => $req->productId,
                'on_hand_qty' => $stock->onHandQty,
                'reserved_qty' => $stock->reservedQty,
                'available_qty' => $stock->availableQty(),
            ];

            $newOnHand = $stock->onHandQty + $req->qtyDelta;
            if ($newOnHand < 0) {
                throw new InvalidStockAdjustment(
                    productId: $req->productId,
                    qtyDelta: $req->qtyDelta,
                    currentOnHandQty: $stock->onHandQty,
                );
            }

            $this->stocks->save($stock->withOnHandQty($newOnHand));

            $this->ledger->append(new StockLedgerEntry(
                productId: $req->productId,
                type: 'ADJUSTMENT',
                qtyDelta: $req->qtyDelta,
                refType: $req->refType,
                refId: $req->refId,
                actorUserId: $req->actorUserId,
                occurredAt: $this->clock->now(),
                note: $note,
            ));

            $after = [
                'product_id' => $req->productId,
                'on_hand_qty' => $newOnHand,
                'reserved_qty' => $stock->reservedQty,
                'available_qty' => $newOnHand - $stock->reservedQty,
            ];

            $this->audit->append(new AuditEntry(
                actorId: $req->actorUserId,
                actorRole: null,
                entityType: 'InventoryStock',
                entityId: $req->productId, // inventory_stocks pk = product_id
                action: 'STOCK_ADJUSTMENT',
                reason: $note,
                before: $before,
                after: $after,
                meta: [
                    'qty_delta' => $req->qtyDelta,
                    'ref_type' => $req->refType,
                    'ref_id' => $req->refId,
                ],
            ));
        });

        if ($this->lowStock === null) {
            return;
        }

        $this->lowStock->handle(new NotifyLowStockForProductRequest(
            productId: $req->productId,
            triggerType: 'ADJUSTMENT',
            actorUserId: $req->actorUserId,
        ));
    }
}
