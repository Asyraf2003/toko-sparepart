<?php

declare(strict_types=1);

namespace App\Application\Ports\Repositories;

use App\Application\DTO\Inventory\StockLedgerEntry;

interface StockLedgerRepositoryPort
{
    public function append(StockLedgerEntry $entry): void;
}
