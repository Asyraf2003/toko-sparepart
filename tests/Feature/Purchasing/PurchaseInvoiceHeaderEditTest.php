<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Purchasing;

use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PurchaseInvoiceHeaderEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_purchase_detail_and_edit_header_and_update_header(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $product = Product::query()->create([
            'sku' => 'SKU-PUR-1',
            'name' => 'Oli Purchase',
            'sell_price_current' => 50000,
            'min_stock_threshold' => 3,
            'is_active' => true,
            'avg_cost' => 0,
        ]);

        $now = now()->format('Y-m-d H:i:s');

        $invoiceId = (int) DB::table('purchase_invoices')->insertGetId([
            'supplier_name' => 'Supplier A',
            'no_faktur' => 'FAK-001',
            'tgl_kirim' => '2026-02-23',
            'kepada' => null,
            'no_pesanan' => null,
            'nama_sales' => null,
            'total_bruto' => 100000,
            'total_diskon' => 0,
            'total_pajak' => 0,
            'grand_total' => 100000,
            'created_by_user_id' => $admin->id,
            'note' => 'seed invoice',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('purchase_invoice_lines')->insert([
            'purchase_invoice_id' => $invoiceId,
            'product_id' => (int) $product->id,
            'qty' => 2,
            'unit_cost' => 50000,
            'disc_bps' => 0,
            'line_total' => 100000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Detail
        $this->actingAs($admin)
            ->get('/admin/purchases/'.$invoiceId)
            ->assertOk()
            ->assertSee('Detail Pembelian')
            ->assertSee('FAK-001')
            ->assertSee('Supplier A');

        // Edit form
        $this->actingAs($admin)
            ->get('/admin/purchases/'.$invoiceId.'/edit')
            ->assertOk()
            ->assertSee('Edit Pembelian (Header)')
            ->assertSee('FAK-001')
            ->assertSee('Supplier A');

        // Update header (POST)
        $this->actingAs($admin)
            ->post('/admin/purchases/'.$invoiceId, [
                'supplier_name' => 'Supplier B',
                'no_faktur' => 'FAK-001-REV',
                'tgl_kirim' => '2026-02-23',
                'kepada' => 'Gudang',
                'no_pesanan' => 'PO-9',
                'nama_sales' => 'Sales X',
                'note' => 'updated header',
                'reason' => 'typo correction',
            ])
            ->assertRedirect('/admin/purchases/'.$invoiceId);

        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $invoiceId,
            'supplier_name' => 'Supplier B',
            'no_faktur' => 'FAK-001-REV',
            'tgl_kirim' => '2026-02-23',
            'kepada' => 'Gudang',
            'no_pesanan' => 'PO-9',
            'nama_sales' => 'Sales X',
            'note' => 'updated header',
        ]);
    }
}
