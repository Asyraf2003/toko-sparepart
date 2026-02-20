<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\DTO\Inventory\ProductSnapshot;
use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Services\TransactionManagerPort;

final readonly class UpdateProductUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ProductRepositoryPort $products,
    ) {}

    public function handle(UpdateProductRequest $req): ProductSnapshot
    {
        $sku = trim($req->sku);
        $name = trim($req->name);

        if ($sku === '') {
            throw new \InvalidArgumentException('sku is required');
        }

        if ($name === '') {
            throw new \InvalidArgumentException('name is required');
        }

        return $this->tx->run(fn () => $this->products->updateBaseFields(
            productId: $req->productId,
            sku: $sku,
            name: $name,
            isActive: $req->isActive,
        ));
    }
}
