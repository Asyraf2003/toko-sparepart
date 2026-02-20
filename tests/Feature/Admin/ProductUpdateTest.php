<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_product_base_fields(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin_update@local.test',
            'role' => User::ROLE_ADMIN,
            'password' => '12345678',
        ]);

        $p = Product::query()->create([
            'sku' => 'SKU-OLD',
            'name' => 'Nama Lama',
            'sell_price_current' => 1000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        $this->actingAs($admin)
            ->post("/admin/products/{$p->id}", [
                'sku' => 'SKU-NEW2',
                'name' => 'Nama Baru',
                'is_active' => '1',
            ])
            ->assertRedirect("/admin/products/{$p->id}/edit");

        $fresh = Product::query()->findOrFail($p->id);
        $this->assertSame('SKU-NEW2', (string) $fresh->sku);
        $this->assertSame('Nama Baru', (string) $fresh->name);
        $this->assertSame(1, (int) $fresh->is_active);
    }
}
