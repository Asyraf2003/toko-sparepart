<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\AdminDashboard\GetAdminDashboardUseCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AdminDashboardController
{
    public function __invoke(Request $request, GetAdminDashboardUseCase $uc): View
    {
        $days = (int) $request->query('days', 14);
        if ($days <= 0) {
            $days = 14;
        }

        $dashboard = $uc->handle($days);

        return view('admin.dashboard.index', [
            'dashboard' => $dashboard,
        ]);
    }
}