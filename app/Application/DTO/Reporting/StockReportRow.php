<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class StockReportRow
{
    public function __construct(
        public readonly int $productId,
        public readonly string $sku,
        public readonly string $name,
        public readonly bool $isActive,
        public readonly int $minStockThreshold,
        public readonly int $onHandQty,
        public readonly int $reservedQty,
        public readonly int $availableQty,
        public readonly bool $isLowStock,
    ) {
    }
}