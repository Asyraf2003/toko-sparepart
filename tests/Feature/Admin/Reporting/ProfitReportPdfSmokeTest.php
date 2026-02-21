<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('can generate profit report pdf (smoke)', function () {
    if (! app()->bound('dompdf.wrapper')) {
        $this->markTestSkipped('dompdf is not installed/bound (barryvdh/laravel-dompdf).');
    }

    $userId = DB::table('users')->insertGetId([
        'name' => 'Admin',
        'email' => 'admin@example.test',
        'password' => bcrypt('password'),
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

    $txId = DB::table('transactions')->insertGetId([
        'transaction_number' => 'TRX-0001',
        'business_date' => '2026-02-21',
        'status' => 'COMPLETED',
        'payment_status' => 'PAID',
        'payment_method' => 'CASH',
        'rounding_mode' => null,
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
        'transaction_id' => $txId,
        'product_id' => $productId,
        'qty' => 1,
        'unit_sell_price_frozen' => 10000,
        'line_subtotal' => 10000,
        'unit_cogs_frozen' => 7000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withoutMiddleware();

    $resp = $this->get('/admin/reports/profit/pdf?from=2026-02-21&to=2026-02-21&granularity=weekly');
    $resp->assertOk();
    $resp->assertHeader('Content-Type', 'application/pdf');
});
