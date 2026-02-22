<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final readonly class ProductSearchController
{
    public function __invoke(): View|JsonResponse|Response
    {
        // Blueprint: query untuk page bisa pakai pq, JS/legacy boleh pakai q.
        $q = trim((string) request()->query('pq', (string) request()->query('q', '')));

        $canSearch = ($q !== '' && mb_strlen($q) >= 2);
        $txId = (int) request()->query('tx_id', 0);

        // ===== JSON mode (dipertahankan untuk kompatibilitas) =====
        if (request()->expectsJson()) {
            if (!$canSearch) {
                return response()->json(['items' => []]);
            }

            $rows = DB::table('products')
                ->join('inventory_stocks', 'inventory_stocks.product_id', '=', 'products.id')
                ->where('products.is_active', 1)
                ->where(function ($qq) use ($q) {
                    $qq->where('products.sku', 'like', '%'.$q.'%')
                        ->orWhere('products.name', 'like', '%'.$q.'%');
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
                ]);

            $items = $rows->map(function ($r) {
                $onHand = (int) ($r->on_hand_qty ?? 0);
                $reserved = (int) ($r->reserved_qty ?? 0);

                return [
                    'id' => (int) $r->id,
                    'sku' => (string) $r->sku,
                    'name' => (string) $r->name,
                    'sell_price_current' => (int) $r->sell_price_current,
                    'on_hand_qty' => $onHand,
                    'reserved_qty' => $reserved,
                    'available_qty' => $onHand - $reserved,
                ];
            })->values();

            return response()->json(['items' => $items]);
        }

        // ===== HTML mode (Blueprint A+B) =====
        $rows = collect();
        if ($canSearch) {
            $rows = DB::table('products')
                ->join('inventory_stocks', 'inventory_stocks.product_id', '=', 'products.id')
                ->where('products.is_active', 1)
                ->where(function ($qq) use ($q) {
                    $qq->where('products.sku', 'like', '%'.$q.'%')
                        ->orWhere('products.name', 'like', '%'.$q.'%');
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
                    DB::raw('(inventory_stocks.on_hand_qty - inventory_stocks.reserved_qty) as available_qty'),
                ]);
        }

        // Fragment rows untuk fetch HTML (B)
        if ((string) request()->query('fragment', '') === 'rows') {
            return response()
                ->view('cashier.products.partials._rows', [
                    'rows' => $rows,
                    'canSearch' => $canSearch,
                    'txId' => $txId > 0 ? $txId : null,
                ])
                ->header('X-Items-Count', (string) $rows->count());
        }

        // Full page (A)
        return view('cashier.products.search', [
            'q' => $q,
            'rows' => $rows,
            'canSearch' => $canSearch,
        ]);
    }
}