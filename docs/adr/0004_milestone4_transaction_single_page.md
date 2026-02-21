# ADR-0004: Milestone 4 — Transaction “1 Halaman” (Sales/Service) with Reservation & Ledger Effects

- **Status**: Accepted
- **Date**: 2026-02-20 (Asia/Makassar)
- **Decision Owner**: APP KASIR / Laravel Kasir Bengkel V1

## Context

V1 membutuhkan kasir dapat operasional end-to-end dengan 1 halaman “Nota” yang mendukung kombinasi:
- Sparepart saja
- Service saja
- Service + sparepart

Hard rules terkait transaksi:
- Role hanya 2: `ADMIN` dan `CASHIER`.
- Kasir hanya akses transaksi `business_date` hari ini.
- Stok **tidak boleh minus**.
- Pembayaran V1: `CASH`, `TRANSFER`, atau `UNPAID` (tanpa metode).
- Refund V1: **VOID full** saja.
- Pembulatan cash: `NEAREST_1000` (pembulatan ke ribuan terdekat), berlaku hanya untuk `CASH`.

Milestone 3 sudah membentuk fondasi inventory:
- `on_hand_qty` vs `reserved_qty`
- stock ledger event: `RESERVE`, `RELEASE`, `ADJUSTMENT`

Milestone 4 harus mengikat transaksi terhadap inventory dan ledger dengan flow status machine yang jelas:
- DRAFT -> OPEN (drop pagi) -> COMPLETED (bayar sore) -> VOID (policy)
- DRAFT -> COMPLETED (bayar langsung)
- DRAFT/OPEN/COMPLETED -> VOID (dengan policy & reason)

## Decision

### 1) Data Model (Schema)
Menambahkan tabel minimum:
- `employees` (untuk assignment karyawan service)
- `transactions`
- `transaction_part_lines`
- `transaction_service_lines`

Konsep utama:
- `transactions.status`: `DRAFT|OPEN|COMPLETED|VOID`
- `transactions.payment_status`: `UNPAID|PAID`
- `transactions.payment_method`: `CASH|TRANSFER|NULL`
- `transactions.business_date`: tanggal operasional (berdasarkan `ClockPort::todayBusinessDate()`)
- `transactions.rounding_mode` + `rounding_amount`: untuk cash rounding `NEAREST_1000`
- `transaction_part_lines` menyimpan:
  - `qty`
  - `unit_sell_price_frozen` (freeze harga jual saat line dibuat/diubah)
  - `unit_cogs_frozen` (freeze avg cost saat completion)
  - `line_subtotal`
- `transaction_service_lines` menyimpan:
  - `description`
  - `price_manual`

### 2) Transaction Number
Nomor transaksi dibuat deterministik:
- Format: `INV-YYYYMMDD-XXXX`
- `nextTransactionNumberForDate()` dipanggil di dalam DB transaction dengan row lock untuk menghindari race condition.

### 3) Inventory & Ledger Effects (Anti-minus)
Aturan stok:
- Pada DRAFT/OPEN, sparepart line tidak mengurangi `on_hand`, hanya menaikkan `reserved`:
  - Tambah/naik qty -> ledger `RESERVE (+qty)`
  - Turun qty/hapus line -> ledger `RELEASE (-qty)`
- Pada COMPLETED:
  - `on_hand_qty` turun `qty`
  - `reserved_qty` turun `qty`
  - ledger:
    - `SALE_OUT (-qty)`
    - `RELEASE (-qty)`
  - `unit_cogs_frozen` diisi dari `products.avg_cost` saat completion
- Pada VOID:
  - Jika VOID dari COMPLETED: `VOID_IN (+qty)` dan `on_hand` naik kembali
  - Jika VOID dari DRAFT/OPEN: `RELEASE (-qty)` dan `reserved` turun

### 4) Payment & Rounding
- Payment method:
  - `CASH`: rounding `NEAREST_1000`, `rounding_amount = rounded_total - gross_total`
  - `TRANSFER`: rounding_amount = 0 (tetap simpan rounding_mode untuk konsistensi)
- V1 tidak menyimpan “DP”/partial payment.

### 5) Policy & Reason Enforcement (V1 minimal)
- Kasir dibatasi hanya untuk transaksi `business_date == today`.
- VOID wajib `reason`.
- Update service line setelah COMPLETED (same-day) wajib `reason`.
- Note/reason ditangkap di UI dan divalidasi di usecase/controller; audit log formal akan dipenuhi pada Milestone 9.

### 6) UI “1 Halaman” (Cashier)
Menggunakan 1 halaman detail transaksi sebagai “form nota”:
- `/cashier/transactions/today` (riwayat hari ini + buat nota baru)
- `/cashier/transactions/{id}` (1 halaman nota: search sparepart, tambah/update/hapus part line, tambah/update/hapus service line, kalkulator cash, tombol open/complete/void)
- `/cashier/transactions/{id}/work-order` (print work order untuk status OPEN)

UI dibangun sebagai Blade mentah, lalu dipecah menjadi partials untuk maintainability:
- `_summary_actions`, `_cash_calculator`, `_product_search`, `_part_lines`, `_service_lines`, `_alerts`

## Consequences

### Positive ✅
- Kasir dapat melakukan flow nyata “drop pagi bayar sore” tanpa stok minus.
- Ledger menjadi sumber kebenaran perubahan stok dan mudah diaudit/didebug.
- Freeze harga jual (unit_sell_price_frozen) dan COGS (unit_cogs_frozen) membuat laporan konsisten.
- UI minimal tapi operasional; refactor view dilakukan tanpa mengubah usecase core.

### Negative ⚠️
- Logika inventory + ledger menambah kompleksitas implementasi.
- Audit log formal belum aktif penuh (baru enforcement reason minimal); akan disempurnakan di Milestone 9.
- UI 1 halaman bisa “gendut” tanpa partial; mitigasi dengan partials sudah diterapkan.

## Alternatives Considered

1) Decrement `on_hand` langsung saat add line (DRAFT/OPEN)
- Ditolak: sulit rollback/edit; rawan mismatch saat transaksi batal.

2) Tidak menyimpan ledger event
- Ditolak: sulit trace dan investigasi selisih stok.

3) Menggunakan scaffolding UI besar (Breeze/Jetstream) untuk transaksi
- Ditolak untuk V1 awal: menambah noise dan footprint; fokus pada core flow.

## Follow-up (Next Milestones)

- Milestone 5: Purchasing + update moving average cost; integrasi `PURCHASE_IN`.
- Milestone 7: Reporting + export PDF memanfaatkan `unit_cogs_frozen`.
- Milestone 8: Telegram low stock berdasarkan `available_qty` + throttle.
- Milestone 9: Audit log formal end-to-end (before/after JSON, actor, reason mandatory) untuk semua aksi sensitif.