<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('admin can view payroll period detail', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $employeeId = (int) DB::table('employees')->insertGetId([
        'name' => 'Emp A',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $periodId = (int) DB::table('payroll_periods')->insertGetId([
        'week_start' => '2026-02-23', // Monday
        'week_end' => '2026-02-28',   // Saturday
        'note' => 'period note',
        'loan_deductions_applied_at' => null,
        'created_by_user_id' => (int) $admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('payroll_lines')->insert([
        'payroll_period_id' => $periodId,
        'employee_id' => $employeeId,
        'gross_pay' => 100000,
        'loan_deduction' => 10000,
        'net_paid' => 90000,
        'note' => 'line note',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get('/admin/payroll/'.$periodId)
        ->assertOk()
        ->assertSee('Payroll Period', false)
        ->assertSee('2026-02-23')
        ->assertSee('2026-02-28')
        ->assertSee('Emp A');
});

it('admin can edit unlocked payroll period and update lines', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $employeeId = (int) DB::table('employees')->insertGetId([
        'name' => 'Emp B',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $periodId = (int) DB::table('payroll_periods')->insertGetId([
        'week_start' => '2026-02-23',
        'week_end' => '2026-02-28',
        'note' => 'before note',
        'loan_deductions_applied_at' => null,
        'created_by_user_id' => (int) $admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('payroll_lines')->insert([
        'payroll_period_id' => $periodId,
        'employee_id' => $employeeId,
        'gross_pay' => 100000,
        'loan_deduction' => 0,
        'net_paid' => 100000,
        'note' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get('/admin/payroll/'.$periodId.'/edit')
        ->assertOk()
        ->assertSee('Edit Payroll Period', false)
        ->assertSee('Emp B');

    $this->actingAs($admin)
        ->post('/admin/payroll/'.$periodId, [
            'week_start' => '2026-02-23',
            'week_end' => '2026-02-28',
            'note' => 'after note',
            'reason' => 'fix gross/deduction',
            'lines' => [
                [
                    'employee_id' => $employeeId,
                    'gross_pay' => 120000,
                    'loan_deduction' => 20000,
                    'note' => 'updated line',
                ],
            ],
        ])
        ->assertRedirect('/admin/payroll/'.$periodId);

    expect(DB::table('payroll_periods')->where('id', $periodId)->value('note'))->toBe('after note');

    $line = DB::table('payroll_lines')
        ->where('payroll_period_id', $periodId)
        ->where('employee_id', $employeeId)
        ->first();

    expect($line)->not->toBeNull();
    expect((int) $line->gross_pay)->toBe(120000);
    expect((int) $line->loan_deduction)->toBe(20000);
    expect((int) $line->net_paid)->toBe(100000);
    expect((string) $line->note)->toBe('updated line');
});

it('locked payroll period allows note-only update and does not change lines', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $employeeId = (int) DB::table('employees')->insertGetId([
        'name' => 'Emp C',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $periodId = (int) DB::table('payroll_periods')->insertGetId([
        'week_start' => '2026-02-23',
        'week_end' => '2026-02-28',
        'note' => 'locked note before',
        'loan_deductions_applied_at' => '2026-02-24 10:00:00',
        'created_by_user_id' => (int) $admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('payroll_lines')->insert([
        'payroll_period_id' => $periodId,
        'employee_id' => $employeeId,
        'gross_pay' => 100000,
        'loan_deduction' => 10000,
        'net_paid' => 90000,
        'note' => 'original',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post('/admin/payroll/'.$periodId, [
            'week_start' => '2026-02-23',
            'week_end' => '2026-02-28',
            'note' => 'locked note after',
            'reason' => 'note correction',
            'lines' => [
                [
                    'employee_id' => $employeeId,
                    'gross_pay' => 999999,
                    'loan_deduction' => 0,
                    'note' => 'hacked',
                ],
            ],
        ])
        ->assertRedirect('/admin/payroll/'.$periodId);

    expect(DB::table('payroll_periods')->where('id', $periodId)->value('note'))->toBe('locked note after');

    $line = DB::table('payroll_lines')
        ->where('payroll_period_id', $periodId)
        ->where('employee_id', $employeeId)
        ->first();

    expect($line)->not->toBeNull();
    expect((int) $line->gross_pay)->toBe(100000);
    expect((int) $line->loan_deduction)->toBe(10000);
    expect((int) $line->net_paid)->toBe(90000);
    expect((string) $line->note)->toBe('original');
});
