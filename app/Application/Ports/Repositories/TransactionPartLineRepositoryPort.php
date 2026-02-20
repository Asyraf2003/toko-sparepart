<?php

declare(strict_types=1);

namespace App\Application\Ports\Repositories;

interface TransactionPartLineRepositoryPort
{
    public function upsertLine(
        int $transactionId,
        int $productId,
        int $qty,
        int $unitSellPriceFrozen,
    ): void;
}
