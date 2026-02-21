<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\Ports\Repositories\TransactionServiceLineRepositoryPort;
use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Domain\Audit\AuditEntry;
use Illuminate\Support\Facades\DB;

final readonly class UpdateServiceLineUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private TransactionServiceLineRepositoryPort $serviceLines,
        private AuditLoggerPort $audit,
    ) {}

    public function handle(UpdateServiceLineRequest $req): void
    {
        $desc = trim($req->description);
        if ($desc === '') {
            throw new \InvalidArgumentException('description is required');
        }

        if ($req->priceManual < 0) {
            throw new \InvalidArgumentException('priceManual must be >= 0');
        }

        // Edit transaksi (same-day cashier) => reason wajib
        $reason = trim((string) ($req->reason ?? ''));
        if ($reason === '') {
            throw new \InvalidArgumentException('reason is required');
        }

        $today = $this->clock->todayBusinessDate();

        $this->tx->run(function () use ($req, $desc, $today, $reason): void {
            $t = DB::table('transactions')->where('id', $req->transactionId)->lockForUpdate()->first();
            if ($t === null) {
                throw new \InvalidArgumentException('transaction not found');
            }

            $status = (string) $t->status;
            $businessDate = (string) $t->business_date;

            // Keputusan kamu: COMPLETED tidak boleh diedit (harus VOID)
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

            // Opsi 1 admin override:
            // CASHIER hanya boleh edit transaksi business_date == today
            if ((string) $actorRole === 'CASHIER' && $businessDate !== $today) {
                throw new \InvalidArgumentException('cashier cannot edit different business date');
            }

            $beforeLine = DB::table('transaction_service_lines')
                ->where('id', $req->serviceLineId)
                ->where('transaction_id', $req->transactionId)
                ->lockForUpdate()
                ->first();

            if ($beforeLine === null) {
                throw new \InvalidArgumentException('service line not found');
            }

            $before = [
                'transaction' => (array) $t,
                'service_line' => (array) $beforeLine,
            ];

            $this->serviceLines->updateLine($req->serviceLineId, $desc, $req->priceManual);

            $afterLine = DB::table('transaction_service_lines')
                ->where('id', $req->serviceLineId)
                ->where('transaction_id', $req->transactionId)
                ->first();

            $after = [
                'transaction' => (array) $t,
                'service_line' => $afterLine ? (array) $afterLine : null,
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
                    'op' => 'service_line_update',
                    'service_line_id' => $req->serviceLineId,
                    'status' => $status,
                    'business_date' => $businessDate,
                ],
            ));
        });
    }
}
