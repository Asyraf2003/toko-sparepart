<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class StockReportSummary
{
    public function __construct(
        public readonly int $count,
        public readonly int $lowStockCount,
    ) {}

    public static function empty(): self
    {
        return new self(count: 0, lowStockCount: 0);
    }
}
