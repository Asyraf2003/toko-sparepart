<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Application\UseCases\Catalog\SetSellingPriceRequest;
use App\Application\UseCases\Catalog\SetSellingPriceUseCase;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SetSellingPriceUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_selling_price_updates_product(): void
    {
        $p = Product::query()->create([
            'sku' => 'SKU-SP',
            'name' => 'Part A',
            'sell_price_current' => 1000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        $uc = $this->app->make(SetSellingPriceUseCase::class);

        $updated = $uc->handle(new SetSellingPriceRequest(
            productId: (int) $p->id,
            sellPriceCurrent: 2500,
            actorUserId: 1,
            note: 'update price',
        ));

        $this->assertSame(2500, $updated->sellPriceCurrent);

        $fresh = Product::query()->findOrFail($p->id);
        $this->assertSame(2500, (int) $fresh->sell_price_current);
    }
}
