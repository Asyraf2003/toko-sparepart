<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

final readonly class RemovePartLineRequest
{
    public function __construct(
        public int $transactionId,
        public int $productId,
        public int $actorUserId,
    ) {}
}
