# ADR-0006: Milestone 6 — Expenses + Payroll + Hutang Karyawan

- **Status**: Accepted
- **Date**: 2026-02-21 (Asia/Makassar)
- **Decision Owner**: APP KASIR / Laravel Kasir Bengkel V1

## Context

V1 membutuhkan komponen beban operasional dan gaji untuk mendukung laporan profit:
- Beban operasional harian (expenses).
- Payroll mingguan (gaji per karyawan).
- Hutang karyawan (employee loans) yang dipotong saat payroll.

Kondisi sistem saat ini:
- Money disimpan sebagai integer (rupiah) untuk konsistensi dan menghindari masalah floating.
- Hexagonal architecture: perubahan domain dilakukan lewat UseCase, UI hanya orchestration.
- V1 membutuhkan aturan sederhana dan audit-friendly: keputusan potongan hutang harus deterministik.

Kebijakan operasional:
- Minggu libur; payroll weekly didefinisikan sebagai **Senin–Sabtu**.

## Decision

### 1) Data Model (Schema)

#### a) `expenses`
Mencatat beban operasional:
- `expense_date` (date)
- `category` (string, indexed)
- `amount` (bigint rupiah)
- `note` (nullable)
- `created_by_user_id` (nullable)

#### b) `employee_loans`
Mencatat hutang karyawan dan sisa hutang sebagai source of truth:
- `employee_id` (FK)
- `loan_date` (date)
- `amount` (bigint rupiah)
- `outstanding_amount` (bigint rupiah) — wajib >= 0
- `note` (nullable)
- `created_by_user_id` (nullable)
Index untuk query outstanding dan per karyawan.

#### c) `payroll_periods`
Mencatat periode payroll mingguan:
- `week_start` (date) — harus Senin
- `week_end` (date) — harus Sabtu
- `note` (nullable)
- `loan_deductions_applied_at` (nullable timestamp) — guard agar apply loan deduction tidak double-apply
- `created_by_user_id` (nullable)
Unique: `(week_start, week_end)`.

#### d) `payroll_lines`
Mencatat gaji per karyawan per periode:
- `payroll_period_id` (FK)
- `employee_id` (FK)
- `gross_pay` (bigint)
- `loan_deduction` (bigint, default 0)
- `net_paid` (bigint) = gross_pay - loan_deduction
- `note` (nullable)
Unique: `(payroll_period_id, employee_id)`.

### 2) Payroll Week Definition (K1)
Payroll week didefinisikan ketat:
- `week_start` harus Senin.
- `week_end` harus Sabtu.
- Durasi tepat 6 hari (selisih 5 hari).

### 3) Loan Deduction Allocation (K2)
Jika satu karyawan memiliki beberapa loan outstanding, potongan dialokasikan:
- **FIFO (oldest-first)** berdasarkan `loan_date` lalu `id`.
Tujuan:
- deterministik,
- mudah dijelaskan,
- audit-friendly.

### 4) Over-Deduction Policy (K3)
Jika `loan_deduction` melebihi total outstanding karyawan:
- **Reject** (error), tidak ada silent clamp.
Tujuan:
- menjaga integritas data,
- konsisten dengan filosofi V1 yang ketat (anti-minus, enforcement reason, dsb).

### 5) Use Cases

#### a) `CreateExpense`
- Validasi kategori, tanggal, amount.
- Insert row expenses (money integer).
- Actor dicatat via `created_by_user_id`.

#### b) `CreateEmployeeLoan`
- Insert loan dengan `outstanding_amount = amount`.
- Validasi amount > 0.

#### c) `CreatePayrollPeriod` (+ lines)
- Membuat `payroll_periods` dan `payroll_lines`.
- Validasi Senin–Sabtu.
- Validasi `loan_deduction <= gross_pay`.
- Mengaplikasikan potongan hutang FIFO dalam DB transaction.
- Mengisi `loan_deductions_applied_at` untuk idempotency.

#### d) `ApplyLoanDeduction`
- UseCase terpisah tersedia untuk apply manual jika diperlukan.
- Menolak jika sudah pernah apply (`loan_deductions_applied_at` non-null).
- FIFO + reject over-deduction sama dengan policy di atas.

### 6) Concurrency & Consistency
Untuk mencegah race condition:
- Update outstanding loan dilakukan di dalam DB transaction.
- Row loan yang terkait di-lock (`FOR UPDATE`) sebelum update outstanding.

## Consequences

### Positive ✅
- Komponen beban (expenses + payroll) tersedia untuk laporan profit.
- Hutang karyawan memiliki sisa hutang (`outstanding_amount`) yang konsisten dan mudah direkap.
- FIFO membuat potongan hutang deterministik dan audit-friendly.
- Reject over-deduction mencegah data hutang menjadi negatif atau mismatch payroll.
- Payroll dapat direkap per minggu (Senin–Sabtu) dengan agregasi gross/deduction/net.

### Negative ⚠️
- Tidak ada histori detail “potongan hutang per loan per payroll” (belum ada table allocation); hanya outstanding yang berubah.
- Kebijakan week Senin–Sabtu ketat; input tanggal salah akan ditolak.
- UI raw minimal; kenyamanan input payroll masih sederhana.

## Alternatives Considered

1) Minggu Minggu–Sabtu atau periode bebas
- Ditolak: kebijakan operasional sudah menetapkan Minggu libur dan payroll Senin–Sabtu.

2) LIFO atau admin memilih loan yang dipotong
- Ditolak untuk V1: menambah kompleksitas UI dan risiko human error; FIFO lebih deterministik.

3) Clamp jika deduction melebihi outstanding
- Ditolak: silent correction berisiko mismatch; dipilih reject untuk integritas data.

4) Menyimpan table detail allocation (loan_deduction_events)
- Ditunda: V1 minimal cukup dengan outstanding; audit detail akan dipertimbangkan di milestone audit/reporting lanjutan.

## Testing

Minimum tests:
- Potongan hutang FIFO mengurangi outstanding dengan benar (multi-loan).
- Reject jika potongan hutang melebihi total outstanding.
- Payroll period dapat direkap per minggu (sum gross/deduction/net).

## Follow-up (Next Milestones)

- Milestone 7: Reporting + PDF profit report menggunakan:
  - Revenue (sales + service)
  - COGS (unit_cogs_frozen)
  - Expenses
  - Payroll (sum net)
- Milestone 9: Audit log formal end-to-end untuk aksi sensitif termasuk payroll/loan adjustments jika ditambahkan.