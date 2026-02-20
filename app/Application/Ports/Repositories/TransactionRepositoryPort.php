<?php

declare(strict_types=1);

namespace App\Application\Ports\Repositories;

use App\Application\DTO\Sales\TransactionSnapshot;

interface TransactionRepositoryPort
{
    public function createDraft(
        string $businessDate, // YYYY-MM-DD
        int $createdByUserId,
        string $transactionNumber,
    ): TransactionSnapshot;

    /**
     * Generate next transaction number for a given business date.
     * Must be called inside a DB transaction (FOR UPDATE).
     */
    public function nextTransactionNumberForDate(string $businessDate): string;
}
