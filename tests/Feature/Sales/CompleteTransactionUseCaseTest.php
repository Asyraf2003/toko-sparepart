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
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CompleteTransactionUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_cash_applies_rounding_moves_stock_and_freezes_cogs(): void
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
            'email' => 'cashier_complete@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        $p = Product::query()->create([
            'sku' => 'TES-COMP',
            'name' => 'Test Complete',
            'sell_price_current' => 10500, // total 10,500 -> rounded 11,000
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 7000,
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
            qty: 1,
            actorUserId: (int) $cashier->id,
        ));

        // reserve should be 1
        $stock1 = DB::table('inventory_stocks')->where('product_id', $p->id)->first();
        $this->assertSame(1, (int) $stock1->reserved_qty);

        /** @var CompleteTransactionUseCase $complete */
        $complete = $this->app->make(CompleteTransactionUseCase::class);
        $complete->handle(new CompleteTransactionRequest(
            transactionId: $tx->id,
            paymentMethod: 'CASH',
            actorUserId: (int) $cashier->id,
        ));

        $tRow = DB::table('transactions')->where('id', $tx->id)->first();
        $this->assertSame('COMPLETED', (string) $tRow->status);
        $this->assertSame('PAID', (string) $tRow->payment_status);
        $this->assertSame('CASH', (string) $tRow->payment_method);
        $this->assertSame(500, (int) $tRow->rounding_amount); // 11,000 - 10,500

        $stock2 = DB::table('inventory_stocks')->where('product_id', $p->id)->first();
        $this->assertSame(9, (int) $stock2->on_hand_qty);
        $this->assertSame(0, (int) $stock2->reserved_qty);

        $saleOut = DB::table('stock_ledgers')
            ->where('product_id', $p->id)
            ->where('type', 'SALE_OUT')
            ->where('ref_id', $tx->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($saleOut);
        $this->assertSame(-1, (int) $saleOut->qty_delta);

        $release = DB::table('stock_ledgers')
            ->where('product_id', $p->id)
            ->where('type', 'RELEASE')
            ->where('ref_id', $tx->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($release);
        $this->assertSame(-1, (int) $release->qty_delta);

        $line = DB::table('transaction_part_lines')
            ->where('transaction_id', $tx->id)
            ->where('product_id', $p->id)
            ->first();
        $this->assertSame(7000, (int) $line->unit_cogs_frozen);
    }
}
