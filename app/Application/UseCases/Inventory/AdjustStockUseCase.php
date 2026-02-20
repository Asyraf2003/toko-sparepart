<?php

declare(strict_types=1);

namespace App\Application\UseCases\Inventory;

use App\Application\DTO\Inventory\StockLedgerEntry;
use App\Application\Ports\Repositories\InventoryStockRepositoryPort;
use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Repositories\StockLedgerRepositoryPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;

final readonly class AdjustStockUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private ProductRepositoryPort $products,
        private InventoryStockRepositoryPort $stocks,
        private StockLedgerRepositoryPort $ledger,
    ) {}

    public function handle(AdjustStockRequest $req): void
    {
        if ($req->qtyDelta === 0) {
            throw new \InvalidArgumentException('qtyDelta must not be 0');
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
        });
    }
}
