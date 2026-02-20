<?php

declare(strict_types=1);

namespace App\Application\DTO\Inventory;

final readonly class InventoryStockSnapshot
{
    public function __construct(
        public int $id,
        public int $productId,
        public int $onHandQty,
        public int $reservedQty,
    ) {}

    public function availableQty(): int
    {
        return $this->onHandQty - $this->reservedQty;
    }

    public function withReservedQty(int $reservedQty): self
    {
        return new self(
            id: $this->id,
            productId: $this->productId,
            onHandQty: $this->onHandQty,
            reservedQty: $reservedQty,
        );
    }

    public function withOnHandQty(int $onHandQty): self
    {
        return new self(
            id: $this->id,
            productId: $this->productId,
            onHandQty: $onHandQty,
            reservedQty: $this->reservedQty,
        );
    }
}
