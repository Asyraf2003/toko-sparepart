<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Application\Ports\Repositories\ProductStockQueryPort;
use App\Infrastructure\Persistence\Eloquent\Models\InventoryStock;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductStockQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_returns_products_with_stock_and_available(): void
    {
        $p1 = Product::query()->create([
            'sku' => 'SKU-Q1',
            'name' => 'Barang A',
            'sell_price_current' => 1000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        $p2 = Product::query()->create([
            'sku' => 'SKU-Q2',
            'name' => 'Barang B',
            'sell_price_current' => 2000,
            'min_stock_threshold' => 1,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        InventoryStock::query()->create([
            'product_id' => $p1->id,
            'on_hand_qty' => 10,
            'reserved_qty' => 4,
        ]);

        // p2 intentionally no stock row -> should COALESCE to 0/0

        $q = $this->app->make(ProductStockQueryPort::class);

        $rows = $q->list();

        $this->assertCount(2, $rows);

        $rowA = collect($rows)->firstWhere('sku', 'SKU-Q1');
        $this->assertNotNull($rowA);
        $this->assertSame(6, $rowA->availableQty());

        $rowB = collect($rows)->firstWhere('sku', 'SKU-Q2');
        $this->assertNotNull($rowB);
        $this->assertSame(0, $rowB->availableQty());
        $this->assertTrue($rowB->isLowStock());
    }

    public function test_list_can_filter_search(): void
    {
        Product::query()->create([
            'sku' => 'ABC-123',
            'name' => 'Oli Mesin',
            'sell_price_current' => 1000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        Product::query()->create([
            'sku' => 'XYZ-999',
            'name' => 'Busi',
            'sell_price_current' => 1000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        $q = $this->app->make(ProductStockQueryPort::class);

        $rows = $q->list(search: 'Oli');
        $this->assertCount(1, $rows);
        $this->assertSame('Oli Mesin', $rows[0]->name);
    }

    public function test_find_by_product_id_returns_row_or_null(): void
    {
        $p = Product::query()->create([
            'sku' => 'SKU-FIND',
            'name' => 'Find Me',
            'sell_price_current' => 1000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        InventoryStock::query()->create([
            'product_id' => $p->id,
            'on_hand_qty' => 7,
            'reserved_qty' => 2,
        ]);

        $q = $this->app->make(ProductStockQueryPort::class);

        $row = $q->findByProductId((int) $p->id);
        $this->assertNotNull($row);
        $this->assertSame('SKU-FIND', $row->sku);
        $this->assertSame(5, $row->availableQty());

        $missing = $q->findByProductId(999999);
        $this->assertNull($missing);
    }
}
