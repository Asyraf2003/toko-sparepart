<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\Ports\Repositories\ProfitReportQueryPort;
use App\Application\Ports\Services\PdfRendererPort;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProfitReportPdfController
{
    public function __invoke(Request $request, ProfitReportQueryPort $query, PdfRendererPort $pdf): Response
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'granularity' => ['required', 'in:weekly,monthly'],
        ]);

        $result = $query->aggregate($validated['from'], $validated['to'], $validated['granularity']);

        $bytes = $pdf->renderBlade('admin.reports.profit.pdf', [
            'generated_at' => now()->toDateTimeString(),
            'filters' => $validated,
            'result' => $result,
        ]);

        $filename = sprintf('profit-report_%s_%s_to_%s.pdf', $validated['granularity'], $validated['from'], $validated['to']);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}