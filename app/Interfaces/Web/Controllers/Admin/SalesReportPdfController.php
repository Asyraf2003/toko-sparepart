<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\Ports\Repositories\SalesReportQueryPort;
use App\Application\Ports\Services\PdfRendererPort;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SalesReportPdfController
{
    public function __invoke(Request $request, SalesReportQueryPort $query, PdfRendererPort $pdf): Response
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', 'in:DRAFT,OPEN,COMPLETED,VOID'],
            'payment_status' => ['nullable', 'in:UNPAID,PAID'],
            'payment_method' => ['nullable', 'in:CASH,TRANSFER'],
            'cashier_user_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $result = $query->list(
            fromDate: $validated['from'],
            toDate: $validated['to'],
            status: $validated['status'] ?? null,
            paymentStatus: $validated['payment_status'] ?? null,
            paymentMethod: $validated['payment_method'] ?? null,
            cashierUserId: isset($validated['cashier_user_id']) ? (int) $validated['cashier_user_id'] : null,
            limit: isset($validated['limit']) ? (int) $validated['limit'] : 5000,
        );

        $bytes = $pdf->renderBlade('admin.reports.sales.pdf', [
            'generated_at' => now()->toDateTimeString(),
            'filters' => $validated,
            'result' => $result,
        ]);

        $filename = sprintf('sales-report_%s_to_%s.pdf', $validated['from'], $validated['to']);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}