<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\DTO\Reporting\ProfitReportResult;
use App\Application\DTO\Reporting\ProfitReportRow;
use App\Application\DTO\Reporting\ProfitReportSummary;
use App\Application\Ports\Repositories\ProfitReportQueryPort;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentProfitReportQuery implements ProfitReportQueryPort
{
    public function aggregate(string $fromDate, string $toDate, string $granularity): ProfitReportResult
    {
        if (!in_array($granularity, ['weekly', 'monthly'], true)) {
            $granularity = 'weekly';
        }

        // 1) SALES daily aggregates (COMPLETED only; per ADR stock/cogs freeze happens on completion)
        $salesDaily = DB::table('transactions as t')
            ->leftJoin('transaction_part_lines as pl', 'pl.transaction_id', '=', 't.id')
            ->leftJoin('transaction_service_lines as sl', 'sl.transaction_id', '=', 't.id')
            ->whereBetween('t.business_date', [$fromDate, $toDate])
            ->where('t.status', 'COMPLETED')
            ->groupBy('t.business_date')
            ->orderBy('t.business_date')
            ->get([
                't.business_date as d',
                DB::raw('COALESCE(SUM(DISTINCT t.rounding_amount), 0) as rounding_amount'),
                DB::raw('COALESCE(SUM(pl.line_subtotal), 0) as revenue_part'),
                DB::raw('COALESCE(SUM(sl.price_manual), 0) as revenue_service'),
                DB::raw('COALESCE(SUM(COALESCE(pl.unit_cogs_frozen, 0) * pl.qty), 0) as cogs_total'),
                DB::raw('COALESCE(SUM(CASE WHEN pl.unit_cogs_frozen IS NULL THEN pl.qty ELSE 0 END), 0) as missing_cogs_qty'),
            ]);

        // 2) EXPENSES daily aggregates
        $expensesDaily = DB::table('expenses')
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->groupBy('expense_date')
            ->orderBy('expense_date')
            ->get([
                'expense_date as d',
                DB::raw('SUM(amount) as expenses_total'),
            ]);

        // 3) PAYROLL weekly aggregates (payroll_periods.week_end within range)
        $payrollWeekly = DB::table('payroll_periods as pp')
            ->join('payroll_lines as pl', 'pl.payroll_period_id', '=', 'pp.id')
            ->whereBetween('pp.week_end', [$fromDate, $toDate])
            ->groupBy('pp.week_start', 'pp.week_end')
            ->orderBy('pp.week_start')
            ->get([
                'pp.week_start',
                'pp.week_end',
                DB::raw('SUM(pl.gross_pay) as payroll_gross'),
            ]);

        // Convert to maps
        $salesByDate = [];
        foreach ($salesDaily as $r) {
            $salesByDate[(string) $r->d] = [
                'rounding' => (int) $r->rounding_amount,
                'part' => (int) $r->revenue_part,
                'service' => (int) $r->revenue_service,
                'cogs' => (int) $r->cogs_total,
                'missing' => (int) $r->missing_cogs_qty,
            ];
        }

        $expensesByDate = [];
        foreach ($expensesDaily as $r) {
            $expensesByDate[(string) $r->d] = (int) $r->expenses_total;
        }

        // Bucket accumulators
        $buckets = []; // key => sums
        $from = CarbonImmutable::parse($fromDate);
        $to = CarbonImmutable::parse($toDate);

        for ($d = $from; $d->lte($to); $d = $d->addDay()) {
            $ds = $d->toDateString();

            if ($granularity === 'weekly') {
                $keyDate = $d->startOfWeek(CarbonImmutable::MONDAY);
                $key = $keyDate->toDateString();
                $label = $key.' (week)';
            } else {
                $key = $d->format('Y-m');
                $label = $key.' (month)';
            }

            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'label' => $label,
                    'part' => 0,
                    'service' => 0,
                    'rounding' => 0,
                    'cogs' => 0,
                    'missing' => 0,
                    'expenses' => 0,
                    'payroll' => 0,
                ];
            }

            $s = $salesByDate[$ds] ?? null;
            if ($s !== null) {
                $buckets[$key]['part'] += $s['part'];
                $buckets[$key]['service'] += $s['service'];
                $buckets[$key]['rounding'] += $s['rounding'];
                $buckets[$key]['cogs'] += $s['cogs'];
                $buckets[$key]['missing'] += $s['missing'];
            }

            $buckets[$key]['expenses'] += $expensesByDate[$ds] ?? 0;
        }

        // Payroll: allocate by week_start bucket for weekly; by month of week_end for monthly
        foreach ($payrollWeekly as $p) {
            $weekStart = CarbonImmutable::parse((string) $p->week_start);
            $weekEnd = CarbonImmutable::parse((string) $p->week_end);
            $gross = (int) $p->payroll_gross;

            if ($granularity === 'weekly') {
                $key = $weekStart->toDateString();
            } else {
                $key = $weekEnd->format('Y-m');
            }

            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'label' => $granularity === 'weekly' ? ($key.' (week)') : ($key.' (month)'),
                    'part' => 0,
                    'service' => 0,
                    'rounding' => 0,
                    'cogs' => 0,
                    'missing' => 0,
                    'expenses' => 0,
                    'payroll' => 0,
                ];
            }

            $buckets[$key]['payroll'] += $gross;
        }

        ksort($buckets);

        $rows = [];
        $sum = ProfitReportSummary::empty();

        foreach ($buckets as $key => $b) {
            $revenueTotal = $b['part'] + $b['service'] + $b['rounding'];
            $net = $revenueTotal - $b['cogs'] - $b['expenses'] - $b['payroll'];

            $rows[] = new ProfitReportRow(
                periodKey: (string) $key,
                periodLabel: (string) $b['label'],
                revenuePart: (int) $b['part'],
                revenueService: (int) $b['service'],
                roundingAmount: (int) $b['rounding'],
                revenueTotal: (int) $revenueTotal,
                cogsTotal: (int) $b['cogs'],
                expensesTotal: (int) $b['expenses'],
                payrollGross: (int) $b['payroll'],
                netProfit: (int) $net,
                missingCogsQty: (int) $b['missing'],
            );

            $sum = new ProfitReportSummary(
                revenuePart: $sum->revenuePart + (int) $b['part'],
                revenueService: $sum->revenueService + (int) $b['service'],
                roundingAmount: $sum->roundingAmount + (int) $b['rounding'],
                revenueTotal: $sum->revenueTotal + (int) $revenueTotal,
                cogsTotal: $sum->cogsTotal + (int) $b['cogs'],
                expensesTotal: $sum->expensesTotal + (int) $b['expenses'],
                payrollGross: $sum->payrollGross + (int) $b['payroll'],
                netProfit: $sum->netProfit + (int) $net,
                missingCogsQty: $sum->missingCogsQty + (int) $b['missing'],
            );
        }

        return new ProfitReportResult(
            rows: $rows,
            summary: $sum,
            granularity: $granularity,
            fromDate: $fromDate,
            toDate: $toDate,
        );
    }
}