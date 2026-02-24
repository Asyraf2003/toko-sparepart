# WORKFLOW: Implementation Roadmap

## Step 1: Schema & Contract Update (Database & Port)
* **Goal:** Mempersiapkan pondasi data tanpa menyentuh business logic inti.
* **Tasks:**
    1.  Buat Migration untuk `purchase_invoices` (add `due_date`, `payment_status`, dkk).
    2.  Buat Migration untuk tabel pendukung (links, tokens, submissions, notification_states).
    3.  Update Interface `ProfitReportQueryPort` agar mendukung granularitas `daily`.

## Step 2: Compute & Maintain Due Date (Source of Truth)
* **Goal:** Memastikan data `due_date` selalu valid sejak transaksi dibuat.
* **Tasks:**
    1.  Modifikasi `CreatePurchaseInvoiceUseCase` dan `UpdatePurchaseInvoiceHeaderUseCase`.
    2.  Implementasi `Carbon::addMonthNoOverflow()` pada logic `due_date`.
    3.  Set default `payment_status = UNPAID`.

## Step 3: Generic Telegram Sender (Unified Path)
* **Goal:** Sentralisasi pengiriman pesan agar bisa di-retry dan di-queue.
* **Tasks:**
    1.  Implementasi `TelegramSenderPort` menggunakan HTTP client ke Telegram Bot API.
    2.  Refactor notifier low stock (jika ada) ke sender ini.

## Step 4: Scheduler, Queue, & Dedup (Reliability)
* **Goal:** Otomasi pengiriman pesan tanpa duplikasi.
* **Tasks:**
    1.  Daftarkan cron/scheduler (Daily Profit: 18:00, Reminders: Configurable).
    2.  Implementasi pengecekan tabel `notification_states` sebelum pengiriman.

## Step 5: Webhook Bot & Interactive Commands
* **Goal:** Admin dapat berinteraksi dengan sistem via Telegram.
* **Tasks:**
    1.  Setup endpoint Webhook (Secret validation).
    2.  Implementasi command `/link`, `/purchases_unpaid`, `/profit_latest`, `/pay`.

## Step 6: Approval Workflow (Admin Web)
* **Goal:** Melengkapi Opsi 1 (Review Bukti Bayar).
* **Tasks:**
    1.  Buat UI Daftar Submission `PENDING`.
    2.  Tombol Approve/Reject yang mengupdate status invoice dan memicu notifikasi balik ke Telegram.

## Step 7: Future-ready Opsi 2 (Direct Pay)
* **Goal:** Menyediakan jalur bypass jika diaktifkan (config-based).
* **Tasks:**
    1.  Implementasi `MarkPurchaseInvoicePaidViaTelegramUseCase`.