# ADR: Standardisasi UI Admin berbasis Shared Mazer + Kebijakan Edit (Purchases/Payroll) + PDF & UX Error

- **Status**: Accepted
- **Tanggal**: 2026-02-23
- **Scope**: UI Admin (Blade), Shared Layout/Sidebar, Purchases, Inventory Adjust Stock, Payroll, Audit Logs, Reports (Index + PDF), Testing.

## Context

Implementasi UI Admin awal bercampur antara:
- Halaman Blade yang sudah memakai layout Mazer (shared), dan
- Halaman standalone HTML (`<!doctype html> ... <html>`) yang menempelkan CSS inline sendiri.

Dampaknya:
- Redundansi sidebar/layout (admin vs cashier).
- Tampilan tidak konsisten antar halaman.
- Navigasi/active state berbeda-beda.
- Beberapa fitur “enterprise minimum” belum lengkap (misalnya detail).
- Ada ketidaksesuaian schema pada audit logs (`actor_id` vs `actor_user_id`) yang menyebabkan query error.
- PDF report berpotensi overflow di kertas (kolom banyak).
- Perubahan kebijakan domain (stok masuk hanya lewat purchases) memerlukan penyesuaian test.

Tujuan perubahan:
- UI Admin konsisten, modular, dan minim redundansi lewat **shared layout + shared sidebar**.
- Menjaga prinsip hexagonal: keputusan bisnis/invariant di UseCase, UI hanya adaptasi.
- Menambah minimal enterprise behavior: detail view untuk entitas transaksional (purchases/payroll).
- Output PDF lebih rapi dan sesuai batas kertas.
- Error UX rapi (flash/notif + error page), bukan tampilan error default.

## Decision

### 1) Standardisasi Layout & Sidebar
- **Semua halaman Admin** dipindahkan ke **`@extends('shared.layouts.app')`** (wrapper Mazer) dan mengikuti section:
  - `@section('title')`
  - `@section('page_heading')`
  - `@section('content')`
- Sidebar disatukan di shared partial:
  - `resources/views/shared/partials/_sidebar_menu.blade.php`
  - Layout shared meng-include sidebar via `shared.layouts.app`.
- Halaman standalone HTML (`<!doctype html>`) dimigrasikan ke pattern Mazer sehingga konsisten.

### 2) Purchases: Detail ada, Edit dibatasi (Header-only)
- Ditambahkan/diaktifkan halaman **detail**: `GET /admin/purchases/{id}`.
- Index purchases diarahkan ke detail, bukan edit line.
- Ditambahkan fitur **Edit Purchases (Header-only)**:
  - Boleh ubah metadata header (supplier/no_faktur/tgl_kirim/note, dst).
  - **Reason wajib** untuk audit trail.
  - Tidak menyediakan edit lines (qty/unit_cost/discount) untuk menghindari inkonsistensi ledger/avg_cost & audit.

### 3) Inventory: Stok Masuk wajib via Purchases
- `AdjustStockUseCase` ditegaskan:
  - `qtyDelta > 0` **ditolak** → stok masuk harus lewat purchases.
  - `qtyDelta < 0` boleh untuk koreksi (opname/selisih/rusak).
- Controller `ProductAdjustStockController` dibuat “fail fast”:
  - Validasi `qty_delta` hanya negatif (`max:-1`).
- Test inventory disesuaikan mengikuti kebijakan baru.

### 4) Payroll: Detail ada, Edit dengan Lock
- Ditambahkan halaman **Payroll Detail**: `GET /admin/payroll/{periodId}`.
- Ditambahkan **Edit/Update Payroll** dengan kebijakan lock:
  - Jika `loan_deductions_applied_at IS NOT NULL` (locked):
    - Edit lines **ditolak/diabaikan**.
    - Update hanya boleh `note` header (reason wajib).
  - Jika unlocked:
    - Update header + replace lines diperbolehkan (reason wajib).
- Disediakan tests (Pest) untuk: detail, edit unlocked, update locked.

### 5) Audit Logs: Perbaikan Query & UI
- Query audit logs di-infra disesuaikan terhadap schema:
  - Auto-detect kolom `actor_id` atau `actor_user_id` via `Schema::hasColumn`.
  - Selalu alias ke `actor_id` di output agar view/DTO stabil.
- UI Audit Logs dimigrasikan ke layout shared dan dibuat:
  - Filter panel kanan (1/3), list kiri (2/3).
  - Pagination Mazer + metadata “Menampilkan X–Y dari Z” (fragment root).
  - Baris per halaman untuk tampilan UI: 10.

### 6) Reports: Index konsisten + PDF lebih profesional
- Index report (profit/purchasing/sales/stock) dimigrasikan ke `shared.layouts.app`:
  - Panel filter kanan, ringkasan + tabel kiri.
  - Tombol Export PDF tampil sesuai syarat filter.
- PDF report (profit/sales/stock) dibuat lebih profesional:
  - CSS `@page` + margin.
  - `table-layout: fixed`, `word-break: break-word` agar tabel tidak overflow.
  - Profit & Sales memakai `A4 landscape`, Stock `A4 portrait`.
  - Header/meta/summary styling konsisten.
- **Sales PDF limit dibatasi** (disepakati) agar tidak error saat export besar:
  - Batas maksimal: 200.

### 7) Testing & CI Hygiene
- Test yang gagal karena perubahan UI/teks diperbaiki dengan menyesuaikan heading/label.
- Test inventory disesuaikan agar:
  - qtyDelta positif expect exception.
  - qtyDelta negatif valid.
- Ditambahkan test untuk:
  - Purchases detail + edit header.
  - Payroll detail/edit/update (Pest style).

## Rationale (Drivers)

- **Consistency**: satu sistem layout & sidebar mempermudah maintenance.
- **Hexagonal integrity**: aturan domain di UseCase (bukan di blade).
- **Auditability**: Purchases dan Payroll bersifat transaksional; edit dibatasi/di-lock.
- **Operational safety**: mencegah perubahan yang merusak ledger/avg_cost dan laporan.
- **User experience**: tampilan rapi, navigasi jelas, pagination konsisten, PDF siap cetak.

## Implementation Notes (High-level)

- Migrasi Blade:
  - Hilangkan standalone HTML wrapper.
  - Gunakan `shared.layouts.app` untuk admin.
  - Pastikan semua menu admin via shared sidebar.
- Purchases:
  - Index link → `/admin/purchases/{id}`.
  - Edit header-only + reason.
- Inventory:
  - `AdjustStockUseCase`: block stok masuk.
  - Controller: validasi negatif saja.
- Payroll:
  - Show controller + view.
  - Edit/update dengan lock policy `loan_deductions_applied_at`.
- Audit logs:
  - Repository query alias `actor_id` dari `actor_id/actor_user_id`.
  - UI index: filter kanan; list kiri; paginate 10.
- Reports:
  - Index report mengikuti layout shared.
  - PDF: tambah CSS + layout fixed.
  - Sales PDF clamp limit max 200.

## Consequences

### Positive
- UI Admin konsisten secara visual & struktur.
- Redundansi sidebar/layout berkurang.
- Invariant domain lebih kuat:
  - stok masuk hanya via purchases.
  - payroll locked setelah applied.
  - purchases lines tidak diedit.
- PDF lebih siap cetak, tidak overflow.
- Test suite kembali hijau setelah update.

### Trade-offs
- Tidak ada “edit purchases lines” (by design).
- Payroll edit dibatasi setelah applied (by design).
- Audit logs pagination UI memotong list (10 per page) dari hasil default query.

## Alternatives Considered

1) Membiarkan masing-masing area (admin/cashier) punya layout sendiri  
   - Ditolak: redundansi tinggi dan rawan drift.

2) Mengizinkan edit purchases lines  
   - Ditolak: akan memerlukan mekanisme ledger replay/snapshot avg_cost dan audit strategy yang lebih kompleks.

3) Mengizinkan payroll edit tanpa lock  
   - Ditolak: berisiko mengubah laporan dan status potongan pinjaman yang sudah applied.

## Follow-up Work (Next)

1) **Error UX** (disepakati sebagai fokus berikutnya):
   - Tampilkan error sebagai flash alert/notif atau error page custom (403/404/419/500).
   - Pastikan `APP_DEBUG=false` pada environment produksi.
   - Mapping exception domain (`InvalidArgumentException`, dll) menjadi redirect back + flash error, bukan 500.
2) Sales PDF limit clamp di controller PDF/index agar konsisten (max 200).
3) Terapkan styling PDF serupa untuk purchasing PDF bila diperlukan (saat ini sudah cukup rapi, tetapi bisa diseragamkan).

## Definition of Done

- Semua halaman admin yang tadinya standalone HTML sudah memakai `shared.layouts.app`.
- Sidebar unified, active state benar.
- Purchases: index → detail; edit header-only; reason wajib; tests ada.
- Inventory: stok masuk via adjust ditolak; tests updated.
- Payroll: ada detail + edit/update dengan lock; tests ada.
- Audit logs: query tidak error karena schema mismatch; UI paginated 10; layout rapi.
- Reports: index konsisten; PDF tidak overflow; sales PDF limit clamp.
- Tidak ada error page default Laravel yang “bocor” pada flow normal (akan ditangani pada follow-up error UX).