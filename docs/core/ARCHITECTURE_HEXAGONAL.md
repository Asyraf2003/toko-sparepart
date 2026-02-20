# Hexagonal Architecture (Ports & Adapters) — V1

Dokumen ini menjelaskan **kontrak boundary** dan pola implementasi Hexagonal untuk proyek **Laravel Kasir Bengkel V1**.

Referensi:
- `docs/core/BLUEPRINT_V1.md`
- `docs/core/WORKFLOW_V1.md`

---

## 1) Layering (Wajib)

### 1.1 Domain
**Tujuan:** aturan bisnis murni (tanpa Laravel).  
**Larangan import:** `Illuminate\*`, `Eloquent`, `DB`, `Request`, `Auth`, `Carbon`, `Http`, `Storage`, dsb.

Isi yang boleh ada:
- Aggregate / Entity / Value Object
- Domain Service (bila perlu)
- Domain Policy (role/status/rules)
- Domain Errors (exception khusus domain)

### 1.2 Application
**Tujuan:** orkestrasi bisnis via **UseCase** + definisi **Port** (interface).  
**Catatan:** Application **boleh tahu** Domain, tapi **tidak boleh** tahu implementasi Infra.

Isi yang boleh ada:
- UseCases (command-style)
- DTO (input/output)
- Ports (RepositoryPort, ServicePort)
- Application Errors (validasi orkestrasi, mapping error domain)

### 1.3 Infrastructure
**Tujuan:** adapter teknis untuk Port (Eloquent repo, Telegram, PDF, clock, dll).  
Isi yang boleh ada:
- Eloquent Models
- Repository implementations
- External clients (Telegram Bot API, PDF generator)
- Clock adapter (Carbon) untuk ClockPort
- Transaction adapter (DB::transaction) untuk TransactionManagerPort

### 1.4 Interfaces (Web/UI)
**Tujuan:** Controller/Blade hanya:
- Validasi request
- Panggil UseCase
- Mapping response (ViewModel/JSON)
- Guard policy (middleware / gate) tanpa bisnis inti

---

## 2) Struktur Folder Target

```text
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
routes/
database/migrations/
docs/
```

## 3) Dependency Rules (Kontrak Boundary)
### 3.1 Aturan dependensi

- Domain → tidak bergantung pada layer lain.
- Application → bergantung pada Domain + Port (interface) yang didefinisikan di Application.
- Infrastructure → bergantung pada Application (untuk Port) + Laravel framework.
- Interfaces → bergantung pada Application (UseCase/DTO) + Laravel framework.

### 3.2 Praktik minimal untuk menjaga boundary

- Port didefinisikan di: app/Application/Ports/...
- Implementasi Port di: app/Infrastructure/...
- Controller hanya depend ke UseCase, bukan ke repo langsung.

## 4) Pola UseCase (Wajib konsisten)
### 4.1 Tipe UseCase

**Command-style:**

- Input DTO
- Output DTO
- Semua side-effects (DB/Telegram/PDF) melalui Port

## 4.2 Transaction boundary

- Semua UseCase yang memodifikasi state memakai TransactionManagerPort.

**Contoh flow konseptual:**

- Load aggregate/state via RepositoryPort
- Jalankan Domain rules (validate + compute)
- Persist via RepositoryPort
- Append audit via AuditPort (bila sensitif)
- Trigger notify via NotifierPort (bila ada perubahan stok)
- Commit

## 5) Error Handling (Kontrak)
### 5.1 Domain Errors

Domain melempar error yang eksplisit, misalnya:

- InsufficientStock
- ReasonRequired
- ForbiddenByPolicy
- InvalidStatusTransition

## 5.2 Mapping di Interfaces

**Controller:**

- Tangkap error (atau middleware handler)
- Map ke HTTP response yang konsisten (422/403/409/404)
- Jangan bocorkan internal stack trace ke user

## 6) Kontrak V1 yang harus ditaati

- Role hanya **ADMIN** dan **CASHIER**
- Kasir hanya akses transaksi business_date == today
- Stok tidak boleh minus (reservation required)
- VOID only full (tidak ada partial refund)
- Cash rounding: NEAREST_1000 (CASH saja)
- Semua aksi sensitif → audit + reason

## 7) Testing Strategy (Minimal)

**Domain unit tests:**

- reservation/release invariants
- status transitions
- reason required policy

**Feature tests:**

- policy kasir today-only vs admin
- transaksi sparepart-only vs service drop
- void + reversal stock ledger