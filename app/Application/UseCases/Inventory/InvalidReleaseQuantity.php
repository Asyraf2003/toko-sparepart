<?php

declare(strict_types=1);

namespace App\Application\UseCases\Inventory;

use RuntimeException;

final class InvalidReleaseQuantity extends RuntimeException
{
    public function __construct(
        public readonly int $productId,
        public readonly int $requestedReleaseQty,
        public readonly int $currentReservedQty,
    ) {
        parent::__construct("Invalid release for product_id={$productId}. requested_release={$requestedReleaseQty}, reserved={$currentReservedQty}");
    }
}
