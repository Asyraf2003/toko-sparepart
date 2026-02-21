<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\DTO\Inventory\StockLedgerEntry;
use App\Application\Ports\Repositories\InventoryStockRepositoryPort;
use App\Application\Ports\Repositories\StockLedgerRepositoryPort;
use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Domain\Audit\AuditEntry;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePartLineQtyUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private InventoryStockRepositoryPort $stocks,
        private StockLedgerRepositoryPort $ledger,
        private AuditLoggerPort $audit,
    ) {}

    public function handle(UpdatePartLineQtyRequest $req): void
    {
        if ($req->newQty < 1) {
            throw new \InvalidArgumentException('qty must be >= 1');
        }

        $reason = trim($req->reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('reason is required');
        }

        $this->tx->run(function () use ($req, $reason): void {
            $t = DB::table('transactions')->where('id', $req->transactionId)->lockForUpdate()->first();
            if ($t === null) {
                throw new \InvalidArgumentException('transaction not found');
            }

            $status = (string) $t->status;
            if (! in_array($status, ['DRAFT', 'OPEN'], true)) {
                throw new \InvalidArgumentException('cannot edit part lines unless DRAFT/OPEN');
            }

            $actorRole = DB::table('users')->where('id', $req->actorUserId)->value('role');
            if ($actorRole === null) {
                throw new \InvalidArgumentException('actor user not found');
            }

            $line = DB::table('transaction_part_lines')
                ->where('id', $req->lineId)
                ->where('transaction_id', $req->transactionId)
                ->lockForUpdate()
                ->first();

            if ($line === null) {
                throw new \InvalidArgumentException('part line not found');
            }

            $productId = (int) $line->product_id;
            $oldQty = (int) $line->qty;
            $newQty = $req->newQty;
            $delta = $newQty - $oldQty;

            if ($delta === 0) {
                return;
            }

            $stock = $this->stocks->lockOrCreateByProductId($productId);

            $before = [
                'transaction' => (array) $t,
                'part_line' => (array) $line,
                'stock' => [
                    'product_id' => $productId,
                    'on_hand_qty' => $stock->onHandQty,
                    'reserved_qty' => $stock->reservedQty,
                    'available_qty' => $stock->availableQty(),
                ],
            ];

            $now = $this->clock->now();

            if ($delta > 0) {
                if ($stock->availableQty() < $delta) {
                    throw new \InvalidArgumentException('insufficient available stock');
                }

                $this->stocks->save($stock->withReservedQty($stock->reservedQty + $delta));

                $this->ledger->append(new StockLedgerEntry(
                    productId: $productId,
                    type: 'RESERVE',
                    qtyDelta: $delta,
                    refType: 'transaction',
                    refId: $req->transactionId,
                    actorUserId: $req->actorUserId,
                    occurredAt: $now,
                    note: 'update part qty: '.$reason,
                ));
            } else {
                $releaseQty = abs($delta);

                if ($stock->reservedQty < $releaseQty) {
                    throw new \InvalidArgumentException('reserved stock insufficient');
                }

                $this->stocks->save($stock->withReservedQty($stock->reservedQty - $releaseQty));

                $this->ledger->append(new StockLedgerEntry(
                    productId: $productId,
                    type: 'RELEASE',
                    qtyDelta: -$releaseQty,
                    refType: 'transaction',
                    refId: $req->transactionId,
                    actorUserId: $req->actorUserId,
                    occurredAt: $now,
                    note: 'update part qty: '.$reason,
                ));
            }

            $unitPrice = (int) $line->unit_sell_price_frozen;
            $nowStr = $now->format('Y-m-d H:i:s');

            DB::table('transaction_part_lines')->where('id', $req->lineId)->update([
                'qty' => $newQty,
                'line_subtotal' => $unitPrice * $newQty,
                'updated_at' => $nowStr,
            ]);

            $lineAfter = DB::table('transaction_part_lines')
                ->where('id', $req->lineId)
                ->where('transaction_id', $req->transactionId)
                ->first();

            $stockAfter = $this->stocks->lockOrCreateByProductId($productId);

            $after = [
                'transaction' => (array) $t,
                'part_line' => $lineAfter ? (array) $lineAfter : null,
                'stock' => [
                    'product_id' => $productId,
                    'on_hand_qty' => $stockAfter->onHandQty,
                    'reserved_qty' => $stockAfter->reservedQty,
                    'available_qty' => $stockAfter->availableQty(),
                ],
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
                    'op' => 'part_line_update_qty',
                    'line_id' => $req->lineId,
                    'product_id' => $productId,
                    'old_qty' => $oldQty,
                    'new_qty' => $newQty,
                    'delta' => $delta,
                ],
            ));
        });
    }
}
