<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\View\View;

final class AdminDashboardController
{
    public function __invoke(Request $request): View
    {
        return view('admin.dashboard.index');
    }
}
