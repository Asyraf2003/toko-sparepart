<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Infrastructure\Persistence\Eloquent\Models\InventoryStock;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductStockPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_to_login(): void
    {
        $this->get('/admin/products')->assertRedirect('/login');
    }

    public function test_cashier_forbidden(): void
    {
        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_test@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        $this->actingAs($cashier)->get('/admin/products')->assertForbidden();
    }

    public function test_admin_can_view_list(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin_test@local.test',
            'role' => User::ROLE_ADMIN,
            'password' => '12345678',
        ]);

        $p = Product::query()->create([
            'sku' => 'SKU-VIEW',
            'name' => 'Oli View',
            'sell_price_current' => 10000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        InventoryStock::query()->create([
            'product_id' => $p->id,
            'on_hand_qty' => 5,
            'reserved_qty' => 2,
        ]);

        $this->actingAs($admin)
            ->get('/admin/products')
            ->assertOk()
            ->assertSee('Produk dan Stok', false)
            ->assertSee('SKU-VIEW')
            ->assertSee('Oli View');
    }
}
