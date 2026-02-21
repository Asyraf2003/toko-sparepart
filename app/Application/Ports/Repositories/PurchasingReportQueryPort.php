<?php

declare(strict_types=1);

namespace App\Application\Ports\Repositories;

use App\Application\DTO\Reporting\PurchasingReportResult;

interface PurchasingReportQueryPort
{
    public function list(
        string $fromDate,
        string $toDate,
        ?string $noFakturSearch,
        int $limit = 200,
    ): PurchasingReportResult;
}
