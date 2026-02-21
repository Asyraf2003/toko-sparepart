<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\Ports\Repositories\PurchasingReportQueryPort;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PurchasingReportIndexController
{
    public function __invoke(Request $request, PurchasingReportQueryPort $query): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'no_faktur' => ['nullable', 'string', 'max:64'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        $result = null;
        if ($from !== null && $to !== null) {
            $result = $query->list(
                fromDate: $from,
                toDate: $to,
                noFakturSearch: $validated['no_faktur'] ?? null,
                limit: isset($validated['limit']) ? (int) $validated['limit'] : 200,
            );
        }

        return view('admin.reports.purchasing.index', [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'no_faktur' => $validated['no_faktur'] ?? null,
                'limit' => $validated['limit'] ?? 200,
            ],
            'result' => $result,
        ]);
    }
}
