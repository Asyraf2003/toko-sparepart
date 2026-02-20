<?php

declare(strict_types=1);

namespace App\Application\UseCases\Inventory;

use RuntimeException;

final class InsufficientStock extends RuntimeException
{
    public function __construct(
        public readonly int $productId,
        public readonly int $requestedQty,
        public readonly int $availableQty,
    ) {
        parent::__construct("Insufficient stock for product_id={$productId}. requested={$requestedQty}, available={$availableQty}");
    }
}
