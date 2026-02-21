<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PurchaseInvoiceIndexController
{
    public function __invoke(Request $request): View
    {
        $search = $request->string('q')->trim()->value();

        $qb = DB::table('purchase_invoices')
            ->orderByDesc('tgl_kirim')
            ->orderByDesc('id');

        if ($search !== '') {
            $qb->where(function ($q) use ($search) {
                $q->where('no_faktur', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        $rows = $qb->limit(200)->get([
            'id',
            'no_faktur',
            'tgl_kirim',
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
        ]);
    }
}
