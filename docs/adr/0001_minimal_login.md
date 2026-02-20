# ADR-0001: Minimal Login (Session-based) for V1

- **Status**: Accepted
- **Date**: 2026-02-20 (Asia/Makassar)
- **Decision Owner**: APP KASIR / Laravel Kasir Bengkel V1

## Context

V1 membutuhkan autentikasi secepat mungkin untuk:
- Memisahkan akses **Admin** vs **Cashier** sejak awal implementasi.
- Memastikan semua fitur setelahnya (admin routes, cashier routes, laporan, inventory) bisa diproteksi dengan middleware standar Laravel.
- Menjaga implementasi tetap kecil (minim dependensi) di fase build awal.

Constraint:
- Fokus V1: **web (Blade)**, bukan API.
- UI masih **HTML mentah**, tanpa styling (atau minimal sekali) untuk percepat delivery.
- User seed awal untuk development: **admin & cashier** dengan password sederhana (sementara) untuk mempercepat UAT internal.

## Decision

Menggunakan autentikasi Laravel **session-based** (guard default) dengan **LoginController minimal** dan Blade form sederhana, tanpa scaffolding UI tambahan.

Komponen utama:
- Route login: `GET /login` + `POST /login`
- Route logout: `POST /logout`
- Login menggunakan email + password (hash melalui cast `password => hashed` pada model User).

## Implementation Notes

### Route
- Routing berada di `routes/web.php` untuk `login/logout` (karena ini entry point web).
- Routes admin/cashier tetap dipisah ke `routes/admin.php` dan `routes/cashier.php` (untuk boundary & maintainability), namun aksesnya tetap via middleware `auth` + role.

### Controller (Interfaces Layer)
- Controller diletakkan di `app/Interfaces/Web/Controllers/Auth/LoginController.php` (sebagai adapter web/UI).
- Controller hanya orchestration + redirect, tidak mengandung domain logic.

### Blade
- View minimal: `resources/views/auth/login.blade.php` (atau lokasi serupa yang sudah digunakan).
- Tanpa layout kompleks, hanya HTML mentah.

### Seed
- Seeder `DefaultUsersSeeder` membuat:
  - `admin@local.test` role `ADMIN`
  - `cashier@local.test` role `CASHIER`
  - password sementara: `12345678` (untuk fase build & testing internal)

## Consequences

### Positive ✅
- Cepat jalan untuk milestone awal; semua fitur berikutnya bisa diproteksi.
- Minim dependensi; mudah dirawat dan dimodifikasi saat UI berkembang.
- Cocok untuk arsitektur hexagonal: controller tetap tipis.

### Negative ⚠️
- Belum ada fitur advanced:
  - reset password UI
  - rate limiting/login throttling khusus
  - MFA
- Password seed sederhana tidak boleh dipakai produksi.

## Alternatives Considered

1) Laravel Breeze/Jetstream/Fortify
- Kelebihan: lengkap dan cepat untuk UI auth.
- Ditolak sementara karena menambah kompleksitas UI & file footprint pada fase “build core”.

2) Token/API auth
- Tidak sesuai kebutuhan (V1 fokus web).

## Follow-up (Future ADR / Tasks)

- Hardening login: rate limit, lockout, password policy produksi.
- Pengaturan password seed menjadi random kuat saat mendekati produksi.
- Audit untuk login/logout (opsional) jika dibutuhkan.