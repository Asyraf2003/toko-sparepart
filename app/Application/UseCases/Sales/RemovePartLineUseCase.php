<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\Ports\Repositories\TransactionPartLineRepositoryPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Application\UseCases\Inventory\ReleaseStockRequest;
use App\Application\UseCases\Inventory\ReleaseStockUseCase;
use Illuminate\Support\Facades\DB;

final readonly class RemovePartLineUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private TransactionPartLineRepositoryPort $partLines,
        private ReleaseStockUseCase $releaseStock,
    ) {}

    public function handle(RemovePartLineRequest $req): void
    {
        $this->tx->run(function () use ($req) {
            $t = DB::table('transactions')->where('id', $req->transactionId)->lockForUpdate()->first();
            if ($t === null) {
                throw new \InvalidArgumentException('transaction not found');
            }

            if (! in_array($t->status, ['DRAFT', 'OPEN'], true)) {
                throw new \InvalidArgumentException('transaction not editable');
            }

            $existingQty = DB::table('transaction_part_lines')
                ->where('transaction_id', $req->transactionId)
                ->where('product_id', $req->productId)
                ->lockForUpdate()
                ->value('qty');

            if ($existingQty === null) {
                throw new \InvalidArgumentException('part line not found');
            }

            $qty = (int) $existingQty;

            if ($qty > 0) {
                $this->releaseStock->handle(new ReleaseStockRequest(
                    productId: $req->productId,
                    qty: $qty,
                    actorUserId: $req->actorUserId,
                    note: 'release for transaction part line removal',
                    refType: 'transaction',
                    refId: $req->transactionId,
                ));
            }

            $this->partLines->deleteLine(
                transactionId: $req->transactionId,
                productId: $req->productId,
            );
        });
    }
}
