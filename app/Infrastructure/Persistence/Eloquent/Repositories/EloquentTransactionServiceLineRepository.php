<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Ports\Repositories\TransactionServiceLineRepositoryPort;
use App\Infrastructure\Persistence\Eloquent\Models\TransactionServiceLine;

final class EloquentTransactionServiceLineRepository implements TransactionServiceLineRepositoryPort
{
    public function createLine(int $transactionId, string $description, int $priceManual): int
    {
        $line = TransactionServiceLine::query()->create([
            'transaction_id' => $transactionId,
            'description' => $description,
            'price_manual' => $priceManual,
        ]);

        return (int) $line->id;
    }

    public function updateLine(int $lineId, string $description, int $priceManual): void
    {
        $updated = TransactionServiceLine::query()
            ->whereKey($lineId)
            ->update([
                'description' => $description,
                'price_manual' => $priceManual,
            ]);

        if ($updated === 0) {
            throw new \InvalidArgumentException('service line not found');
        }
    }

    public function deleteLine(int $lineId): void
    {
        TransactionServiceLine::query()->whereKey($lineId)->delete();
    }
}
