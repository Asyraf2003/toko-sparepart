# Definition of Done (DoD)

### 1. Fungsionalitas (Functional)
- [ ] **Reminder H-5:** Terkirim otomatis untuk invoice unpaid dengan rule clamp +1 bulan yang akurat.
- [ ] **Overdue Reminder:** Terkirim jika melewati due date.
- [ ] **Profit Report:** Terkirim setiap jam 18:00 (Senin-Sabtu).
- [ ] **Pairing System:** User Admin berhasil melakukan pairing via token unik (bukan manual chat_id).
- [ ] **Bot Commands:** `/purchases_unpaid` dan `/profit_latest` menampilkan data yang valid.
- [ ] **Payment Submission:** Admin bisa upload bukti bayar via Telegram dan muncul di dashboard admin web dengan status `PENDING`.
- [ ] **Feedback Loop:** Notifikasi persetujuan/penolakan bukti bayar terkirim kembali ke user Telegram.

### 2. Standar Enterprise (Non-Functional)
- [ ] **Security:** Webhook terlindungi dengan Secret Token dan Rate Limiter.
- [ ] **Reliability:** Semua pengiriman pesan masuk ke Queue (antrean) dengan mekanisme Retry.
- [ ] **Idempotency:** Tidak ada pengiriman pesan ganda untuk invoice yang sama di hari yang sama (Anti-Spam).
- [ ] **Traceability:** Semua interaksi bot yang bersifat transaksional tercatat di Audit Log.
- [ ] **Privacy:** File bukti bayar disimpan di storage private, tidak dapat diakses publik secara langsung.

### 3. Kualitas Kode (Testing)
- [ ] **Unit Test:** Fungsi kalkulasi `due_date` lulus tes untuk kasus akhir bulan (Januari 30 ke Februari 28).
- [ ] **Unit Test:** Fungsi deteksi `overdue` akurat.
- [ ] **Feature Test:** Request webhook dari chat_id yang tidak ter-link ditolak secara otomatis.
- [ ] **Feature Test:** Token pairing hanya bisa digunakan satu kali (Single Use).