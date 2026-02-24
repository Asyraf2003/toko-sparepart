<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PurchaseInvoiceShowController
{
    public function __invoke(Request $request, int $purchaseInvoiceId): View
    {
        $invoice = DB::table('purchase_invoices as pi')
            ->leftJoin('users as u', 'u.id', '=', 'pi.created_by_user_id')
            ->leftJoin('users as up', 'up.id', '=', 'pi.paid_by_user_id')
            ->where('pi.id', $purchaseInvoiceId)
            ->first([
                'pi.id',
                'pi.supplier_name',
                'pi.no_faktur',
                'pi.tgl_kirim',
                'pi.due_date',
                'pi.payment_status',
                'pi.paid_at',
                'pi.paid_by_user_id',
                'pi.paid_note',
                'pi.kepada',
                'pi.no_pesanan',
                'pi.nama_sales',
                'pi.total_bruto',
                'pi.total_diskon',
                'pi.total_pajak',
                'pi.grand_total',
                'pi.note',
                'pi.created_at',
                'pi.created_by_user_id',
                DB::raw('u.name as created_by_name'),
                DB::raw('up.name as paid_by_name'),
            ]);

        if ($invoice === null) {
            abort(404);
        }

        $lines = DB::table('purchase_invoice_lines as l')
            ->join('products as p', 'p.id', '=', 'l.product_id')
            ->where('l.purchase_invoice_id', $purchaseInvoiceId)
            ->orderBy('l.id')
            ->get([
                'l.id',
                'l.product_id',
                'p.sku',
                'p.name',
                'l.qty',
                'l.unit_cost',
                'l.disc_bps',
                'l.line_total',
            ]);

        $proofs = DB::table('telegram_payment_proof_submissions')
            ->where('purchase_invoice_id', $purchaseInvoiceId)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();
        $targetDue = CarbonImmutable::parse($today)->addDays(5)->toDateString();

        return view('admin.purchases.show', [
            'invoice' => $invoice,
            'lines' => $lines,
            'today' => $today,
            'targetDue' => $targetDue,
            'proofs' => $proofs,
        ]);
    }
}
