<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class ProfitReportResult
{
    /**
     * @param  array<int,ProfitReportRow>  $rows
     */
    public function __construct(
        public readonly array $rows,
        public readonly ProfitReportSummary $summary,
        public readonly string $granularity,
        public readonly string $fromDate,
        public readonly string $toDate,
    ) {}
}
