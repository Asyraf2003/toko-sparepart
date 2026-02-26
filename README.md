# APP KASIR

Sistem Point of Sale (POS) + Manajemen Operasional Bengkel berbasis Laravel dengan arsitektur Hexagonal (Ports & Adapters), dirancang untuk skalabilitas, auditability, dan kontrol bisnis yang ketat.

---

# 🎯 Tujuan Proyek

APP KASIR dibangun untuk:

- Menjadi **source of truth transaksi bengkel**
- Mengontrol arus kas, stok, dan pembelian
- Menyediakan reporting yang konsisten & dapat diaudit
- Siap dikembangkan ke skala multi-branch / cloud-native
- Memiliki boundary arsitektur yang jelas (tidak spaghetti code)

---

# 🚀 Keunggulan (Advantages)

## 1️⃣ Arsitektur Hexagonal (Ports & Adapters)

- Business logic terisolasi di Application Layer
- Controller hanya sebagai adapter (bukan tempat logika bisnis)
- Mudah testing (unit & feature)
- Mudah migrasi storage / transport layer (HTTP → API → Worker → dsb)
- Mengurangi coupling antar layer

---

## 🛡️ Data Integrity & Security

- **Pessimistic Locking:** Menggunakan `FOR UPDATE` di level database untuk mencegah race condition pada stok.
- **Transactional Consistency:** Seluruh rangkaian mutasi (Transaction -> Stock -> Ledger -> Audit) dibungkus dalam Database Transaction; gagal satu, batal semua.
- **Operational Guarding:** Validasi Business Date untuk mencegah manipulasi data historis oleh user dengan role tertentu.

---

## 2️⃣ Audit Trail & Governance

- Perubahan transaksi memerlukan reason (controlled mutation)
- Struktur audit-friendly
- Error envelope konsisten
- Debug route bisa digate via environment

Cocok untuk:
- Bisnis yang butuh kontrol internal
- Persiapan skala enterprise
- Compliance internal

---

## 3️⃣ Inventory Integrity

- Ledger-based stock tracking
- COGS tercatat dari pembelian
- Mutasi stok hanya lewat use case
- Tidak ada manipulasi stok langsung di controller

Menghindari:
- Stok minus misterius
- Selisih tidak terlacak
- Ketidaksesuaian laporan

---

## 4️⃣ Modular & Expandable

Dirancang dengan modul terpisah:

- Sales
- Purchasing
- Inventory
- Payroll
- Expenses
- Reporting
- Telegram Integration

Mudah ditambah:
- Multi outlet
- API mobile
- Integrasi payment gateway
- Integrasi marketplace

---

## 5️⃣ Reporting Siap Produksi

- Sales Report (HTML + PDF)
- Summary & detail terstruktur (DTO based)
- Data konsisten dari domain layer
- Bisa dikembangkan ke dashboard BI

---

## 6️⃣ Telegram Notification Integration

- Low stock alert
- Purchase notification
- Ops webhook support
- Dapat dikontrol via config

Cocok untuk:
- Owner yang ingin monitoring real-time
- Alert jatuh tempo pembelian
- Kontrol stok cepat

---

## 7️⃣ UI Kasir Fokus Efisiensi

- Kasir-first design
- Native JS (progressive enhancement)
- Format rupiah konsisten (15.000)
- Search produk support page + fetch
- Shared layout base

---

# 📦 Fitur Utama (Core Features)

## 🛒 Sales (Transaksi)

- Buat transaksi
- Tambah sparepart
- Tambah jasa
- Perhitungan subtotal part & service
- Pembulatan (rounding)
- Status pembayaran
- Detail nota
- Today transaction view
- Audit edit transaksi (same-day control)

---

## 📦 Inventory

- Master produk
- Harga jual aktif
- Average cost tracking
- Inventory stock table
- Stock ledger history
- Low stock alert

---

## 🧾 Purchasing

- Buat purchase invoice
- Multiple line items
- Due date support
- Status: PAID / UNPAID
- Integrasi ke inventory (COGS update)
- Seed data untuk dev testing

---

## 💰 Expenses

- Input pengeluaran
- Tercatat sebagai biaya operasional

---

## 👥 Payroll (Basic)

- Periode gaji
- Employee loan
- Kontrol payroll period
- Edit periode

---

## 📊 Reporting

- Sales report by period
- Summary & row DTO
- Export PDF
- Business-date based reporting

---

## 🔔 Notification

- Telegram low stock alert
- Telegram purchase event
- Config-based enable/disable

---

# 🧱 Struktur Arsitektur

Layer utama:

- Domain
- Application (Use Cases)
- Infrastructure
- Interfaces (Web Controller)
- Database (Eloquent sebagai adapter persistence)

Boundary penting:

- Controller tidak boleh berisi logika bisnis
- Semua mutasi melalui UseCase
- Presenter sebagai output formatter
- DTO untuk transfer data

---

# 🧪 Testing Coverage

- Feature tests
- UseCase tests
- Inventory integrity tests
- Validation tests
- HTTP sanity checks

Semua perubahan besar harus hijau sebelum merge.

---

# 🛠 Tech Stack

- Laravel
- PHP 8+
- MySQL
- Blade
- Native JS
- Telegram Bot API
- PDF generation (reporting)

---

# 🧠 Filosofi Desain

- Kasir adalah pusat arus kas
- Inventory harus akurat secara matematis
- Pembelian mempengaruhi COGS
- Reporting harus berasal dari domain, bukan query liar
- Perubahan transaksi harus dapat dipertanggungjawabkan

---

# 🔮 Roadmap Potensial

- Multi-branch
- Role-based granular permission
- Dashboard analytics
- API versioning
- Queue-based notification
- Cloud deployment (AWS ready)
- Automated scheduled reports

---

# 📌 Cocok Untuk

- Bengkel motor / mobil
- Workshop kecil-menengah
- Bisnis sparepart
- Operasional berbasis kasir
