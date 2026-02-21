<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\Ports\Repositories\StockReportQueryPort;
use App\Application\Ports\Services\PdfRendererPort;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class StockReportPdfController
{
    public function __invoke(Request $request, StockReportQueryPort $query, PdfRendererPort $pdf): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'only_active' => ['nullable', 'in:0,1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $search = $request->string('q')->trim()->value();
        $onlyActive = ($validated['only_active'] ?? '1') === '1';

        $result = $query->list(
            search: $search !== '' ? $search : null,
            onlyActive: $onlyActive,
            limit: isset($validated['limit']) ? (int) $validated['limit'] : 5000,
        );

        $bytes = $pdf->renderBlade('admin.reports.stock.pdf', [
            'generated_at' => now()->toDateTimeString(),
            'filters' => [
                'q' => $search,
                'only_active' => $onlyActive ? '1' : '0',
                'limit' => $validated['limit'] ?? 5000,
            ],
            'result' => $result,
        ]);

        $filename = 'stock-report.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
