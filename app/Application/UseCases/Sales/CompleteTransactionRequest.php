<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

final readonly class CompleteTransactionRequest
{
    public function __construct(
        public int $transactionId,
        public string $paymentMethod, // CASH|TRANSFER
        public int $actorUserId,
    ) {}
}
