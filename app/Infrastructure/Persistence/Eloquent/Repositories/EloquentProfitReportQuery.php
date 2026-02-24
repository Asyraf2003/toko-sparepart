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
        if (! in_array($granularity, ['daily', 'weekly', 'monthly'], true)) {
            $granularity = 'weekly';
        }

        // Aggregate per transaction first to avoid join multiplication.
        $partAgg = DB::table('transaction_part_lines')
            ->selectRaw('transaction_id')
            ->selectRaw('SUM(line_subtotal) AS part_subtotal')
            ->selectRaw('SUM(COALESCE(unit_cogs_frozen, 0) * qty) AS cogs_total')
            ->selectRaw('SUM(CASE WHEN unit_cogs_frozen IS NULL THEN qty ELSE 0 END) AS missing_cogs_qty')
            ->groupBy('transaction_id');

        $serviceAgg = DB::table('transaction_service_lines')
            ->selectRaw('transaction_id')
            ->selectRaw('SUM(price_manual) AS service_subtotal')
            ->groupBy('transaction_id');

        $txAgg = DB::table('transactions as t')
            ->leftJoinSub($partAgg, 'p', fn ($j) => $j->on('p.transaction_id', '=', 't.id'))
            ->leftJoinSub($serviceAgg, 's', fn ($j) => $j->on('s.transaction_id', '=', 't.id'))
            ->whereBetween('t.business_date', [$fromDate, $toDate])
            ->where('t.status', 'COMPLETED')
            ->select([
                't.business_date as d',
                't.id as transaction_id',
                't.rounding_amount',
                DB::raw('COALESCE(p.part_subtotal, 0) as revenue_part'),
                DB::raw('COALESCE(s.service_subtotal, 0) as revenue_service'),
                DB::raw('COALESCE(p.cogs_total, 0) as cogs_total'),
                DB::raw('COALESCE(p.missing_cogs_qty, 0) as missing_cogs_qty'),
            ]);

        $salesDaily = DB::query()
            ->fromSub($txAgg, 'x')
            ->groupBy('x.d')
            ->orderBy('x.d')
            ->get([
                'x.d',
                DB::raw('SUM(x.rounding_amount) as rounding_amount'),
                DB::raw('SUM(x.revenue_part) as revenue_part'),
                DB::raw('SUM(x.revenue_service) as revenue_service'),
                DB::raw('SUM(x.cogs_total) as cogs_total'),
                DB::raw('SUM(x.missing_cogs_qty) as missing_cogs_qty'),
            ]);

        $expensesDaily = DB::table('expenses')
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->groupBy('expense_date')
            ->orderBy('expense_date')
            ->get([
                'expense_date as d',
                DB::raw('SUM(amount) as expenses_total'),
            ]);

        // Payroll
        if ($granularity === 'daily') {
            $payrollWeekly = DB::table('payroll_periods as pp')
                ->join('payroll_lines as pl', 'pl.payroll_period_id', '=', 'pp.id')
                ->where('pp.week_end', '>=', $fromDate)
                ->where('pp.week_start', '<=', $toDate)
                ->groupBy('pp.week_start', 'pp.week_end')
                ->orderBy('pp.week_start')
                ->get([
                    'pp.week_start',
                    'pp.week_end',
                    DB::raw('SUM(pl.gross_pay) as payroll_gross'),
                ]);
        } else {
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
        }

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

        $buckets = [];
        $from = CarbonImmutable::parse($fromDate);
        $to = CarbonImmutable::parse($toDate);

        for ($d = $from; $d->lte($to); $d = $d->addDay()) {
            $ds = $d->toDateString();

            if ($granularity === 'daily') {
                $key = $ds;
                $label = $ds.' (day)';
            } elseif ($granularity === 'weekly') {
                $keyDate = $d->startOfWeek(CarbonImmutable::MONDAY);
                $key = $keyDate->toDateString();
                $label = $key.' (week)';
            } else {
                $key = $d->format('Y-m');
                $label = $key.' (month)';
            }

            if (! isset($buckets[$key])) {
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

        // Payroll allocation
        foreach ($payrollWeekly as $p) {
            $weekStart = CarbonImmutable::parse((string) $p->week_start);
            $weekEnd = CarbonImmutable::parse((string) $p->week_end);
            $gross = (int) $p->payroll_gross;

            if ($gross <= 0) {
                continue;
            }

            if ($granularity === 'daily') {
                $days = (int) $weekStart->diffInDays($weekEnd) + 1;
                if ($days <= 0) {
                    continue;
                }

                $base = intdiv($gross, $days);
                $rem = $gross % $days;

                for ($i = 0; $i < $days; $i++) {
                    $day = $weekStart->addDays($i)->toDateString();
                    if ($day < $fromDate || $day > $toDate) {
                        continue;
                    }

                    if (! isset($buckets[$day])) {
                        $buckets[$day] = [
                            'label' => $day.' (day)',
                            'part' => 0,
                            'service' => 0,
                            'rounding' => 0,
                            'cogs' => 0,
                            'missing' => 0,
                            'expenses' => 0,
                            'payroll' => 0,
                        ];
                    }

                    $buckets[$day]['payroll'] += $base + ($i < $rem ? 1 : 0);
                }

                continue;
            }

            $key = $granularity === 'weekly'
                ? $weekStart->toDateString()
                : $weekEnd->format('Y-m');

            if (! isset($buckets[$key])) {
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
