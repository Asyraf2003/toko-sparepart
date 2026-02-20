<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

final readonly class CreateTransactionRequest
{
    public function __construct(
        public int $actorUserId,
    ) {}
}
