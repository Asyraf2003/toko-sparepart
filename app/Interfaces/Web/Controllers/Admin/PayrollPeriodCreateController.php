<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PayrollPeriodCreateController
{
    public function __invoke(): View
    {
        $employees = DB::table('employees')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $outstanding = DB::table('employee_loans')
            ->selectRaw('employee_id, SUM(outstanding_amount) AS outstanding')
            ->where('outstanding_amount', '>', 0)
            ->groupBy('employee_id')
            ->pluck('outstanding', 'employee_id');

        return view('admin.payroll.create', [
            'employees' => $employees,
            'outstandingByEmployeeId' => $outstanding,
        ]);
    }
}
