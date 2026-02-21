<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Application\Ports\Services\ClockPort;
use App\Application\UseCases\Sales\AddPartLineRequest;
use App\Application\UseCases\Sales\AddPartLineUseCase;
use App\Application\UseCases\Sales\CompleteTransactionRequest;
use App\Application\UseCases\Sales\CompleteTransactionUseCase;
use App\Application\UseCases\Sales\CreateTransactionRequest;
use App\Application\UseCases\Sales\CreateTransactionUseCase;
use App\Application\UseCases\Sales\VoidTransactionRequest;
use App\Application\UseCases\Sales\VoidTransactionUseCase;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VoidTransactionUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_void_draft_releases_reserved_and_sets_void(): void
    {
        $this->app->instance(ClockPort::class, new class implements ClockPort
        {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-02-20 11:00:00', new DateTimeZone('Asia/Makassar'));
            }

            public function todayBusinessDate(): string
            {
                return '2026-02-20';
            }
        });

        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_void_draft@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        $p = Product::query()->create([
            'sku' => 'TES-VOID-D',
            'name' => 'Test Void Draft',
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

        /** @var CreateTransactionUseCase $create */
        $create = $this->app->make(CreateTransactionUseCase::class);
        $tx = $create->handle(new CreateTransactionRequest(actorUserId: (int) $cashier->id));

        /** @var AddPartLineUseCase $add */
        $add = $this->app->make(AddPartLineUseCase::class);
        $add->handle(new AddPartLineRequest(
            transactionId: $tx->id,
            productId: (int) $p->id,
            qty: 3,
            actorUserId: (int) $cashier->id,
            reason: 'test',
        ));

        $stock1 = DB::table('inventory_stocks')->where('product_id', $p->id)->first();
        $this->assertSame(3, (int) $stock1->reserved_qty);

        /** @var VoidTransactionUseCase $void */
        $void = $this->app->make(VoidTransactionUseCase::class);
        $void->handle(new VoidTransactionRequest(
            transactionId: $tx->id,
            actorUserId: (int) $cashier->id,
            reason: 'Salah input',
        ));

        $stock2 = DB::table('inventory_stocks')->where('product_id', $p->id)->first();
        $this->assertSame(10, (int) $stock2->on_hand_qty);
        $this->assertSame(0, (int) $stock2->reserved_qty);

        $ledger = DB::table('stock_ledgers')
            ->where('product_id', $p->id)
            ->where('type', 'RELEASE')
            ->where('ref_id', $tx->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($ledger);
        $this->assertSame(-3, (int) $ledger->qty_delta);

        $tRow = DB::table('transactions')->where('id', $tx->id)->first();
        $this->assertSame('VOID', (string) $tRow->status);
        $this->assertSame('2026-02-20 11:00:00', (string) $tRow->voided_at);
    }

    public function test_void_completed_returns_on_hand_with_void_in_ledger(): void
    {
        $this->app->instance(ClockPort::class, new class implements ClockPort
        {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-02-20 12:00:00', new DateTimeZone('Asia/Makassar'));
            }

            public function todayBusinessDate(): string
            {
                return '2026-02-20';
            }
        });

        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_void_completed@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        $p = Product::query()->create([
            'sku' => 'TES-VOID-C',
            'name' => 'Test Void Completed',
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

        /** @var AddPartLineUseCase $add */
        $add = $this->app->make(AddPartLineUseCase::class);
        $add->handle(new AddPartLineRequest(
            transactionId: $tx->id,
            productId: (int) $p->id,
            qty: 2,
            actorUserId: (int) $cashier->id,
            reason: 'test',
        ));

        /** @var CompleteTransactionUseCase $complete */
        $complete = $this->app->make(CompleteTransactionUseCase::class);
        $complete->handle(new CompleteTransactionRequest(
            transactionId: $tx->id,
            paymentMethod: 'TRANSFER',
            actorUserId: (int) $cashier->id,
        ));

        $stockAfterComplete = DB::table('inventory_stocks')->where('product_id', $p->id)->first();
        $this->assertSame(8, (int) $stockAfterComplete->on_hand_qty);
        $this->assertSame(0, (int) $stockAfterComplete->reserved_qty);

        /** @var VoidTransactionUseCase $void */
        $void = $this->app->make(VoidTransactionUseCase::class);
        $void->handle(new VoidTransactionRequest(
            transactionId: $tx->id,
            actorUserId: (int) $cashier->id,
            reason: 'Customer batal',
        ));

        $stockAfterVoid = DB::table('inventory_stocks')->where('product_id', $p->id)->first();
        $this->assertSame(10, (int) $stockAfterVoid->on_hand_qty);

        $ledger = DB::table('stock_ledgers')
            ->where('product_id', $p->id)
            ->where('type', 'VOID_IN')
            ->where('ref_id', $tx->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($ledger);
        $this->assertSame(2, (int) $ledger->qty_delta);

        $tRow = DB::table('transactions')->where('id', $tx->id)->first();
        $this->assertSame('VOID', (string) $tRow->status);
        $this->assertSame('2026-02-20 12:00:00', (string) $tRow->voided_at);
    }
}
