<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class ProfitReportRow
{
    public function __construct(
        public readonly string $periodKey,   // e.g. 2026-02-16 (week start) OR 2026-02 (month)
        public readonly string $periodLabel, // human label
        public readonly int $revenuePart,
        public readonly int $revenueService,
        public readonly int $roundingAmount,
        public readonly int $revenueTotal,
        public readonly int $cogsTotal,
        public readonly int $expensesTotal,
        public readonly int $payrollGross,
        public readonly int $netProfit,
        public readonly int $missingCogsQty,
    ) {}
}
