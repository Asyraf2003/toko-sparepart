<?php

declare(strict_types=1);

namespace Tests\Feature\Cashier;

use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionShowProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_see_search_results_on_show_page(): void
    {
        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_show_search@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        $p = Product::query()->create([
            'sku' => 'SP-ABC',
            'name' => 'Sparepart ABC',
            'sell_price_current' => 10000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 5000,
        ]);

        DB::table('inventory_stocks')->insert([
            'product_id' => $p->id,
            'on_hand_qty' => 10,
            'reserved_qty' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // bikin transaksi via route (biar sesuai flow kamu)
        $resp = $this->actingAs($cashier)->post('/cashier/transactions');
        $resp->assertRedirect();
        $location = $resp->headers->get('Location');
        $this->assertNotNull($location);

        $this->actingAs($cashier)
            ->get($location.'?pq=SP-ABC')
            ->assertOk()
            ->assertSee('SP-ABC')
            ->assertSee('Sparepart ABC');
    }
}
