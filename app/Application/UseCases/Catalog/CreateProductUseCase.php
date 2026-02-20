<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\DTO\Inventory\ProductSnapshot;
use App\Application\Ports\Repositories\InventoryStockRepositoryPort;
use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Services\TransactionManagerPort;

final readonly class CreateProductUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ProductRepositoryPort $products,
        private InventoryStockRepositoryPort $stocks,
    ) {}

    public function handle(CreateProductRequest $req): ProductSnapshot
    {
        $sku = trim($req->sku);
        $name = trim($req->name);

        if ($sku === '') {
            throw new \InvalidArgumentException('sku is required');
        }

        if ($name === '') {
            throw new \InvalidArgumentException('name is required');
        }

        if ($req->sellPriceCurrent < 0) {
            throw new \InvalidArgumentException('sellPriceCurrent must be >= 0');
        }

        if ($req->minStockThreshold < 0) {
            throw new \InvalidArgumentException('minStockThreshold must be >= 0');
        }

        return $this->tx->run(function () use ($req, $sku, $name): ProductSnapshot {
            $product = $this->products->create(
                sku: $sku,
                name: $name,
                sellPriceCurrent: $req->sellPriceCurrent,
                minStockThreshold: $req->minStockThreshold,
                isActive: $req->isActive,
                avgCost: 0,
            );

            // ensure stock row exists (0/0)
            $this->stocks->lockOrCreateByProductId($product->id);

            return $product;
        });
    }
}
