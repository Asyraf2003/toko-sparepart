<?php

declare(strict_types=1);

namespace App\Application\UseCases\Notifications;

final readonly class NotifyLowStockForProductRequest
{
    public function __construct(
        public int $productId,
        public string $triggerType,
        public ?int $actorUserId,
    ) {}
}
