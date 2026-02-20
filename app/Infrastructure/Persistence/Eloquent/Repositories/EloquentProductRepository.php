<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\DTO\Inventory\ProductSnapshot;
use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Infrastructure\Persistence\Eloquent\Models\Product;

final class EloquentProductRepository implements ProductRepositoryPort
{
    public function findById(int $id): ?ProductSnapshot
    {
        $p = Product::query()->find($id);

        if ($p === null) {
            return null;
        }

        return new ProductSnapshot(
            id: (int) $p->id,
            sku: (string) $p->sku,
            name: (string) $p->name,
            sellPriceCurrent: (int) $p->sell_price_current,
            minStockThreshold: (int) $p->min_stock_threshold,
            isActive: (bool) $p->is_active,
            avgCost: (int) $p->avg_cost,
        );
    }

    public function create(
        string $sku,
        string $name,
        int $sellPriceCurrent,
        int $minStockThreshold,
        bool $isActive,
        int $avgCost,
    ): ProductSnapshot {
        $p = Product::query()->create([
            'sku' => $sku,
            'name' => $name,
            'sell_price_current' => $sellPriceCurrent,
            'min_stock_threshold' => $minStockThreshold,
            'is_active' => $isActive,
            'avg_cost' => $avgCost,
        ]);

        return new ProductSnapshot(
            id: (int) $p->id,
            sku: (string) $p->sku,
            name: (string) $p->name,
            sellPriceCurrent: (int) $p->sell_price_current,
            minStockThreshold: (int) $p->min_stock_threshold,
            isActive: (bool) $p->is_active,
            avgCost: (int) $p->avg_cost,
        );
    }

    public function setSellingPrice(int $productId, int $sellPriceCurrent): ProductSnapshot
    {
        Product::query()->whereKey($productId)->update([
            'sell_price_current' => $sellPriceCurrent,
        ]);

        $updated = $this->findById($productId);
        if ($updated === null) {
            throw new \InvalidArgumentException('product not found');
        }

        return $updated;
    }

    public function setMinStockThreshold(int $productId, int $minStockThreshold): ProductSnapshot
    {
        Product::query()->whereKey($productId)->update([
            'min_stock_threshold' => $minStockThreshold,
        ]);

        $updated = $this->findById($productId);
        if ($updated === null) {
            throw new \InvalidArgumentException('product not found');
        }

        return $updated;
    }

    public function updateBaseFields(int $productId, string $sku, string $name, bool $isActive): ProductSnapshot
    {
        Product::query()->whereKey($productId)->update([
            'sku' => $sku,
            'name' => $name,
            'is_active' => $isActive,
        ]);

        $updated = $this->findById($productId);
        if ($updated === null) {
            throw new \InvalidArgumentException('product not found');
        }

        return $updated;
    }

    public function getSellingPrice(int $productId): int
    {
        $price = \App\Infrastructure\Persistence\Eloquent\Models\Product::query()
            ->whereKey($productId)
            ->value('sell_price_current');

        if ($price === null) {
            throw new \InvalidArgumentException('product not found');
        }

        return (int) $price;
    }
}
