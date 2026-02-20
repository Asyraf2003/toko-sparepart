<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

final readonly class UpdateServiceLineRequest
{
    public function __construct(
        public int $transactionId,
        public int $serviceLineId,
        public string $description,
        public int $priceManual,
        public int $actorUserId,
        public ?string $reason = null,
    ) {}
}
