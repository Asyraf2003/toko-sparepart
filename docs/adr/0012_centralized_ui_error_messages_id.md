# ADR-0010: Sentralisasi Pesan Error UI (Bahasa Indonesia)

Status: **Accepted**  
Tanggal: **2026-02-23**  
Konteks: **APP KASIR (Laravel, Hexagonal Architecture)**

## Context

Saat ini beberapa controller menampilkan error ke user menggunakan:

- `->with('error', $e->getMessage())`
- atau `->withErrors(['error' => $e->getMessage()])`

Sementara itu, banyak UseCase (layer Application) melempar `\InvalidArgumentException('...')` dengan pesan berbahasa Inggris (legacy). Akibatnya:

1. **Pesan UI tidak konsisten** (campur Inggris/Indonesia).
2. **Kebocoran detail internal** (message domain/infra tampil mentah ke user).
3. Sulit dikontrol karena pesan tersebar di banyak file.

Kebutuhan: ada **1 folder / 1 titik kontrol** untuk pesan error (dan nantinya notifikasi) agar rapi dan konsisten, dengan target UI berbahasa Indonesia.

## Decision

Kita memperkenalkan **katalog pesan UI** terpusat, dengan aturan:

1. **Controller dilarang menampilkan `$e->getMessage()` langsung ke UI.**
2. Semua error user-facing wajib lewat **translator** terpusat:
   - `App\Shared\Messages\MessagesId::error(Throwable $e): string`
3. Translasi menggunakan pendekatan bertahap:
   - **Tahap 1 (minim perubahan):** mapping dari *legacy exception message* (Inggris) → pesan Indonesia.
   - **Tahap 2 (rapih jangka panjang):** gunakan *error code* (domain/application) → pesan Indonesia, mengurangi ketergantungan pada string message.

Dengan demikian, UseCase tetap fokus pada domain/application logic; UI layer bertanggung jawab menampilkan pesan yang tepat ke user.

## Scope

Termasuk:
- Flash error (`session('error')`) yang ditampilkan di Blade.
- `withErrors()` yang masuk ke `$errors` di Blade.

Tidak termasuk (sementara):
- Message yang dikirim ke Telegram notifier (low stock) dan log internal (tetap teknis).
- Copywriting umum UI (label, tombol) yang sudah berada di Blade (dibahas ADR terpisah jika diperlukan).

## Implementation

### 1) Tambah modul pesan terpusat

Lokasi:
- `app/Shared/Messages/MessagesId.php`

Tanggung jawab:
- Normalisasi message exception.
- Mapping legacy message → code → pesan Indonesia.
- Special-case untuk exception dinamis (contoh: `InsufficientStock`).
- Fallback aman: tidak membocorkan detail internal.

Contoh penggunaan (controller):

~~~php
use App\Shared\Messages\MessagesId;

try {
    // ...
} catch (Throwable $e) {
    return redirect($url)->with('error', MessagesId::error($e));
}
~~~

### 2) Refactor controller yang sebelumnya memakai `$e->getMessage()`

Aturan patch:
- Tambahkan import `use App\Shared\Messages\MessagesId;`
- Ganti:
  - `$e->getMessage()` → `MessagesId::error($e)`

Contoh (dari `->withErrors()`):

~~~php
return back()
  ->withInput()
  ->withErrors(['error' => MessagesId::error($e)]);
~~~

### 3) Blade tetap sederhana

Blade hanya menampilkan:
- `session('error')`
- `$errors`

Tidak ada logic translasi di Blade. Semua translasi dipastikan terjadi sebelum data masuk view.

## Consequences

### Positive ✅
- Pesan error UI menjadi **konsisten Bahasa Indonesia**.
- Mengurangi risiko **bocor detail internal**.
- Perubahan cepat: tidak perlu refactor besar semua UseCase pada tahap awal.
- Ada 1 titik kontrol untuk menambah/ubah pesan.

### Negative / Trade-offs ⚠️
- Tahap 1 masih bergantung pada *string message* legacy:
  - Jika message di UseCase berubah, mapping bisa gagal dan jatuh ke fallback.
- Butuh disiplin: semua controller baru harus mengikuti aturan tidak memakai `$e->getMessage()`.

## Alternatives Considered

1) **Langsung i18n penuh via Laravel `lang/id/*.php` + `__('key')`**
   - Pro: standar Laravel.
   - Kontra: butuh refactor lebih besar untuk memastikan domain error punya key/codes.

2) **Ubah semua UseCase agar lempar exception berbahasa Indonesia**
   - Pro: cepat terlihat di UI.
   - Kontra: domain/application jadi tercampur UI concern, sulit multi-lingual, raw message tetap bocor.

3) **Buat custom Exception bertipe DomainError dengan code**
   - Pro: desain ideal jangka panjang.
   - Kontra: lebih besar scope untuk tahap awal (migrasi banyak usecase).

Dipilih: **Tahap 1 translator terpusat**, lanjut **Tahap 2 domain code**.

## Migration Plan

### Tahap 1 (sekarang)
1. Tambah `MessagesId`.
2. Refactor semua controller yang menggunakan `$e->getMessage()` ke `MessagesId::error($e)`.
3. Pastikan error UI sudah Indonesia pada alur kasir & purchasing yang utama.

### Tahap 2 (incremental)
1. Perkenalkan exception berbasis **code** (misal `DomainError(code: 'TX_NOT_FOUND')`).
2. UseCase berhenti mengandalkan string message sebagai kontrak.
3. `MessagesId` menerima code, mapping code → pesan Indonesia.
4. Optional: integrasi ke `lang/id/*.php` bila diperlukan.

## Definition of Done (DoD)

1. Tidak ada lagi `$e->getMessage()` yang ditampilkan langsung ke UI.
   - Command:
     ~~~bash
     rg -n "\$e->getMessage\(\)" app/Interfaces/Web/Controllers
     ~~~
   - Hasil: **0 match** (atau minimal tidak digunakan untuk flash error UI).

2. Smoke test manual:
   - Kasir: tambah/hapus line, update qty, complete cash/transfer, void.
   - Purchasing: create purchase invoice dengan validasi gagal.
   - Semua pesan error di UI tampil **Bahasa Indonesia**.

3. Fallback aman:
   - Untuk error tak dikenal, UI menampilkan: **"Terjadi kesalahan. Silakan coba lagi."** (atau pesan fallback yang diputuskan).

## Notes

- Pesan error internal/log tetap teknis (untuk debugging), tetapi UI tidak menampilkan detail tersebut.
- Notifikasi low stock via Telegram tidak diubah dalam ADR ini; jika perlu, akan ada ADR terpisah untuk kebijakan bahasa notifikasi eksternal.