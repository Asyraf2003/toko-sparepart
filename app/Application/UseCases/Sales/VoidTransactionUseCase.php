<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Application\UseCases\Notifications\NotifyLowStockForProductRequest;
use App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase;
use App\Domain\Audit\AuditEntry;
use Illuminate\Support\Facades\DB;

final readonly class VoidTransactionUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private NotifyLowStockForProductUseCase $lowStock,
        private AuditLoggerPort $audit,
    ) {}

    public function handle(VoidTransactionRequest $req): void
    {
        $reason = trim($req->reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('reason is required');
        }

        $now = $this->clock->now();
        $today = $this->clock->todayBusinessDate();

        /** @var list<int> $productIdsToNotify */
        $productIdsToNotify = [];
        $triggerType = null;

        $this->tx->run(function () use ($req, $reason, $now, $today, &$productIdsToNotify, &$triggerType) {
            $t = DB::table('transactions')->where('id', $req->transactionId)->lockForUpdate()->first();
            if ($t === null) {
                throw new \InvalidArgumentException('transaction not found');
            }

            $status = (string) $t->status;
            $businessDate = (string) $t->business_date;

            if (! in_array($status, ['DRAFT', 'OPEN', 'COMPLETED'], true)) {
                throw new \InvalidArgumentException('transaction not voidable');
            }

            $actorRole = DB::table('users')->where('id', $req->actorUserId)->value('role');
            if ($actorRole === null) {
                throw new \InvalidArgumentException('actor user not found');
            }
            if ((string) $actorRole === 'CASHIER' && $businessDate !== $today) {
                throw new \InvalidArgumentException('cashier cannot void different business date');
            }

            // BEFORE snapshots
            $beforePartLines = DB::table('transaction_part_lines')
                ->where('transaction_id', $req->transactionId)
                ->lockForUpdate()
                ->get()
                ->all();

            $beforeServiceLines = DB::table('transaction_service_lines')
                ->where('transaction_id', $req->transactionId)
                ->lockForUpdate()
                ->get()
                ->all();

            $beforeStocks = [];

            $lines = DB::table('transaction_part_lines')
                ->where('transaction_id', $req->transactionId)
                ->lockForUpdate()
                ->get(['product_id', 'qty']);

            $nowStr = $now->format('Y-m-d H:i:s');

            foreach ($lines as $line) {
                $productId = (int) $line->product_id;
                $qty = (int) $line->qty;

                if ($qty <= 0) {
                    continue;
                }

                $stock = DB::table('inventory_stocks')
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if ($stock === null) {
                    throw new \InvalidArgumentException('inventory stock not found');
                }

                $beforeStocks[(string) $productId] = (array) $stock;

                $onHand = (int) $stock->on_hand_qty;
                $reserved = (int) $stock->reserved_qty;

                if ($status === 'COMPLETED') {
                    DB::table('inventory_stocks')->where('product_id', $productId)->update([
                        'on_hand_qty' => $onHand + $qty,
                        'updated_at' => $nowStr,
                    ]);

                    DB::table('stock_ledgers')->insert([
                        'product_id' => $productId,
                        'type' => 'VOID_IN',
                        'qty_delta' => $qty,
                        'ref_type' => 'transaction',
                        'ref_id' => $req->transactionId,
                        'actor_user_id' => $req->actorUserId,
                        'occurred_at' => $nowStr,
                        'note' => 'void completed transaction',
                        'created_at' => $nowStr,
                        'updated_at' => $nowStr,
                    ]);

                    $productIdsToNotify[] = $productId;
                    $triggerType = 'VOID_IN';

                    continue;
                }

                if ($reserved < $qty) {
                    throw new \InvalidArgumentException('reserved stock insufficient at void');
                }

                DB::table('inventory_stocks')->where('product_id', $productId)->update([
                    'reserved_qty' => $reserved - $qty,
                    'updated_at' => $nowStr,
                ]);

                DB::table('stock_ledgers')->insert([
                    'product_id' => $productId,
                    'type' => 'RELEASE',
                    'qty_delta' => -$qty,
                    'ref_type' => 'transaction',
                    'ref_id' => $req->transactionId,
                    'actor_user_id' => $req->actorUserId,
                    'occurred_at' => $nowStr,
                    'note' => 'void draft/open transaction release reserve',
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ]);

                $productIdsToNotify[] = $productId;
                $triggerType = 'RELEASE';
            }

            $update = [
                'status' => 'VOID',
                'voided_at' => $nowStr,
                'updated_at' => $nowStr,
            ];

            $schema = DB::getSchemaBuilder();
            if ($schema->hasColumn('transactions', 'void_reason')) {
                $update['void_reason'] = $reason;
            } elseif ($schema->hasColumn('transactions', 'note')) {
                $update['note'] = 'VOID: '.$reason;
            }

            DB::table('transactions')->where('id', $req->transactionId)->update($update);

            // AFTER snapshots
            $tAfter = DB::table('transactions')->where('id', $req->transactionId)->first();
            $afterPartLines = DB::table('transaction_part_lines')
                ->where('transaction_id', $req->transactionId)
                ->get()
                ->all();

            $afterServiceLines = DB::table('transaction_service_lines')
                ->where('transaction_id', $req->transactionId)
                ->get()
                ->all();

            $afterStocks = [];
            $pids = array_values(array_unique(array_map(
                static fn ($r) => (int) $r->product_id,
                $lines->all()
            )));
            if (count($pids) > 0) {
                $stocksAfterRows = DB::table('inventory_stocks')->whereIn('product_id', $pids)->get()->all();
                foreach ($stocksAfterRows as $row) {
                    $afterStocks[(string) ((int) $row->product_id)] = (array) $row;
                }
            }

            $before = [
                'transaction' => (array) $t,
                'part_lines' => array_map(static fn ($r) => (array) $r, $beforePartLines),
                'service_lines' => array_map(static fn ($r) => (array) $r, $beforeServiceLines),
                'stocks' => $beforeStocks,
            ];

            $after = [
                'transaction' => $tAfter ? (array) $tAfter : null,
                'part_lines' => array_map(static fn ($r) => (array) $r, $afterPartLines),
                'service_lines' => array_map(static fn ($r) => (array) $r, $afterServiceLines),
                'stocks' => $afterStocks,
            ];

            $this->audit->append(new AuditEntry(
                actorId: $req->actorUserId,
                actorRole: (string) $actorRole,
                entityType: 'Transaction',
                entityId: $req->transactionId,
                action: 'VOID',
                reason: $reason,
                before: $before,
                after: $after,
                meta: [
                    'business_date' => $businessDate,
                    'status_before' => $status,
                ],
            ));
        });

        // after commit
        if ($triggerType === null) {
            return;
        }

        $productIdsToNotify = array_values(array_unique($productIdsToNotify));
        foreach ($productIdsToNotify as $pid) {
            $this->lowStock->handle(new NotifyLowStockForProductRequest(
                productId: $pid,
                triggerType: $triggerType,
                actorUserId: $req->actorUserId,
            ));
        }
    }
}
