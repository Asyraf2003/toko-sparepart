# ADR-0013: Telegram Ops Bot via Webhook + Queue + Rate-Limit Retry

- **Date:** 2026-02-24
- **Status:** Accepted

## Context

APP KASIR membutuhkan kanal operasional via Telegram untuk:
- Monitoring hutang supplier (UNPAID), termasuk **H-5** dan **overdue**.
- Profit harian (Mon–Sat 18:00 WITA).
- Interaksi admin via bot (`/menu`, `/unpaid`, `/profit_latest`, `/pay`, dll).
- Workflow bukti bayar via upload file Telegram → review/approve di Web Admin.

Kebutuhan non-fungsional:
- **Reliability**: tidak boleh “silent drop” pada pesan/command.
- **Security**: webhook harus tervalidasi (secret header).
- **No spam**: scheduled notification harus idempotent.
- **Hexagonal boundary**: domain/usecase tidak bergantung ke Telegram library (via Port).

Kondisi implementasi di repo:
- Webhook endpoint: `POST /telegram/webhook` (routes/telegram.php).
- Secret header: `X-Telegram-Bot-Api-Secret-Token`.
- Queue digunakan untuk mengirim menu inline keyboard dan beberapa notifikasi.
- Dedup scheduled notification memakai `notification_states`.
- Bot pairing: `/link <TOKEN>` → `telegram_links` + token sekali pakai (`telegram_pairing_tokens`).
- Bot state machine: `telegram_conversations` untuk state `/pay`.
- Upload bukti bayar: `DownloadTelegramPaymentProofJob` → `telegram_payment_proof_submissions`.

## Decision

### 1) Webhook model (bukan polling)
- Telegram Bot menerima update hanya lewat **webhook** pada `POST /telegram/webhook`.
- Request harus memiliki header `X-Telegram-Bot-Api-Secret-Token` yang cocok dengan `services.telegram_ops.webhook_secret`.
- Jika secret tidak cocok atau kosong → return **403**.

**Alasan:**
- Webhook lebih sesuai enterprise: push-based, latency rendah, operasional sederhana.
- Secret header adalah kontrol minimal yang aman untuk mencegah spoof.

### 2) Queue wajib untuk jalur kirim tertentu (khususnya menu/inline keyboard)
- `SendTelegramMenuJob` berjalan di queue `notifications`.
- `SendTelegramMessageJob` dipakai untuk scheduled notifications (profit/due/overdue) dan mendukung idempotency.

**Konsekuensi operasional:**
- Karena menggunakan `QUEUE_CONNECTION=database`, maka tabel queue **wajib ada**:
  - `jobs`
  - `failed_jobs`
- Worker wajib berjalan:
  - `php artisan queue:work --queue=notifications --tries=3`

Tanpa worker atau tabel jobs, command seperti `/menu` bisa terlihat “diam” (job tidak dieksekusi / gagal insert).

### 3) Idempotency / no-spam untuk scheduled notifications
- Scheduled messages (profit daily, due H-5, overdue) menggunakan `notification_states.key` sebagai dedup.
- Dedup key format (contoh):
  - `profit_daily:<date>:<chatId>`
  - `purchase_due_digest:<sendDate>:<chatId>`
  - `purchase_overdue_digest:<date>:<chatId>`

Tujuan: scheduler boleh rerun tanpa double-send.

### 4) Rate limit handling (HTTP 429) harus retry, bukan silent drop
- Telegram API dapat mengembalikan **429 Too Many Requests** dengan `parameters.retry_after`.
- Kebijakan:
  - Pada 429, job **release(delay=retry_after)** sehingga retry sesuai saran Telegram.
  - Pada non-2xx selain 429, job melempar exception agar mekanisme retry queue berjalan dan error tidak “hilang”.

Target: tidak ada kondisi “worker DONE tapi pesan tidak terkirim”.

### 5) Command router & naming convention
Telegram command tidak mendukung spasi di nama command. Standar:
- Gunakan underscore: `/jatuh_tempo`, `/stok_menipis`, dll.
- Alias boleh untuk UX (`/menu` dan `/laporan`).

Command baseline:
- `/menu` atau `/laporan` → tampilkan inline keyboard menu.
- `/unpaid` → daftar supplier invoice UNPAID (top 20).
- `/jatuh_tempo` → daftar invoice UNPAID due H-5.
- `/overdue` → daftar invoice UNPAID overdue.
- `/stok_menipis` → daftar low stock (top N).
- `/produk <q>` → search produk (SKU/Nama), top N.
- `/profit_latest` → profit terakhir (daily) sesuai business_date terakhir.
- `/pay` → submit bukti bayar: state `AWAIT_INVOICE_NO` → `AWAIT_PROOF_UPLOAD`.
- `/link <TOKEN>` → pairing chat → user admin.

### 6) Local development policy
Untuk testing bot interaktif di lokal:
- Harus ada URL HTTPS publik (tunnel seperti ngrok/cloudflared), karena Telegram tidak dapat memanggil localhost.
- Webhook harus di-set ke `<PUBLIC_HTTPS_URL>/telegram/webhook` + `secret_token`.

## Alternatives Considered

1) **Polling (`getUpdates`)**
- Pro: bisa jalan tanpa tunnel.
- Kontra: perlu loop daemon, offset management, lebih rawan duplikasi, tidak enterprise friendly.
- Keputusan: ditolak.

2) **Synchronous send (tanpa queue) untuk semua**
- Pro: mudah, tidak perlu worker.
- Kontra: rentan timeout pada webhook, sulit retry/backoff, rate limit makin sering.
- Keputusan: ditolak.

3) **Third-party Telegram SDK/package**
- Pro: wrapper nyaman.
- Kontra: menambah dependency & boundary leak, tidak perlu untuk kebutuhan saat ini.
- Keputusan: ditolak.

## Consequences

### Positive
- Bot interaktif stabil, aman (secret header), scalable (queue).
- Retry rate limit terkontrol, mengurangi “silent failure”.
- Scheduled notifications aman dari spam via `notification_states`.

### Negative / Trade-offs
- Queue infra menjadi dependensi operasional (tabel jobs + worker).
- Local dev memerlukan tunnel untuk webhook.

## Implementation Notes

### Environment keys (minimum)
- `TELEGRAM_OPS_ENABLED=true`
- `TELEGRAM_OPS_BOT_TOKEN=...`
- `TELEGRAM_OPS_WEBHOOK_SECRET=...`
- `QUEUE_CONNECTION=database`
- Worker: `queue:work --queue=notifications`

### Scheduler (routes/console.php)
- Profit: Mon–Sat 18:00 WITA.
- Due H-5: daily at config `telegram_ops.purchase_due_reminder_time`.
- Overdue: daily at config `telegram_ops.purchase_overdue_reminder_time`.

### Observability
- Error/429 tercatat via log channel default.
- Failed jobs tercatat di `failed_jobs` (jika configured).

## Testing Strategy (DoD for this ADR)

- [ ] Webhook rejects invalid secret (403).
- [ ] `/menu` menghasilkan `SendTelegramMenuJob` dan menu muncul ketika worker aktif.
- [ ] `/profit_latest` tidak crash dan membalas pesan.
- [ ] Flood control 429 menyebabkan retry (job release) bukan silent drop.
- [ ] Scheduled profit/due/overdue tidak double-send pada rerun scheduler (dedup via `notification_states`).
- [ ] `/pay` flow: invoice no → upload → submission PENDING terbentuk → admin bisa review/approve/reject.

## Follow-ups
- Pastikan dokumentasi operasional (README internal): “Queue tables + worker wajib untuk Telegram ops”.
- Jika perlu, tambahkan throttling per-chat untuk menu untuk menurunkan risiko 429.