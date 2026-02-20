<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\DTO\Inventory\StockLedgerEntry;
use App\Application\Ports\Repositories\StockLedgerRepositoryPort;
use App\Infrastructure\Persistence\Eloquent\Models\StockLedger;

final class EloquentStockLedgerRepository implements StockLedgerRepositoryPort
{
    public function append(StockLedgerEntry $entry): void
    {
        StockLedger::query()->create([
            'product_id' => $entry->productId,
            'type' => $entry->type,
            'qty_delta' => $entry->qtyDelta,
            'ref_type' => $entry->refType,
            'ref_id' => $entry->refId,
            'actor_user_id' => $entry->actorUserId,
            'occurred_at' => $entry->occurredAt,
            'note' => $entry->note,
        ]);
    }
}
