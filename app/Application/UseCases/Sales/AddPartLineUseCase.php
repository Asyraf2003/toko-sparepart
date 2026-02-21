<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Repositories\TransactionPartLineRepositoryPort;
use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Application\UseCases\Inventory\ReleaseStockRequest;
use App\Application\UseCases\Inventory\ReleaseStockUseCase;
use App\Application\UseCases\Inventory\ReserveStockRequest;
use App\Application\UseCases\Inventory\ReserveStockUseCase;
use App\Domain\Audit\AuditEntry;
use Illuminate\Support\Facades\DB;

final readonly class AddPartLineUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private ProductRepositoryPort $products,
        private TransactionPartLineRepositoryPort $partLines,
        private ReserveStockUseCase $reserveStock,
        private ReleaseStockUseCase $releaseStock,
        private AuditLoggerPort $audit,
    ) {}

    public function handle(AddPartLineRequest $req): void
    {
        if ($req->qty <= 0) {
            throw new \InvalidArgumentException('qty must be positive');
        }

        $reason = trim($req->reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('reason is required');
        }

        $today = $this->clock->todayBusinessDate();

        $this->tx->run(function () use ($req, $reason, $today): void {
            $t = DB::table('transactions')->where('id', $req->transactionId)->lockForUpdate()->first();
            if ($t === null) {
                throw new \InvalidArgumentException('transaction not found');
            }

            $status = (string) $t->status;
            $businessDate = (string) $t->business_date;

            if ($status === 'COMPLETED') {
                throw new \InvalidArgumentException('transaction not editable');
            }
            if (! in_array($status, ['DRAFT', 'OPEN'], true)) {
                throw new \InvalidArgumentException('transaction not editable');
            }

            $actorRole = DB::table('users')->where('id', $req->actorUserId)->value('role');
            if ($actorRole === null) {
                throw new \InvalidArgumentException('actor user not found');
            }
            if ((string) $actorRole === 'CASHIER' && $businessDate !== $today) {
                throw new \InvalidArgumentException('cashier cannot edit different business date');
            }

            $existingLine = DB::table('transaction_part_lines')
                ->where('transaction_id', $req->transactionId)
                ->where('product_id', $req->productId)
                ->lockForUpdate()
                ->first();

            $existingQty = $existingLine ? (int) $existingLine->qty : 0;
            $delta = $req->qty - $existingQty;

            $beforeStock = DB::table('inventory_stocks')->where('product_id', $req->productId)->lockForUpdate()->first();

            $before = [
                'transaction' => (array) $t,
                'part_line' => $existingLine ? (array) $existingLine : null,
                'stock' => $beforeStock ? (array) $beforeStock : null,
            ];

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
                $this->releaseStock->handle(new ReleaseStockRequest(
                    productId: $req->productId,
                    qty: abs($delta),
                    actorUserId: $req->actorUserId,
                    note: 'release for transaction part line update',
                    refType: 'transaction',
                    refId: $req->transactionId,
                ));
            }

            $price = $this->products->getSellingPrice($req->productId);

            $this->partLines->upsertLine(
                transactionId: $req->transactionId,
                productId: $req->productId,
                qty: $req->qty,
                unitSellPriceFrozen: $price,
            );

            $afterLine = DB::table('transaction_part_lines')
                ->where('transaction_id', $req->transactionId)
                ->where('product_id', $req->productId)
                ->first();

            $afterStock = DB::table('inventory_stocks')->where('product_id', $req->productId)->first();

            $after = [
                'transaction' => (array) $t,
                'part_line' => $afterLine ? (array) $afterLine : null,
                'stock' => $afterStock ? (array) $afterStock : null,
            ];

            $this->audit->append(new AuditEntry(
                actorId: $req->actorUserId,
                actorRole: (string) $actorRole,
                entityType: 'Transaction',
                entityId: $req->transactionId,
                action: 'UPDATE',
                reason: $reason,
                before: $before,
                after: $after,
                meta: [
                    'op' => 'part_line_upsert',
                    'product_id' => $req->productId,
                    'old_qty' => $existingQty,
                    'new_qty' => $req->qty,
                    'delta' => $delta,
                    'status' => $status,
                    'business_date' => $businessDate,
                ],
            ));
        });
    }
}
