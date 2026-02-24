# Workflow Implementasi Step-by-Step

> **Catatan:** Sesuai instruksi "No Asumsi", setiap langkah yang membutuhkan data akan meminta inspeksi data terlebih dahulu.

### Step 1: Snapshot & Audit Data (Wajib)
Sebelum memulai kode, lakukan inspeksi pada file berikut dan kirimkan isinya:
1.  `database/migrations/..._create_purchase_invoices_table.php`
2.  `app/Application/Ports/Repositories/ProfitReportQueryPort.php` (Jika sudah ada).
3.  `app/Infrastructure/Notifications/Telegram/TelegramLowStockNotifier.php` (Sebagai referensi pola yang sudah ada).
4.  Daftar Route/Controller Telegram yang sudah ada (Jika ada).

### Step 2: Schema & Persistence
Bentuk tabel database untuk mendukung fitur enterprise:
~~~php
// Migration untuk telegram_links, pairing_tokens, dan proof_submissions
~~~
Generalisasi tabel `notification_states` agar bisa menampung berbagai tipe key notifikasi.

### Step 3: Infrastruktur & Port Unifikasi
1.  Ekstrak `TelegramSenderPort`.
2.  Refactor `TelegramLowStockNotifier` agar menggunakan Port yang baru (DRY Principle).
3.  Implementasi `TelegramSender` menggunakan `Http::post`.

### Step 4: Scheduler & Idempotency
1.  Buat Job untuk pengiriman notifikasi dengan logic retry/backoff.
2.  Daftarkan di `routes/console.php` atau `App\Console\Kernel`.
3.  Gunakan `notification_states` untuk memastikan notifikasi tidak terkirim dua kali (Deduplication).

### Step 5: Webhook & Command Router
1.  Buat endpoint `POST /telegram/webhook`.
2.  Implementasi Middleware untuk verifikasi `X-Telegram-Bot-Api-Secret-Token`.
3.  Buat router sederhana untuk menangani command: `/start`, `/link`, `/purchases_unpaid`, `/profit_latest`, dan handle upload foto.

### Step 6: Admin Approval Flow (Opsi 1)
1.  Buat UI di Web Admin untuk melihat daftar `telegram_payment_proof_submissions`.
2.  Tombol Approve/Reject:
    * **Approve:** Ubah status invoice ke PAID + Kirim notifikasi balik ke Telegram.
    * **Reject:** Simpan alasan + Kirim notifikasi "Ditolak" ke Telegram.

### Step 7: Audit Log & Final Testing
1.  Pastikan semua aksi masuk ke tabel `audit_logs`.
2.  Jalankan Unit Test untuk kalkulasi tanggal (Clamp Februari).