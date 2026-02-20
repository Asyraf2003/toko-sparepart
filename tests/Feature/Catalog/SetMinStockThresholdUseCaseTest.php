<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Application\UseCases\Catalog\SetMinStockThresholdRequest;
use App\Application\UseCases\Catalog\SetMinStockThresholdUseCase;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SetMinStockThresholdUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_threshold_updates_product(): void
    {
        $p = Product::query()->create([
            'sku' => 'SKU-TH',
            'name' => 'Part B',
            'sell_price_current' => 1000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        $uc = $this->app->make(SetMinStockThresholdUseCase::class);

        $updated = $uc->handle(new SetMinStockThresholdRequest(
            productId: (int) $p->id,
            minStockThreshold: 5,
            actorUserId: 1,
            note: 'update threshold',
        ));

        $this->assertSame(5, $updated->minStockThreshold);

        $fresh = Product::query()->findOrFail($p->id);
        $this->assertSame(5, (int) $fresh->min_stock_threshold);
    }
}
