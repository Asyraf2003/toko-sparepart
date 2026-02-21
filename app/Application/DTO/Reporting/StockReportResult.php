<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class StockReportResult
{
    /**
     * @param array<int,StockReportRow> $rows
     */
    public function __construct(
        public readonly array $rows,
        public readonly StockReportSummary $summary,
    ) {
    }

    public static function empty(): self
    {
        return new self(rows: [], summary: StockReportSummary::empty());
    }
}