<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('can generate purchasing report pdf (smoke)', function () {
    if (! app()->bound('dompdf.wrapper')) {
        $this->markTestSkipped('dompdf is not installed/bound (barryvdh/laravel-dompdf).');
    }

    DB::table('purchase_invoices')->insert([
        'supplier_name' => 'Supplier A',
        'no_faktur' => 'FKT-001',
        'tgl_kirim' => '2026-02-21',
        'kepada' => null,
        'no_pesanan' => null,
        'nama_sales' => null,
        'total_bruto' => 100000,
        'total_diskon' => 5000,
        'total_pajak' => 1000,
        'grand_total' => 96000,
        'created_by_user_id' => null,
        'note' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withoutMiddleware();

    $resp = $this->get('/admin/reports/purchasing/pdf?from=2026-02-21&to=2026-02-21');
    $resp->assertOk();
    $resp->assertHeader('Content-Type', 'application/pdf');
});
