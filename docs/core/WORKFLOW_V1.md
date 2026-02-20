# Core Workflow — Laravel Kasir Bengkel V1 (Hexagonal)

Dokumen ini adalah **alur kerja implementasi** dari nol sampai siap dipakai (V1), dengan **milestone**, **checklist**, **pengujian**, dan **DoD** (Definition of Done).

---

## Prinsip & Aturan Teknis

### Hexagonal (Ports & Adapters)
- **Domain**: aturan bisnis murni (tanpa Eloquent/HTTP/DB).
- **Application**: UseCase + Port (interface) yang memanggil Domain.
- **Infrastructure**: implement Port (Eloquent repo, Telegram adapter, PDF adapter, dll).
- **Interfaces (Web/UI)**: Controller/Blade hanya orchestration + mapping.

### Hard Rules V1
- Role **hanya 2**: `ADMIN`, `CASHIER`.
- **Stok haram minus**.
- **Refund = VOID full saja**. Partial refund → transaksi baru + catatan.
- Kasir:
  - akses transaksi **business_date hari ini saja**
  - boleh **VOID/EDIT hari yang sama** dengan **reason wajib**
  - boleh ubah **harga service manual**, tidak boleh ubah harga sparepart
- Pembayaran: `CASH`, `TRANSFER`, atau `UNPAID` (tanpa metode).
- Pembulatan cash: **NEAREST_1000**.
- Alert stok minimum → Telegram (dengan throttle anti-spam).

---

## Milestone 0 — Setup Repo & Baseline Proyek
**Tujuan:** environment stabil, repo rapi, fondasi siap.

### Checklist
- [ ] Buat project Laravel baru
- [ ] Set `.env` (DB, APP_KEY, timezone Asia/Makassar)
- [ ] Setup quality gate (opsional tapi direkomendasikan):
  - [ ] Laravel Pint (format)
  - [ ] PHPStan/Psalm (static analysis)
- [ ] Buat doc baseline di `docs/core`:
  - [ ] ringkasan arsitektur hexagonal
  - [ ] aturan audit + reason
  - [ ] aturan stok anti minus (reservation)

### Pengujian
- [ ] `php artisan test` berjalan (smoke)
- [ ] migrasi kosong berjalan
- [ ] aplikasi bisa run lokal tanpa error

### DoD
- Proyek bisa dijalankan, siap mulai implement modul.

---

## Milestone 1 — Skeleton Hexagonal + Boundary Contract
**Tujuan:** struktur layer jelas, semua fitur mengikuti pola yang sama.

### Checklist
- [ ] Buat folder:
  - [ ] `app/Domain`
  - [ ] `app/Application`
  - [ ] `app/Infrastructure`
  - [ ] `app/Interfaces`
- [ ] Definisikan Port dasar:
  - [ ] `ClockPort` (waktu konsisten)
  - [ ] `TransactionManagerPort` (DB transaction boundary)
- [ ] Buat contoh UseCase + Controller pemanggil (dummy) untuk validasi struktur

### Pengujian
- [ ] unit test dummy untuk memastikan autoload/namespace benar

### DoD
- Ada 1 UseCase yang dipanggil via Controller tanpa melanggar boundary.

---

## Milestone 2 — Auth & Role Policy (Admin/Kasir)
**Tujuan:** akses dibatasi sejak awal.

### Checklist
- [ ] Implement login (scaffolding bebas)
- [ ] `users.role`: `ADMIN|CASHIER`
- [ ] Middleware/policy:
  - [ ] kasir hanya akses transaksi `business_date == today_business_date`
  - [ ] admin akses semua
- [ ] Seeder:
  - [ ] 1 admin default
  - [ ] 1 kasir default

### Pengujian
- [ ] kasir tidak bisa akses route admin
- [ ] kasir tidak bisa lihat transaksi hari lain

### DoD
- Role & policy berfungsi dan jadi gate untuk semua modul berikutnya.

---

## Milestone 3 — Catalog + Inventory (Reservation + Ledger)
**Tujuan:** fondasi stok kuat (anti minus) sebelum transaksi.

### Migrasi Minimum
- [ ] `products` (sku unik, sell_price_current, min_stock_threshold default 3)
- [ ] `inventory_stocks` (on_hand_qty, reserved_qty)
- [ ] `stock_ledgers` (type, qty_delta, ref_type/ref_id, occurred_at, actor)

### UseCases
- [ ] Create/Update Product (admin)
- [ ] Set Selling Price (admin)
- [ ] Reserve Stock (cek `available = on_hand - reserved`)
- [ ] Release Stock
- [ ] Adjust Stock (admin, reason wajib + audit)

### UI (admin minimal)
- [ ] list produk + stok + available + threshold
- [ ] set harga jual
- [ ] set threshold

### Pengujian (wajib)
- [ ] reserve gagal jika `available < qty`
- [ ] reserve/release update `reserved_qty` benar
- [ ] ledger tercatat untuk setiap perubahan stok

### DoD
- Inventory sudah “bulletproof” untuk dipakai transaksi.

---

## Milestone 4 — Transaction 1 Halaman (Sales/Service)
**Tujuan:** kasir bisa operasional end-to-end.

### Migrasi Minimum
- [ ] `transactions`:
  - status: DRAFT|OPEN|COMPLETED|VOID
  - payment_status: UNPAID|PAID
  - payment_method: CASH|TRANSFER|NULL
  - business_date
  - rounding_mode, rounding_amount
  - customer_name/phone/vehicle_plate (opsional)
  - service_employee_id (opsional)
  - timestamps: opened_at, completed_at, voided_at
- [ ] `transaction_part_lines`:
  - qty, unit_sell_price_frozen, unit_cogs_frozen (nullable sampai completed)
- [ ] `transaction_service_lines`:
  - description, price_manual

### UseCases Inti
- [ ] CreateTransaction (DRAFT)
- [ ] Add/Update/Remove PartLine:
  - reserve/release sesuai delta
  - selalu cek available
- [ ] Add/Update ServiceLine (manual)
- [ ] OpenTransaction (OPEN + UNPAID) untuk “drop pagi”
- [ ] CompleteTransaction:
  - CASH: rounding NEAREST_1000
  - TRANSFER: payment_meta opsional
  - stok: SALE_OUT + RELEASE
  - freeze unit_cogs_frozen
- [ ] VoidTransaction:
  - completed: VOID_IN
  - draft/open: release reserve
  - reason wajib
- [ ] Edit transaksi hari yang sama:
  - kasir boleh, reason wajib

### UI (kasir)
- [ ] 1 halaman buat nota:
  - sparepart section (search, harga, available, qty)
  - service section (manual: desc + harga)
  - kalkulator total + kembalian (cash)
  - tombol: SIMPAN OPEN (UNPAID) / COMPLETE CASH / COMPLETE TRANSFER
  - VOID hari sama + reason
- [ ] riwayat hari ini (filter status minimal)
- [ ] print work order untuk OPEN

### Pengujian (wajib)
- [ ] sparepart-only completed → stok turun, ledger SALE_OUT ada
- [ ] service drop pagi:
  - open unpaid
  - reserve parts
  - completed sore → on_hand turun, reserved turun
- [ ] void completed hari sama butuh reason
- [ ] kasir tidak bisa ubah harga sparepart

### DoD
- Kasir bisa menjalankan transaksi harian dari awal sampai selesai.

---

## Milestone 5 — Purchasing + Moving Average COGS
**Tujuan:** pembelian supplier menghasilkan stok masuk + cost basis untuk laba.

### Migrasi Minimum
- [ ] `purchase_invoices`, `purchase_invoice_lines`
- [ ] simpan avg cost per product:
  - [ ] `products.avg_cost` (atau tabel `product_costs`)

### UseCases
- [ ] CreatePurchaseInvoice:
  - simpan header + lines (diskon/pajak per item)
  - ledger PURCHASE_IN
  - update avg_cost dengan moving average

### Pengujian
- [ ] moving average formula benar
- [ ] on_hand bertambah sesuai invoice
- [ ] ledger purchase masuk tercatat

### DoD
- COGS siap dipakai laporan profit (via freeze saat completion).

---

## Milestone 6 — Expenses + Payroll + Hutang Karyawan
**Tujuan:** biaya operasional & gaji masuk sistem untuk laporan profit.

### Migrasi Minimum
- [ ] `employees`
- [ ] `employee_loans`
- [ ] `payroll_periods`, `payroll_lines`
- [ ] `expenses`

### UseCases
- [ ] CreateExpense
- [ ] CreatePayrollPeriod + lines
- [ ] ApplyLoanDeduction (kurangi outstanding)

### Pengujian
- [ ] potongan hutang mengurangi outstanding
- [ ] payroll period bisa direkap per minggu

### DoD
- Komponen beban (expense + payroll) lengkap untuk report profit.

---

## Milestone 7 — Reporting + Export PDF
**Tujuan:** admin dapat laporan + filter + ringkasan + PDF.

### Report Minimum
- [ ] Sales report (periode, status, metode bayar, kasir)
- [ ] Purchasing report (periode, no faktur)
- [ ] Stock report (on_hand/reserved/available + threshold)
- [ ] Profit report (weekly/monthly):
  - Revenue (sparepart + service)
  - COGS (unit_cogs_frozen * qty)
  - Expenses
  - Payroll gross
  - Net profit

### PDF
- [ ] Export PDF untuk tiap laporan utama
- [ ] Header + periode + filter yang dipakai + ringkasan

### Pengujian
- [ ] angka profit konsisten untuk periode tertentu
- [ ] PDF bisa dihasilkan (smoke)

### DoD
- Laporan sesuai kebutuhan admin + bisa cetak PDF.

---

## Milestone 8 — Telegram Low Stock Alert
**Tujuan:** notif stok menipis masuk Telegram, tidak spam.

### Checklist
- [ ] Port: `LowStockNotifierPort`
- [ ] Adapter: Telegram notifier (Bot API)
- [ ] Trigger setelah:
  - PURCHASE_IN, RESERVE/RELEASE, SALE_OUT, VOID_IN, ADJUSTMENT
- [ ] Throttle anti-spam:
  - simpan last_notified_at per product atau tabel alert events
  - interval minimal (misal 6 jam)

### Pengujian
- [ ] alert terkirim saat `available <= threshold`
- [ ] throttle bekerja (tidak spam)

### DoD
- Telegram alert stabil dan aman.

---

## Milestone 9 — Audit Trail & Hardening (Reason Required)
**Tujuan:** semua perubahan sensitif dapat ditelusuri.

### Checklist
- [ ] `audit_logs` aktif
- [ ] reason wajib untuk:
  - void transaksi
  - edit transaksi hari sama
  - edit transaksi lama (admin override)
  - set harga jual
  - adjust stok
  - edit service price (terutama setelah completed hari sama)
- [ ] UI admin: audit viewer + filter actor/entity/date

### Pengujian
- [ ] aksi sensitif tanpa reason ditolak
- [ ] audit log menyimpan before/after

### DoD
- Sistem siap audit dan aman untuk operasional.

---

## UAT Checklist (Skenario Nyata)
### Transaksi
- [ ] Sparepart-only: stok cukup → completed → stok turun, ledger ada
- [ ] Sparepart-only: stok kurang → tambah qty ditolak
- [ ] Service-only: cash rounding NEAREST_1000 benar
- [ ] Service drop:
  - [ ] open unpaid
  - [ ] reserve parts (reserved naik, on_hand tetap)
  - [ ] completed (on_hand turun, reserved turun, cogs frozen terisi)
- [ ] VOID completed hari sama:
  - [ ] wajib reason
  - [ ] stok balik (VOID_IN)

### Purchasing
- [ ] Purchase invoice masuk → stok masuk → avg cost update

### Reporting
- [ ] Profit weekly/monthly sesuai rumus
- [ ] Filter laporan bekerja
- [ ] Export PDF berhasil

### Telegram
- [ ] available <= threshold → Telegram terkirim
- [ ] throttle tidak spam

### Security
- [ ] kasir tidak bisa akses data hari lain
- [ ] admin full access + audit

---

## Urutan Implementasi (Minim Putar Balik)
1) Milestone 0–2 (setup + auth + skeleton)
2) Milestone 3 (inventory + reservation + ledger) — fondasi
3) Milestone 4 (transaction 1 halaman)
4) Milestone 5 (purchasing + avg cost)
5) Milestone 6 (expenses + payroll)
6) Milestone 7 (report + PDF)
7) Milestone 8 (telegram)
8) Milestone 9 (audit hardening)

---