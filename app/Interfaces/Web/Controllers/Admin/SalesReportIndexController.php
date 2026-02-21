<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\Ports\Repositories\SalesReportQueryPort;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SalesReportIndexController
{
    public function __invoke(Request $request, SalesReportQueryPort $query): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', 'in:DRAFT,OPEN,COMPLETED,VOID'],
            'payment_status' => ['nullable', 'in:UNPAID,PAID'],
            'payment_method' => ['nullable', 'in:CASH,TRANSFER'],
            'cashier_user_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        $result = null;
        if ($from !== null && $to !== null) {
            $result = $query->list(
                fromDate: $from,
                toDate: $to,
                status: $validated['status'] ?? null,
                paymentStatus: $validated['payment_status'] ?? null,
                paymentMethod: $validated['payment_method'] ?? null,
                cashierUserId: isset($validated['cashier_user_id']) ? (int) $validated['cashier_user_id'] : null,
                limit: isset($validated['limit']) ? (int) $validated['limit'] : 200,
            );
        }

        return view('admin.reports.sales.index', [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'status' => $validated['status'] ?? null,
                'payment_status' => $validated['payment_status'] ?? null,
                'payment_method' => $validated['payment_method'] ?? null,
                'cashier_user_id' => $validated['cashier_user_id'] ?? null,
                'limit' => $validated['limit'] ?? 200,
            ],
            'result' => $result,
        ]);
    }
}