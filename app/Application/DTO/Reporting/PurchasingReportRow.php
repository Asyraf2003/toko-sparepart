<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class PurchasingReportRow
{
    public function __construct(
        public readonly int $id,
        public readonly string $tglKirim,
        public readonly string $noFaktur,
        public readonly string $supplierName,
        public readonly int $totalBruto,
        public readonly int $totalDiskon,
        public readonly int $totalPajak,
        public readonly int $grandTotal,
    ) {
    }
}