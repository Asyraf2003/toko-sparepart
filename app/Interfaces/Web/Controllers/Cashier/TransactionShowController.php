<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\Ports\Services\ClockPort;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

final readonly class TransactionShowController
{
    public function __construct(private ClockPort $clock) {}

    public function __invoke(int $transactionId): View
    {
        $today = $this->clock->todayBusinessDate();

        $tx = DB::table('transactions')->where('id', $transactionId)->first();
        if ($tx === null) {
            abort(404);
        }

        // hard gate: kasir hanya boleh lihat business_date hari ini
        $actor = request()->user();
        if ($actor !== null && (string) $actor->role === 'CASHIER') {
            if ((string) $tx->business_date !== $today) {
                abort(403);
            }
        }

        $partLines = DB::table('transaction_part_lines')
            ->join('products', 'products.id', '=', 'transaction_part_lines.product_id')
            ->where('transaction_part_lines.transaction_id', $transactionId)
            ->orderBy('transaction_part_lines.id')
            ->get([
                'transaction_part_lines.id',
                'transaction_part_lines.product_id',
                'products.sku',
                'products.name',
                'transaction_part_lines.qty',
                'transaction_part_lines.unit_sell_price_frozen',
                'transaction_part_lines.line_subtotal',
                'transaction_part_lines.unit_cogs_frozen',
            ]);

        $serviceLines = DB::table('transaction_service_lines')
            ->where('transaction_id', $transactionId)
            ->orderBy('id')
            ->get(['id', 'description', 'price_manual']);

        $search = trim((string) request()->query('q', ''));

        $products = DB::table('products')
            ->join('inventory_stocks', 'inventory_stocks.product_id', '=', 'products.id')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('products.sku', 'like', '%'.$search.'%')
                        ->orWhere('products.name', 'like', '%'.$search.'%');
                });
            })
            ->where('products.is_active', 1)
            ->orderBy('products.name')
            ->limit(50)
            ->get([
                'products.id',
                'products.sku',
                'products.name',
                'products.sell_price_current',
                'inventory_stocks.on_hand_qty',
                'inventory_stocks.reserved_qty',
            ])
            ->map(function ($r) {
                $r->available_qty = (int) $r->on_hand_qty - (int) $r->reserved_qty;

                return $r;
            });

        $partsTotal = (int) \DB::table('transaction_part_lines')->where('transaction_id', $tx->id)->sum('line_subtotal');
        $serviceTotal = (int) \DB::table('transaction_service_lines')->where('transaction_id', $tx->id)->sum('price_manual');
        $grossTotal = $partsTotal + $serviceTotal;

        $roundedCashTotal = (int) (round($grossTotal / 1000) * 1000);
        $cashRoundingAmount = $roundedCashTotal - $grossTotal;

        $pq = trim((string) request()->query('pq', ''));

        $productRows = \Illuminate\Support\Facades\DB::table('products')
            ->join('inventory_stocks', 'inventory_stocks.product_id', '=', 'products.id')
            ->where('products.is_active', 1)
            ->when($pq !== '', function ($qq) use ($pq) {
                $qq->where(function ($x) use ($pq) {
                    $x->where('products.sku', 'like', '%'.$pq.'%')
                        ->orWhere('products.name', 'like', '%'.$pq.'%');
                });
            })
            ->orderBy('products.name')
            ->limit(25)
            ->get([
                'products.id',
                'products.sku',
                'products.name',
                'products.sell_price_current',
                'inventory_stocks.on_hand_qty',
                'inventory_stocks.reserved_qty',
                \Illuminate\Support\Facades\DB::raw('(inventory_stocks.on_hand_qty - inventory_stocks.reserved_qty) as available_qty'),
            ]);

        return view('v2.cashier.transactions.show', [
            'today' => $today,
            'tx' => $tx,
            'partLines' => $partLines,
            'serviceLines' => $serviceLines,
            'products' => $products,
            'search' => $search,
            'partsTotal' => $partsTotal,
            'serviceTotal' => $serviceTotal,
            'grossTotal' => $grossTotal,
            'roundedCashTotal' => $roundedCashTotal,
            'cashRoundingAmount' => $cashRoundingAmount,
        ]);
    }
}
