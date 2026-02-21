<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class EmployeeIndexController
{
    public function __invoke(): View
    {
        $rows = DB::table('employees')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $outstanding = DB::table('employee_loans')
            ->selectRaw('employee_id, SUM(outstanding_amount) AS outstanding')
            ->where('outstanding_amount', '>', 0)
            ->groupBy('employee_id')
            ->pluck('outstanding', 'employee_id');

        return view('admin.employees.index', [
            'rows' => $rows,
            'outstandingByEmployeeId' => $outstanding,
        ]);
    }
}
