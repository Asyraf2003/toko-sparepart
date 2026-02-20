<?php

declare(strict_types=1);

namespace App\Application\Ports\Repositories;

interface TransactionServiceLineRepositoryPort
{
    public function createLine(int $transactionId, string $description, int $priceManual): int;

    public function updateLine(int $lineId, string $description, int $priceManual): void;

    public function deleteLine(int $lineId): void;
}
