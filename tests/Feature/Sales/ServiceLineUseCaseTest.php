<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Application\Ports\Services\ClockPort;
use App\Application\UseCases\Sales\AddServiceLineRequest;
use App\Application\UseCases\Sales\AddServiceLineUseCase;
use App\Application\UseCases\Sales\CreateTransactionRequest;
use App\Application\UseCases\Sales\CreateTransactionUseCase;
use App\Application\UseCases\Sales\UpdateServiceLineRequest;
use App\Application\UseCases\Sales\UpdateServiceLineUseCase;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ServiceLineUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_and_update_service_line_in_draft(): void
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
            'email' => 'cashier_service@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        /** @var CreateTransactionUseCase $createTx */
        $createTx = $this->app->make(CreateTransactionUseCase::class);
        $tx = $createTx->handle(new CreateTransactionRequest(actorUserId: (int) $cashier->id));

        /** @var AddServiceLineUseCase $add */
        $add = $this->app->make(AddServiceLineUseCase::class);
        $lineId = $add->handle(new AddServiceLineRequest(
            transactionId: $tx->id,
            description: 'Ganti oli',
            priceManual: 25000,
            actorUserId: (int) $cashier->id,
            reason: 'test',
        ));

        $row = DB::table('transaction_service_lines')->where('id', $lineId)->first();
        $this->assertNotNull($row);
        $this->assertSame($tx->id, (int) $row->transaction_id);
        $this->assertSame('Ganti oli', (string) $row->description);
        $this->assertSame(25000, (int) $row->price_manual);

        /** @var UpdateServiceLineUseCase $upd */
        $upd = $this->app->make(UpdateServiceLineUseCase::class);
        $upd->handle(new UpdateServiceLineRequest(
            transactionId: $tx->id,
            serviceLineId: (int) $lineId,
            description: 'Ganti oli + cek rantai',
            priceManual: 30000,
            actorUserId: (int) $cashier->id,
            reason: 'test edit service',
        ));

        $row2 = DB::table('transaction_service_lines')->where('id', $lineId)->first();
        $this->assertSame('Ganti oli + cek rantai', (string) $row2->description);
        $this->assertSame(30000, (int) $row2->price_manual);
    }

    public function test_update_service_line_after_completed_same_day_requires_reason(): void
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
            'email' => 'cashier_service2@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        /** @var CreateTransactionUseCase $createTx */
        $createTx = $this->app->make(CreateTransactionUseCase::class);
        $tx = $createTx->handle(new CreateTransactionRequest(actorUserId: (int) $cashier->id));

        /** @var AddServiceLineUseCase $add */
        $add = $this->app->make(AddServiceLineUseCase::class);
        $lineId = $add->handle(new AddServiceLineRequest(
            transactionId: $tx->id,
            description: 'Service ringan',
            priceManual: 20000,
            actorUserId: (int) $cashier->id,
            reason: 'test',
        ));

        // mark transaction as completed same-day
        DB::table('transactions')->where('id', $tx->id)->update([
            'status' => 'COMPLETED',
            'payment_status' => 'PAID',
        ]);

        /** @var UpdateServiceLineUseCase $upd */
        $upd = $this->app->make(UpdateServiceLineUseCase::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reason is required');

        $upd->handle(new UpdateServiceLineRequest(
            transactionId: $tx->id,
            serviceLineId: (int) $lineId,
            description: 'Service ringan (revisi)',
            priceManual: 22000,
            actorUserId: (int) $cashier->id,
            reason: null,
        ));
    }
}
