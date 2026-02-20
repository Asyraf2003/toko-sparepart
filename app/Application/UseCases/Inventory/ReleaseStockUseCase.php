<?php

declare(strict_types=1);

namespace App\Application\UseCases\Inventory;

use App\Application\DTO\Inventory\StockLedgerEntry;
use App\Application\Ports\Repositories\InventoryStockRepositoryPort;
use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Repositories\StockLedgerRepositoryPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;

final readonly class ReleaseStockUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private ProductRepositoryPort $products,
        private InventoryStockRepositoryPort $stocks,
        private StockLedgerRepositoryPort $ledger,
    ) {}

    public function handle(ReleaseStockRequest $req): void
    {
        if ($req->qty <= 0) {
            throw new \InvalidArgumentException('qty must be > 0');
        }

        $product = $this->products->findById($req->productId);
        if ($product === null) {
            throw new \InvalidArgumentException('product not found');
        }

        $this->tx->run(function () use ($req): void {
            $stock = $this->stocks->lockOrCreateByProductId($req->productId);

            if ($stock->reservedQty < $req->qty) {
                throw new InvalidReleaseQuantity(
                    productId: $req->productId,
                    requestedReleaseQty: $req->qty,
                    currentReservedQty: $stock->reservedQty,
                );
            }

            $newReserved = $stock->reservedQty - $req->qty;
            $this->stocks->save($stock->withReservedQty($newReserved));

            $this->ledger->append(new StockLedgerEntry(
                productId: $req->productId,
                type: 'RELEASE',
                qtyDelta: -$req->qty,
                refType: $req->refType,
                refId: $req->refId,
                actorUserId: $req->actorUserId,
                occurredAt: $this->clock->now(),
                note: $req->note,
            ));
        });
    }
}
