<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('can generate stock report pdf (smoke)', function () {
    if (! app()->bound('dompdf.wrapper')) {
        $this->markTestSkipped('dompdf is not installed/bound (barryvdh/laravel-dompdf).');
    }

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

    DB::table('inventory_stocks')->insert([
        'product_id' => $productId,
        'on_hand_qty' => 10,
        'reserved_qty' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withoutMiddleware();

    $resp = $this->get('/admin/reports/stock/pdf');
    $resp->assertOk();
    $resp->assertHeader('Content-Type', 'application/pdf');
});
