# Blueprint: Sistem Notifikasi & Bot Telegram Enterprise (Purchasing & Profit)

## A. Aturan Bisnis (Fixed - Zero Assumption)
* **Anchor:** `tgl_pengiriman` (Delivery Date).
* **Due Date:** `tgl_pengiriman` + 1 bulan dengan *Calendar Clamp* (Opsi A).
    * Contoh: `15/06` → Due `15/07`.
    * Contoh: `30/01/2026` → Due `28/02/2026` (Clamp ke hari terakhir Februari).
* **Reminder H-5:** `notify_at = due_date - 5 hari` (Tetap -5 meskipun di bulan Februari).
* **Overdue Reminder:** Jika `today > due_date` DAN `invoice status != PAID` → Kirim reminder sesuai jadwal.
* **Implementasi:** Rule wajib berupa *Pure Function* dengan *Unit Test* untuk menjamin stabilitas.

## B. Scope & Akses
* **Otoritas:** Notifikasi push dan bot interaktif hanya untuk user dengan role **Admin**.
* **Keamanan:** Bot hanya melayani user yang sudah melalui proses *Pairing* (terhubung ke `user_id` admin), bukan berdasarkan `chat_id` yang di-hardcode.

## C. Kanal Telegram
1.  **Push Scheduled (Outbound):**
    * Purchase Due H-5 (Reminder jatuh tempo).
    * Purchase Overdue (Reminder keterlambatan).
    * Profit Harian: Jam 18:00 (Senin–Sabtu).
2.  **Bot Interaktif (Inbound/Pull):**
    * ` /purchases_unpaid `: Daftar invoice supplier belum lunas.
    * ` /profit_latest `: Profit harian terakhir.
    * ` /pay <no_faktur> `: Submit bukti bayar (Opsi 1: Upload bukti).

## D. Komponen Hexagonal Architecture
### 1. Application Ports
* `TelegramSenderPort`: Mengirim teks dan dokumen/gambar.
* `TelegramUpdateReceiverPort`: Menangani payload webhook.
* `PurchaseInvoiceQueryPort`: Mengambil data invoice unpaid, due date, dan status.
* `PaymentProofRepositoryPort`: Menyimpan metadata bukti bayar yang diupload via bot.

### 2. Use Cases
* `SendPurchaseInvoiceDueRemindersUseCase`
* `SendPurchaseInvoiceOverdueRemindersUseCase`
* `SendDailyProfitTelegramReportUseCase`
* `HandleTelegramWebhookUpdateUseCase`
* `LinkTelegramChatUseCase` (Proses pairing admin ↔ chat id).
* `SubmitPaymentProofViaTelegramUseCase` (Opsi 1).

### 3. Infrastructure
* `Infrastructure/Notifications/Telegram/TelegramSender`: Implementasi HTTP client ke Telegram API.
* `Interfaces/Web/Controllers/TelegramWebhookController`: Endpoint penerima webhook.
* `Persistence/Eloquent`: Tabel link, pairing tokens, submissions, dan idempotency states.

## E. Model Data (Enterprise Minimum)
* `telegram_links`: Mapping `user_id` ke `chat_id`.
* `telegram_pairing_tokens`: Token sekali pakai (OTP) untuk proses pairing.
* `telegram_payment_proof_submissions`: Log upload bukti bayar (Status: `PENDING`, `APPROVED`, `REJECTED`).
* `notification_states`: Tabel Idempotency untuk mencegah spam notifikasi jika job di-retry.
    * Key: `purchase_due:{invoiceId}:{notifyAtYmd}`
    * Key: `purchase_overdue:{invoiceId}:{businessDateYmd}`

## F. Penjadwalan (Scheduling)
* **Profit Harian:** 18:00 WITA (Senin–Sabtu).
* **Reminders:** Configurable `TELEGRAM_REMINDER_DAYS` (Default: 1-6 / Senin-Sabtu).

## G. Audit & Compliance
* Setiap aksi bot yang mengubah data wajib mencatat **Audit Log**.
* Webhook wajib menggunakan **Secret Token Header** dan **Rate Limiting (Throttle)**.