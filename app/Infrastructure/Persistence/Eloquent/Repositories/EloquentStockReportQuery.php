<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\DTO\Reporting\StockReportResult;
use App\Application\DTO\Reporting\StockReportRow;
use App\Application\DTO\Reporting\StockReportSummary;
use App\Application\Ports\Repositories\StockReportQueryPort;
use Illuminate\Support\Facades\DB;

final class EloquentStockReportQuery implements StockReportQueryPort
{
    public function list(
        ?string $search,
        bool $onlyActive,
        int $limit = 500,
    ): StockReportResult {
        $qb = DB::table('products as p')
            ->leftJoin('inventory_stocks as s', 's.product_id', '=', 'p.id')
            ->orderBy('p.name')
            ->orderBy('p.id');

        if ($onlyActive) {
            $qb->where('p.is_active', true);
        }

        if ($search !== null && trim($search) !== '') {
            $q = trim($search);
            $qb->where(function ($w) use ($q) {
                $w->where('p.sku', 'like', "%{$q}%")
                    ->orWhere('p.name', 'like', "%{$q}%");
            });
        }

        $rowsDb = $qb->limit($limit)->get([
            'p.id as product_id',
            'p.sku',
            'p.name',
            'p.is_active',
            'p.min_stock_threshold',
            DB::raw('COALESCE(s.on_hand_qty, 0) as on_hand_qty'),
            DB::raw('COALESCE(s.reserved_qty, 0) as reserved_qty'),
        ]);

        $rows = [];
        $count = 0;
        $low = 0;

        foreach ($rowsDb as $r) {
            $onHand = (int) $r->on_hand_qty;
            $reserved = (int) $r->reserved_qty;
            $available = $onHand - $reserved;

            $threshold = (int) $r->min_stock_threshold;
            $isLow = $available <= $threshold;

            $rows[] = new StockReportRow(
                productId: (int) $r->product_id,
                sku: (string) $r->sku,
                name: (string) $r->name,
                isActive: (bool) $r->is_active,
                minStockThreshold: $threshold,
                onHandQty: $onHand,
                reservedQty: $reserved,
                availableQty: $available,
                isLowStock: $isLow,
            );

            $count++;
            if ($isLow) {
                $low++;
            }
        }

        return new StockReportResult(
            rows: $rows,
            summary: new StockReportSummary(count: $count, lowStockCount: $low),
        );
    }
}
