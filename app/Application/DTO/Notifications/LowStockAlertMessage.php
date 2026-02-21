<?php

declare(strict_types=1);

namespace App\Application\DTO\Notifications;

final readonly class LowStockAlertMessage
{
    public function __construct(
        public int $productId,
        public string $sku,
        public string $name,
        public int $availableQty,
        public int $threshold,
        public string $triggerType,
        public \DateTimeImmutable $occurredAt,
    ) {}
}