<?php

declare(strict_types=1);

namespace App\Application\UseCases\Purchasing;

final readonly class CreatePurchaseInvoiceLine
{
    public function __construct(
        public int $productId,
        public int $qty,
        public int $unitCost,
        public int $discBps, // 0..10000
    ) {}
}
