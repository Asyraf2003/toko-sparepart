<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Illuminate\View\View;

final class ExpenseCreateController
{
    public function __invoke(): View
    {
        return view('admin.expenses.create');
    }
}
