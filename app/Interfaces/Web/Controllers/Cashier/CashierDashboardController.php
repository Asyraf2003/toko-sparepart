<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use Illuminate\Contracts\View\View;

final readonly class CashierDashboardController
{
    public function __invoke(): View
    {
        return view('v2.cashier.dashboard.index');
    }
}