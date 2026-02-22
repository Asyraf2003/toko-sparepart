<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class SalesReportRow
{
    public function __construct(
        public readonly int $id,
        public readonly string $transactionNumber,
        public readonly string $businessDate,
        public readonly string $status,
        public readonly string $paymentStatus,
        public readonly ?string $paymentMethod,
        public readonly int $cashierUserId,
        public readonly int $partSubtotal,
        public readonly int $serviceSubtotal,
        public readonly int $roundingAmount,
        public readonly int $grandTotal,
        public readonly int $cogsTotal,
        public readonly int $missingCogsQty,

        public readonly ?int $cashReceived,
        public readonly ?int $cashChange,
    ) {}
}
