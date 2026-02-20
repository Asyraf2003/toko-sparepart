<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Application\UseCases\Inventory\AdjustStockRequest;
use App\Application\UseCases\Inventory\AdjustStockUseCase;
use App\Application\UseCases\Inventory\InvalidStockAdjustment;
use App\Infrastructure\Persistence\Eloquent\Models\InventoryStock;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\StockLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdjustStockUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjust_positive_increases_on_hand_and_writes_ledger(): void
    {
        $product = Product::query()->create([
            'sku' => 'SKU-5',
            'name' => 'Filter',
            'sell_price_current' => 20000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        InventoryStock::query()->create([
            'product_id' => $product->id,
            'on_hand_qty' => 0,
            'reserved_qty' => 0,
        ]);

        $useCase = $this->app->make(AdjustStockUseCase::class);

        $useCase->handle(new AdjustStockRequest(
            productId: (int) $product->id,
            qtyDelta: 10,
            actorUserId: 1,
            note: 'initial stock',
            refType: 'test',
            refId: 1,
        ));

        $stock = InventoryStock::query()->where('product_id', $product->id)->firstOrFail();
        $this->assertSame(10, (int) $stock->on_hand_qty);

        $this->assertTrue(
            StockLedger::query()
                ->where('product_id', $product->id)
                ->where('type', 'ADJUSTMENT')
                ->where('qty_delta', 10)
                ->exists()
        );
    }

    public function test_adjust_negative_rejects_when_on_hand_would_go_below_zero(): void
    {
        $product = Product::query()->create([
            'sku' => 'SKU-6',
            'name' => 'Lampu',
            'sell_price_current' => 25000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        InventoryStock::query()->create([
            'product_id' => $product->id,
            'on_hand_qty' => 3,
            'reserved_qty' => 0,
        ]);

        $useCase = $this->app->make(AdjustStockUseCase::class);

        $this->expectException(InvalidStockAdjustment::class);

        $useCase->handle(new AdjustStockRequest(
            productId: (int) $product->id,
            qtyDelta: -4,
            actorUserId: 1,
            note: 'bad adjust',
        ));
    }
}
