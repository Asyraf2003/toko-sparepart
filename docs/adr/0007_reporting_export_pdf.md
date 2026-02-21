# ADR-0007: Milestone 7 — Reporting + Export PDF (Sales, Purchasing, Stock, Profit)

- **Status**: Accepted
- **Date**: 2026-02-21 (Asia/Makassar)
- **Decision Owner**: APP KASIR / Laravel Kasir Bengkel V1

## Context

Admin membutuhkan laporan operasional dan laporan profit yang:
- bisa difilter berdasarkan periode dan parameter penting,
- menampilkan ringkasan (summary) + detail,
- bisa diexport sebagai PDF untuk dicetak/dikirim.

Kondisi sistem yang sudah disepakati:
- Money disimpan sebagai integer rupiah (tanpa float).
- Inventory mengikuti kontrak anti-minus melalui `on_hand_qty`, `reserved_qty`, dan ledger event. (ADR-0003 + STOCK_RESERVATION_RULES)
- Purchasing memakai moving average pada `products.avg_cost` dan **COGS untuk profit report harus stabil** memakai freeze `unit_cogs_frozen` pada saat transaksi completed. (ADR-0005)
- Expenses dan Payroll tersedia untuk komponen profit report. (ADR-0006)

## Decision

### 1) Report Set (Minimum)

#### 1.1 Sales Report
Filter minimum:
- Periode (`transactions.business_date`): `from` / `to`
- `transactions.status` (DRAFT|OPEN|COMPLETED|VOID)
- `transactions.payment_status` (UNPAID|PAID)
- `transactions.payment_method` (CASH|TRANSFER|null)
- Kasir: `transactions.created_by_user_id`

Perhitungan:
- Revenue sparepart = SUM(`transaction_part_lines.line_subtotal`)
- Revenue service = SUM(`transaction_service_lines.price_manual`)
- Rounding = SUM(`transactions.rounding_amount`)
- Grand total = part + service + rounding
- COGS (untuk display monitoring) = SUM(`transaction_part_lines.unit_cogs_frozen * qty`) (null dianggap 0)
- `missing_cogs_qty` = SUM(qty where `unit_cogs_frozen` is null)

Output:
- summary + list transaksi (per transaction) dengan breakdown part/service/rounding/grand/cogs.

#### 1.2 Purchasing Report
Filter minimum:
- Periode (`purchase_invoices.tgl_kirim`): `from` / `to`
- `purchase_invoices.no_faktur` (contains)

Perhitungan:
- Menggunakan totals header yang sudah disimpan:
  - `total_bruto`, `total_diskon`, `total_pajak`, `grand_total`

Output:
- summary + list invoice.

#### 1.3 Stock Report
Sumber:
- `products` join `inventory_stocks`

Field:
- `on_hand_qty`, `reserved_qty`
- `available_qty = on_hand_qty - reserved_qty` (derived)
- `min_stock_threshold` dari `products.min_stock_threshold`

Low stock:
- `is_low_stock = (available_qty <= min_stock_threshold)`

Output:
- summary (count + low_stock_count) + list produk stock.

#### 1.4 Profit Report (weekly / monthly)
Filter:
- Periode: `from` / `to`
- Granularity: `weekly` atau `monthly`

Komponen profit:
- Revenue (sparepart + service + rounding) dari transaksi `COMPLETED`
- COGS = SUM(`unit_cogs_frozen * qty`) dari transaksi `COMPLETED`
- Expenses = SUM(`expenses.amount`) pada `expense_date` dalam periode
- Payroll gross = SUM(`payroll_lines.gross_pay`) untuk payroll periods dengan `payroll_periods.week_end` dalam periode
- Net profit = revenue_total - cogs - expenses - payroll_gross

Rule transaksi yang dihitung:
- Profit report **hanya menghitung transaksi dengan `transactions.status = COMPLETED`**.
  - Rationale: efek stok `SALE_OUT` dan freeze `unit_cogs_frozen` terjadi saat completion. (STOCK_RESERVATION_RULES §4)

Catatan agregasi SQL:
- Agregasi revenue/cogs harus dilakukan per-transaction terlebih dulu (subquery) untuk menghindari join multiplication (cartesian) antara part lines dan service lines.

### 2) PDF Export

Untuk tiap report utama tersedia export PDF:
- Sales report PDF
- Purchasing report PDF
- Stock report PDF
- Profit report PDF

Konten PDF minimal:
- Header judul report
- `generated_at`
- Periode yang dipakai
- Filter yang dipakai
- Ringkasan (summary)
- Tabel detail

### 3) PDF Engine

Dipilih:
- `barryvdh/laravel-dompdf` (Dompdf) karena dependency pure PHP (tanpa binary sistem).

Konfigurasi baseline:
- `isRemoteEnabled` harus `false`
- `isPhpEnabled` harus `false`

Tujuan:
- hanya render HTML Blade internal yang dikontrol aplikasi.

## Consequences

### Positive ✅
- Admin mendapatkan laporan minimum yang diperlukan untuk operasi dan kontrol profit.
- Angka profit stabil terhadap perubahan `products.avg_cost` karena COGS memakai `unit_cogs_frozen`. (ADR-0005)
- Export PDF tersedia untuk proses cetak/kirim.

### Negative ⚠️
- Layout PDF dibatasi kemampuan CSS Dompdf (PDF template dibuat minimal/mentah pada V1).
- Payroll monthly bucketing memakai kebijakan yang harus konsisten (V1: bucket by month(week_end)); perubahan kebijakan butuh ADR tambahan.

## Testing / Verification

Minimum verifikasi:
- Sales/Purchasing/Stock/Profit report bisa difilter dan menampilkan summary.
- PDF bisa dihasilkan (smoke) untuk tiap report.
- Profit konsisten untuk periode tertentu:
  - hasil HTML vs PDF sama untuk filter yang sama
  - agregasi tidak double-count akibat join multiplication
- Stock report:
  - `available_qty = on_hand_qty - reserved_qty`
  - low-stock flag sesuai `available_qty <= min_stock_threshold`

## References
- ADR-0003: Inventory Anti-Minus via Reservation + Stock Ledger
- ADR-0005: Purchasing + Moving Average COGS (freeze `unit_cogs_frozen`)
- ADR-0006: Expenses + Payroll + Hutang Karyawan
- docs/core/STOCK_RESERVATION_RULES.md