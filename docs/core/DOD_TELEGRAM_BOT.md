# Definition of Done (DoD) - Telegram Integration

## DoD-0: Arsitektur & Kontrak (Foundational)
- [ ] Seluruh logic bisnis berada di **UseCase** dan **Domain Policy**, bukan di Controller/Webhook.
- [ ] Integrasi Telegram menggunakan `NotifierPort` (Abstraksi Infrastructure).
- [ ] Pengambilan data laporan menggunakan `QueryPort` (Bukan raw query di bot handler).

## DoD-1: Reminder Jatuh Tempo (P0)
- [ ] **Logic**: `DueDatePolicy` menangani tgl 30/31 ke bulan yang lebih pendek (Februari/April).
- [ ] **Filter**: Invoice `PAID` tidak muncul meskipun `notify_date` sesuai.
- [ ] **Idempotency**: Tabel `purchase_due_notification_states` mencegah spam jika job di-restart.
- [ ] **Test**: Unit test meng-cover tahun kabisat dan akhir bulan.

## DoD-2: Daily Profit Push (P0)
- [ ] **Schedule**: Berjalan otomatis Mon-Sat 18:00 WITA.
- [ ] **Format**: Angka menggunakan format Rupiah standar (Contoh: `15.000.000`).
- [ ] **Audit**: Tercatat sebagai `NOTIFICATION_SENT` dengan payload total summary.

## DoD-3: Telegram Bot & Security (P1)
- [ ] **Security**: Webhook memvalidasi `secret_token` dan `allowlist_user_id`.
- [ ] **Audit**: User tidak dikenal yang mencoba command dicatat sebagai `TELEGRAM_DENIED`.
- [ ] **Paging**: Command `/purchases` membatasi output max 10 item per chat.