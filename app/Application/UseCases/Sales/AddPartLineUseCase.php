<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Repositories\TransactionPartLineRepositoryPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Application\UseCases\Inventory\ReserveStockRequest;
use App\Application\UseCases\Inventory\ReserveStockUseCase;
use Illuminate\Support\Facades\DB;

final readonly class AddPartLineUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ProductRepositoryPort $products,
        private TransactionPartLineRepositoryPort $partLines,
        private ReserveStockUseCase $reserveStock,
    ) {}

    public function handle(AddPartLineRequest $req): void
    {
        if ($req->qty <= 0) {
            throw new \InvalidArgumentException('qty must be positive');
        }

        $this->tx->run(function () use ($req) {
            // Lock transaction row (prevent concurrent edits)
            $t = DB::table('transactions')->where('id', $req->transactionId)->lockForUpdate()->first();
            if ($t === null) {
                throw new \InvalidArgumentException('transaction not found');
            }

            if (! in_array($t->status, ['DRAFT', 'OPEN'], true)) {
                throw new \InvalidArgumentException('transaction not editable');
            }

            // Determine current qty in line to compute delta reserve
            $existingQty = (int) DB::table('transaction_part_lines')
                ->where('transaction_id', $req->transactionId)
                ->where('product_id', $req->productId)
                ->lockForUpdate()
                ->value('qty');

            $delta = $req->qty - $existingQty;
            if ($delta > 0) {
                $this->reserveStock->handle(new ReserveStockRequest(
                    productId: $req->productId,
                    qty: $delta,
                    actorUserId: $req->actorUserId,
                    note: 'reserve for transaction part line',
                    refType: 'transaction',
                    refId: $req->transactionId,
                ));
            } elseif ($delta < 0) {
                // release delta (qty turun)
                app(\App\Application\UseCases\Inventory\ReleaseStockUseCase::class)->handle(
                    new \App\Application\UseCases\Inventory\ReleaseStockRequest(
                        productId: $req->productId,
                        qty: abs($delta),
                        actorUserId: $req->actorUserId,
                        note: 'release for transaction part line update',
                        refType: 'transaction',
                        refId: $req->transactionId,
                    )
                );
            }

            $price = $this->products->getSellingPrice($req->productId);

            $this->partLines->upsertLine(
                transactionId: $req->transactionId,
                productId: $req->productId,
                qty: $req->qty,
                unitSellPriceFrozen: $price,
            );
        });
    }
}
