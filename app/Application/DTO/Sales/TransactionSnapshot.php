<?php

declare(strict_types=1);

namespace App\Application\DTO\Sales;

final readonly class TransactionSnapshot
{
    public function __construct(
        public int $id,
        public string $transactionNumber,
        public string $businessDate, // YYYY-MM-DD
        public string $status, // DRAFT|OPEN|COMPLETED|VOID
        public string $paymentStatus, // UNPAID|PAID
        public ?string $paymentMethod, // CASH|TRANSFER|null
        public int $createdByUserId,
    ) {}
}
