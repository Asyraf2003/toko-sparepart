<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PayrollPeriodShowController
{
    public function __invoke(Request $request, int $payrollPeriodId): View
    {
        $period = DB::table('payroll_periods')
            ->where('id', $payrollPeriodId)
            ->first([
                'id',
                'week_start',
                'week_end',
                'note',
                'loan_deductions_applied_at',
                'created_at',
            ]);

        if ($period === null) {
            abort(404);
        }

        $sum = DB::table('payroll_lines')
            ->selectRaw('SUM(gross_pay) AS sum_gross, SUM(loan_deduction) AS sum_deduction, SUM(net_paid) AS sum_net')
            ->where('payroll_period_id', $payrollPeriodId)
            ->first();

        $lines = DB::table('payroll_lines as l')
            ->join('employees as e', 'e.id', '=', 'l.employee_id')
            ->where('l.payroll_period_id', $payrollPeriodId)
            ->orderBy('e.name')
            ->get([
                'l.id',
                'l.employee_id',
                'e.name as employee_name',
                'l.gross_pay',
                'l.loan_deduction',
                'l.net_paid',
                'l.note',
            ]);

        return view('admin.payroll.show', [
            'period' => $period,
            'sum' => $sum,
            'lines' => $lines,
        ]);
    }
}
