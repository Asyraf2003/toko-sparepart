# ADR 0008 — Telegram Low Stock Alert (Anti-Spam Throttle)

- Status: **Accepted**
- Date: **2026-02-21**
- Scope: **Inventory / Notifications**
- Milestone: **8 — Telegram Low Stock Alert**

## Context

V1 membutuhkan notifikasi stok menipis ke Telegram ketika `available_qty <= min_stock_threshold`, tanpa spam walaupun terjadi banyak event stok (reserve/release/sale/purchase/adjust/void).

Arsitektur proyek menggunakan pendekatan **Hexagonal (Ports & Adapters)**:
- Application layer memegang policy (rule throttle, kapan kirim).
- Infrastructure layer mengurus integrasi eksternal (Telegram Bot API).

Kebutuhan non-fungsional:
- **Tidak boleh mengganggu transaksi/stok** bila Telegram gagal (fail-safe).
- **Throttle** per product agar tidak mengirim berulang dalam window tertentu.
- Kredensial Telegram harus disimpan aman (env/config), bukan hard-coded.

## Decision

1) Tambah **Port**:
- `App\Application\Ports\Services\LowStockNotifierPort`

2) Tambah **Adapter Telegram** (Infrastructure):
- `App\Infrastructure\Notifications\Telegram\TelegramLowStockNotifier`
- Mengirim pesan via Telegram Bot API `sendMessage` menggunakan Laravel HTTP client.
- Fail-safe: error/timeout tidak melempar exception yang membatalkan transaksi.

3) Tambah **UseCase policy** untuk low-stock evaluation + throttle:
- `App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase`
- Tanggung jawab:
  - Ambil snapshot `products` + `inventory_stocks`
  - Hitung `available = on_hand_qty - reserved_qty`
  - Cek threshold (per product: `min_stock_threshold`)
  - Cek throttle state
  - Panggil port notifier jika eligible

4) Tambah **Throttle state table** per product:
- `low_stock_notification_states`:
  - `product_id` (unique)
  - `last_notified_at` (nullable)
  - `last_notified_available_qty` (nullable)
  - timestamps

5) Trigger pemanggilan notify dilakukan **setelah transaction commit**:
- Usecase yang memutasi stok memanggil notify setelah `$this->tx->run(...)` selesai.

## Trigger Points

Notifikasi dipicu setelah event stok berikut:
- `PURCHASE_IN`  (CreatePurchaseInvoiceUseCase)
- `RESERVE`      (ReserveStockUseCase)
- `RELEASE`      (ReleaseStockUseCase, VoidTransactionUseCase, CompleteTransactionUseCase)
- `SALE_OUT`     (CompleteTransactionUseCase)
- `VOID_IN`      (VoidTransactionUseCase untuk transaksi COMPLETED)
- `ADJUSTMENT`   (AdjustStockUseCase)

## Throttle Rules (Anti-Spam)

Kriteria low stock:
- Kirim bila: `available_qty <= threshold` (threshold = `products.min_stock_threshold`)

Throttle per product:
- Default interval: **24 jam** (`min_interval_seconds = 86400`)
- Eligible untuk kirim jika:
  1) `last_notified_at` masih null, atau
  2) `now - last_notified_at >= min_interval_seconds`, atau
  3) Kondisi makin kritis: `available_qty < last_notified_available_qty` (bypass interval)

Recovery policy:
- Jika `available_qty > threshold` maka throttle state **di-reset** (hapus row) ketika `reset_on_recover = true`.
  - Tujuan: ketika stok turun lagi di masa depan, alert dapat terkirim segera.

Failure policy:
- Jika Telegram gagal (timeout/HTTP error), sistem tetap melakukan throttle update bila `throttle_on_failure = true`.
  - Tujuan: mencegah burst spam saat Telegram pulih.

## Failure Handling

- Adapter Telegram tidak melempar exception ke caller (fail-safe).
- Error dicatat via `Log::warning(...)` untuk observability.
- Inventory mutation tetap sukses walaupun Telegram down.

## Configuration

Semua konfigurasi Telegram ditempatkan di `config/services.php`:

- `services.telegram_low_stock.enabled`
- `services.telegram_low_stock.bot_token`
- `services.telegram_low_stock.chat_ids` (comma-separated)
- `services.telegram_low_stock.min_interval_seconds` (default 86400)
- `services.telegram_low_stock.reset_on_recover` (default true)
- `services.telegram_low_stock.throttle_on_failure` (default true)

Env vars yang digunakan:
- `TELEGRAM_LOW_STOCK_ENABLED=true|false`
- `TELEGRAM_LOW_STOCK_BOT_TOKEN=...`
- `TELEGRAM_LOW_STOCK_CHAT_IDS=1312692550[,<another_chat_id>]`
- `TELEGRAM_LOW_STOCK_MIN_INTERVAL_SECONDS=86400`
- `TELEGRAM_LOW_STOCK_RESET_ON_RECOVER=true`
- `TELEGRAM_LOW_STOCK_THROTTLE_ON_FAILURE=true`

Security:
- Token bot Telegram wajib disimpan di `.env` dan **tidak** di-commit.
- Jika token pernah terekspos, harus dilakukan rotate (revoke + generate token baru).

## Testing

- Feature test throttle:
  - `tests/Feature/Inventory/LowStockAlertThrottleTest.php`
  - Memastikan:
    - Alert terkirim saat `available <= threshold`
    - Re-trigger dalam interval tidak mengirim ulang
    - Jika available turun lebih rendah, alert boleh terkirim meski belum 24 jam

- Smoke test integrasi:
  - Verifikasi `LowStockNotifierPort` resolve ke `TelegramLowStockNotifier`
  - Kirim pesan via port dan pastikan masuk ke chat ID target

## Alternatives Considered

1) Menyimpan throttle di kolom `products`:
- Pro: sederhana
- Kontra: mencampur concern katalog dengan state notifikasi, lebih sulit audit/extend

2) Menggunakan tabel `stock_alert_events` (append-only):
- Pro: histori lengkap
- Kontra: lebih mahal query, butuh agregasi untuk menentukan last state

3) Men-trigger via DB-level hook / observer Eloquent:
- Pro: otomatis
- Kontra: sulit menjamin after-commit behavior lintas usecase/transaction boundary; hexagonal boundary lebih lemah

Keputusan akhir: **tabel state** + **usecase policy** + **port/adapter**.

## Consequences

Positive:
- Anti-spam deterministik per product dengan interval 24 jam
- Integrasi Telegram terisolasi pada adapter (swap-able)
- Fail-safe: transaksi tidak terganggu

Trade-offs:
- Ada tabel tambahan untuk throttle state
- Ada tambahan dependency injection (port + adapter) di beberapa usecase

## Implementation Notes (References)

Files utama:
- Port: `app/Application/Ports/Services/LowStockNotifierPort.php`
- DTO: `app/Application/DTO/Notifications/LowStockAlertMessage.php`
- UseCase: `app/Application/UseCases/Notifications/NotifyLowStockForProductUseCase.php`
- Adapter: `app/Infrastructure/Notifications/Telegram/TelegramLowStockNotifier.php`
- Migration: `database/migrations/*_create_low_stock_notification_states_table.php`
- Config: `config/services.php`
- Provider binding: `app/Providers/AppServiceProvider.php`

Trigger integration:
- `app/Application/UseCases/Inventory/ReserveStockUseCase.php`
- `app/Application/UseCases/Inventory/ReleaseStockUseCase.php`
- `app/Application/UseCases/Inventory/AdjustStockUseCase.php`
- `app/Application/UseCases/Purchasing/CreatePurchaseInvoiceUseCase.php`
- `app/Application/UseCases/Sales/CompleteTransactionUseCase.php`
- `app/Application/UseCases/Sales/VoidTransactionUseCase.php`

## Follow-ups (Milestone 9 readiness)

- Pertimbangkan audit log khusus untuk “notification send attempt” jika dibutuhkan observability lebih tinggi.
- Jika kebutuhan multi-tenant / multi-outlet muncul, chat_id perlu dipetakan per outlet.
- Jika volume tinggi, pertimbangkan queue (async) + retry policy terkontrol (tetap menjaga after-commit semantics).