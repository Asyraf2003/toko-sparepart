<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\DTO\Inventory\ProductStockRow;
use App\Application\Ports\Repositories\ProductStockQueryPort;
use Illuminate\Support\Facades\DB;

final class EloquentProductStockQuery implements ProductStockQueryPort
{
    public function list(?string $search = null, bool $onlyActive = true): array
    {
        $q = DB::table('products')
            ->leftJoin('inventory_stocks', 'inventory_stocks.product_id', '=', 'products.id')
            ->select([
                'products.id as product_id',
                'products.sku',
                'products.name',
                'products.sell_price_current',
                'products.min_stock_threshold',
                'products.is_active',
                DB::raw('COALESCE(inventory_stocks.on_hand_qty, 0) as on_hand_qty'),
                DB::raw('COALESCE(inventory_stocks.reserved_qty, 0) as reserved_qty'),
            ])
            ->orderBy('products.name');

        if ($onlyActive) {
            $q->where('products.is_active', '=', 1);
        }

        if ($search !== null) {
            $s = trim($search);
            if ($s !== '') {
                $q->where(function ($w) use ($s): void {
                    $w->where('products.sku', 'like', "%{$s}%")
                        ->orWhere('products.name', 'like', "%{$s}%");
                });
            }
        }

        $rows = $q->get();

        $out = [];
        foreach ($rows as $r) {
            $out[] = new ProductStockRow(
                productId: (int) $r->product_id,
                sku: (string) $r->sku,
                name: (string) $r->name,
                sellPriceCurrent: (int) $r->sell_price_current,
                minStockThreshold: (int) $r->min_stock_threshold,
                isActive: (bool) $r->is_active,
                onHandQty: (int) $r->on_hand_qty,
                reservedQty: (int) $r->reserved_qty,
            );
        }

        return $out;
    }

    public function findByProductId(int $productId): ?ProductStockRow
    {
        $r = DB::table('products')
            ->leftJoin('inventory_stocks', 'inventory_stocks.product_id', '=', 'products.id')
            ->select([
                'products.id as product_id',
                'products.sku',
                'products.name',
                'products.sell_price_current',
                'products.min_stock_threshold',
                'products.is_active',
                DB::raw('COALESCE(inventory_stocks.on_hand_qty, 0) as on_hand_qty'),
                DB::raw('COALESCE(inventory_stocks.reserved_qty, 0) as reserved_qty'),
            ])
            ->where('products.id', '=', $productId)
            ->first();

        if ($r === null) {
            return null;
        }

        return new ProductStockRow(
            productId: (int) $r->product_id,
            sku: (string) $r->sku,
            name: (string) $r->name,
            sellPriceCurrent: (int) $r->sell_price_current,
            minStockThreshold: (int) $r->min_stock_threshold,
            isActive: (bool) $r->is_active,
            onHandQty: (int) $r->on_hand_qty,
            reservedQty: (int) $r->reserved_qty,
        );
    }
}
