<?php

declare(strict_types=1);

namespace App\Application\Ports\Repositories;

use App\Application\DTO\Reporting\ProfitReportResult;

interface ProfitReportQueryPort
{
    /**
     * @param  'weekly'|'monthly'  $granularity
     */
    public function aggregate(
        string $fromDate,
        string $toDate,
        string $granularity,
    ): ProfitReportResult;
}
