<?php

declare(strict_types=1);

namespace App\Shared\Messages;

use Throwable;

final class MessagesId
{
    /**
     * @var array<string,string>
     */
    private const LEGACY_TO_CODE = [
        // --- common ---
        'invalid actor user id' => 'AUTH_INVALID_ACTOR',
        'actor user not found' => 'AUTH_USER_NOT_FOUND',
        'reason is required' => 'REASON_REQUIRED',

        // --- sales / cashier ---
        'transaction not found' => 'TX_NOT_FOUND',
        'transaction not editable' => 'TX_NOT_EDITABLE',
        'cannot edit part lines unless draft/open' => 'TX_PART_EDIT_ONLY_DRAFT_OPEN',
        'cannot delete part lines unless draft/open' => 'TX_PART_DELETE_ONLY_DRAFT_OPEN',
        'only draft/open can be updated' => 'TX_ONLY_DRAFT_OPEN_UPDATABLE',
        'cashier cannot edit different business date' => 'TX_CASHIER_DIFFERENT_DATE_EDIT',
        'cashier cannot void different business date' => 'TX_CASHIER_DIFFERENT_DATE_VOID',
        'transaction not voidable' => 'TX_NOT_VOIDABLE',
        'transaction not completable' => 'TX_NOT_COMPLETABLE',
        'cannot complete transaction for different business date' => 'TX_COMPLETE_DIFFERENT_DATE',

        'part line not found' => 'PART_LINE_NOT_FOUND',
        'service line not found' => 'SERVICE_LINE_NOT_FOUND',

        'qty must be positive' => 'QTY_MUST_POSITIVE',
        'qty must be >= 1' => 'QTY_MIN_1',
        'qty must be > 0' => 'QTY_MUST_GT_0',

        'description is required' => 'DESC_REQUIRED',
        'priceManual must be >= 0' => 'PRICE_MIN_0',
        'invalid payment method' => 'PAYMENT_METHOD_INVALID',
        'cash received insufficient' => 'CASH_INSUFFICIENT',

        'insufficient available stock' => 'STOCK_INSUFFICIENT_AVAILABLE',
        'reserved stock insufficient' => 'STOCK_RESERVED_INSUFFICIENT',
        'reserved stock insufficient at completion' => 'STOCK_RESERVED_INSUFFICIENT_COMPLETE',
        'on hand stock insufficient at completion' => 'STOCK_ONHAND_INSUFFICIENT_COMPLETE',
        'reserved stock insufficient at void' => 'STOCK_RESERVED_INSUFFICIENT_VOID',
        'inventory stock not found' => 'STOCK_ROW_NOT_FOUND',
        'inventory stock not found for product' => 'STOCK_ROW_NOT_FOUND_PRODUCT',

        // --- purchasing ---
        'one or more products not found' => 'PURCHASE_PRODUCTS_NOT_FOUND',
        'supplier name required' => 'PURCHASE_SUPPLIER_REQUIRED',
        'no_faktur required' => 'PURCHASE_NO_FAKTUR_REQUIRED',
        'invalid tgl_kirim format (expected y-m-d)' => 'PURCHASE_TGL_KIRIM_INVALID',
        'total_pajak cannot be negative' => 'PURCHASE_TAX_NEGATIVE',
        'lines required' => 'PURCHASE_LINES_REQUIRED',
        'invalid line payload' => 'PURCHASE_LINE_PAYLOAD_INVALID',
        'invalid product id' => 'PURCHASE_PRODUCT_ID_INVALID',
        'unit_cost cannot be negative' => 'PURCHASE_UNIT_COST_NEGATIVE',
        'disc_bps must be within 0..10000' => 'PURCHASE_DISC_INVALID',
        'line net total cannot be negative' => 'PURCHASE_LINE_NET_NEGATIVE',
        'cannot allocate header tax when sum line net is zero' => 'PURCHASE_TAX_ALLOCATE_ZERO_NET',

        // --- inventory adjustments ---
        'stock in is not allowed via adjustment; use purchases' => 'ADJUST_STOCKIN_NOT_ALLOWED',
        'note is required' => 'NOTE_REQUIRED',
        'qtydelta must not be 0' => 'QTY_DELTA_NOT_ZERO',
        'product not found' => 'PRODUCT_NOT_FOUND',

        // --- catalog ---
        'sku is required' => 'SKU_REQUIRED',
        'name is required' => 'NAME_REQUIRED',
        'sellpricecurrent must be >= 0' => 'SELL_PRICE_MIN_0',
        'minstockthreshold must be >= 0' => 'MIN_STOCK_MIN_0',
    ];

    /**
     * @var array<string,string>
     */
    private const CODE_TO_ID = [
        'AUTH_INVALID_ACTOR' => 'Sesi pengguna tidak valid. Silakan login ulang.',
        'AUTH_USER_NOT_FOUND' => 'Pengguna tidak ditemukan. Silakan login ulang.',
        'REASON_REQUIRED' => 'Alasan wajib diisi.',

        'TX_NOT_FOUND' => 'Transaksi tidak ditemukan.',
        'TX_NOT_EDITABLE' => 'Transaksi tidak bisa diedit pada kondisi saat ini.',
        'TX_PART_EDIT_ONLY_DRAFT_OPEN' => 'Item sparepart hanya bisa diedit saat nota DRAFT/OPEN.',
        'TX_PART_DELETE_ONLY_DRAFT_OPEN' => 'Item sparepart hanya bisa dihapus saat nota DRAFT/OPEN.',
        'TX_ONLY_DRAFT_OPEN_UPDATABLE' => 'Hanya nota DRAFT/OPEN yang bisa diubah.',
        'TX_CASHIER_DIFFERENT_DATE_EDIT' => 'Tidak bisa mengubah transaksi beda tanggal bisnis.',
        'TX_CASHIER_DIFFERENT_DATE_VOID' => 'Tidak bisa VOID transaksi beda tanggal bisnis.',
        'TX_NOT_VOIDABLE' => 'Transaksi tidak bisa di-VOID.',
        'TX_NOT_COMPLETABLE' => 'Transaksi belum bisa diselesaikan.',
        'TX_COMPLETE_DIFFERENT_DATE' => 'Tidak bisa menyelesaikan transaksi beda tanggal bisnis.',

        'PART_LINE_NOT_FOUND' => 'Baris sparepart tidak ditemukan.',
        'SERVICE_LINE_NOT_FOUND' => 'Baris service tidak ditemukan.',

        'QTY_MUST_POSITIVE' => 'Qty harus lebih dari 0.',
        'QTY_MIN_1' => 'Qty minimal 1.',
        'QTY_MUST_GT_0' => 'Qty harus lebih dari 0.',

        'DESC_REQUIRED' => 'Deskripsi wajib diisi.',
        'PRICE_MIN_0' => 'Harga tidak boleh negatif.',
        'PAYMENT_METHOD_INVALID' => 'Metode pembayaran tidak valid.',
        'CASH_INSUFFICIENT' => 'Uang diterima kurang.',

        'STOCK_INSUFFICIENT_AVAILABLE' => 'Stok tidak cukup.',
        'STOCK_RESERVED_INSUFFICIENT' => 'Stok reserved tidak mencukupi.',
        'STOCK_RESERVED_INSUFFICIENT_COMPLETE' => 'Stok reserved tidak mencukupi untuk penyelesaian transaksi.',
        'STOCK_ONHAND_INSUFFICIENT_COMPLETE' => 'Stok on-hand tidak mencukupi untuk penyelesaian transaksi.',
        'STOCK_RESERVED_INSUFFICIENT_VOID' => 'Stok reserved tidak mencukupi untuk VOID transaksi.',
        'STOCK_ROW_NOT_FOUND' => 'Data stok tidak ditemukan.',
        'STOCK_ROW_NOT_FOUND_PRODUCT' => 'Data stok produk tidak ditemukan.',

        'PURCHASE_PRODUCTS_NOT_FOUND' => 'Satu atau lebih produk tidak ditemukan.',
        'PURCHASE_SUPPLIER_REQUIRED' => 'Nama supplier wajib diisi.',
        'PURCHASE_NO_FAKTUR_REQUIRED' => 'No faktur wajib diisi.',
        'PURCHASE_TGL_KIRIM_INVALID' => 'Format tanggal kirim tidak valid.',
        'PURCHASE_TAX_NEGATIVE' => 'Total pajak tidak boleh negatif.',
        'PURCHASE_LINES_REQUIRED' => 'Minimal 1 baris pembelian wajib diisi.',
        'PURCHASE_LINE_PAYLOAD_INVALID' => 'Data baris pembelian tidak valid.',
        'PURCHASE_PRODUCT_ID_INVALID' => 'Produk pada baris pembelian tidak valid.',
        'PURCHASE_UNIT_COST_NEGATIVE' => 'Unit cost tidak boleh negatif.',
        'PURCHASE_DISC_INVALID' => 'Diskon tidak valid.',
        'PURCHASE_LINE_NET_NEGATIVE' => 'Total baris pembelian tidak valid.',
        'PURCHASE_TAX_ALLOCATE_ZERO_NET' => 'Pajak header tidak bisa dialokasikan karena total net baris = 0.',

        'ADJUST_STOCKIN_NOT_ALLOWED' => 'Stok masuk tidak boleh lewat penyesuaian. Gunakan Pembelian.',
        'NOTE_REQUIRED' => 'Catatan wajib diisi.',
        'QTY_DELTA_NOT_ZERO' => 'Perubahan qty tidak boleh 0.',
        'PRODUCT_NOT_FOUND' => 'Produk tidak ditemukan.',

        'SKU_REQUIRED' => 'SKU wajib diisi.',
        'NAME_REQUIRED' => 'Nama wajib diisi.',
        'SELL_PRICE_MIN_0' => 'Harga jual tidak boleh negatif.',
        'MIN_STOCK_MIN_0' => 'Minimal stok tidak boleh negatif.',
    ];

    public static function error(Throwable $e): string
    {
        $msg = trim((string) $e->getMessage());

        // dynamic message special-case
        if (stripos($msg, 'Insufficient stock for product_id=') === 0) {
            return 'Stok tidak cukup.';
        }

        $norm = self::normalize($msg);
        $code = self::LEGACY_TO_CODE[$norm] ?? null;

        if ($code !== null && isset(self::CODE_TO_ID[$code])) {
            return self::CODE_TO_ID[$code];
        }

        return 'Terjadi kesalahan. Silakan coba lagi.';
    }

    private static function normalize(string $s): string
    {
        $s = trim($s);
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;

        return strtolower($s);
    }
}
