<?php

declare(strict_types=1);

namespace Tests\Feature\Cashier;

use App\Application\UseCases\Sales\CreateTransactionRequest;
use App\Application\UseCases\Sales\CreateTransactionUseCase;
use App\Application\UseCases\Sales\OpenTransactionRequest;
use App\Application\UseCases\Sales\OpenTransactionUseCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionWorkOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_order_only_for_open(): void
    {
        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_work_order@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        /** @var CreateTransactionUseCase $create */
        $create = $this->app->make(CreateTransactionUseCase::class);
        $tx = $create->handle(new CreateTransactionRequest(actorUserId: (int) $cashier->id));

        $txNumber = (string) DB::table('transactions')->where('id', (int) $tx->id)->value('transaction_number');

        $this->actingAs($cashier)
            ->get('/cashier/transactions/'.(int) $tx->id.'/work-order')
            ->assertStatus(400);

        /** @var OpenTransactionUseCase $open */
        $open = $this->app->make(OpenTransactionUseCase::class);
        $open->handle(new OpenTransactionRequest(transactionId: (int) $tx->id, actorUserId: (int) $cashier->id));

        $this->actingAs($cashier)
            ->get('/cashier/transactions/'.(int) $tx->id.'/work-order')
            ->assertOk()
            ->assertSee('WORK ORDER')
            ->assertSee($txNumber);
    }
}
