<?php

declare(strict_types=1);

namespace Tests\Feature\Cashier;

use App\Application\UseCases\Sales\CreateTransactionRequest;
use App\Application\UseCases\Sales\CreateTransactionUseCase;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionLinesUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_add_part_line_via_ui_post(): void
    {
        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_ui_parts@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        $p = Product::query()->create([
            'sku' => 'UI-PART-1',
            'name' => 'UI Part 1',
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

        /** @var CreateTransactionUseCase $create */
        $create = $this->app->make(CreateTransactionUseCase::class);
        $tx = $create->handle(new CreateTransactionRequest(actorUserId: (int) $cashier->id));

        $this->actingAs($cashier)
            ->post('/cashier/transactions/'.$tx->id.'/part-lines', [
                'product_id' => $p->id,
                'qty' => 2,
                'reason' => 'tambah item',
            ])
            ->assertRedirect('/cashier/transactions/'.$tx->id);

        $stock = DB::table('inventory_stocks')->where('product_id', $p->id)->first();
        $this->assertSame(2, (int) $stock->reserved_qty);

        $line = DB::table('transaction_part_lines')
            ->where('transaction_id', $tx->id)
            ->where('product_id', $p->id)
            ->first();
        $this->assertNotNull($line);
        $this->assertSame(2, (int) $line->qty);
    }

    public function test_cashier_can_add_service_line_via_ui_post(): void
    {
        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_ui_service@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        /** @var CreateTransactionUseCase $create */
        $create = $this->app->make(CreateTransactionUseCase::class);
        $tx = $create->handle(new CreateTransactionRequest(actorUserId: (int) $cashier->id));

        $this->actingAs($cashier)
            ->post('/cashier/transactions/'.$tx->id.'/service-lines', [
                'description' => 'Ganti oli',
                'price_manual' => 25000,
                'reason' => 'tambah jasa',
            ])
            ->assertRedirect('/cashier/transactions/'.$tx->id);

        $line = DB::table('transaction_service_lines')
            ->where('transaction_id', $tx->id)
            ->first();
        $this->assertNotNull($line);
        $this->assertSame('Ganti oli', (string) $line->description);
        $this->assertSame(25000, (int) $line->price_manual);
    }
}
