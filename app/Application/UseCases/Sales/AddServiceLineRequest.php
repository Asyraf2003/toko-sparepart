<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

final readonly class AddServiceLineRequest
{
    public function __construct(
        public int $transactionId,
        public string $description,
        public int $priceManual,
        public int $actorUserId,
    ) {}
}
