## BLUEPRINT v1 (Berbasis Fakta + Blocker) 🧩

### Modul A — Telegram Reports & Alerts (Extend Existing Pattern)

#### A1. Telegram Profit Harian (18:00 WITA, Senin–Sabtu)
- Buat UseCase baru:
  - `SendDailyProfitTelegramReportUseCase`
- Query data:
  - Memakai `ProfitReportQueryPort`
  - Input: `fromDate = toDate = clock->todayBusinessDate()` (mengikuti `ClockPort` yang sudah ada di project)
- Format pesan (ringkas):
  - revenue part, revenue service, rounding
  - cogs
  - expenses
  - payroll
  - net profit
  - missing_cogs_qty (jika perlu sebagai warning singkat)

**Catatan desain (belum diputuskan):**
- Opsi minimal: gunakan output aggregate existing dan format pesan berdasarkan tanggal hari ini (tanpa mengandalkan label period).
- Opsi rapi: extend query mendukung `daily` agar label & row harian jelas.

#### A2. Telegram Alert Purchase Invoice Jatuh Tempo H-5 (18:00 WITA, Senin–Sabtu)
- **Blocker**: tidak ada due date di schema.
- Setelah due date bisa dihitung:
  - Buat UseCase:
    - `SendPurchaseInvoiceDueSoonTelegramAlertUseCase`
  - Filter:
    - due_date = today + 5 hari
    - status belum lunas
  - Isi pesan:
    - judul alert
    - list: no_faktur, supplier_name, grand_total, due_date

#### A3. Telegram Profit Total
- **Blocker**: definisi “total” (all-time / bulan berjalan / range) dan trigger (jadwal / on-demand) belum ditentukan.
- Rekomendasi urutan aman:
  - buat command on-demand dulu: `php artisan report:profit-total --from= --to=`
  - baru dipasang scheduler setelah definisi disepakati.

---

### Modul B — Scheduling (Senin–Sabtu 18:00 WITA)
- Butuh lokasi schedule yang valid (umumnya `app/Console/Kernel.php`).
- Target schedule:
  - timezone: `Asia/Makassar`
  - jam: `18:00`
  - hari: Senin–Sabtu
- Rencana command:
  - `telegram:profit-daily`
  - `telegram:purchase-due-h5`

---

### Modul C — UI & Error Bahasa Indonesia (Global)
Target:
- Semua UI Indonesia
- Tidak ada popup validasi bawaan browser
- Tidak ada error page Laravel ke user (stacktrace/whoops)

Blueprint:
- Form:
  - gunakan `novalidate`
  - tampilkan error server-side inline (rapi)
- Validation message:
  - hardcode Indonesia di `FormRequest::messages()`
- Exception rendering:
  - tampilkan pesan Indonesia generik ke user
  - log detail internal (audit/log)
  - pastikan mode debug tidak bocor ke user (implement detail menunggu file handler & bootstrap)

---

### Modul D — Bug “Tidak bisa tambah produk”
- Input wajib:
  - potongan `storage/logs/laravel.log` atau stacktrace
  - controller + request + usecase + blade create product
- Output:
  - fix berbasis root-cause
  - test regresi (feature test create product)

---