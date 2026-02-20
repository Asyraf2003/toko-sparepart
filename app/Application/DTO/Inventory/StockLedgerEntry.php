<?php

declare(strict_types=1);

namespace App\Application\DTO\Inventory;

use DateTimeImmutable;

final readonly class StockLedgerEntry
{
    public function __construct(
        public int $productId,
        public string $type, // PURCHASE_IN | SALE_OUT | VOID_IN | ADJUSTMENT | RESERVE | RELEASE
        public int $qtyDelta,
        public ?string $refType,
        public ?int $refId,
        public ?int $actorUserId,
        public DateTimeImmutable $occurredAt,
        public ?string $note,
    ) {}
}
