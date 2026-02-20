# ADR-0002: Role-based Authorization (ADMIN/CASHIER) via Middleware + Route Segregation

- **Status**: Accepted
- **Date**: 2026-02-20 (Asia/Makassar)
- **Decision Owner**: APP KASIR / Laravel Kasir Bengkel V1

## Context

V1 hanya punya 2 role:
- **ADMIN**: akses penuh (catalog, inventory adjustment, purchasing, reports, override)
- **CASHIER**: transaksi harian (business_date hari ini), akses terbatas

Kebutuhan keamanan:
- Guest harus redirect ke login.
- Cashier tidak boleh masuk admin route.
- Admin boleh masuk semua area.
- Struktur route harus mudah dipisah sesuai domain UI (admin vs cashier) agar tidak “web.php jadi monolit”.

Laravel 12 memakai konfigurasi middleware di `bootstrap/app.php`, bukan `Http/Kernel.php`.

## Decision

1) Menambahkan kolom `users.role` dengan enum sederhana (string): `ADMIN|CASHIER`.
2) Menggunakan middleware custom `RequireRole` berbasis parameter: `role:ADMIN` / `role:CASHIER`.
3) Memisahkan route:
   - `routes/web.php` untuk entry points umum (login/logout, root redirect, system ping)
   - `routes/admin.php` khusus admin
   - `routes/cashier.php` khusus cashier

## Implementation Notes

### Migration + Model
- Migration users ditambah kolom `role` langsung pada `0001_01_01_000000_create_users_table.php` (rapi, tanpa migration tambahan).
- `App\Models\User` punya konstanta:
  - `ROLE_ADMIN = 'ADMIN'`
  - `ROLE_CASHIER = 'CASHIER'`
- `fillable` mencakup `role` (untuk seeding & internal admin actions).

### Middleware Registration (Laravel 12)
- Middleware alias didaftarkan melalui `bootstrap/app.php` dalam blok:
  - `->withMiddleware(function (Middleware $middleware): void { ... })`
- Alias: `role` → `App\Http\Middleware\RequireRole`

### Behavior
- `RequireRole`:
  - `auth` memastikan user ada
  - `role:*` memastikan `user.role` cocok
  - Jika tidak cocok: **403 Forbidden**
- Root route `/`:
  - guest → `/login`
  - admin → `/admin`
  - cashier → `/cashier`

### Testing
- Feature test:
  - guest → redirect `/login`
  - cashier → 403 saat akses admin
  - admin → OK akses halaman admin

## Consequences

### Positive ✅
- Boundary jelas dan mudah dipelihara (admin/cashier terpisah).
- Role enforcement konsisten dan reusable.
- Cocok untuk fase V1: sederhana tapi aman.

### Negative ⚠️
- Belum meng-cover policy yang lebih granular (mis: kasir hanya business_date hari ini) karena modul transaksi belum dibangun penuh.
- Audit untuk aksi sensitif belum diwajibkan di semua endpoint (dibahas pada ADR audit/hardening berikutnya).

## Alternatives Considered

1) Laravel Policies/Gates per-action
- Akan digunakan nanti untuk granular rule, tapi untuk V1 awal middleware cukup.

2) Spatie Laravel-Permission
- Powerful tapi menambah dependensi dan kompleksitas untuk hanya 2 role.

## Follow-up (Future ADR / Tasks)

- Tambah policy rule “cashier hanya hari ini” saat modul transaksi (Transaction) dibuat.
- Tambah audit + reason enforcement untuk aksi sensitif (price change, adjust stock, void).