<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class PurchasingReportResult
{
    /**
     * @param array<int,PurchasingReportRow> $rows
     */
    public function __construct(
        public readonly array $rows,
        public readonly PurchasingReportSummary $summary,
    ) {
    }

    public static function empty(): self
    {
        return new self(rows: [], summary: PurchasingReportSummary::empty());
    }
}