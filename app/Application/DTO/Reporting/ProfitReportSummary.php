<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class ProfitReportSummary
{
    public function __construct(
        public readonly int $revenuePart,
        public readonly int $revenueService,
        public readonly int $roundingAmount,
        public readonly int $revenueTotal,
        public readonly int $cogsTotal,
        public readonly int $expensesTotal,
        public readonly int $payrollGross,
        public readonly int $netProfit,
        public readonly int $missingCogsQty,
    ) {
    }

    public static function empty(): self
    {
        return new self(
            revenuePart: 0,
            revenueService: 0,
            roundingAmount: 0,
            revenueTotal: 0,
            cogsTotal: 0,
            expensesTotal: 0,
            payrollGross: 0,
            netProfit: 0,
            missingCogsQty: 0,
        );
    }
}