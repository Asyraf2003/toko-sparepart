<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\Ports\Repositories\ProfitReportQueryPort;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProfitReportIndexController
{
    public function __invoke(Request $request, ProfitReportQueryPort $query): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'granularity' => ['nullable', 'in:weekly,monthly'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;
        $granularity = $validated['granularity'] ?? 'weekly';

        $result = null;
        if ($from !== null && $to !== null) {
            $result = $query->aggregate($from, $to, $granularity);
        }

        return view('admin.reports.profit.index', [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'granularity' => $granularity,
            ],
            'result' => $result,
        ]);
    }
}
