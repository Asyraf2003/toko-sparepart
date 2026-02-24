<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function _mkAdminUser(): User
{
    /** @var User $u */
    $u = User::query()->create([
        'name' => 'Admin',
        'email' => 'admin-ap@local.test',
        'role' => User::ROLE_ADMIN,
        'password' => Hash::make('12345678'),
    ]);

    return $u;
}

function _insertPurchaseInvoice(array $override = []): int
{
    $now = now()->format('Y-m-d H:i:s');

    $base = [
        'supplier_name' => 'Supplier X',
        'no_faktur' => 'FAK-TEST-'.bin2hex(random_bytes(4)),
        'tgl_kirim' => '2026-02-24',
        'due_date' => '2026-03-24',
        'payment_status' => 'UNPAID',
        'paid_at' => null,
        'paid_by_user_id' => null,
        'paid_note' => null,
        'kepada' => null,
        'no_pesanan' => null,
        'nama_sales' => null,
        'total_bruto' => 0,
        'total_diskon' => 0,
        'total_pajak' => 0,
        'grand_total' => 100_000,
        'created_by_user_id' => null,
        'note' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ];

    $data = array_merge($base, $override);

    return (int) DB::table('purchase_invoices')->insertGetId($data);
}

test('admin can mark purchase invoice as PAID then UNPAID', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-24 10:00:00', 'Asia/Makassar'));

    $admin = _mkAdminUser();

    $invoiceId = _insertPurchaseInvoice([
        'no_faktur' => 'FAK-PAID-001',
        'supplier_name' => 'Supplier Paid',
        'tgl_kirim' => '2026-02-20',
        'due_date' => '2026-03-20',
        'payment_status' => 'UNPAID',
        'grand_total' => 250_000,
    ]);

    // Mark PAID
    $resp = $this->actingAs($admin)
        ->post("/admin/purchases/{$invoiceId}/mark-paid", [
            'paid_note' => 'dibayar tunai',
            'reason' => 'pelunasan supplier',
        ]);

    $resp->assertRedirect("/admin/purchases/{$invoiceId}");

    $row = DB::table('purchase_invoices')->where('id', $invoiceId)->first([
        'payment_status',
        'paid_at',
        'paid_by_user_id',
        'paid_note',
    ]);

    expect($row)->not->toBeNull();
    expect((string) $row->payment_status)->toBe('PAID');
    expect($row->paid_at)->not->toBeNull();
    expect((int) $row->paid_by_user_id)->toBe((int) $admin->id);
    expect((string) $row->paid_note)->toBe('dibayar tunai');

    // Mark UNPAID
    $resp2 = $this->actingAs($admin)
        ->post("/admin/purchases/{$invoiceId}/mark-unpaid", [
            'reason' => 'koreksi status',
        ]);

    $resp2->assertRedirect("/admin/purchases/{$invoiceId}");

    $row2 = DB::table('purchase_invoices')->where('id', $invoiceId)->first([
        'payment_status',
        'paid_at',
        'paid_by_user_id',
        'paid_note',
    ]);

    expect($row2)->not->toBeNull();
    expect((string) $row2->payment_status)->toBe('UNPAID');
    expect($row2->paid_at)->toBeNull();
    expect($row2->paid_by_user_id)->toBeNull();
    expect($row2->paid_note)->toBeNull();
});

test('admin purchases index filters due_h5 and overdue (unpaid only)', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-24 10:00:00', 'Asia/Makassar'));

    $admin = _mkAdminUser();

    $today = CarbonImmutable::now('Asia/Makassar')->toDateString();
    $targetDue = CarbonImmutable::parse($today, 'Asia/Makassar')->addDays(5)->toDateString();

    // UNPAID due H-5
    _insertPurchaseInvoice([
        'no_faktur' => 'FAK-DUEH5-001',
        'supplier_name' => 'Supplier H5',
        'tgl_kirim' => '2026-02-01',
        'due_date' => $targetDue,
        'payment_status' => 'UNPAID',
        'grand_total' => 111_000,
    ]);

    // UNPAID overdue
    _insertPurchaseInvoice([
        'no_faktur' => 'FAK-OVERDUE-001',
        'supplier_name' => 'Supplier Overdue',
        'tgl_kirim' => '2026-01-01',
        'due_date' => '2026-02-20',
        'payment_status' => 'UNPAID',
        'grand_total' => 222_000,
    ]);

    // PAID (should not appear in due_h5 / overdue bucket)
    _insertPurchaseInvoice([
        'no_faktur' => 'FAK-PAID-999',
        'supplier_name' => 'Supplier Paid',
        'tgl_kirim' => '2026-02-10',
        'due_date' => $targetDue,
        'payment_status' => 'PAID',
        'paid_at' => CarbonImmutable::now('Asia/Makassar')->format('Y-m-d H:i:s'),
        'paid_by_user_id' => (int) $admin->id,
        'paid_note' => 'done',
        'grand_total' => 333_000,
    ]);

    // due_h5 bucket: should contain only FAK-DUEH5-001
    $resp = $this->actingAs($admin)
        ->get('/admin/purchases?bucket=due_h5&status=all&limit=200');

    $resp->assertOk();
    $resp->assertSee('FAK-DUEH5-001');
    $resp->assertDontSee('FAK-OVERDUE-001');
    $resp->assertDontSee('FAK-PAID-999');

    // overdue bucket: should contain only FAK-OVERDUE-001
    $resp2 = $this->actingAs($admin)
        ->get('/admin/purchases?bucket=overdue&status=all&limit=200');

    $resp2->assertOk();
    $resp2->assertSee('FAK-OVERDUE-001');
    $resp2->assertDontSee('FAK-DUEH5-001');
    $resp2->assertDontSee('FAK-PAID-999');

    // paid status filter: should show PAID only
    $resp3 = $this->actingAs($admin)
        ->get('/admin/purchases?status=paid&bucket=all&limit=200');

    $resp3->assertOk();
    $resp3->assertSee('FAK-PAID-999');
});
