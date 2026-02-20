<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Application\UseCases\Catalog\CreateProductRequest;
use App\Application\UseCases\Catalog\CreateProductUseCase;
use App\Infrastructure\Persistence\Eloquent\Models\InventoryStock;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateProductUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_product_creates_stock_row(): void
    {
        $uc = $this->app->make(CreateProductUseCase::class);

        $created = $uc->handle(new CreateProductRequest(
            sku: 'SKU-NEW',
            name: 'Oli Mesin',
            sellPriceCurrent: 25000,
            minStockThreshold: 3,
            isActive: true,
        ));

        $this->assertSame('SKU-NEW', $created->sku);

        $p = Product::query()->where('sku', 'SKU-NEW')->firstOrFail();
        $this->assertSame('Oli Mesin', (string) $p->name);

        $stock = InventoryStock::query()->where('product_id', $p->id)->firstOrFail();
        $this->assertSame(0, (int) $stock->on_hand_qty);
        $this->assertSame(0, (int) $stock->reserved_qty);
    }
}
