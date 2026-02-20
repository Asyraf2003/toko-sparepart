<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\DTO\Sales\TransactionSnapshot;
use App\Application\Ports\Repositories\TransactionRepositoryPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;

final readonly class CreateTransactionUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private TransactionRepositoryPort $transactions,
    ) {}

    public function handle(CreateTransactionRequest $req): TransactionSnapshot
    {
        $businessDate = $this->clock->todayBusinessDate();

        return $this->tx->run(function () use ($businessDate, $req) {
            $number = $this->transactions->nextTransactionNumberForDate($businessDate);

            return $this->transactions->createDraft(
                businessDate: $businessDate,
                createdByUserId: $req->actorUserId,
                transactionNumber: $number,
            );
        });
    }
}
