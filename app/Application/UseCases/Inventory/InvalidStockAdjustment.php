<?php

declare(strict_types=1);

namespace App\Application\UseCases\Inventory;

use RuntimeException;

final class InvalidStockAdjustment extends RuntimeException
{
    public function __construct(
        public readonly int $productId,
        public readonly int $qtyDelta,
        public readonly int $currentOnHandQty,
    ) {
        parent::__construct("Invalid adjustment for product_id={$productId}. delta={$qtyDelta}, on_hand={$currentOnHandQty}");
    }
}
