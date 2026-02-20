<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

final readonly class SetSellingPriceRequest
{
    public function __construct(
        public int $productId,
        public int $sellPriceCurrent,
        public int $actorUserId,
        public string $note,
    ) {}
}
