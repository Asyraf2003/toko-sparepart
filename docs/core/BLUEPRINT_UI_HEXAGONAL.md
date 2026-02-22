# Blueprint UI Blade (Kasir-First) — APP KASIR (Hexagonal)

Dokumen ini adalah pegangan resmi untuk membangun ulang UI dari 0 secara **terstruktur**:
1) Analisis routes → 2) Pemetaan 1–1 halaman/aksi → 3) Implementasi UI + Native JS (fungsi dulu) → 4) Styling Mazer di tahap akhir.

---

## 0) Keputusan Final (Berdasarkan Jawaban User)

1) `/cashier/products/search` bersifat **A + B**:
   - Bisa dibuka sebagai halaman yang menampilkan hasil pencarian.
   - Juga dipakai oleh Native JS untuk menampilkan hasil tanpa reload (progressive enhancement).

2) Interaksi UI:
   - Gunakan **Native JS saja** (tanpa jQuery).

3) Mekanisme aksi kasir:
   - Pilihan **B**: boleh AJAX/fetch untuk add/update/delete line & search agar UX cepat.
   - Fallback tetap tersedia: tanpa JS harus tetap bisa jalan (form submit biasa).

4) Format tampilan uang:
   - Rupiah dengan pemisah ribuan titik: contoh `15.000`, `200.000`.

5) Layout:
   - Gunakan layout **shared** (satu fondasi layout untuk admin & kasir).

6) Prioritas implementasi:
   - **Kasir dulu** (lebih sedikit, fokus input transaksi).

---

## 1) Prinsip Hexagonal untuk UI (yang wajib dipatuhi)

### 1.1 Peran tiap layer (bahasa sederhana)
- **Blade (Tampilan)**: render data + form, tidak mengandung logika bisnis inti.
- **Controller Web (Adapter)**: terima request → panggil UseCase → return view/redirect/response.
- **UseCase (Application)**: aturan bisnis, perhitungan, validasi bisnis, perubahan state.
- **Repository/DB**: akses data di bawah kontrak yang sudah ada.

### 1.2 Batasan “No Bocor”
- Blade/JS tidak memutuskan aturan bisnis final (misal rounding/total final).
- JS hanya untuk UX (preview, auto-fill, update tampilan), server tetap sumber kebenaran final.
- Semua perubahan transaksi dilakukan via route/controller → UseCase.

---

## 2) Inventaris Routes Kasir (Source of Truth)

### 2.1 GET (Halaman)
- `/cashier/dashboard`
- `/cashier/transactions/today`
- `/cashier/transactions/{transactionId}`
- `/cashier/products/search`  ✅ (halaman + dipakai fetch)
- `/cashier/transactions/{transactionId}/work-order`

### 2.2 POST (Aksi)
- `/cashier/transactions` (buat transaksi)
- `/cashier/transactions/{transactionId}/open`
- `/cashier/transactions/{transactionId}/complete-cash`
- `/cashier/transactions/{transactionId}/complete-transfer`
- `/cashier/transactions/{transactionId}/void`
- `/cashier/transactions/{transactionId}/part-lines`
- `/cashier/transactions/{transactionId}/part-lines/{lineId}/qty`
- `/cashier/transactions/{transactionId}/part-lines/{lineId}/delete`
- `/cashier/transactions/{transactionId}/service-lines`
- `/cashier/transactions/{transactionId}/service-lines/{lineId}/update`
- `/cashier/transactions/{transactionId}/service-lines/{lineId}/delete`

---

## 3) Pemetaan 1–1: Route → Halaman → Elemen UI Pemanggil Aksi

Bagian ini harus dipenuhi agar “semua fitur muncul di UI”.

### 3.1 `/cashier/dashboard`
Konten minimum:
- Ringkasan sederhana (hari ini / shortcut).
Aksi UI:
- Link ke “Transaksi Hari Ini”.

### 3.2 `/cashier/transactions/today`
Konten minimum:
- Daftar transaksi hari ini (tabel/list).
Aksi UI:
- Tombol “Buat Transaksi Baru” → POST `/cashier/transactions`
- Link tiap transaksi → GET `/cashier/transactions/{transactionId}`

### 3.3 `/cashier/transactions/{transactionId}` (Halaman inti)
Konten minimum:
- Status transaksi (draft/open/complete/void sesuai data yang dikirim controller).
- Panel input customer (jika ada) + daftar part lines + daftar service lines.
- Ringkasan total (subtotal part/service, rounding, total, paid, change/kembalian jika cash).

Aksi UI yang wajib ada:
- Open: POST `/cashier/transactions/{id}/open`
- Add part: POST `/cashier/transactions/{id}/part-lines`
- Update qty part: POST `/cashier/transactions/{id}/part-lines/{lineId}/qty`
- Delete part: POST `/cashier/transactions/{id}/part-lines/{lineId}/delete`
- Add service: POST `/cashier/transactions/{id}/service-lines`
- Update service: POST `/cashier/transactions/{id}/service-lines/{lineId}/update`
- Delete service: POST `/cashier/transactions/{id}/service-lines/{lineId}/delete`
- Complete cash: POST `/cashier/transactions/{id}/complete-cash`
- Complete transfer: POST `/cashier/transactions/{id}/complete-transfer`
- Void: POST `/cashier/transactions/{id}/void`
- Work Order: GET `/cashier/transactions/{id}/work-order`

Catatan penting:
- Semua tombol ini harus “nyambung” ke route yang tepat.
- Tanpa JS: semuanya harus bisa lewat form submit standar.
- Dengan JS: enhancement (fetch) untuk UX.

### 3.4 `/cashier/products/search` (A + B)
Fungsi:
- Menampilkan hasil search produk.
- Dipakai JS untuk update hasil search pada halaman transaksi.

Kontrak UI yang disepakati:
- Bisa dibuka sebagai halaman (misal dengan query `?pq=...`).
- Untuk JS, fetch ke route yang sama dan hasilnya diambil sebagai HTML untuk dimasukkan ke container hasil.

### 3.5 `/cashier/transactions/{transactionId}/work-order`
Konten:
- Tampilan WO siap print (HTML sederhana).

---

## 4) Fondasi Layout Shared + Sidebar Stabil

### 4.1 Target struktur layout
- Satu layout shared: `resources/views/shared/layouts/base.blade.php`
- Halaman kasir “mengisi” area konten dan menyertakan sidebar kasir.
- Sidebar kasir tetap pada semua halaman kasir.

### 4.2 Single-source menu
- `resources/views/shared/partials/_sidebar_menu.blade.php` boleh menjadi “pegangan”.
- Implementasi kasir minimal:
  - `cashier/partials/_sidebar_menu.blade.php` memanggil shared atau berisi menu kasir sendiri.
- Dashboard kasir adalah landing page setelah login kasir.

---

## 5) Strategi Native JS (Progressive Enhancement) — Kasir

### 5.1 Prinsip
- UI harus jalan walau JS mati (form biasa).
- JS menambah:
  - pencarian cepat produk (fetch ke `/cashier/products/search`)
  - update qty inline (fetch)
  - delete line tanpa reload (fetch)
  - kalkulator cash (preview kembalian)
  - loading/error state

### 5.2 Kontrak minimum untuk JS
- Tambahkan CSRF meta di layout shared:
  - `<meta name="csrf-token" content="{{ csrf_token() }}">`
- Elemen penting diberi `data-*`:
  - `data-endpoint="..."`
  - `data-transaction-id="..."`
  - `data-line-id="..."`

### 5.3 Format Rupiah
Aturan UI:
- Tampilkan angka dengan format `15.000` (pemisah ribuan titik).
- JS helper:
  - `formatRupiah(value)` untuk display.
  - `parseRupiah(text)` untuk input → number (hapus titik).

Catatan:
- Penyimpanan internal angka (integer/decimal) tidak diputuskan di Blade/JS.
- JS hanya memformat tampilan dan mengirim nilai numerik sesuai kebutuhan form.

### 5.4 Perhitungan
- Preview total/kembalian di JS boleh.
- Total final/rounding final **harus** sesuai nilai server (render ulang/response payload).
- Jika JS melakukan preview, maka setelah aksi sukses (add/update/delete), UI harus:
  - (opsi) refresh bagian ringkasan dari server response, atau
  - (opsi) reload halaman jika ingin paling aman.

---

## 6) Workflow Implementasi (Kasir Dulu)

Workflow ini dibuat agar pekerjaan rapi, bisa diulang, dan tidak “lompat-lompat”.

### Fase 0 — Inventaris “Route → UI”
Output:
- Tabel pemetaan: setiap route punya lokasi UI pemanggil.
Checklist:
- [ ] Semua GET kasir ada entry point (menu/link)
- [ ] Semua POST kasir ada tombol/form yang memanggilnya

### Fase 1 — Layout shared + sidebar kasir permanen
Output:
- `shared/layouts/base.blade.php` menjadi fondasi.
- Halaman kasir meng-extend layout shared (atau layout kasir wrapper yang tetap memakai shared base).
- Sidebar kasir tidak berubah ketika pindah halaman.
Checklist:
- [ ] Sidebar kasir tampil di dashboard/today/transaction/work-order
- [ ] Flash message/alert area tersedia di layout

### Fase 2 — Bangun UI Kasir minimal (fungsi dulu, raw HTML)
Urutan:
1) Dashboard
2) Today list
3) Transaction show (inti)
4) Work order
5) Products search page (render hasil)

Checklist per halaman:
- [ ] GET 200 OK
- [ ] Link dan tombol mengarah ke route benar
- [ ] Form submit berhasil (redirect + flash)

### Fase 3 — Lengkapi semua aksi transaksi (POST) dari UI
Target:
- Di transaction show: semua aksi transaksi tersedia.
Checklist:
- [ ] Create transaksi berjalan dari today/dashboard
- [ ] Add part/service berjalan
- [ ] Update qty part berjalan
- [ ] Update service line berjalan
- [ ] Delete line berjalan
- [ ] Complete cash/transfer berjalan
- [ ] Void berjalan
- [ ] Work order bisa dibuka

### Fase 4 — Native JS enhancement (fetch) + fallback aman
Prioritas:
1) Search produk cepat:
   - Input search → fetch `/cashier/products/search?pq=...`
   - Hasil HTML dimasukkan ke container hasil.
2) Update qty inline:
   - Tombol +/- atau input qty → fetch POST qty endpoint.
3) Delete line:
   - Konfirmasi → fetch POST delete endpoint.
4) Cash calculator:
   - Input uang diterima → preview kembalian.

Checklist:
- [ ] Tanpa JS: form biasa tetap bisa
- [ ] Dengan JS: UX lebih cepat
- [ ] Error fetch tampil (tidak silent)
- [ ] Loading state jelas (disable tombol saat request)

### Fase 5 — Stabilkan (Tes + Smoke)
Output:
- UI stabil, tidak ada link mati, tidak ada aksi yang “diam”.
Checklist:
- [ ] `phpunit` PASS
- [ ] Smoke test kasir 1 skenario lengkap:
  - buat transaksi → tambah item → update qty → complete → work-order

### Fase 6 — Styling Mazer (terakhir)
Output:
- Class/style Mazer diterapkan tanpa merusak fitur.
Checklist:
- [ ] Setelah styling, semua tombol/route tetap jalan
- [ ] Layout responsif minimum (sidebar + konten)

---

## 7) Definition of Done (DoD) — Final untuk Milestone UI Kasir

### 7.1 DoD Global (Kasir)
- [ ] Semua GET kasir bisa diakses dari UI (menu/link)
- [ ] Semua POST kasir punya pemanggil UI (tombol/form)
- [ ] Transaction show menjadi “pusat transaksi” yang lengkap
- [ ] Native JS enhancement aktif untuk search + (minimal) 1 aksi (update qty atau delete)
- [ ] Format rupiah tampil konsisten (contoh: `15.000`)
- [ ] Error handling rapi:
  - validasi form tampil
  - error fetch tampil
  - flash message tampil
- [ ] Gate kualitas:
  - [ ] `phpunit` PASS
  - [ ] Smoke test kasir end-to-end PASS

### 7.2 DoD per Halaman (Template)
- [ ] Halaman terbuka (200 OK)
- [ ] Judul/heading jelas
- [ ] Navigasi kembali/aksi utama tersedia
- [ ] Form punya CSRF + action benar
- [ ] Error validasi muncul di UI
- [ ] Sukses → redirect + flash sukses

### 7.3 DoD khusus Transaction Show
- [ ] Semua aksi transaksi tersedia
- [ ] Tidak ada “dead button”
- [ ] JS enhancement tidak memutus fallback
- [ ] Ringkasan total selalu sinkron dengan server (setelah aksi)

---

## 8) Matriks Pengujian Minimal (Kasir)

### 8.1 Skenario E2E (wajib)
- [ ] Login kasir
- [ ] Buka dashboard
- [ ] Buat transaksi baru
- [ ] Cari produk (search)
- [ ] Tambah part line
- [ ] Tambah service line
- [ ] Update qty part line
- [ ] Delete salah satu line
- [ ] Complete cash (cek kembalian) atau transfer
- [ ] Buka work order

### 8.2 Smoke navigasi
- [ ] Dashboard → Today → Transaction show → Work order (tanpa error)

---

## 9) Catatan Operasional (Biar Tidak Balik “Berantakan”)
- Setiap selesai 1 modul/halaman → langsung smoke test.
- Jangan styling sebelum semua tombol “nyambung”.
- Hindari duplikasi menu: sidebar single-source.
- Jika ada perubahan aturan bisnis, lakukan di UseCase, bukan di Blade/JS.

---

## 10) Log Perubahan
- 2026-02-22: Draft awal dibuat dari daftar routes & tree views.
- 2026-02-22: Finalisasi keputusan (A+B search, native JS, fetch allowed, rupiah format, shared layout, kasir-first).