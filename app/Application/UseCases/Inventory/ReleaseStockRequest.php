<?php

declare(strict_types=1);

namespace App\Application\UseCases\Inventory;

final readonly class ReleaseStockRequest
{
    public function __construct(
        public int $productId,
        public int $qty,
        public ?string $refType = null,
        public ?int $refId = null,
        public ?int $actorUserId = null,
        public ?string $note = null,
    ) {}
}
