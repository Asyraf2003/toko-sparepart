<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Application\UseCases\Inventory\InvalidReleaseQuantity;
use App\Application\UseCases\Inventory\ReleaseStockRequest;
use App\Application\UseCases\Inventory\ReleaseStockUseCase;
use App\Infrastructure\Persistence\Eloquent\Models\InventoryStock;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\StockLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReleaseStockUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_success_updates_reserved_and_writes_ledger(): void
    {
        $product = Product::query()->create([
            'sku' => 'SKU-3',
            'name' => 'Kampas Rem',
            'sell_price_current' => 15000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        InventoryStock::query()->create([
            'product_id' => $product->id,
            'on_hand_qty' => 10,
            'reserved_qty' => 7,
        ]);

        $useCase = $this->app->make(ReleaseStockUseCase::class);

        $useCase->handle(new ReleaseStockRequest(
            productId: (int) $product->id,
            qty: 2,
            refType: 'test',
            refId: 456,
            actorUserId: null,
            note: 'release test',
        ));

        $stock = InventoryStock::query()->where('product_id', $product->id)->firstOrFail();
        $this->assertSame(5, (int) $stock->reserved_qty);

        $this->assertTrue(
            StockLedger::query()
                ->where('product_id', $product->id)
                ->where('type', 'RELEASE')
                ->where('qty_delta', -2)
                ->exists()
        );
    }

    public function test_release_rejects_when_qty_exceeds_reserved(): void
    {
        $product = Product::query()->create([
            'sku' => 'SKU-4',
            'name' => 'Rantai',
            'sell_price_current' => 30000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        InventoryStock::query()->create([
            'product_id' => $product->id,
            'on_hand_qty' => 5,
            'reserved_qty' => 1,
        ]);

        $useCase = $this->app->make(ReleaseStockUseCase::class);

        $this->expectException(InvalidReleaseQuantity::class);

        $useCase->handle(new ReleaseStockRequest(
            productId: (int) $product->id,
            qty: 2,
        ));
    }
}
