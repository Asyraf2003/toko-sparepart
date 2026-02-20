<?php

declare(strict_types=1);

namespace App\Application\Ports\Repositories;

use App\Application\DTO\Inventory\InventoryStockSnapshot;

interface InventoryStockRepositoryPort
{
    /**
     * Must be called inside a DB transaction.
     * Must lock the row (SELECT ... FOR UPDATE).
     *
     * If stock row does not exist yet for the product, it must be created then locked.
     */
    public function lockOrCreateByProductId(int $productId): InventoryStockSnapshot;

    /**
     * Persist updated quantities.
     * Must be called inside a DB transaction.
     */
    public function save(InventoryStockSnapshot $stock): void;
}
