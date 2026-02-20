<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Application\UseCases\Inventory\InsufficientStock;
use App\Application\UseCases\Inventory\ReserveStockRequest;
use App\Application\UseCases\Inventory\ReserveStockUseCase;
use App\Infrastructure\Persistence\Eloquent\Models\InventoryStock;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\StockLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReserveStockUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserve_success_updates_reserved_and_writes_ledger(): void
    {
        $product = Product::query()->create([
            'sku' => 'SKU-1',
            'name' => 'Oli',
            'sell_price_current' => 10000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        InventoryStock::query()->create([
            'product_id' => $product->id,
            'on_hand_qty' => 10,
            'reserved_qty' => 2,
        ]);

        $useCase = $this->app->make(ReserveStockUseCase::class);

        $useCase->handle(new ReserveStockRequest(
            productId: (int) $product->id,
            qty: 3,
            refType: 'test',
            refId: 123,
            actorUserId: null,
            note: 'reserve test',
        ));

        $stock = InventoryStock::query()->where('product_id', $product->id)->firstOrFail();
        $this->assertSame(5, (int) $stock->reserved_qty);

        $this->assertTrue(
            StockLedger::query()
                ->where('product_id', $product->id)
                ->where('type', 'RESERVE')
                ->where('qty_delta', 3)
                ->exists()
        );
    }

    public function test_reserve_rejects_when_available_insufficient(): void
    {
        $product = Product::query()->create([
            'sku' => 'SKU-2',
            'name' => 'Busi',
            'sell_price_current' => 5000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        InventoryStock::query()->create([
            'product_id' => $product->id,
            'on_hand_qty' => 2,
            'reserved_qty' => 2,
        ]);

        $useCase = $this->app->make(ReserveStockUseCase::class);

        $this->expectException(InsufficientStock::class);

        $useCase->handle(new ReserveStockRequest(
            productId: (int) $product->id,
            qty: 1,
        ));
    }
}
