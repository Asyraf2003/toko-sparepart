<?php

use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Application\UseCases\Purchasing\CreatePurchaseInvoiceLine;
use App\Application\UseCases\Purchasing\CreatePurchaseInvoiceRequest;
use App\Application\UseCases\Purchasing\CreatePurchaseInvoiceUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('creates purchase invoice, increases on_hand, writes PURCHASE_IN ledger, and updates moving average avg_cost with header tax allocation', function () {
    // Arrange: product with existing stock & avg_cost
    $productId = (int) DB::table('products')->insertGetId([
        'sku' => 'SKU-001',
        'name' => 'Oil Filter',
        'sell_price_current' => 25000,
        'min_stock_threshold' => 3,
        'is_active' => true,
        'avg_cost' => 1000, // old avg cost
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('inventory_stocks')->insert([
        'product_id' => $productId,
        'on_hand_qty' => 10,
        'reserved_qty' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $uc = new CreatePurchaseInvoiceUseCase(
        app(TransactionManagerPort::class),
        app(ClockPort::class),
    );

    $req = new CreatePurchaseInvoiceRequest(
        actorUserId: 1,
        supplierName: 'SUPPLIER A',
        noFaktur: 'FAK-0001',
        tglKirim: '2026-02-21',
        kepada: 'ADMIN',
        noPesanan: null,
        namaSales: 'Budi',
        totalPajak: 1000, // header tax included in cost basis
        note: null,
        lines: [
            new CreatePurchaseInvoiceLine(
                productId: $productId,
                qty: 5,
                unitCost: 2000,
                discBps: 0
            ),
        ],
    );

    // Act
    $uc->handle($req);

    // Assert invoice + line exists
    $invoice = DB::table('purchase_invoices')->where('no_faktur', 'FAK-0001')->first();
    expect($invoice)->not()->toBeNull();

    $lines = DB::table('purchase_invoice_lines')->where('purchase_invoice_id', $invoice->id)->get();
    expect($lines)->toHaveCount(1);

    // Stock updated
    $stock = DB::table('inventory_stocks')->where('product_id', $productId)->first();
    expect((int) $stock->on_hand_qty)->toBe(15);

    // Ledger exists (ref to purchase_invoice_line)
    $lineId = (int) $lines[0]->id;
    $ledger = DB::table('stock_ledgers')
        ->where('type', 'PURCHASE_IN')
        ->where('product_id', $productId)
        ->where('ref_type', 'purchase_invoice_line')
        ->where('ref_id', $lineId)
        ->first();

    expect($ledger)->not()->toBeNull();
    expect((int) $ledger->qty_delta)->toBe(5);

    // Avg cost updated:
    // old: 10 * 1000 = 10000
    // purchase: (5 * 2000) = 10000, tax 1000 -> costIn=11000
    // total = 21000 / 15 = 1400
    $product = DB::table('products')->where('id', $productId)->first();
    expect((int) $product->avg_cost)->toBe(1400);
});

it('allocates header tax with largest remainder and supports disc_bps', function () {
    $p1 = (int) DB::table('products')->insertGetId([
        'sku' => 'SKU-101',
        'name' => 'Part A',
        'sell_price_current' => 10000,
        'min_stock_threshold' => 3,
        'is_active' => true,
        'avg_cost' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $p2 = (int) DB::table('products')->insertGetId([
        'sku' => 'SKU-102',
        'name' => 'Part B',
        'sell_price_current' => 10000,
        'min_stock_threshold' => 3,
        'is_active' => true,
        'avg_cost' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('inventory_stocks')->insert([
        ['product_id' => $p1, 'on_hand_qty' => 0, 'reserved_qty' => 0, 'created_at' => now(), 'updated_at' => now()],
        ['product_id' => $p2, 'on_hand_qty' => 0, 'reserved_qty' => 0, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $uc = new CreatePurchaseInvoiceUseCase(
        app(TransactionManagerPort::class),
        app(ClockPort::class),
    );

    // Line nets:
    // p1 bruto 100, disc 0 => net 100
    // p2 bruto 200, disc 2.50% => disc 5 => net 195
    // Sum net = 295, total_pajak = 100
    //
    // Largest remainder allocation:
    // p1: floor(100*100/295)=33, remainder 265  -> gets +1 => 34
    // p2: floor(100*195/295)=66, remainder 30   -> stays 66
    //
    // So allocated tax: p1=34, p2=66
    $req = new CreatePurchaseInvoiceRequest(
        actorUserId: 1,
        supplierName: 'SUPPLIER B',
        noFaktur: 'FAK-0002',
        tglKirim: '2026-02-21',
        kepada: null,
        noPesanan: null,
        namaSales: null,
        totalPajak: 100,
        note: null,
        lines: [
            new CreatePurchaseInvoiceLine(productId: $p1, qty: 1, unitCost: 100, discBps: 0),
            new CreatePurchaseInvoiceLine(productId: $p2, qty: 1, unitCost: 200, discBps: 250), // 2.50% => 5
        ],
    );

    $uc->handle($req);

    $invoice = DB::table('purchase_invoices')->where('no_faktur', 'FAK-0002')->first();
    $lines = DB::table('purchase_invoice_lines')->where('purchase_invoice_id', $invoice->id)->orderBy('id')->get();

    // sanity: stored line_total
    expect((int) $lines[0]->line_total)->toBe(100);
    expect((int) $lines[1]->line_total)->toBe(195);

    // avg_cost results should reflect allocated header tax:
    // p1 costIn = 100 + 34 = 134, qty=1 -> avg_cost = 134
    // p2 costIn = 195 + 66 = 261, qty=1 -> avg_cost = 261
    $prod1 = DB::table('products')->where('id', $p1)->first();
    $prod2 = DB::table('products')->where('id', $p2)->first();

    expect((int) $prod1->avg_cost)->toBe(134);
    expect((int) $prod2->avg_cost)->toBe(261);
});
