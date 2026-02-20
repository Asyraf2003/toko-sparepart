<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

final readonly class SetMinStockThresholdRequest
{
    public function __construct(
        public int $productId,
        public int $minStockThreshold,
        public int $actorUserId,
        public string $note,
    ) {}
}
