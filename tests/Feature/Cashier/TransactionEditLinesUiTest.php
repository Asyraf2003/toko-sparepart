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

final class TransactionEditLinesUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_part_qty_increases_reserved_and_writes_ledger(): void
    {
        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_ui_update_part@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        $p = Product::query()->create([
            'sku' => 'UI-UPD-PART',
            'name' => 'UI Update Part',
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

        // seed line qty 2 + reserve 2 via existing add flow
        $this->actingAs($cashier)->post('/cashier/transactions/'.$tx->id.'/part-lines', [
            'product_id' => $p->id,
            'qty' => 2,
            'reason' => 'setup',
        ]);

        $line = DB::table('transaction_part_lines')->where('transaction_id', $tx->id)->first();
        $this->assertNotNull($line);

        $this->actingAs($cashier)->post('/cashier/transactions/'.$tx->id.'/part-lines/'.$line->id.'/qty', [
            'qty' => 5,
            'reason' => 'ubah qty',
        ])->assertRedirect('/cashier/transactions/'.$tx->id);

        $stock = DB::table('inventory_stocks')->where('product_id', $p->id)->first();
        $this->assertSame(5, (int) $stock->reserved_qty);

        $reserveLedger = DB::table('stock_ledgers')
            ->where('product_id', $p->id)
            ->where('type', 'RESERVE')
            ->where('ref_id', $tx->id)
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($reserveLedger);
        $this->assertSame(3, (int) $reserveLedger->qty_delta);
    }

    public function test_delete_part_line_releases_reserved(): void
    {
        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_ui_delete_part@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        $p = Product::query()->create([
            'sku' => 'UI-DEL-PART',
            'name' => 'UI Delete Part',
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

        $this->actingAs($cashier)->post('/cashier/transactions/'.$tx->id.'/part-lines', [
            'product_id' => $p->id,
            'qty' => 4,
            'reason' => 'setup',
        ]);

        $line = DB::table('transaction_part_lines')->where('transaction_id', $tx->id)->first();
        $this->assertNotNull($line);

        $this->actingAs($cashier)->post('/cashier/transactions/'.$tx->id.'/part-lines/'.$line->id.'/delete', [
            'reason' => 'hapus item',
        ])->assertRedirect('/cashier/transactions/'.$tx->id);

        $stock = DB::table('inventory_stocks')->where('product_id', $p->id)->first();
        $this->assertSame(0, (int) $stock->reserved_qty);

        $exists = DB::table('transaction_part_lines')->where('id', $line->id)->exists();
        $this->assertFalse($exists);
    }

    public function test_update_and_delete_service_line(): void
    {
        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_ui_service_edit@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        /** @var CreateTransactionUseCase $create */
        $create = $this->app->make(CreateTransactionUseCase::class);
        $tx = $create->handle(new CreateTransactionRequest(actorUserId: (int) $cashier->id));

        $this->actingAs($cashier)->post('/cashier/transactions/'.$tx->id.'/service-lines', [
            'description' => 'Service A',
            'price_manual' => 10000,
            'reason' => 'setup',
        ]);

        $line = DB::table('transaction_service_lines')->where('transaction_id', $tx->id)->first();
        $this->assertNotNull($line);

        $this->actingAs($cashier)->post('/cashier/transactions/'.$tx->id.'/service-lines/'.$line->id.'/update', [
            'description' => 'Service B',
            'price_manual' => 25000,
            'reason' => 'koreksi',
        ])->assertRedirect('/cashier/transactions/'.$tx->id);

        $updated = DB::table('transaction_service_lines')->where('id', $line->id)->first();
        $this->assertSame('Service B', (string) $updated->description);
        $this->assertSame(25000, (int) $updated->price_manual);

        $this->actingAs($cashier)->post('/cashier/transactions/'.$tx->id.'/service-lines/'.$line->id.'/delete', [
            'reason' => 'hapus',
        ])->assertRedirect('/cashier/transactions/'.$tx->id);

        $exists = DB::table('transaction_service_lines')->where('id', $line->id)->exists();
        $this->assertFalse($exists);
    }
}
