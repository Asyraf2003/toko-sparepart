<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\Ports\Repositories\StockReportQueryPort;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class StockReportIndexController
{
    public function __invoke(Request $request, StockReportQueryPort $query): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'only_active' => ['nullable', 'in:0,1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:2000'],
        ]);

        $search = $request->string('q')->trim()->value();
        $onlyActive = ($validated['only_active'] ?? '1') === '1';

        $result = $query->list(
            search: $search !== '' ? $search : null,
            onlyActive: $onlyActive,
            limit: isset($validated['limit']) ? (int) $validated['limit'] : 500,
        );

        return view('admin.reports.stock.index', [
            'filters' => [
                'q' => $search,
                'only_active' => $onlyActive ? '1' : '0',
                'limit' => $validated['limit'] ?? 500,
            ],
            'result' => $result,
        ]);
    }
}