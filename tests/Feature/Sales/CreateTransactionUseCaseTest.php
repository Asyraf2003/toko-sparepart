<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Application\Ports\Services\ClockPort;
use App\Application\UseCases\Sales\CreateTransactionRequest;
use App\Application\UseCases\Sales\CreateTransactionUseCase;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateTransactionUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_transaction_creates_draft_with_incrementing_number(): void
    {
        // freeze clock supaya deterministic
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

        $u = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_tx@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        /** @var CreateTransactionUseCase $uc */
        $uc = $this->app->make(CreateTransactionUseCase::class);

        $t1 = $uc->handle(new CreateTransactionRequest(actorUserId: (int) $u->id));
        $t2 = $uc->handle(new CreateTransactionRequest(actorUserId: (int) $u->id));

        $this->assertSame('DRAFT', $t1->status);
        $this->assertSame('UNPAID', $t1->paymentStatus);
        $this->assertSame(null, $t1->paymentMethod);
        $this->assertSame('2026-02-20', $t1->businessDate);

        $this->assertMatchesRegularExpression('/^INV-20260220-\d{4}$/', $t1->transactionNumber);
        $this->assertMatchesRegularExpression('/^INV-20260220-\d{4}$/', $t2->transactionNumber);

        $this->assertSame('INV-20260220-0001', $t1->transactionNumber);
        $this->assertSame('INV-20260220-0002', $t2->transactionNumber);
    }
}
