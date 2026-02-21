<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\DTO\Reporting\PurchasingReportResult;
use App\Application\DTO\Reporting\PurchasingReportRow;
use App\Application\DTO\Reporting\PurchasingReportSummary;
use App\Application\Ports\Repositories\PurchasingReportQueryPort;
use Illuminate\Support\Facades\DB;

final class EloquentPurchasingReportQuery implements PurchasingReportQueryPort
{
    public function list(
        string $fromDate,
        string $toDate,
        ?string $noFakturSearch,
        int $limit = 200,
    ): PurchasingReportResult {
        $qb = DB::table('purchase_invoices')
            ->whereBetween('tgl_kirim', [$fromDate, $toDate])
            ->orderByDesc('tgl_kirim')
            ->orderByDesc('id');

        if ($noFakturSearch !== null && trim($noFakturSearch) !== '') {
            $s = trim($noFakturSearch);
            $qb->where('no_faktur', 'like', "%{$s}%");
        }

        $rowsDb = $qb->limit($limit)->get([
            'id',
            'tgl_kirim',
            'no_faktur',
            'supplier_name',
            'total_bruto',
            'total_diskon',
            'total_pajak',
            'grand_total',
        ]);

        $rows = [];
        $count = 0;
        $sumBruto = 0;
        $sumDiskon = 0;
        $sumPajak = 0;
        $sumGrand = 0;

        foreach ($rowsDb as $r) {
            $row = new PurchasingReportRow(
                id: (int) $r->id,
                tglKirim: (string) $r->tgl_kirim,
                noFaktur: (string) $r->no_faktur,
                supplierName: (string) $r->supplier_name,
                totalBruto: (int) $r->total_bruto,
                totalDiskon: (int) $r->total_diskon,
                totalPajak: (int) $r->total_pajak,
                grandTotal: (int) $r->grand_total,
            );

            $rows[] = $row;
            $count++;
            $sumBruto += $row->totalBruto;
            $sumDiskon += $row->totalDiskon;
            $sumPajak += $row->totalPajak;
            $sumGrand += $row->grandTotal;
        }

        return new PurchasingReportResult(
            rows: $rows,
            summary: new PurchasingReportSummary(
                count: $count,
                totalBruto: $sumBruto,
                totalDiskon: $sumDiskon,
                totalPajak: $sumPajak,
                grandTotal: $sumGrand,
            ),
        );
    }
}