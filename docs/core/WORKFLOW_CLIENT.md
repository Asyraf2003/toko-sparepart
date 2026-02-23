## WORKFLOW v1 (Langkah Eksekusi Berbasis Data) 🛠️

### Step 1 — Validasi Scheduler
Kirim isi file:
- `app/Console/Kernel.php`

### Step 2 — Validasi Konfigurasi Telegram
Kirim:
- potongan `config/services.php` untuk `telegram_low_stock`
- `.env` terkait:
  - `TELEGRAM_LOW_STOCK_ENABLED`
  - `TELEGRAM_LOW_STOCK_BOT_TOKEN` (boleh disensor)
  - `TELEGRAM_LOW_STOCK_CHAT_IDS` (chat_id harus valid)

### Step 3 — Putuskan Sumber “Jatuh Tempo” Purchase Invoice
Pilih salah satu:
- (A) tambah kolom `jatuh_tempo`
- (B) derivasi `tgl_kirim + N`
- (C) lainnya (definisikan)

### Step 4 — Putuskan “Hutang Karyawan” dalam Profit
Pilih salah satu:
- (A) pencairan loan mengurangi profit
- (B) cicilan/deduction payroll mengurangi profit
- (C) tidak masuk profit
- (D) lainnya

### Step 5 — Implementasi (setelah Step 1–4 lengkap)
- Tambah command + schedule
- Tambah usecase profit harian telegram
- Tambah usecase jatuh tempo telegram
- Extend profit query jika perlu (daily + employee loan inclusion)
- Implement UI/error Indonesia
- Fix bug tambah produk + test

---