<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\Ports\Repositories\PurchasingReportQueryPort;
use App\Application\Ports\Services\PdfRendererPort;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PurchasingReportPdfController
{
    public function __invoke(Request $request, PurchasingReportQueryPort $query, PdfRendererPort $pdf): Response
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'no_faktur' => ['nullable', 'string', 'max:64'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $result = $query->list(
            fromDate: $validated['from'],
            toDate: $validated['to'],
            noFakturSearch: $validated['no_faktur'] ?? null,
            limit: isset($validated['limit']) ? (int) $validated['limit'] : 5000,
        );

        $bytes = $pdf->renderBlade('admin.reports.purchasing.pdf', [
            'generated_at' => now()->toDateTimeString(),
            'filters' => $validated,
            'result' => $result,
        ]);

        $filename = sprintf('purchasing-report_%s_to_%s.pdf', $validated['from'], $validated['to']);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
