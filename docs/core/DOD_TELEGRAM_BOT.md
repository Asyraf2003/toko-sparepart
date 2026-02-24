# Definition of Done (DoD)

## 1. Functional Success
- [ ] **Due Date:** Invoice yang dibuat 31 Januari 2026 otomatis memiliki `due_date` 28 Februari 2026.
- [ ] **Reminder H-5:** Pesan terkirim tepat 5 hari sebelum jatuh tempo untuk invoice `UNPAID`.
- [ ] **Overdue:** Pesan terkirim setiap hari jika invoice melewati `due_date` dan masih `UNPAID`.
- [ ] **Profit:** Laporan profit harian sampai di Telegram Senin–Sabtu jam 18:00 (WITA).
- [ ] **Bot Interaction:** Admin bisa melihat list unpaid dan upload bukti bayar via bot.
- [ ] **Audit Trail:** Invoice yang di-approve via Web mencatat `paid_by_user_id` dan `paid_at` dengan benar.

## 2. Robustness & Security
- [ ] **No Spam:** Menggunakan `notification_states` sehingga tidak ada pesan duplikat meskipun scheduler dijalankan ulang.
- [ ] **Retry Mechanism:** Pesan yang gagal kirim (karena API Telegram down) akan dicoba kembali via Queue.
- [ ] **Webhook Security:** Hanya request dengan secret header valid yang diproses.
- [ ] **Privacy:** Bukti bayar disimpan di private storage, bukan public folder.

## 3. Code Quality
- [ ] **Hexagonal Integrity:** Domain logic tidak tergantung pada library Telegram (menggunakan Port).
- [ ] **Zero Assumptions:** Semua query filter menggunakan kolom yang sudah di-index (`due_date`, `payment_status`).
- [ ] **Tests:** Unit test untuk kalkulasi `due_date` dan Feature test untuk workflow approval lulus 100%.