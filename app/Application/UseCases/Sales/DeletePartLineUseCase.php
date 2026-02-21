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

final readonly class DeletePartLineUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private InventoryStockRepositoryPort $stocks,
        private StockLedgerRepositoryPort $ledger,
        private AuditLoggerPort $audit,
    ) {}

    public function handle(DeletePartLineRequest $req): void
    {
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
                throw new \InvalidArgumentException('cannot delete part lines unless DRAFT/OPEN');
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
            $qty = (int) $line->qty;

            $stock = $this->stocks->lockOrCreateByProductId($productId);

            if ($stock->reservedQty < $qty) {
                throw new \InvalidArgumentException('reserved stock insufficient');
            }

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

            // release reserved
            $this->stocks->save($stock->withReservedQty($stock->reservedQty - $qty));

            $now = $this->clock->now();
            $this->ledger->append(new StockLedgerEntry(
                productId: $productId,
                type: 'RELEASE',
                qtyDelta: -$qty,
                refType: 'transaction',
                refId: $req->transactionId,
                actorUserId: $req->actorUserId,
                occurredAt: $now,
                note: 'delete part line: '.$reason,
            ));

            DB::table('transaction_part_lines')->where('id', $req->lineId)->delete();

            $stockAfter = $this->stocks->lockOrCreateByProductId($productId);

            $after = [
                'transaction' => (array) $t,
                'part_line' => null,
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
                    'op' => 'part_line_delete',
                    'line_id' => $req->lineId,
                    'product_id' => $productId,
                    'qty' => $qty,
                ],
            ));
        });
    }
}
