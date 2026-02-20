<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

final readonly class UpdateProductRequest
{
    public function __construct(
        public int $productId,
        public string $sku,
        public string $name,
        public bool $isActive,
    ) {}
}
