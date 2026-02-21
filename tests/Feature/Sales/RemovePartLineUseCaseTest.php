<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Application\Ports\Services\ClockPort;
use App\Application\UseCases\Sales\AddPartLineRequest;
use App\Application\UseCases\Sales\AddPartLineUseCase;
use App\Application\UseCases\Sales\CreateTransactionRequest;
use App\Application\UseCases\Sales\CreateTransactionUseCase;
use App\Application\UseCases\Sales\RemovePartLineRequest;
use App\Application\UseCases\Sales\RemovePartLineUseCase;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RemovePartLineUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_remove_part_line_releases_stock_and_deletes_line(): void
    {
        $this->app->instance(ClockPort::class, new class implements ClockPort
        {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-02-20 10:00:00', new DateTimeZone('Asia/Makassar'));
            }

            public function todayBusinessDate(): string
            {
                return '2026-02-20';
            }
        });

        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_removeline@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        $p = Product::query()->create([
            'sku' => 'TES-DEL',
            'name' => 'Test Delete Line',
            'sell_price_current' => 10000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        DB::table('inventory_stocks')->insert([
            'product_id' => $p->id,
            'on_hand_qty' => 10,
            'reserved_qty' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /** @var CreateTransactionUseCase $createTx */
        $createTx = $this->app->make(CreateTransactionUseCase::class);
        $tx = $createTx->handle(new CreateTransactionRequest(actorUserId: (int) $cashier->id));

        /** @var AddPartLineUseCase $add */
        $add = $this->app->make(AddPartLineUseCase::class);
        $add->handle(new AddPartLineRequest(
            transactionId: $tx->id,
            productId: (int) $p->id,
            qty: 4,
            actorUserId: (int) $cashier->id,
            reason: 'test',
        ));

        $stockAfterAdd = DB::table('inventory_stocks')->where('product_id', $p->id)->first();
        $this->assertSame(4, (int) $stockAfterAdd->reserved_qty);

        /** @var RemovePartLineUseCase $rm */
        $rm = $this->app->make(RemovePartLineUseCase::class);
        $rm->handle(new RemovePartLineRequest(
            transactionId: $tx->id,
            productId: (int) $p->id,
            actorUserId: (int) $cashier->id,
            reason: 'test',
        ));

        $stock = DB::table('inventory_stocks')->where('product_id', $p->id)->first();
        $this->assertSame(10, (int) $stock->on_hand_qty);
        $this->assertSame(0, (int) $stock->reserved_qty);

        $line = DB::table('transaction_part_lines')
            ->where('transaction_id', $tx->id)
            ->where('product_id', $p->id)
            ->first();

        $this->assertNull($line);

        $ledger = DB::table('stock_ledgers')
            ->where('product_id', $p->id)
            ->where('type', 'RELEASE')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertNotNull($ledger);
        $this->assertSame(-4, (int) $ledger->qty_delta);
        $this->assertSame('transaction', (string) $ledger->ref_type);
        $this->assertSame($tx->id, (int) $ledger->ref_id);
    }
}
