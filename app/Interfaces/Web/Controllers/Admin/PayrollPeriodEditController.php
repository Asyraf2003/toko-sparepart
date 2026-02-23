<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PayrollPeriodEditController
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

        $employees = DB::table('employees')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $outstanding = DB::table('employee_loans')
            ->selectRaw('employee_id, SUM(outstanding_amount) AS outstanding')
            ->where('outstanding_amount', '>', 0)
            ->groupBy('employee_id')
            ->pluck('outstanding', 'employee_id');

        $existingLines = DB::table('payroll_lines')
            ->where('payroll_period_id', $payrollPeriodId)
            ->get(['employee_id', 'gross_pay', 'loan_deduction', 'note']);

        $lineByEmployeeId = [];
        foreach ($existingLines as $l) {
            $lineByEmployeeId[(int) $l->employee_id] = [
                'gross_pay' => (int) $l->gross_pay,
                'loan_deduction' => (int) $l->loan_deduction,
                'note' => $l->note !== null ? (string) $l->note : null,
            ];
        }

        return view('admin.payroll.edit', [
            'period' => $period,
            'employees' => $employees,
            'outstandingByEmployeeId' => $outstanding,
            'lineByEmployeeId' => $lineByEmployeeId,
        ]);
    }
}
