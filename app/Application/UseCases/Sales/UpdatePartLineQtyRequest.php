<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

final readonly class UpdatePartLineQtyRequest
{
    public function __construct(
        public int $transactionId,
        public int $lineId,
        public int $newQty,
        public int $actorUserId,
        public string $reason,
    ) {}
}
