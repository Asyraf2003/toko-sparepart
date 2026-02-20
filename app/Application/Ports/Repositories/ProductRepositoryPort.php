<?php

declare(strict_types=1);

namespace App\Application\Ports\Repositories;

use App\Application\DTO\Inventory\ProductSnapshot;

interface ProductRepositoryPort
{
    public function findById(int $id): ?ProductSnapshot;

    public function create(
        string $sku,
        string $name,
        int $sellPriceCurrent,
        int $minStockThreshold,
        bool $isActive,
        int $avgCost,
    ): ProductSnapshot;

    public function updateBaseFields(
        int $productId,
        string $sku,
        string $name,
        bool $isActive,
    ): ProductSnapshot;

    public function setSellingPrice(int $productId, int $sellPriceCurrent): ProductSnapshot;

    public function setMinStockThreshold(int $productId, int $minStockThreshold): ProductSnapshot;

    public function getSellingPrice(int $productId): int;
}
