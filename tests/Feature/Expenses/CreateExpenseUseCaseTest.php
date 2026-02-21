<?php

use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Application\UseCases\Expenses\CreateExpenseRequest;
use App\Application\UseCases\Expenses\CreateExpenseUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('creates an expense row', function () {
    $uc = new CreateExpenseUseCase(
        app(TransactionManagerPort::class),
        app(ClockPort::class),
    );

    $uc->handle(new CreateExpenseRequest(
        actorUserId: 1,
        expenseDate: '2026-02-21',
        category: 'listrik',
        amount: 50000,
        note: 'token',
    ));

    $row = DB::table('expenses')->where('category', 'listrik')->first();
    expect($row)->not()->toBeNull();
    expect((int) $row->amount)->toBe(50000);
});
