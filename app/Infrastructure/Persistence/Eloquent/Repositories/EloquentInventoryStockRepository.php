<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\DTO\Inventory\InventoryStockSnapshot;
use App\Application\Ports\Repositories\InventoryStockRepositoryPort;
use App\Infrastructure\Persistence\Eloquent\Models\InventoryStock;
use Illuminate\Database\QueryException;

final class EloquentInventoryStockRepository implements InventoryStockRepositoryPort
{
    public function lockOrCreateByProductId(int $productId): InventoryStockSnapshot
    {
        // Must be called inside an outer DB transaction.
        $stock = InventoryStock::query()
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($stock === null) {
            try {
                InventoryStock::query()->create([
                    'product_id' => $productId,
                    'on_hand_qty' => 0,
                    'reserved_qty' => 0,
                ]);
            } catch (QueryException $e) {
                // race: another tx inserted it; ignore and re-lock below
            }

            $stock = InventoryStock::query()
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->firstOrFail();
        }

        return new InventoryStockSnapshot(
            id: (int) $stock->id,
            productId: (int) $stock->product_id,
            onHandQty: (int) $stock->on_hand_qty,
            reservedQty: (int) $stock->reserved_qty,
        );
    }

    public function save(InventoryStockSnapshot $stock): void
    {
        InventoryStock::query()
            ->whereKey($stock->id)
            ->update([
                'on_hand_qty' => $stock->onHandQty,
                'reserved_qty' => $stock->reservedQty,
            ]);
    }
}
