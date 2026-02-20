# ADR-0003: Inventory Anti-Minus via Reservation + Stock Ledger (RESERVE/RELEASE/ADJUSTMENT)

- **Status**: Accepted
- **Date**: 2026-02-20 (Asia/Makassar)
- **Decision Owner**: APP KASIR / Laravel Kasir Bengkel V1

## Context

Hard rule V1: **stok tidak boleh minus**.

Kasus operasional:
- Nota bisa dibuat (DRAFT/OPEN) sebelum pembayaran (UNPAID), sehingga item sparepart perlu “dikunci” tanpa mengurangi on_hand sampai transaksi selesai.
- Saat transaksi selesai (COMPLETED), on_hand harus berkurang (SALE_OUT) dan reserved dilepas.
- Saat VOID, efek stok harus dibalik sesuai status transaksi (VOID_IN atau RELEASE).

Agar akuntabel dan audit-friendly:
- Semua perubahan stok harus tercatat sebagai event.

## Decision

Mengimplementasikan model stok dengan 2 angka per product:
- `on_hand_qty`: stok fisik yang tercatat
- `reserved_qty`: stok yang “dikunci” oleh transaksi DRAFT/OPEN

Derived:
- `available_qty = on_hand_qty - reserved_qty`

Mencatat semua perubahan stok ke tabel ledger:
- `stock_ledgers(type, qty_delta, ref_type, ref_id, actor_user_id, occurred_at, note)`

V1 minimal (sudah diimplementasi):
- RESERVE (qty_delta positif) untuk mengunci stok
- RELEASE (qty_delta negatif) untuk melepas reservasi
- ADJUSTMENT (qty_delta bisa +/-) untuk input stok awal / koreksi
- Query read-side list stok untuk admin (join products + inventory_stocks)

## Implementation Notes

### Schema
- `products`:
  - `sku` unique
  - `sell_price_current`, `min_stock_threshold`, `is_active`
  - `avg_cost` (dipakai nanti moving average)
- `inventory_stocks`:
  - `product_id` unique
  - `on_hand_qty`, `reserved_qty`
- `stock_ledgers`:
  - `product_id`, `type`, `qty_delta`, `ref_type`, `ref_id`, `actor_user_id`, `occurred_at`, `note`

### Transaction Boundary & Locking
- Semua mutasi stok berjalan dalam DB transaction melalui `TransactionManagerPort`.
- Saat mutasi: row `inventory_stocks` di-lock `FOR UPDATE` (via repository adapter) agar race-condition tidak membuat minus.
- Jika stock row belum ada: dibuat default (0/0) dalam path yang aman.

### UseCases (Application Layer)
- `ReserveStockUseCase`:
  - validasi `qty > 0`
  - lock stock row
  - hitung available
  - jika `available < qty` → throw `InsufficientStock`
  - update `reserved_qty += qty`
  - ledger `RESERVE (+qty)`
- `ReleaseStockUseCase`:
  - validasi `qty > 0`
  - lock stock row
  - jika `reserved_qty < qty` → throw `InvalidReleaseQuantity`
  - update `reserved_qty -= qty`
  - ledger `RELEASE (-qty)`
- `AdjustStockUseCase`:
  - validasi `qty_delta != 0`, `note wajib`
  - lock stock row
  - jika `on_hand + delta < 0` → throw `InvalidStockAdjustment`
  - update `on_hand_qty += delta`
  - ledger `ADJUSTMENT (delta)`

### Catalog Integration
- `CreateProductUseCase` auto memastikan `inventory_stocks` row ada (0/0) untuk product baru.

### Read Side / Query Model
- `ProductStockQueryPort` + adapter Eloquent query join:
  - list produk beserta stok dan available
  - filter search sku/nama
  - findByProductId untuk halaman edit admin
- Halaman admin minimal (HTML mentah):
  - list `/admin/products`
  - create `/admin/products/create`
  - edit `/admin/products/{id}/edit`
  - actions: set price, set threshold, adjust stock (note wajib di UI form)

## Consequences

### Positive ✅
- Anti-minus enforced secara deterministik.
- Mendukung flow transaksi “drop pagi bayar sore” via reserved stock.
- Ledger menyediakan jejak perubahan stok untuk audit dan debugging.
- Struktur cocok untuk future: SALE_OUT, VOID_IN, PURCHASE_IN, dan moving average COGS.

### Negative ⚠️
- Ada tambahan kompleksitas (reserved tracking + ledger) dibanding stok langsung decrement.
- Membutuhkan disiplin: semua perubahan stok harus lewat usecase (bukan update langsung model).

## Alternatives Considered

1) Decrement on_hand langsung saat DRAFT/OPEN
- Ditolak: akan menyulitkan rollback/edit sebelum completion dan berisiko mismatch saat transaksi batal.

2) Tanpa ledger (hanya update stok)
- Ditolak: sulit audit dan investigasi selisih stok.

## Follow-up (Next Milestones)

- Tambah event ledger:
  - PURCHASE_IN (purchasing)
  - SALE_OUT + RELEASE (complete transaction)
  - VOID_IN / RELEASE (void transaction)
- Integrasi Telegram low-stock (based on available <= threshold) dengan throttle.
- AuditLog formal untuk semua aksi sensitif (reason mandatory end-to-end).