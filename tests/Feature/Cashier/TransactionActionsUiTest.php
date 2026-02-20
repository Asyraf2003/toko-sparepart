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

final class TransactionActionsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_open_transaction_via_ui(): void
    {
        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_ui_open@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        /** @var CreateTransactionUseCase $create */
        $create = $this->app->make(CreateTransactionUseCase::class);
        $tx = $create->handle(new CreateTransactionRequest(actorUserId: (int) $cashier->id));

        $this->actingAs($cashier)
            ->post('/cashier/transactions/'.$tx->id.'/open')
            ->assertRedirect('/cashier/transactions/'.$tx->id);

        $row = DB::table('transactions')->where('id', $tx->id)->first();
        $this->assertSame('OPEN', (string) $row->status);
        $this->assertSame('UNPAID', (string) $row->payment_status);
    }

    public function test_cashier_can_complete_cash_via_ui(): void
    {
        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_ui_complete_cash@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        $p = Product::query()->create([
            'sku' => 'UI-COMP-CASH',
            'name' => 'UI Complete Cash',
            'sell_price_current' => 10500,
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

        $this->actingAs($cashier)->post('/cashier/transactions/'.$tx->id.'/part-lines', [
            'product_id' => $p->id,
            'qty' => 1,
        ]);

        $this->actingAs($cashier)
            ->post('/cashier/transactions/'.$tx->id.'/complete-cash')
            ->assertRedirect('/cashier/transactions/'.$tx->id);

        $row = DB::table('transactions')->where('id', $tx->id)->first();
        $this->assertSame('COMPLETED', (string) $row->status);
        $this->assertSame('PAID', (string) $row->payment_status);
        $this->assertSame('CASH', (string) $row->payment_method);
    }

    public function test_cashier_can_void_via_ui_requires_reason(): void
    {
        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_ui_void@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        /** @var CreateTransactionUseCase $create */
        $create = $this->app->make(CreateTransactionUseCase::class);
        $tx = $create->handle(new CreateTransactionRequest(actorUserId: (int) $cashier->id));

        $this->actingAs($cashier)
            ->post('/cashier/transactions/'.$tx->id.'/void', ['reason' => 'salah input'])
            ->assertRedirect('/cashier/transactions/'.$tx->id);

        $row = DB::table('transactions')->where('id', $tx->id)->first();
        $this->assertSame('VOID', (string) $row->status);
    }
}
