<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class SalesReportSummary
{
    public function __construct(
        public readonly int $count,
        public readonly int $partSubtotal,
        public readonly int $serviceSubtotal,
        public readonly int $roundingAmount,
        public readonly int $grandTotal,
        public readonly int $cogsTotal,
        public readonly int $missingCogsQty,
    ) {
    }

    public static function empty(): self
    {
        return new self(
            count: 0,
            partSubtotal: 0,
            serviceSubtotal: 0,
            roundingAmount: 0,
            grandTotal: 0,
            cogsTotal: 0,
            missingCogsQty: 0,
        );
    }
}