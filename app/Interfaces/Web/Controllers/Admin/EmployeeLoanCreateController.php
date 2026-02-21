<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class EmployeeLoanCreateController
{
    public function __invoke(int $employeeId): View
    {
        $emp = DB::table('employees')->where('id', $employeeId)->first(['id', 'name']);
        if ($emp === null) {
            abort(404);
        }

        return view('admin.employee_loans.create', [
            'employee' => $emp,
        ]);
    }
}
