# Stock Reservation + Ledger Rules (Anti Minus) — V1

Dokumen ini adalah **kontrak stok** untuk memastikan:
1) stok tidak pernah minus,
2) kasus OPEN (drop pagi) bisa mengunci sparepart tanpa mengurangi on_hand sampai completed,
3) semua perubahan stok punya jejak di ledger.

Referensi:
- `docs/core/BLUEPRINT_V1.md` (InventoryStock + StockLedger + rules)
- `docs/core/WORKFLOW_V1.md` (Milestone 3 & 4)

---

## 1) Istilah & Invariant (Hard Rules)

### 1.1 Field utama
- `on_hand_qty`: stok fisik tersimpan
- `reserved_qty`: stok yang dikunci oleh transaksi DRAFT/OPEN
- `available_qty = on_hand_qty - reserved_qty`

### 1.2 Invariant (wajib selalu benar)
- `on_hand_qty >= 0`
- `reserved_qty >= 0`
- `available_qty >= 0`
- `reserved_qty <= on_hand_qty` (implikasi dari available >= 0)

---

## 2) Ledger (Wajib)

### 2.1 Tipe ledger minimal
- `PURCHASE_IN`   (+) menambah on_hand
- `SALE_OUT`      (-) mengurangi on_hand
- `VOID_IN`       (+) mengembalikan on_hand untuk transaksi COMPLETED yang di-VOID
- `ADJUSTMENT`    (+/-) koreksi stok (admin only)
- `RESERVE`       (+) menambah reserved
- `RELEASE`       (-) mengurangi reserved

### 2.2 Field ledger minimal
- `product_id`
- `type`
- `qty_delta`
- `ref_type`, `ref_id` (transaction/purchase_invoice)
- `occurred_at`
- `actor_user_id`
- `note` (opsional, reason untuk adjustment/void)

---

## 3) Rules by Action

## 3.1 Tambah Part Line pada DRAFT/OPEN
Input: `product_id`, `qty` (qty > 0)

1) Hitung `available = on_hand - reserved`
2) Jika `available < qty` → REJECT (InsufficientStock)
3) Apply:
   - `reserved += qty`
   - ledger: `RESERVE (+qty)`

## 3.2 Update Qty Part Line pada DRAFT/OPEN
Delta:
- `delta = new_qty - old_qty`

Case A: delta > 0 (tambah)
- cek `available >= delta`
- `reserved += delta`
- ledger: `RESERVE (+delta)`

Case B: delta < 0 (kurang)
- `reserved -= abs(delta)`
- ledger: `RELEASE (-abs(delta))`

## 3.3 Remove Part Line pada DRAFT/OPEN
- `reserved -= old_qty`
- ledger: `RELEASE (-old_qty)`

---

## 4) Complete Transaction
Saat transaksi berubah ke `COMPLETED`:

Untuk tiap part line qty = Q:
1) `on_hand -= Q`
2) `reserved -= Q`
3) ledger:
   - `SALE_OUT (-Q)`
   - `RELEASE (-Q)`
4) Freeze COGS:
   - `unit_cogs_frozen = avg_cost_current` saat completion

Catatan:
- Untuk transaksi cash: apply rounding NEAREST_1000 dan simpan `rounding_amount`.

---

## 5) Void Transaction

## 5.1 Void transaksi COMPLETED
Untuk tiap part line qty = Q:
1) `on_hand += Q`
2) ledger: `VOID_IN (+Q)`
3) (Tidak ada reserved pada transaksi completed; reserved sudah release saat complete)

Reason: wajib.

## 5.2 Void transaksi DRAFT/OPEN
Untuk tiap part line qty = Q:
1) `reserved -= Q`
2) ledger: `RELEASE (-Q)`

Reason: wajib.

---

## 6) Concurrency (Wajib ada proteksi)
Agar tidak terjadi race condition (double reserve), implementasi reserve/release/complete/void harus:
- Berjalan dalam DB transaction (TransactionManagerPort)
- Lock row inventory (misal `SELECT ... FOR UPDATE` pada `inventory_stocks` untuk `product_id`)
- Validasi ulang `available` setelah lock

---

## 7) Test Cases Minimal (Wajib)
- Reserve gagal bila `available < qty`
- Reserve sukses menambah reserved dan ledger RESERVE tercatat
- Release sukses mengurangi reserved dan ledger RELEASE tercatat
- Complete:
  - on_hand turun sesuai qty
  - reserved turun sesuai qty
  - ledger SALE_OUT & RELEASE tercatat
  - `unit_cogs_frozen` terisi
- Void completed:
  - on_hand naik
  - ledger VOID_IN tercatat
- Void draft/open:
  - reserved turun
  - ledger RELEASE tercatat
- Invariant `available >= 0` selalu benar setelah setiap aksi