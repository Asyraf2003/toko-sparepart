<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final readonly class ProductSearchController
{
    public function __invoke(): JsonResponse
    {
        $q = trim((string) request()->query('q', ''));

        // default: min 2 chars biar ga spam query
        if ($q === '' || mb_strlen($q) < 2) {
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
}
