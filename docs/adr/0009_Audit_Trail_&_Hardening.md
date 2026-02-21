# ADR-0009: Audit Trail & Hardening (Reason Required)

- **Status:** Accepted
- **Date:** 2026-02-22
- **Scope:** Sales, Catalog, Inventory, Admin UI

## Context

Proyek **Laravel Kasir Bengkel V1** membutuhkan jejak audit untuk setiap perubahan sensitif agar:

- Perubahan bisa ditelusuri (actor, waktu, entity, reason).
- Tersedia bukti **before/after snapshot** untuk investigasi operasional.
- Sistem tetap konsisten saat UI akan mengalami perubahan besar (refactor/rewrite), tanpa melanggar boundary Hexagonal dan kontrak audit.

Dokumen rujukan kontrak:
- `docs/core/ARCHITECTURE_HEXAGONAL.md`
- `docs/core/AUDIT_REASON_RULES.md`

## Decision

1) Mengaktifkan **audit trail terpusat** melalui **Port & Adapter** (Hexagonal):
- Application mendefinisikan `AuditLoggerPort`
- Infrastructure mengimplementasikan adapter penyimpanan ke tabel `audit_logs`

2) Menetapkan **Reason Required** untuk semua aksi sensitif (hard rule) dan menolak request tanpa reason (defense-in-depth):
- Controller (Interfaces) memvalidasi input `reason`
- UseCase (Application) tetap melakukan trim + reject jika kosong

3) Menyimpan audit log berisi:
- actor (`actor_id`, `actor_role`)
- entity (`entity_type`, `entity_id`)
- action (string konsisten)
- reason (string, non-empty pada aksi sensitif)
- before/after snapshot (JSON)
- metadata opsional (JSON) termasuk `ip`, `user_agent` bila tersedia

4) Kebijakan edit transaksi (hardening policy):
- **COMPLETED tidak boleh diedit** (harus VOID/batal).
- **CASHIER** hanya boleh mengedit transaksi dengan `business_date == today`.
- **ADMIN** boleh mengedit transaksi `DRAFT/OPEN` lintas business_date (admin override opsi-1), namun tetap tidak boleh edit `COMPLETED`.

5) Menyediakan UI Admin untuk audit viewer:
- List + filter by actor/entity/action/date
- Detail view untuk `before/after/meta`

## Schema / Storage

Tabel `audit_logs` menyimpan (minimal):
- `actor_id` (nullable)
- `actor_role` (nullable)
- `entity_type` (string)
- `entity_id` (nullable)
- `action` (string)
- `reason` (string)
- `before` (json nullable)
- `after` (json nullable)
- `meta` (json nullable, termasuk ip/user_agent jika tersedia)
- `created_at`

Catatan: `ip` dan `user_agent` disimpan di `meta` untuk menjaga skema minimal dan fleksibel.

## Action Names (Konsistensi)

Untuk konsistensi analisis audit:
- `VOID` — void transaksi
- `PRICE_CHANGE` — perubahan harga jual
- `STOCK_ADJUSTMENT` — penyesuaian stok
- `UPDATE` — edit transaksi (tambah/hapus/ubah part/service lines, dsb)
- (Opsional future) `OVERRIDE` — bila nanti perlu memisahkan event admin override secara eksplisit

## Aksi Sensitif yang Di-audit (V1)

### Sales / Transaction
- Void transaksi (reason wajib, before/after wajib)
- Edit transaksi (DRAFT/OPEN):
  - add part line (reason wajib)
  - update part qty (reason wajib)
  - delete/remove part line (reason wajib)
  - add service line (reason wajib)
  - update service line (reason wajib)
  - delete service line (reason wajib)

### Catalog
- Set selling price (reason wajib: menggunakan field `note` pada request)

### Inventory
- Adjust stock (reason wajib: menggunakan field `note` pada request)

## Implementation Notes (Hexagonal)

### Ports & Adapters
- **Application Port:** `App\Application\Ports\Services\AuditLoggerPort`
- **Domain DTO:** `App\Domain\Audit\AuditEntry`
- **Infrastructure Adapter:** `App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditLogger`
- Binding DI dilakukan di `AppServiceProvider`

### UseCase Instrumentation
Setiap UseCase sensitif harus:
1) Ambil snapshot `before` (entity utama + bagian yang relevan).
2) Lakukan mutasi state.
3) Ambil snapshot `after`.
4) `audit->append(new AuditEntry(...))` dalam transaksi yang sama (agar atomik).

### Interfaces Layer Rule
Controller tidak boleh melakukan mutasi bisnis/DB langsung untuk aksi sensitif.
Controller hanya:
- validasi input (termasuk `reason`)
- panggil UseCase
- mapping response/redirect

## Admin Audit Viewer

Routing admin menyediakan:
- `GET /admin/audit-logs` — list + filter
- `GET /admin/audit-logs/{auditLogId}` — detail (before/after/meta)

Filter minimal:
- actor (name/email contains) / actor_id
- entity_type / entity_id
- action
- date_from / date_to

## Testing

Minimal tests yang diwajibkan:
- Aksi sensitif tanpa reason => ditolak (422/InvalidArgumentException sesuai mapping)
- Aksi sensitif dengan reason => sukses dan audit tercatat
- Audit row memiliki before/after yang relevan

Catatan: Setelah menerapkan `reason required` pada add/edit lines, feature tests UI harus mengirim field `reason` pada POST endpoint terkait.

## Consequences

### Positif
- Traceability operasional meningkat: siapa mengubah apa, kapan, dan kenapa.
- Mendukung investigasi stock discrepancy, dispute transaksi, serta audit internal.
- UI refactor besar bisa dilakukan tanpa mengorbankan compliance: selama UI tetap memanggil UseCase dan mengirim `reason`, audit trail tetap konsisten.

### Negatif / Trade-offs
- Payload snapshot bisa membesar (JSON before/after). Perlu disiplin snapshot “relevan” (bukan dump tak terbatas).
- Developer harus konsisten menjaga aturan: jangan kembali mutasi DB di Controller.

## Alternatives Considered

1) Audit via DB trigger
- Ditolak: sulit menyimpan reason yang bermakna dan menyelaraskan dengan domain rules; menyulitkan boundary Hex.

2) Audit log parsial (tanpa before/after)
- Ditolak: tidak cukup untuk kebutuhan investigasi dan audit trail yang kuat.

## Notes untuk Perubahan UI Besar Berikutnya

Perubahan UI boleh besar (layout, UX, flow), namun kontrak berikut **tidak boleh dilanggar**:
- Semua aksi sensitif tetap melalui UseCase.
- UI wajib menyediakan `reason` untuk aksi sensitif.
- `COMPLETED` tidak boleh diedit; gunakan VOID untuk pembatalan.
- Audit Viewer admin harus tetap tersedia (atau dipindah tapi fungsinya tetap ada).

## References
- `docs/core/ARCHITECTURE_HEXAGONAL.md`
- `docs/core/AUDIT_REASON_RULES.md`
- Milestone 9: Audit Trail & Hardening (Reason Required)