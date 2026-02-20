<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\Ports\Repositories\TransactionServiceLineRepositoryPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use Illuminate\Support\Facades\DB;

final readonly class UpdateServiceLineUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private TransactionServiceLineRepositoryPort $serviceLines,
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

        $today = $this->clock->todayBusinessDate();

        $this->tx->run(function () use ($req, $desc, $today) {
            $t = DB::table('transactions')->where('id', $req->transactionId)->lockForUpdate()->first();
            if ($t === null) {
                throw new \InvalidArgumentException('transaction not found');
            }

            $status = (string) $t->status;
            $businessDate = (string) $t->business_date;

            if (in_array($status, ['DRAFT', 'OPEN'], true)) {
                $this->serviceLines->updateLine($req->serviceLineId, $desc, $req->priceManual);

                return;
            }

            if ($status === 'COMPLETED' && $businessDate === $today) {
                $reason = trim((string) ($req->reason ?? ''));
                if ($reason === '') {
                    throw new \InvalidArgumentException('reason is required for updating service line after completion (same day)');
                }

                $this->serviceLines->updateLine($req->serviceLineId, $desc, $req->priceManual);

                return;
            }

            throw new \InvalidArgumentException('transaction not editable');
        });
    }
}
