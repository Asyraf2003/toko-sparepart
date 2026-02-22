<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Application\Ports\Services\ClockPort;
use App\Application\UseCases\Sales\CreateTransactionRequest;
use App\Application\UseCases\Sales\CreateTransactionUseCase;
use App\Application\UseCases\Sales\OpenTransactionRequest;
use App\Application\UseCases\Sales\OpenTransactionUseCase;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class OpenTransactionUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_transaction_sets_open_and_unpaid_and_opened_at(): void
    {
        $this->app->instance(ClockPort::class, new class implements ClockPort
        {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-02-20 09:00:00', new DateTimeZone('Asia/Makassar'));
            }

            public function todayBusinessDate(): string
            {
                return '2026-02-20';
            }
        });

        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_open@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        /** @var CreateTransactionUseCase $create */
        $create = $this->app->make(CreateTransactionUseCase::class);
        $tx = $create->handle(new CreateTransactionRequest(actorUserId: (int) $cashier->id));

        /** @var OpenTransactionUseCase $open */
        $open = $this->app->make(OpenTransactionUseCase::class);
        $open->handle(new OpenTransactionRequest(
            transactionId: $tx->id,
            actorUserId: (int) $cashier->id,
        ));

        $row = DB::table('transactions')->where('id', $tx->id)->first();
        $this->assertNotNull($row);

        $this->assertSame('OPEN', (string) $row->status);
        $this->assertSame('UNPAID', (string) $row->payment_status);
        $this->assertNull($row->payment_method);
        $this->assertSame('2026-02-20 09:00:00', (string) $row->opened_at);
    }

    public function test_open_allows_patching_fields_if_already_open(): void
    {
        $this->app->instance(ClockPort::class, new class implements ClockPort
        {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-02-20 09:00:00', new DateTimeZone('Asia/Makassar'));
            }

            public function todayBusinessDate(): string
            {
                return '2026-02-20';
            }
        });

        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_open2@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        /** @var CreateTransactionUseCase $create */
        $create = $this->app->make(CreateTransactionUseCase::class);
        $tx = $create->handle(new CreateTransactionRequest(actorUserId: (int) $cashier->id));

        // Simulasikan transaksi sudah OPEN (misal dari request sebelumnya)
        DB::table('transactions')->where('id', $tx->id)->update([
            'status' => 'OPEN',
            'customer_name' => 'Lama',
            'customer_phone' => '0000',
        ]);

        /** @var OpenTransactionUseCase $open */
        $open = $this->app->make(OpenTransactionUseCase::class);

        // Eksekusi handle dengan data baru (Patch)
        $open->handle(new OpenTransactionRequest(
            transactionId: $tx->id,
            actorUserId: (int) $cashier->id,
            fields: [
                'customer_name' => 'Budi',
                'customer_phone' => '081234',
            ]
        ));

        // Audit Data dari Database
        $row = DB::table('transactions')->where('id', $tx->id)->first();

        // Assertions
        $this->assertNotNull($row);
        $this->assertSame('OPEN', (string) $row->status, 'Status harus tetap OPEN');
        $this->assertSame('Budi', (string) ($row->customer_name ?? ''), 'Field customer_name harus terupdate');
        $this->assertSame('081234', (string) ($row->customer_phone ?? ''), 'Field customer_phone harus terupdate');
    }
}
