<?php

declare(strict_types=1);

namespace App\Application\DTO\Inventory;

final readonly class ProductSnapshot
{
    public function __construct(
        public int $id,
        public string $sku,
        public string $name,
        public int $sellPriceCurrent,
        public int $minStockThreshold,
        public bool $isActive,
        public int $avgCost,
    ) {}
}
