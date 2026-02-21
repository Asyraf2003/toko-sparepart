<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\Ports\Repositories\TransactionServiceLineRepositoryPort;
use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Domain\Audit\AuditEntry;
use Illuminate\Support\Facades\DB;

final readonly class AddServiceLineUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private TransactionServiceLineRepositoryPort $serviceLines,
        private AuditLoggerPort $audit,
    ) {}

    public function handle(AddServiceLineRequest $req): int
    {
        $desc = trim($req->description);
        if ($desc === '') {
            throw new \InvalidArgumentException('description is required');
        }

        if ($req->priceManual < 0) {
            throw new \InvalidArgumentException('priceManual must be >= 0');
        }

        $reason = trim($req->reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('reason is required');
        }

        $today = $this->clock->todayBusinessDate();

        return $this->tx->run(function () use ($req, $desc, $reason, $today): int {
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

            $before = [
                'transaction' => (array) $t,
                'service_lines' => array_map(
                    static fn ($r) => (array) $r,
                    DB::table('transaction_service_lines')->where('transaction_id', $req->transactionId)->get()->all()
                ),
            ];

            $id = $this->serviceLines->createLine(
                transactionId: $req->transactionId,
                description: $desc,
                priceManual: $req->priceManual,
            );

            $after = [
                'transaction' => (array) $t,
                'service_lines' => array_map(
                    static fn ($r) => (array) $r,
                    DB::table('transaction_service_lines')->where('transaction_id', $req->transactionId)->get()->all()
                ),
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
                    'op' => 'service_line_add',
                    'service_line_id' => $id,
                    'status' => $status,
                    'business_date' => $businessDate,
                ],
            ));

            return $id;
        });
    }
}
