<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\DTO\Inventory\ProductSnapshot;
use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Services\TransactionManagerPort;

final readonly class SetMinStockThresholdUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ProductRepositoryPort $products,
    ) {}

    public function handle(SetMinStockThresholdRequest $req): ProductSnapshot
    {
        $note = trim($req->note);
        if ($note === '') {
            throw new \InvalidArgumentException('note is required');
        }

        if ($req->minStockThreshold < 0) {
            throw new \InvalidArgumentException('minStockThreshold must be >= 0');
        }

        return $this->tx->run(fn () => $this->products->setMinStockThreshold(
            productId: $req->productId,
            minStockThreshold: $req->minStockThreshold,
        ));
    }
}
