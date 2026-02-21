# ADR-0005: Milestone 5 — Purchasing + Moving Average COGS (Header Tax Allocation)

- **Status**: Accepted
- **Date**: 2026-02-21 (Asia/Makassar)
- **Decision Owner**: APP KASIR / Laravel Kasir Bengkel V1

## Context

V1 membutuhkan proses pembelian dari supplier yang:
- Menambah stok masuk (on_hand bertambah).
- Mencatat jejak perubahan stok secara audit-friendly melalui stock ledger.
- Menghasilkan cost basis untuk laba (COGS) yang stabil untuk laporan profit.

Fondasi yang sudah ada (Milestone 3–4):
- `inventory_stocks` sebagai sumber kuantitas stok: `on_hand_qty` dan `reserved_qty`.
- `stock_ledgers` sebagai jejak event stok, termasuk tipe `PURCHASE_IN`.
- `products.avg_cost` sudah tersedia sebagai integer rupiah untuk moving average cost.
- Sales completion sudah melakukan freeze COGS (`unit_cogs_frozen`) saat transaksi `COMPLETED`, sehingga laporan profit tidak berubah walau avg_cost berubah setelahnya.

Kebutuhan tambahan dari milestone 5:
- Menambah entitas PurchaseInvoice (header + lines).
- Diskon pembelian ada per item (per line) berbentuk persen.
- Pajak pembelian dicatat sebagai total header (bukan per item) dan diputuskan masuk ke cost basis.

## Decision

### 1) Data Model (Schema)

#### a) `purchase_invoices` (header)
Menambahkan tabel `purchase_invoices` sebagai dokumen pembelian supplier (V1 tanpa master supplier):
- `supplier_name` (string) — V1 tidak membuat tabel `suppliers`.
- `no_faktur` (string, unique)
- `tgl_kirim` (date)
- field nota opsional: `kepada`, `no_pesanan`, `nama_sales`, `note`
- totals integer rupiah:
  - `total_bruto`
  - `total_diskon`
  - `total_pajak` (header-level)
  - `grand_total`
- trace: `created_by_user_id`

#### b) `purchase_invoice_lines` (lines)
Menambahkan tabel `purchase_invoice_lines`:
- `purchase_invoice_id`
- `product_id`
- `qty` (unsigned int)
- `unit_cost` (bigint, rupiah)
- diskon per line disimpan sebagai basis points:
  - `disc_bps` (0..10000 untuk 0.00%..100.00%)
- `line_total` (bigint, rupiah) = net line setelah diskon, sebelum alokasi pajak header

#### c) Avg cost storage
Diputuskan menggunakan penyimpanan avg cost pada `products.avg_cost` (existing):
- Tidak menambah tabel history `product_costs` pada V1.
- Nilai `avg_cost` selalu “current moving average” dalam rupiah integer.

### 2) Policy Pajak Header untuk Cost Basis

Pajak pembelian dicatat sebagai total di header dan diputuskan:
- **Masuk ke cost basis (COGS)**.
- Dialokasikan ke lines secara proporsional berdasarkan `line_total` (net setelah diskon).
- Metode alokasi: **largest remainder**, memastikan total alokasi tepat sama dengan `total_pajak`.

### 3) Stock Ledger Reference Granularity

Diputuskan pencatatan ledger `PURCHASE_IN` per line:
- `ref_type = 'purchase_invoice_line'`
- `ref_id = purchase_invoice_lines.id`

Tujuan:
- Audit stok granular per item/line lebih mudah dibanding referensi ke header.

### 4) Use Case: CreatePurchaseInvoice

UseCase `CreatePurchaseInvoice`:
- Menyimpan header + lines.
- Menghitung totals:
  - `line_total` = (qty * unit_cost) - discount
  - `total_bruto` = sum(qty * unit_cost)
  - `total_diskon` = sum(discount)
  - `grand_total` = sum(line_total) + total_pajak
- Alokasi pajak header:
  - Berdasarkan proporsi `line_total` terhadap total net.
  - Largest remainder untuk distribusi sisa rupiah.
- Efek inventory & ledger:
  - `inventory_stocks.on_hand_qty` bertambah sesuai total qty per product.
  - Insert `stock_ledgers`:
    - type `PURCHASE_IN`
    - qty_delta `+qty`
    - ref ke `purchase_invoice_line`
    - `actor_user_id` dari request
    - `occurred_at` dari `ClockPort`

### 5) Moving Average Update (COGS)

Moving average dihitung per product setelah agregasi per invoice (per product):
- `old_on_hand` = on_hand sebelum purchase
- `old_avg_cost` = products.avg_cost sebelum update
- `qty_in` = total qty masuk untuk product pada invoice
- `cost_in` = sum(line_total + allocated_tax) untuk product

Formula:
- `avg_cost_new = ((old_on_hand * old_avg_cost) + cost_in) / (old_on_hand + qty_in)`
- Pembulatan: **round half-up** ke rupiah integer.

### 6) Concurrency & Locking

Untuk mencegah race condition pada purchase paralel:
- `SELECT ... FOR UPDATE` pada:
  - row `inventory_stocks` by `product_id`
  - row `products` by `id`
- Perhitungan `old_on_hand` + update `on_hand_qty` dan `avg_cost` dilakukan dalam 1 DB transaction.

### 7) Admin UI (Minimal, Raw Blade)

Menambahkan UI admin minimal untuk input pembelian:
- Routes (auth + role ADMIN):
  - `GET /admin/purchases`
  - `GET /admin/purchases/create`
  - `POST /admin/purchases`
- Blade raw minimal tanpa styling berat:
  - index list pembelian + search sederhana (no faktur / supplier)
  - create form header + tabel lines (fixed row count)
- Input diskon pada form menggunakan persen desimal (`step 0.01`) dan dikonversi ke basis points (`disc_bps`) di controller.

## Consequences

### Positive ✅
- Purchasing menambah stok secara konsisten melalui `inventory_stocks` dan `stock_ledgers`.
- `avg_cost` terupdate moving average dengan pajak header masuk ke cost basis, membuat COGS lebih realistis untuk laporan profit.
- Granular audit: setiap line purchase memiliki jejak ledger `PURCHASE_IN` tersendiri.
- Integrasi dengan Milestone 4 berjalan tanpa perubahan: sales completion tetap freeze `unit_cogs_frozen` dari `products.avg_cost`.
- UI admin minimal tersedia untuk operasional V1.

### Negative ⚠️
- Tidak ada histori perubahan avg_cost (karena tidak memakai `product_costs`).
- Alokasi pajak header menambah kompleksitas perhitungan (largest remainder) dan perlu test khusus.
- UI minimal (raw blade) belum memiliki UX search/auto-add line yang lebih nyaman (di-upgrade nanti bila diperlukan).

## Alternatives Considered

1) Simpan cost history di tabel `product_costs`
- Ditolak untuk V1: scope bertambah, wiring port/repo meningkat, tidak mandatory karena profit report memakai freeze COGS.

2) Pajak header tidak masuk cost basis
- Ditolak: target V1 profit report membutuhkan cost yang lebih representatif sesuai kebijakan operasional saat ini.

3) Ledger `PURCHASE_IN` merefer ke header invoice
- Ditolak: audit per item lebih sulit; dipilih ref ke `purchase_invoice_line`.

4) Diskon disimpan sebagai integer persen tanpa desimal
- Ditolak: dipilih basis points agar bisa represent 0.01% (atau 0.01 langkah input), tanpa memakai decimal money.

## Testing

Minimum tests:
- Moving average benar (termasuk:
  - diskon basis points
  - alokasi pajak header largest remainder
  - edge case on_hand=0
)
- `on_hand_qty` bertambah sesuai invoice
- Ledger `PURCHASE_IN` tercatat per line dengan `ref_type/ref_id` benar
- Integrasi sanity: sales completion freeze COGS tetap mengambil `products.avg_cost`

## Follow-up (Next Milestones)

- Milestone 7: Reporting + export PDF, memanfaatkan `unit_cogs_frozen` sebagai sumber COGS untuk profit report.
- Milestone 8: Low stock Telegram notification berdasarkan `available_qty`.
- Milestone 9: Audit log formal end-to-end (before/after JSON, actor, reason enforcement) termasuk aksi purchasing (create/void/edit bila ditambahkan).