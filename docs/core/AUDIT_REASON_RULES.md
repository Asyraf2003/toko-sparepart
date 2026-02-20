
# Audit Trail & Reason Rules — V1

Dokumen ini adalah **kontrak audit** untuk semua perubahan sensitif pada V1.

Referensi:
- `docs/core/BLUEPRINT_V1.md` (bagian AuditLog)
- `docs/core/WORKFLOW_V1.md` (Milestone 9)

---

## 1) Prinsip Audit (Wajib)
- Semua perubahan sensitif **harus** melalui UseCase agar audit konsisten.
- Aksi tertentu **wajib** `reason` (tidak boleh kosong).
- Audit menyimpan:
  - actor (user + role)
  - entity (type + id)
  - action (jenis aksi)
  - before/after (snapshot JSON)
  - metadata opsional (ip/user_agent jika tersedia)
- Audit tidak boleh menyimpan data rahasia yang tidak perlu (redaction bila ada).

---

## 2) Skema Data (Kontrak)

Minimal field yang harus ada pada `audit_logs`:
- `id`
- `actor_user_id`
- `actor_role`
- `entity_type`
- `entity_id`
- `action`
- `reason` (nullable, tapi **wajib** pada aksi tertentu)
- `before_json` (nullable)
- `after_json` (nullable)
- `created_at`
- `ip` (opsional)
- `user_agent` (opsional)

---

## 3) Aksi yang WAJIB Reason (Hard Rule)

### 3.1 Transaksi
- VOID transaksi (semua status yang diizinkan)
- EDIT transaksi (kasir hari yang sama)
- EDIT transaksi lama (admin override)
- Update service price pada transaksi yang sudah COMPLETED (hari yang sama)

### 3.2 Catalog & Inventory
- Set selling price (admin)
- Adjust stock (admin)
- Set min stock threshold (admin) — direkomendasikan reason (boleh wajib jika ingin lebih ketat)

### 3.3 Payroll/Loans (opsional ketat)
- Update loan outstanding manual (jika ada)
- Edit payroll line setelah finalisasi (kalau V1 mengizinkan)

---

## 4) Standar Action Names (Disarankan)
Gunakan string konstan untuk konsistensi:
- `CREATE`
- `UPDATE`
- `COMPLETE`
- `OPEN`
- `VOID`
- `PRICE_CHANGE`
- `STOCK_ADJUSTMENT`
- `OVERRIDE`
- `THRESHOLD_CHANGE`

---

## 5) Aturan Snapshot before/after

### 5.1 Kapan before/after diisi
- CREATE: `before_json = null`, `after_json = snapshot`
- UPDATE: `before_json = snapshot`, `after_json = snapshot`
- VOID: sebelum & sesudah (termasuk perubahan status dan efek penting)

### 5.2 Snapshot minimal transaksi
Untuk transaksi sensitif, snapshot sebaiknya mencakup:
- status, payment_status, payment_method
- totals (subtotals, rounding_amount, grand_total)
- part lines (product_id, qty, unit_sell_price_frozen, unit_cogs_frozen)
- service lines (description, price_manual)
- timestamps (opened_at/completed_at/voided_at)

---

## 6) Validasi Reason
- Reason dianggap valid jika:
  - string non-empty setelah trim
  - minimal panjang (misal >= 3 karakter) — optional, tapi direkomendasikan
- Jika reason wajib tapi kosong → tolak dengan error `ReasonRequired`.

---

## 7) Implementasi Praktik (Hexagonal)
- Application:
  - `AuditLoggerPort`
  - UseCase memanggil port setelah perubahan state siap disimpan/commit
- Infrastructure:
  - adapter menyimpan ke tabel `audit_logs` (Eloquent)
- Interfaces:
  - request validation untuk field `reason` pada endpoint yang membutuhkan

---

## 8) Testing (Minimal)
- Unit/Feature test:
  - aksi sensitif tanpa reason → gagal (422/409 sesuai mapping)
  - aksi sensitif dengan reason → sukses dan audit tercatat
  - audit punya before/after sesuai aksi