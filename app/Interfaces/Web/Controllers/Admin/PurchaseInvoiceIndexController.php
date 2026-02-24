<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PurchaseInvoiceIndexController
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', 'in:all,paid,unpaid'],
            'bucket' => ['nullable', 'in:all,due_h5,overdue'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:2000'],
        ]);

        $search = $request->string('q')->trim()->value();
        $status = $validated['status'] ?? 'all';
        $bucket = $validated['bucket'] ?? 'all';
        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 200;

        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();
        $targetDue = CarbonImmutable::parse($today)->addDays(5)->toDateString();

        $qb = DB::table('purchase_invoices')
            ->orderByDesc('tgl_kirim')
            ->orderByDesc('id');

        if ($search !== '') {
            $qb->where(function ($q) use ($search) {
                $q->where('no_faktur', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        if ($status === 'paid') {
            $qb->where('payment_status', 'PAID');
        } elseif ($status === 'unpaid') {
            $qb->where(function ($q) {
                $q->whereNull('payment_status')->orWhere('payment_status', 'UNPAID');
            });
        }

        if ($bucket === 'due_h5') {
            $qb->where(function ($q) {
                $q->whereNull('payment_status')->orWhere('payment_status', 'UNPAID');
            });
            $qb->whereNotNull('due_date')->where('due_date', $targetDue);
        } elseif ($bucket === 'overdue') {
            $qb->where(function ($q) {
                $q->whereNull('payment_status')->orWhere('payment_status', 'UNPAID');
            });
            $qb->whereNotNull('due_date')->where('due_date', '<', $today);
        }

        $rows = $qb->limit($limit)->get([
            'id',
            'no_faktur',
            'tgl_kirim',
            'due_date',
            'payment_status',
            'paid_at',
            'supplier_name',
            'total_bruto',
            'total_diskon',
            'total_pajak',
            'grand_total',
            'created_at',
        ]);

        return view('admin.purchases.index', [
            'q' => $search,
            'rows' => $rows,
            'filters' => [
                'status' => $status,
                'bucket' => $bucket,
                'limit' => $limit,
            ],
            'today' => $today,
            'targetDue' => $targetDue,
        ]);
    }
}