<?php

declare(strict_types=1);

namespace App\Application\DTO\Inventory;

final readonly class ProductStockRow
{
    public function __construct(
        public int $productId,
        public string $sku,
        public string $name,
        public int $sellPriceCurrent,
        public int $minStockThreshold,
        public bool $isActive,
        public int $onHandQty,
        public int $reservedQty,
    ) {}

    public function availableQty(): int
    {
        return $this->onHandQty - $this->reservedQty;
    }

    public function isLowStock(): bool
    {
        return $this->availableQty() <= $this->minStockThreshold;
    }
}
