<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\DTO\Reporting\SalesReportResult;
use App\Application\DTO\Reporting\SalesReportRow;
use App\Application\DTO\Reporting\SalesReportSummary;
use App\Application\Ports\Repositories\SalesReportQueryPort;
use Illuminate\Support\Facades\DB;

final class EloquentSalesReportQuery implements SalesReportQueryPort
{
    public function list(
        string $fromDate,
        string $toDate,
        ?string $status,
        ?string $paymentStatus,
        ?string $paymentMethod,
        ?int $cashierUserId,
        int $limit = 200,
    ): SalesReportResult {
        $partAgg = DB::table('transaction_part_lines')
            ->selectRaw('transaction_id')
            ->selectRaw('SUM(line_subtotal) AS part_subtotal')
            ->selectRaw('SUM(COALESCE(unit_cogs_frozen, 0) * qty) AS part_cogs')
            ->selectRaw('SUM(CASE WHEN unit_cogs_frozen IS NULL THEN qty ELSE 0 END) AS missing_cogs_qty')
            ->groupBy('transaction_id');

        $serviceAgg = DB::table('transaction_service_lines')
            ->selectRaw('transaction_id')
            ->selectRaw('SUM(price_manual) AS service_subtotal')
            ->groupBy('transaction_id');

        $qb = DB::table('transactions as t')
            ->leftJoinSub($partAgg, 'p', fn ($join) => $join->on('p.transaction_id', '=', 't.id'))
            ->leftJoinSub($serviceAgg, 's', fn ($join) => $join->on('s.transaction_id', '=', 't.id'))
            ->whereBetween('t.business_date', [$fromDate, $toDate])
            ->orderByDesc('t.business_date')
            ->orderByDesc('t.id');

        if ($status !== null) {
            $qb->where('t.status', $status);
        }

        if ($paymentStatus !== null) {
            $qb->where('t.payment_status', $paymentStatus);
        }

        if ($paymentMethod !== null) {
            $qb->where('t.payment_method', $paymentMethod);
        }

        if ($cashierUserId !== null) {
            $qb->where('t.created_by_user_id', $cashierUserId);
        }

        $rowsDb = $qb->limit($limit)->get([
            't.id',
            't.transaction_number',
            't.business_date',
            't.status',
            't.payment_status',
            't.payment_method',
            't.created_by_user_id',
            DB::raw('COALESCE(p.part_subtotal, 0) AS part_subtotal'),
            DB::raw('COALESCE(s.service_subtotal, 0) AS service_subtotal'),
            't.rounding_amount',
            DB::raw('(COALESCE(p.part_subtotal, 0) + COALESCE(s.service_subtotal, 0) + t.rounding_amount) AS grand_total'),
            DB::raw('COALESCE(p.part_cogs, 0) AS cogs_total'),
            DB::raw('COALESCE(p.missing_cogs_qty, 0) AS missing_cogs_qty'),
        ]);

        $rows = [];
        $sumCount = 0;
        $sumPart = 0;
        $sumService = 0;
        $sumRounding = 0;
        $sumGrand = 0;
        $sumCogs = 0;
        $sumMissingCogsQty = 0;

        foreach ($rowsDb as $r) {
            $row = new SalesReportRow(
                id: (int) $r->id,
                transactionNumber: (string) $r->transaction_number,
                businessDate: (string) $r->business_date,
                status: (string) $r->status,
                paymentStatus: (string) $r->payment_status,
                paymentMethod: $r->payment_method !== null ? (string) $r->payment_method : null,
                cashierUserId: (int) $r->created_by_user_id,
                partSubtotal: (int) $r->part_subtotal,
                serviceSubtotal: (int) $r->service_subtotal,
                roundingAmount: (int) $r->rounding_amount,
                grandTotal: (int) $r->grand_total,
                cogsTotal: (int) $r->cogs_total,
                missingCogsQty: (int) $r->missing_cogs_qty,
            );

            $rows[] = $row;

            $sumCount++;
            $sumPart += $row->partSubtotal;
            $sumService += $row->serviceSubtotal;
            $sumRounding += $row->roundingAmount;
            $sumGrand += $row->grandTotal;
            $sumCogs += $row->cogsTotal;
            $sumMissingCogsQty += $row->missingCogsQty;
        }

        $summary = new SalesReportSummary(
            count: $sumCount,
            partSubtotal: $sumPart,
            serviceSubtotal: $sumService,
            roundingAmount: $sumRounding,
            grandTotal: $sumGrand,
            cogsTotal: $sumCogs,
            missingCogsQty: $sumMissingCogsQty,
        );

        return new SalesReportResult(rows: $rows, summary: $summary);
    }
}