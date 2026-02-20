<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\DTO\Inventory\ProductSnapshot;
use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Services\TransactionManagerPort;

final readonly class SetSellingPriceUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ProductRepositoryPort $products,
    ) {}

    public function handle(SetSellingPriceRequest $req): ProductSnapshot
    {
        $note = trim($req->note);
        if ($note === '') {
            throw new \InvalidArgumentException('note is required');
        }

        if ($req->sellPriceCurrent < 0) {
            throw new \InvalidArgumentException('sellPriceCurrent must be >= 0');
        }

        return $this->tx->run(fn () => $this->products->setSellingPrice(
            productId: $req->productId,
            sellPriceCurrent: $req->sellPriceCurrent,
        ));
    }
}
