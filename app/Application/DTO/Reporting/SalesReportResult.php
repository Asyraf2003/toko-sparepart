<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class SalesReportResult
{
    /**
     * @param array<int,SalesReportRow> $rows
     */
    public function __construct(
        public readonly array $rows,
        public readonly SalesReportSummary $summary,
    ) {
    }

    public static function empty(): self
    {
        return new self(rows: [], summary: SalesReportSummary::empty());
    }
}