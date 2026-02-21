<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PayrollPeriodIndexController
{
    public function __invoke(): View
    {
        $periods = DB::table('payroll_periods')
            ->orderByDesc('week_start')
            ->orderByDesc('id')
            ->limit(100)
            ->get([
                'id',
                'week_start',
                'week_end',
                'note',
                'loan_deductions_applied_at',
                'created_at',
            ]);

        $ids = $periods->pluck('id')->all();

        $sumByPeriodId = [];
        if (count($ids) > 0) {
            $sums = DB::table('payroll_lines')
                ->selectRaw('payroll_period_id, SUM(gross_pay) AS sum_gross, SUM(loan_deduction) AS sum_deduction, SUM(net_paid) AS sum_net')
                ->whereIn('payroll_period_id', $ids)
                ->groupBy('payroll_period_id')
                ->get();

            foreach ($sums as $s) {
                $sumByPeriodId[(int) $s->payroll_period_id] = $s;
            }
        }

        return view('admin.payroll.index', [
            'periods' => $periods,
            'sumByPeriodId' => $sumByPeriodId,
        ]);
    }
}
