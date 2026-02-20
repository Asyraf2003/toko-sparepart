# BLUEPRINT V1 — Laravel Kasir Bengkel (Hexagonal, Scalable)

## 1) Scope Produk

### 1.1 Role (HANYA 2)
- **Admin**: akses penuh (pembelian, katalog/price, stok, gaji, hutang karyawan, operasional, laporan, edit override) + semua perubahan sensitif wajib audit.
- **Kasir**: input transaksi & service; tidak boleh ubah harga sparepart; akses riwayat terbatas hari operasional berjalan; dapat VOID/EDIT hari yang sama dengan alasan wajib.

### 1.2 Jenis Transaksi (1 halaman, 3 kombinasi)
Satu form “Nota” yang bisa berisi:
- Sparepart saja
- Service saja
- Service + sparepart

### 1.3 Pembayaran
- Metode: **CASH** dan **TRANSFER**
- “Hutang pelanggan” dimodelkan sebagai **UNPAID** (tidak ada metode pembayaran sampai dibayar)
- Tidak ada partial payment (DP) pada V1

### 1.4 Kasus Khusus
- **Service drop pagi, bayar sore**: nota dibuat saat drop (OPEN + UNPAID), lalu di-COMPLETED saat dibayar/diambil.
- **Refund**: V1 = **VOID full saja**. Partial return/uang tidak 100% → buat transaksi baru + catatan opsional.

### 1.5 Data opsional pada service
- `nama pembeli` (opsional)
- `no HP` (opsional)
- `no polisi` (opsional)
- `karyawan service` (wajib pilih 1 orang jika ada service line)

### 1.6 Stok
- **Stok tidak boleh minus** (hard rule).

### 1.7 Cetak
- Fokus V1: **PDF laporan**
- Struk thermal: opsional (arsitektur disiapkan agar mudah ditambah)

### 1.8 Pembulatan Cash
- Mode: **NEAREST_1000** (pembulatan ke ribuan terdekat)
- Berlaku hanya untuk CASH

### 1.9 Alert stok minimum
- Jika stok tersisa kecil (threshold per item; default 3; contoh alert saat <= 3 atau saat “2–3 sisa” sesuai konfigurasi) → kirim notifikasi via **Telegram**.

---

## 2) Arsitektur: Hexagonal (Ports & Adapters)

### 2.1 Prinsip
- UI (Blade/Controller) hanya orchestration + mapping request/response.
- Logic bisnis ada di **Application UseCases** + **Domain**.
- Akses database, PDF generator, Telegram, clock, auth adapter ada di **Infrastructure**.
- Semua perubahan sensitif melewati UseCase agar audit konsisten.

### 2.2 Struktur Folder (target)
app/
  Domain/
    Sales/
    Catalog/
    Inventory/
    Purchasing/
    Expenses/
    Payroll/
    Audit/
    Reporting/
    Notifications/
    Shared/
  Application/
    UseCases/
    DTO/
    Ports/
      Repositories/
      Services/
  Infrastructure/
    Persistence/
      Eloquent/
        Models/
        Repositories/
    Pdf/
    Notifications/
      Telegram/
    Auth/
    Clock/
  Interfaces/
    Web/
      Controllers/
      Requests/
      ViewModels/
    Console/
routes/
resources/views/
database/migrations/
docs/

---

## 3) Domain Model (Aggregate, Entity, Value Object)

## 3.1 Aggregate: Transaction (Nota)
**Transaction**
- id (uuid/ulid atau bigint)
- transaction_number (INV-YYYYMMDD-XXXX)
- business_date (tanggal operasional)
- status: DRAFT | OPEN | COMPLETED | VOID
- payment_status: UNPAID | PAID
- payment_method: CASH | TRANSFER | NULL (NULL jika UNPAID)
- rounding_mode: NEAREST_1000
- rounding_amount (int)
- note (opsional)
- opened_at, completed_at, voided_at
- created_by_user_id (kasir/admin)

Customer (opsional)
- customer_name
- customer_phone
- vehicle_plate

Service (opsional)
- service_employee_id (1 orang)

**TransactionPartLine**
- product_id
- qty
- unit_sell_price_frozen
- line_subtotal
- unit_cogs_frozen (dibekukan saat COMPLETED)

**TransactionServiceLine**
- description (bebas ketik)
- price_manual

## 3.2 Entity: Product (Sparepart)
- id
- sku / kode_barang (unique)
- name / desc
- sell_price_current
- min_stock_threshold (default 3; bisa diubah admin per item)
- is_active

## 3.3 Entity: InventoryStock
- product_id (unique)
- on_hand_qty
- reserved_qty

Derived:
- available_qty = on_hand_qty - reserved_qty

## 3.4 Entity: StockLedger (wajib)
- id
- product_id
- type: PURCHASE_IN | SALE_OUT | VOID_IN | ADJUSTMENT | RESERVE | RELEASE
- qty_delta (+/-)
- ref_type, ref_id (purchase_invoice / transaction)
- occurred_at
- actor_user_id
- note/reason (opsional sesuai aksi)

## 3.5 Aggregate: PurchaseInvoice (nota beli supplier)
Header:
- no_faktur
- tgl_kirim
- kepada (text: admin)
- no_pesanan
- nama_sales (text)
- totals: total_bruto, total_diskon, total_pajak, grand_total

Lines:
- kode_barang (text)
- desc (text)
- qty
- unit_cost
- disc_percent
- tax_percent
- line_total

Efek domain:
- tambah stok (PURCHASE_IN)
- update moving average cost

## 3.6 Expenses
- id, category, amount, date, note

## 3.7 Payroll (ringkas V1)
Employees:
- id, name, is_active

EmployeeLoans:
- employee_id, amount, date, note, outstanding_amount

PayrollPeriods:
- week_start, week_end

PayrollLines:
- employee_id
- gross_pay
- loan_deduction
- net_paid
- note

## 3.8 AuditLog (wajib)
- id
- actor_user_id, actor_role
- entity_type, entity_id
- action (CREATE/UPDATE/COMPLETE/VOID/PRICE_CHANGE/OVERRIDE/...)
- reason (wajib pada aksi tertentu)
- before_json, after_json
- created_at
- ip/user_agent (opsional)

---

## 4) Status Flow & Policy (Kasir/Admin)

### 4.1 Status Machine
- DRAFT -> COMPLETED (sparepart-only; bayar langsung)
- DRAFT -> OPEN (service drop; UNPAID)
- OPEN -> COMPLETED (bayar sore)
- DRAFT/OPEN/COMPLETED -> VOID (sesuai policy & audit)

### 4.2 Policy Kasir
- Boleh buat/edit/complete/void transaksi **hari business_date yang sama**
- VOID/EDIT wajib isi `reason`
- Akses riwayat: **business_date hari ini** saja
- Tidak bisa ubah harga sparepart
- Bisa ubah harga service manual (bebas ketik), termasuk setelah completed di hari yang sama, wajib reason

### 4.3 Policy Admin
- Akses penuh semua tanggal
- Override edit/void transaksi lama: wajib reason dan dicatat audit

---

## 5) Aturan Stok (Anti Minus) — Reservation Required

**Motivasi:** stok tidak boleh minus, dan kasus OPEN (service drop) boleh mengunci sparepart tanpa mengurangi on_hand sampai completed.

### 5.1 Konsep
- on_hand hanya berubah pada COMPLETED (SALE_OUT) atau VOID_IN / ADJUSTMENT / PURCHASE_IN
- reserved berubah saat item dimasukkan/diubah/dihapus pada DRAFT/OPEN

### 5.2 Rules
Saat tambah part line pada DRAFT/OPEN:
- cek available_qty >= qty (on_hand - reserved)
- jika fail -> reject (stok tidak cukup)
- ledger: RESERVE (+qty), update reserved_qty

Saat kurangi qty / hapus line:
- ledger: RELEASE (-qty), update reserved_qty

Saat COMPLETED:
- ledger: SALE_OUT (-qty) => on_hand_qty turun
- ledger: RELEASE (-qty) => reserved_qty turun
- freeze unit_cogs_frozen (moving average saat completion)

Saat VOID:
- jika transaksi COMPLETED:
  - ledger: VOID_IN (+qty) => on_hand naik
- jika transaksi DRAFT/OPEN:
  - ledger: RELEASE (-qty) => reserved turun
- wajib reason (lebih ketat jika lewat beberapa hari, khusus admin)

---

## 6) COGS — Moving Average
- Simpan `avg_cost` per product (di tabel cost atau di product_cost)
- Saat purchase invoice masuk:
  - avg_cost_new = ((on_hand * avg_cost_old) + (qty_in * unit_cost_net)) / (on_hand + qty_in)
- Saat sale completed:
  - unit_cogs_frozen = avg_cost_current pada saat completion
  - (mendukung laporan laba yang konsisten walau avg_cost berubah setelahnya)

---

## 7) Laporan (Reporting + PDF)

### 7.1 Sales Report
Filter:
- periode (harian/mingguan/bulanan)
- kasir
- status (completed/void)
- payment method (cash/transfer)
Ringkasan:
- total revenue sparepart
- total revenue service
- total rounding (cash)
- count completed/void

### 7.2 Profit Report
- Revenue (sparepart + service)
- COGS sparepart (berdasarkan unit_cogs_frozen)
- Gross profit sparepart
- Expenses
- Payroll (gross)
- Net profit

### 7.3 Purchasing Report
- per faktur: totals, pajak, diskon, item count

### 7.4 Stock Report
- on_hand, reserved, available
- alert list (available <= threshold)

### 7.5 Export
- PDF laporan wajib V1
- Struk thermal: opsional (disiapkan port)

---

## 8) Notifications — Telegram Low Stock

### 8.1 Trigger
- Setelah event yang mempengaruhi available/on_hand:
  - PURCHASE_IN, RESERVE/RELEASE, SALE_OUT, VOID_IN, ADJUSTMENT

### 8.2 Rule
- Jika available_qty <= min_stock_threshold -> kirim Telegram

### 8.3 Anti Spam
- throttle per product:
  - simpan last_notified_at atau tabel stock_alert_events
  - kirim ulang hanya jika:
    - sudah lewat interval (misal 6 jam) ATAU
    - available turun melewati nilai sebelumnya (lebih kritikal)

### 8.4 Hexagonal
- Port: LowStockNotifierPort
- Adapter: TelegramLowStockNotifier (HTTP Bot API)
- UseCase yang memanggil port: InventoryRecalculateAndNotify

---

## 9) Use Cases (Application Layer)

### 9.1 Sales/Service
- CreateTransaction (kasir/admin)
- AddPartLine (reserve)
- UpdatePartLineQty (reserve delta)
- RemovePartLine (release)
- AddServiceLine (manual)
- UpdateServiceLine (manual; reason required if completed same-day)
- SetCustomerInfo (opsional)
- SetServiceEmployee
- CompleteTransaction (cash/transfer + rounding cash)
- VoidTransaction (policy + reason + stock reversal/release)
- PrintWorkOrder (untuk OPEN)

### 9.2 Catalog/Inventory
- CreateProduct / UpdateProduct
- SetSellingPrice (admin)
- AdjustStock (admin; audit)
- SetMinStockThreshold (admin)

### 9.3 Purchasing
- CreatePurchaseInvoice (stock in + avg cost update)

### 9.4 Expenses/Payroll
- CreateExpense
- CreatePayrollPeriod + PayrollLines + apply loan deduction

### 9.5 Reporting
- GenerateReport (query model)
- ExportReportPdf

### 9.6 Notifications
- NotifyLowStock (telegram adapter)

---

## 10) UI Map (Interfaces/Web) — garis besar
- Kasir:
  - /cashier/transactions/create (1 halaman)
  - /cashier/transactions/today (riwayat hari ini)
  - /cashier/transactions/{id} (view/print work order)
- Admin:
  - /admin/products (catalog + min stock threshold + price)
  - /admin/purchases (invoice supplier)
  - /admin/expenses
  - /admin/payroll
  - /admin/reports (filter + export pdf)
  - /admin/transactions (full search + audit trail)

---

## 11) Migration Blueprint Preview (high level, detail di V1 schema next step)
Tabel minimal:
- users (role: admin/kasir)
- employees
- products
- product_price_histories (opsional; atau audit log saja)
- inventory_stocks
- stock_ledgers
- transactions
- transaction_part_lines
- transaction_service_lines
- purchase_invoices
- purchase_invoice_lines
- expenses
- employee_loans
- payroll_periods
- payroll_lines
- audit_logs
- notification_throttles (opsional) / stock_alert_events

---

## 12) Definition of Done (DoD) V1
- Semua usecase utama berjalan tanpa stok minus
- Semua edit/void wajib reason dan tercatat audit
- Kasir hanya akses business_date hari ini
- Laporan profit mingguan/bulanan + export PDF
- Telegram low-stock alert aktif + throttle
- Test minimal:
  - unit test domain reservation + complete + void
  - feature test role policy kasir vs admin
  - profit calc smoke test
