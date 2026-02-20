<?php

declare(strict_types=1);

namespace App\Application\Ports\Repositories;

use App\Application\DTO\Inventory\ProductStockRow;

interface ProductStockQueryPort
{
    /**
     * @return list<ProductStockRow>
     */
    public function list(?string $search = null, bool $onlyActive = true): array;

    public function findByProductId(int $productId): ?ProductStockRow;
}
