<?php

declare(strict_types=1);

namespace App\Application\UseCases\Inventory;

final readonly class AdjustStockRequest
{
    public function __construct(
        public int $productId,
        public int $qtyDelta, // + / -
        public int $actorUserId,
        public string $note, // reason mandatory
        public ?string $refType = null,
        public ?int $refId = null,
    ) {}
}
