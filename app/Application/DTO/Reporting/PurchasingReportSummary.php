<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class PurchasingReportSummary
{
    public function __construct(
        public readonly int $count,
        public readonly int $totalBruto,
        public readonly int $totalDiskon,
        public readonly int $totalPajak,
        public readonly int $grandTotal,
    ) {
    }

    public static function empty(): self
    {
        return new self(count: 0, totalBruto: 0, totalDiskon: 0, totalPajak: 0, grandTotal: 0);
    }
}