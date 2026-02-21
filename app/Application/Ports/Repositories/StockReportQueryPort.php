<?php

declare(strict_types=1);

namespace App\Application\Ports\Repositories;

use App\Application\DTO\Reporting\StockReportResult;

interface StockReportQueryPort
{
    public function list(
        ?string $search,
        bool $onlyActive,
        int $limit = 500,
    ): StockReportResult;
}
