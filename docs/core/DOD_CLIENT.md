## DoD v1 (Definition of Done) ✅

### DoD — Telegram Profit Harian
- Command manual tersedia: `php artisan telegram:profit-daily`
- Pesan terkirim ke chat_id yang ditentukan
- Angka konsisten dengan query profit existing
- Ada test minimal (fake notifier / assert payload dibentuk)

### DoD — Alert Jatuh Tempo H-5
- Ada sumber jatuh tempo yang jelas (kolom/aturan)
- Command: `php artisan telegram:purchase-due-h5`
- Filter hanya invoice due H-5 dan belum lunas
- Ada test (seed data + assert hasil)

### DoD — Scheduler
- Job terdaftar di schedule dengan:
  - timezone `Asia/Makassar`
  - `18:00`
  - Senin–Sabtu
- Tidak spam (opsional: idempotency marker jika diperlukan)

### DoD — UI & Error Indonesia
- Tidak ada popup validasi browser (`novalidate`)
- Validation error tampil inline dalam Bahasa Indonesia
- Tidak ada stacktrace/whoops ke user
- Label UI Admin + Cashier konsisten Bahasa Indonesia

### DoD — Bug tambah produk
- Repro fixed
- Test regresi tersedia
- Tidak ada error baru di flow create product

---

## Data Minimum yang Dibutuhkan untuk “Final Blueprint/Workflow/DoD” 📌
1) `app/Console/Kernel.php`  
2) `config/services.php` (bagian telegram) + `.env` chat_ids  
3) Keputusan jatuh tempo Purchase Invoice: (A)/(B)/(C)  
4) Keputusan hutang karyawan dalam profit: (A)/(B)/(C)/(D)  
5) Log/stacktrace bug tambah produk (untuk masuk ke jalur fix)

---