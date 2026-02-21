<?php

declare(strict_types=1);

namespace App\Application\Ports\Repositories;

use App\Application\DTO\Reporting\SalesReportResult;

interface SalesReportQueryPort
{
    public function list(
        string $fromDate,
        string $toDate,
        ?string $status,
        ?string $paymentStatus,
        ?string $paymentMethod,
        ?int $cashierUserId,
        int $limit = 200,
    ): SalesReportResult;
}