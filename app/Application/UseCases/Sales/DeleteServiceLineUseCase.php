<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Domain\Audit\AuditEntry;
use Illuminate\Support\Facades\DB;

final readonly class DeleteServiceLineUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private AuditLoggerPort $audit,
    ) {}

    public function handle(DeleteServiceLineRequest $req): void
    {
        $reason = trim($req->reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('reason is required');
        }

        $today = $this->clock->todayBusinessDate();

        $this->tx->run(function () use ($req, $today, $reason): void {
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

            $line = DB::table('transaction_service_lines')
                ->where('id', $req->serviceLineId)
                ->where('transaction_id', $req->transactionId)
                ->lockForUpdate()
                ->first();

            if ($line === null) {
                throw new \InvalidArgumentException('service line not found');
            }

            $before = [
                'transaction' => (array) $t,
                'service_line' => (array) $line,
            ];

            DB::table('transaction_service_lines')->where('id', $req->serviceLineId)->delete();

            $after = [
                'transaction' => (array) $t,
                'service_line' => null,
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
                    'op' => 'service_line_delete',
                    'service_line_id' => $req->serviceLineId,
                    'status' => $status,
                    'business_date' => $businessDate,
                ],
            ));
        });
    }
}
