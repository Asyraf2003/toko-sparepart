# Workflow: Telegram Automation & Notification System
Version: 1.0
Status: FINAL
Audit Requirement: ZERO ASSUMPTION / DATA-DRIVEN

## 1. Konsep & Aturan Inti (Business Logic)

### A. Purchase Invoice Reminder
Setiap invoice supplier memiliki siklus notifikasi sebagai berikut:
* **Perhitungan Due Date**: 
    ~~~
    due_date = delivery_date + 1 bulan
    ~~~
    *Aturan Khusus:* Jika tanggal tidak tersedia (misal: 31 Jan -> 31 Feb), maka otomatis menjadi hari terakhir bulan tersebut (28/29 Feb).
* **Perhitungan Notify Date**:
    ~~~
    notify_date = due_date - 5 hari
    ~~~
* **Filter Status**: Notifikasi **WAJIB** hanya dikirim untuk status `UNPAID` atau `PARTIAL`. Status `PAID` dilarang memicu notifikasi.

### B. Daily Profit Report
* **Jadwal Rutin**: Senin – Sabtu, Jam 18:00 WITA.
* **On-Demand**: Bot harus merespon command `/profit now` secara instan.

### C. Payment Status
* **Source of Truth**: Status `PAID` diubah manual di sistem internal.
* **Bot Role**: Hanya sebagai Read-Only Viewer dan Request Generator (Pull/Push).

---

## 2. Operasional Terjadwal (Scheduler)

### A. Push Profit Harian
1. Scheduler memanggil `SendDailyProfitSummaryUseCase(businessDate=today)`.
2. Query ringkasan via `ProfitReportQueryPort`.
3. Validasi Idempotency: Cek tabel `daily_profit_notification_states` (mencegah duplikasi).
4. Kirim ke Telegram Allowlist.

### B. Push Reminder Jatuh Tempo
1. Scheduler jalan setiap hari jam 09:00 WITA.
2. Memanggil `NotifyPurchaseInvoicesDueUseCase(today)`.
3. Query invoice dengan kriteria: `status IN (UNPAID, PARTIAL)` AND `notify_date == today`.
4. Validasi Idempotency per `invoice_id` untuk tanggal terkait.

---

## 3. Bot Command (Pull Mechanism)

Sistem merespon perintah berikut dengan validasi `allowlist_user_id`:

| Command | Deskripsi | Parameter |
| :--- | :--- | :--- |
| `/profit` | Profit hari ini | - |
| `/profit [date]` | Profit tanggal spesifik | `YYYY-MM-DD` |
| `/purchases unpaid` | Daftar invoice belum lunas | Paging support |
| `/purchases due` | Invoice mendekati jatuh tempo | <= 7 hari & Overdue |
| `/help` | Daftar bantuan | - |

---

## 4. Audit Trail (Mandatory)
Setiap interaksi dicatat ke Audit Log sesuai ADR:
1.  **NOTIFICATION_SENT**: Triggered by System (Scheduler).
2.  **TELEGRAM_COMMAND**: Triggered by User (Command sukses).
3.  **TELEGRAM_DENIED**: Security event (User tidak terdaftar).