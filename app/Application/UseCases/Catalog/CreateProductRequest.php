<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

final readonly class CreateProductRequest
{
    public function __construct(
        public string $sku,
        public string $name,
        public int $sellPriceCurrent,
        public int $minStockThreshold = 3,
        public bool $isActive = true,
    ) {}
}
