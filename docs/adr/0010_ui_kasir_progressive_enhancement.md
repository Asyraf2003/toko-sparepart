# ADR-0010: UI Kasir (Kasir-First) dengan Progressive Enhancement, Native JS, dan Fragment Refresh

- **Status**: Accepted
- **Tanggal**: 2026-02-23
- **Konteks Project**: APP KASIR (Laravel, Hexagonal)

## Context

UI Kasir dibangun ulang dengan prinsip:
- **Kasir-first**: prioritas UI kasir sebelum admin.
- **Hexagonal boundary**: Blade/JS hanya untuk presentasi & UX; aturan bisnis tetap di UseCase.
- **Native JS only**: tidak memakai jQuery.
- **Progressive enhancement**: tanpa JS tetap jalan (form submit & navigasi normal).
- **Server sebagai source of truth**: semua mutasi transaksi melalui route/controller → UseCase.

Permasalahan yang ditemukan pada implementasi awal:
- Pencarian produk awalnya **JSON-only** sehingga tidak memenuhi kebutuhan “halaman + fetch”.
- Banyak aksi transaksi memerlukan reload penuh (UX lambat).
- Kalkulator cash dapat menampilkan nilai negatif setelah transaksi selesai (UX buruk).
- Void untuk transaksi completed sempat “mati” karena gating UI.
- Kode Blade menjadi panjang (JS inline) sehingga rawan “gendut” dan sulit dirawat.

## Decision

### D1. Shared Base Layout + Kasir Wrapper
- Kasir layout menggunakan **shared base** untuk fondasi (shared flash/footer/scripts).
- Kasir memiliki wrapper layout untuk sidebar kasir dan area konten.

### D2. /cashier/products/search mendukung mode A + B
Route `/cashier/products/search` mendukung:
- **A (Page HTML)**: bisa dibuka sebagai halaman pencarian produk.
- **B (Fragment HTML)**: dapat dipanggil oleh JS untuk mendapatkan HTML rows dan disisipkan ke container.
- **JSON compatibility**: tetap tersedia bila request mengharapkan JSON (`expectsJson`).

Implementasi:
- Parameter query untuk page dan transaksi:
  - `pq` (preferred untuk page/HTML)
  - `q` (legacy/compat)
- Fragment rows:
  - `fragment=rows` menghasilkan HTML `<tbody>` rows + header `X-Items-Count`.

### D3. Transaction Show: “No Refresh” via Fragment Refresh (HTML)
Pada halaman transaksi (`/cashier/transactions/{id}`):
- Semua aksi POST (add/update/delete part/service, open, customer save, void) diproses server seperti biasa (redirect).
- JS **intercept submit** dan melakukan:
  1) `fetch(POST)` ke endpoint yang sama,
  2) lalu **refresh fragment** melalui `GET ?fragment=1`,
  3) replace beberapa container UI saja (tanpa reload halaman).

Fragment yang di-refresh:
- alerts
- part lines
- service lines
- customer form
- cash calculator panel
- summary/actions

Catatan teknis:
- Script tidak diletakkan di dalam fragment HTML karena `innerHTML` tidak menjalankan `<script>`. Re-init dilakukan melalui fungsi JS di show page.

### D4. Today List: paginate(10) + JS navigation (tanpa refresh)
Pada halaman today (`/cashier/transactions/today`):
- Data diurutkan **terbaru → lama** (`orderByDesc('id')`).
- Pagination diubah menjadi `paginate(10)` dan query filter ikut terbawa (`withQueryString/appends`).
- JS intercept:
  - submit filter (status + q)
  - klik pagination
  - `popstate` back/forward
- Fetch fragment HTML via `?fragment=1` lalu replace container list.

### D5. Custom Pagination View (Reusable)
Dibuat custom pagination view `resources/views/vendor/pagination/mazer.blade.php` agar:
- markup konsisten,
- bisa dipakai kasir/admin,
- tetap memakai engine pagination Laravel.

Pemakaian:
- `{{ $rows->links('vendor.pagination.mazer') }}`

### D6. Format Rupiah konsisten via Blade Component
Dibuat Blade component:
- `resources/views/components/ui/rupiah.blade.php`
untuk konsistensi format angka rupiah (pemisah ribuan titik), dipakai pada:
- summary totals
- part line price/subtotal
- service price display
- cash calculator display (server-rendered)

### D7. Cash Calculator: gating by status/payment + UX non-negatif
Aturan UI cash calculator:
- Jika transaksi **PAID/COMPLETED**: tampil ringkasan pembayaran, kalkulator input disembunyikan.
- Jika transaksi **DRAFT**: kalkulator input disembunyikan; tampil instruksi bahwa pembayaran hanya saat OPEN.
- Jika transaksi **OPEN** dan belum paid: kalkulator aktif.
- Jika cash kurang, UI menampilkan:
  - kembalian tetap `0`
  - “Kurang: X” (tidak menampilkan minus)

### D8. Void untuk COMPLETED tetap tersedia + redirect ke Today
Void diaktifkan untuk status `DRAFT|OPEN|COMPLETED` (sesuai UseCase).
Perubahan UX:
- Setelah VOID berhasil, server mengarahkan ke `/cashier/transactions/today` dengan flash success.

Gating UI:
- Payment actions dimatikan saat paid/completed,
- Namun form VOID tetap tampil selama status bukan VOID.

### D9. “Degendutkan” Blade: pindah JS ke partial scripts
Untuk mengurangi JS inline yang panjang:
- show scripts dipindah ke `cashier/transactions/partials/_show_scripts.blade.php`
- today scripts dipindah ke `cashier/transactions/partials/_today_scripts.blade.php`
View utama tetap memanggil via `@push('scripts') @include(...)`.

## Consequences

### Positif
- UX kasir meningkat (minim reload, lebih cepat).
- Fallback non-JS tetap berjalan.
- Hexagonal boundary terjaga: JS hanya memanggil endpoint yang sama; business rules tetap di UseCase.
- Reusable pagination view untuk konsistensi.

### Negatif / Trade-off
- Ada kompleksitas tambahan: mode fragment dan mekanisme refresh.
- Perlu disiplin: fragment response tidak boleh mengandalkan `<script>` inline.
- Perlu re-init handler setelah fragment refresh (misalnya cash calculator).

## Alternatives Considered

1) **SPA penuh (Vue/React/Inertia)**
- Ditolak: bertentangan dengan target “Native JS only + progressive enhancement”, dan meningkatkan kompleksitas.

2) **Semua endpoint POST mengembalikan JSON**
- Ditunda: saat ini controller sudah return redirect; mengganti kontrak response lebih invasif.

3) **Hanya reload halaman setelah POST**
- Ditolak: UX terlalu lambat dan tidak sesuai target “no refresh” untuk kasir.

## Implementation Notes (Ringkas)

- Product search:
  - tambah page view + fragment rows + JSON mode.
- Today:
  - controller `paginate(10)` + `fragment=1` view partial.
  - JS intercept filter + pagination.
- Show:
  - controller support `fragment=1` view partial yang membungkus region.
  - JS intercept submit semua form terkait transaksi + refresh fragment.
  - “open” submission merge field customer agar terasa “barengan”.
- Cash calculator:
  - tampil berdasarkan status, dan re-init setelah fragment refresh.
- Void:
  - UI tetap tampil pada completed, controller redirect to today.

## Testing / DoD

- `phpunit` harus PASS.
- Smoke test kasir:
  1) Buat transaksi (DRAFT)
  2) Cari produk (JS + non-JS fallback)
  3) Add part/service (tanpa reload)
  4) Update qty & delete line (tanpa reload)
  5) Open (barengan simpan customer)
  6) Complete cash/transfer
  7) VOID transaksi completed (kasir) → redirect today
  8) Navigasi today pagination/filter tanpa refresh

## Related Files / Changes (Referensi)

- `resources/views/shared/layouts/base.blade.php` (csrf meta)
- `resources/views/vendor/pagination/mazer.blade.php`
- `resources/views/components/ui/rupiah.blade.php`
- `app/Interfaces/Web/Controllers/Cashier/ProductSearchController.php` (A+B + JSON)
- `app/Interfaces/Web/Controllers/Cashier/TransactionTodayController.php` (paginate + fragment)
- `app/Interfaces/Web/Controllers/Cashier/TransactionShowController.php` (fragment)
- `app/Interfaces/Web/Controllers/Cashier/TransactionVoidController.php` (redirect today)
- `resources/views/cashier/transactions/partials/_show_fragments.blade.php`
- `resources/views/cashier/transactions/partials/_today_list.blade.php`
- `resources/views/cashier/transactions/partials/_show_scripts.blade.php`
- `resources/views/cashier/transactions/partials/_today_scripts.blade.php`
- `resources/views/cashier/transactions/partials/_cash_calculator.blade.php` (status gating)
- `resources/views/cashier/transactions/partials/_summary_actions.blade.php` (VOID available on completed)
