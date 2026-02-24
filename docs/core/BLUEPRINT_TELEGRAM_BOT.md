# BLUEPRINT: Enterprise Telegram Integration & Purchase Payment System

## 1. Business Logic & Constraints (Fixed)
* **Due Date Calculation:** `tgl_kirim` + 1 bulan. 
    * *Constraint:* Wajib menggunakan `Carbon::addMonthNoOverflow()` (Calendar Clamp) untuk menangani akhir bulan (contoh: 31 Jan -> 28 Feb).
* **Due Reminder (H-5):** Trigger otomatis jika `today == due_date - 5`.
* **Overdue Reminder:** Trigger otomatis jika `today > due_date` AND `payment_status == 'UNPAID'`.
* **Daily Profit Schedule:** Senin–Sabtu pukul 18:00 (Timezone: `Asia/Makassar`).

## 2. Data Model Requirements (Audit-Ready)
### A. Purchase Invoices (Alteration)
| Column | Type | Index | Description |
| :--- | :--- | :--- | :--- |
| `due_date` | Date | Yes | Persisted for query efficiency |
| `payment_status` | Enum/String | Yes | `UNPAID`, `PAID` |
| `paid_at` | Datetime | No | Nullable |
| `paid_by_user_id` | BigInt | No | FK to users (Audit Trail) |

### B. New Support Tables
1.  **`telegram_links`**: Menghubungkan `user_id` dengan `chat_id` Telegram.
2.  **`telegram_pairing_tokens`**: Token sekali pakai untuk proses linking admin.
3.  **`telegram_payment_proof_submissions`**: Storage metadata untuk bukti bayar (Status: `PENDING`, `APPROVED`, `REJECTED`).
4.  **`notification_states`**: Idempotency table untuk mencegah spam/double-send.

## 3. Architecture (Hexagonal Boundaries)
### A. Ports (Interfaces)
~~~php
interface TelegramSenderPort {
    public function sendMessage(string $chatId, string $text): void;
    public function sendDocument(string $chatId, mixed $file, string $caption): void;
}

interface PurchaseInvoiceQueryPort {
    public function getUnpaidInvoices(array $filters): array;
    public function getInvoicesDueInDays(int $days): array;
}

interface ProfitReportQueryPort {
    // Extend for 'daily' support
    public function getReport(string $granularity, \DateTimeInterface $date): ProfitDTO;
}
~~~

### B. Use Cases
* `SendPurchaseDueH5TelegramUseCase`
* `SendPurchaseOverdueTelegramUseCase`
* `SendDailyProfitTelegramUseCase`
* `HandleTelegramWebhookUpdateUseCase` (Bot Interaction)
* `SubmitPaymentProofViaTelegramUseCase` (Opsi 1)

## 4. Bot Interactive Model
- **Command Router:** Bot mendeteksi command (`/purchases_unpaid`, `/profit_latest`, `/pay <no_faktur>`).
- **State Machine:** Untuk `/pay`, user masuk ke state "Awaiting Upload". Upload dokumen memicu perubahan status ke `PENDING`.
- **Inline Keyboards:** Menu pilih untuk daftar invoice unpaid.