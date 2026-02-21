<?php

use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Application\UseCases\Payroll\CreatePayrollPeriodLine;
use App\Application\UseCases\Payroll\CreatePayrollPeriodRequest;
use App\Application\UseCases\Payroll\CreatePayrollPeriodUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('applies loan deduction FIFO and reduces outstanding', function () {
    $employeeId = (int) DB::table('employees')->insertGetId([
        'name' => 'Andi',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // two loans: FIFO oldest-first
    DB::table('employee_loans')->insert([
        [
            'employee_id' => $employeeId,
            'loan_date' => '2026-02-01',
            'amount' => 100,
            'outstanding_amount' => 100,
            'note' => null,
            'created_by_user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'employee_id' => $employeeId,
            'loan_date' => '2026-02-10',
            'amount' => 200,
            'outstanding_amount' => 200,
            'note' => null,
            'created_by_user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $uc = new CreatePayrollPeriodUseCase(
        app(TransactionManagerPort::class),
        app(ClockPort::class),
    );

    $uc->handle(new CreatePayrollPeriodRequest(
        actorUserId: 1,
        weekStart: '2026-02-16', // Monday
        weekEnd: '2026-02-21',   // Saturday
        note: null,
        lines: [
            new CreatePayrollPeriodLine(
                employeeId: $employeeId,
                grossPay: 1000,
                loanDeduction: 250,
                note: null,
            ),
        ],
    ));

    $loans = DB::table('employee_loans')
        ->where('employee_id', $employeeId)
        ->orderBy('loan_date')
        ->get(['outstanding_amount']);

    // first loan 100 -> 0, second loan 200 -> 50
    expect((int) $loans[0]->outstanding_amount)->toBe(0);
    expect((int) $loans[1]->outstanding_amount)->toBe(50);
});

it('rejects when loan deduction exceeds total outstanding', function () {
    $employeeId = (int) DB::table('employees')->insertGetId([
        'name' => 'Budi',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('employee_loans')->insert([
        'employee_id' => $employeeId,
        'loan_date' => '2026-02-01',
        'amount' => 100,
        'outstanding_amount' => 100,
        'note' => null,
        'created_by_user_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $uc = new CreatePayrollPeriodUseCase(
        app(TransactionManagerPort::class),
        app(ClockPort::class),
    );

    $fn = function () use ($uc, $employeeId) {
        $uc->handle(new CreatePayrollPeriodRequest(
            actorUserId: 1,
            weekStart: '2026-02-16',
            weekEnd: '2026-02-21',
            note: null,
            lines: [
                new CreatePayrollPeriodLine(
                    employeeId: $employeeId,
                    grossPay: 1000,
                    loanDeduction: 200, // > outstanding 100
                    note: null,
                ),
            ],
        ));
    };

    expect($fn)->toThrow(\InvalidArgumentException::class);
});
