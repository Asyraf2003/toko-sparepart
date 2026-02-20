<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Ports\Repositories\TransactionPartLineRepositoryPort;
use App\Infrastructure\Persistence\Eloquent\Models\TransactionPartLine;

final class EloquentTransactionPartLineRepository implements TransactionPartLineRepositoryPort
{
    public function upsertLine(int $transactionId, int $productId, int $qty, int $unitSellPriceFrozen): void
    {
        $lineSubtotal = $qty * $unitSellPriceFrozen;

        $existing = TransactionPartLine::query()
            ->where('transaction_id', $transactionId)
            ->where('product_id', $productId)
            ->first();

        if ($existing === null) {
            TransactionPartLine::query()->create([
                'transaction_id' => $transactionId,
                'product_id' => $productId,
                'qty' => $qty,
                'unit_sell_price_frozen' => $unitSellPriceFrozen,
                'line_subtotal' => $lineSubtotal,
                'unit_cogs_frozen' => null,
            ]);

            return;
        }

        $existing->update([
            'qty' => $qty,
            'unit_sell_price_frozen' => $unitSellPriceFrozen,
            'line_subtotal' => $lineSubtotal,
        ]);
    }

    public function deleteLine(int $transactionId, int $productId): void
    {
        TransactionPartLine::query()
            ->where('transaction_id', $transactionId)
            ->where('product_id', $productId)
            ->delete();
    }
}
