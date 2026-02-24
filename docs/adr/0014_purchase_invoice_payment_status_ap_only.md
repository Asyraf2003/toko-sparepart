# ADR-0014: Purchase Invoice Payment Status = AP-only (Tidak Mempengaruhi Profit Report)

- **Date:** 2026-02-24
- **Status:** Accepted

## Context

Ada kebutuhan untuk memantau pembelian supplier (hutang) dengan:
- `due_date` = `tgl_kirim + 1 bulan` memakai `Carbon::addMonthNoOverflow()` (end-of-month safe).
- Reminder H-5 dan overdue melalui Telegram, hanya untuk invoice yang **UNPAID**.
- Bukti bayar via Telegram (`/pay` + upload) dengan approval via UI Admin.

Namun stakeholder menginginkan proses yang “ringan” (sering langsung lunas) dan tidak ingin sistem akuntansi penuh.

Sementara itu, sistem laporan keuntungan (profit) sudah berjalan berbasis transaksi penjualan dan COGS (unit cost frozen), bukan berbasis status pembayaran supplier.

## Decision

### 1) Kita memodelkan hutang supplier sebagai “status AP” saja
Pada tabel `purchase_invoices`:
- `due_date` (indexed)
- `payment_status` (indexed): `UNPAID|PAID`
- `paid_at` (nullable)
- `paid_by_user_id` (nullable, audit trail)
- `paid_note` (nullable)

**Makna status:**
- `UNPAID` = kewajiban bayar masih ada (untuk reminder dan monitoring AP).
- `PAID` = kewajiban sudah diselesaikan (untuk audit & suppress reminder).

### 2) Payment status tidak mengubah laporan profit
Profit report tetap dihitung dari:
- Revenue (part + service + rounding)
- minus COGS (unit_cogs_frozen * qty)
- minus expenses
- minus payroll

Artinya:
- Mengubah `payment_status` **tidak** mengubah profit harian/mingguan/bulanan.
- Ini bukan manipulasi profit; ini pemisahan domain:
  - **Profit** = kinerja penjualan (accrual-ish via COGS frozen).
  - **AP status** = posisi kewajiban/cashflow.

### 3) Reminder Telegram tergantung payment_status
Reminder H-5 & overdue hanya menargetkan:
- `payment_status IS NULL OR payment_status = 'UNPAID'`
- plus filter `due_date` (H-5) atau `< today` (overdue)

Ini membuat reminder relevan dan tidak spam pada invoice yang sudah ditandai PAID.

### 4) Bukti bayar via Telegram adalah evidence, bukan sumber kebenaran status
- Upload Telegram menghasilkan `telegram_payment_proof_submissions` (PENDING).
- Admin melakukan approve/reject dari UI admin Telegram.
- Approval dapat mengubah invoice menjadi PAID dan mencatat audit metadata (`paid_at`, `paid_by_user_id`, `paid_note` jika ada).
- Bukti bayar berfungsi sebagai **evidence** yang memperkuat audit trail.

### 5) Future-ready: hexagonal memungkinkan upgrade ke ledger/cashflow
Dengan keputusan ini, sistem tetap sederhana, namun tetap membuka jalur upgrade:
- AP ledger (partial payments)
- cashflow module (kas/bank)
- capital/modality tracking

Tanpa merombak domain yang sudah berjalan.

## Alternatives Considered

1) **Due date only (selalu dianggap PAID)**
- Pro: paling ringan.
- Kontra: reminder tidak meaningful, tidak ada audit status nyata.
- Keputusan: ditolak.

2) **AP ledger penuh (supplier_payments + payment_lines)**
- Pro: enterprise accounting ready (partial payments).
- Kontra: kompleks, SOP lebih berat.
- Keputusan: ditunda (phase berikutnya).

3) **Cashflow module sebagai dasar profit**
- Pro: cocok untuk manajemen kas.
- Kontra: scope besar, tidak diperlukan untuk MVP.
- Keputusan: ditunda.

## Consequences

### Positive
- Reminder H-5/overdue menjadi valid dan berguna.
- Audit trail pembayaran tersedia tanpa mengubah sistem profit.
- Ops bot + payment proof punya landasan data yang jelas.

### Negative / Trade-offs
- Ada kebutuhan disiplin operasional: invoice perlu ditandai PAID ketika lunas (atau via approval bukti bayar).
- Posisi kas tidak dimodelkan (belum ada cashflow module), sehingga monitoring cash harus manual atau di phase berikutnya.

## Implementation Notes

### Due date policy
- Source of truth: `CreatePurchaseInvoiceUseCase` dan `UpdatePurchaseInvoiceHeaderUseCase`
- Wajib: `CarbonImmutable::parse(tgl_kirim)->addMonthNoOverflow()`.

### UI policy (Admin)
- Index purchases harus bisa filter:
  - status: all/unpaid/paid
  - bucket: all/due_h5/overdue
- Show purchases harus menampilkan:
  - due_date + badge (H-5 / overdue)
  - payment_status + paid_at + paid_by + paid_note
  - link “Bukti Bayar (Telegram)” jika ada submission terkait invoice

### Audit policy
- Perubahan status pembayaran idealnya dicatat ke audit log:
  - action: `PURCHASE_INVOICE_MARK_PAID` / `PURCHASE_INVOICE_MARK_UNPAID`
  - reason wajib.

## Testing Strategy (DoD for this ADR)

- [ ] Unit test: due_date clamp (31 Jan → 28 Feb) via addMonthNoOverflow.
- [ ] Feature test: mark PAID mengisi `paid_at` dan `paid_by_user_id`, serta status menjadi `PAID`.
- [ ] Feature test: mark UNPAID mengosongkan `paid_at/paid_by/paid_note`.
- [ ] Feature test: filter index `due_h5` dan `overdue` hanya menampilkan invoice UNPAID sesuai due_date.
- [ ] Feature test: reminder query tidak mengirim untuk invoice `PAID`.
- [ ] UI: detail purchase menampilkan list submission bukti bayar (jika ada) dan link review/download.

## Follow-ups
- Jika diperlukan: tambah “auto-paid default” sebagai konfigurasi bisnis (bukan default sistem).
- Phase berikutnya: AP ledger parsial + cashflow module, jika kebutuhan sudah nyata.