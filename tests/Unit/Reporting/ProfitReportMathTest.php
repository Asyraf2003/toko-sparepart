<?php

declare(strict_types=1);

use App\Application\Ports\Repositories\ProfitReportQueryPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(\Tests\TestCase::class, RefreshDatabase::class);

it('profit report math is deterministic for a period (no payroll rows)', function () {
    $userId = DB::table('users')->insertGetId([
        'name' => 'Admin',
        'email' => 'admin@example.test',
        // tidak perlu bcrypt untuk test ini
        'password' => 'x',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $productId = DB::table('products')->insertGetId([
        'sku' => 'P-001',
        'name' => 'Part A',
        'sell_price_current' => 10000,
        'min_stock_threshold' => 3,
        'is_active' => true,
        'avg_cost' => 7000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // COMPLETED transaction (counted)
    $txCompleted = DB::table('transactions')->insertGetId([
        'transaction_number' => 'TRX-0001',
        'business_date' => '2026-02-21',
        'status' => 'COMPLETED',
        'payment_status' => 'PAID',
        'payment_method' => 'CASH',
        'rounding_mode' => 'NEAREST_1000',
        'rounding_amount' => 0,
        'customer_name' => null,
        'customer_phone' => null,
        'vehicle_plate' => null,
        'service_employee_id' => null,
        'note' => null,
        'opened_at' => now(),
        'completed_at' => now(),
        'voided_at' => null,
        'created_by_user_id' => $userId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('transaction_part_lines')->insert([
        'transaction_id' => $txCompleted,
        'product_id' => $productId,
        'qty' => 2,
        'unit_sell_price_frozen' => 10000,
        'line_subtotal' => 20000,
        'unit_cogs_frozen' => 7000, // cogs = 14000
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('transaction_service_lines')->insert([
        'transaction_id' => $txCompleted,
        'description' => 'Service A',
        'price_manual' => 15000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // OPEN transaction (NOT counted)
    DB::table('transactions')->insert([
        'transaction_number' => 'TRX-0002',
        'business_date' => '2026-02-21',
        'status' => 'OPEN',
        'payment_status' => 'UNPAID',
        'payment_method' => null,
        'rounding_mode' => null,
        'rounding_amount' => 0,
        'customer_name' => null,
        'customer_phone' => null,
        'vehicle_plate' => null,
        'service_employee_id' => null,
        'note' => null,
        'opened_at' => now(),
        'completed_at' => null,
        'voided_at' => null,
        'created_by_user_id' => $userId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Expenses inside range
    DB::table('expenses')->insert([
        'expense_date' => '2026-02-21',
        'category' => 'OPERASIONAL',
        'amount' => 3000,
        'note' => null,
        'created_by_user_id' => $userId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    /** @var ProfitReportQueryPort $q */
    $q = app(ProfitReportQueryPort::class);

    $r1 = $q->aggregate('2026-02-21', '2026-02-21', 'weekly');
    $r2 = $q->aggregate('2026-02-21', '2026-02-21', 'weekly');

    // revenue = 20000 + 15000 + 0 = 35000
    // cogs = 7000*2 = 14000
    // expenses = 3000
    // payroll = 0
    // net = 35000 - 14000 - 3000 - 0 = 18000
    expect($r1->summary->revenueTotal)->toBe(35000);
    expect($r1->summary->cogsTotal)->toBe(14000);
    expect($r1->summary->expensesTotal)->toBe(3000);
    expect($r1->summary->payrollGross)->toBe(0);
    expect($r1->summary->netProfit)->toBe(18000);

    // deterministic
    expect($r2->summary->netProfit)->toBe($r1->summary->netProfit);
});
