<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PurchaseInvoiceEditController
{
    public function __invoke(Request $request, int $purchaseInvoiceId): View
    {
        $invoice = DB::table('purchase_invoices')
            ->where('id', $purchaseInvoiceId)
            ->first([
                'id',
                'supplier_name',
                'no_faktur',
                'tgl_kirim',
                'kepada',
                'no_pesanan',
                'nama_sales',
                'note',
                'created_at',
                'created_by_user_id',
            ]);

        if ($invoice === null) {
            abort(404);
        }

        return view('admin.purchases.edit', [
            'invoice' => $invoice,
        ]);
    }
}
