# Mini Project 3: Inventory Management System (InventPro)

Aplikasi manajemen persediaan barang berbasis **PHP Native (PDO)** dan **MySQL** yang dirancang dengan arsitektur bersih, aman, dan mematuhi seluruh kaidah perancangan basis data relasional serta keamanan aplikasi web modern.

---

## 🚀 Fitur Utama & Kepatuhan Rubrik

1. **Desain Basis Data Relasional & Integritas Data (30%)**:
   - **One-to-One**: `users` $\leftrightarrow$ `user_profiles` (FK + UNIQUE).
   - **One-to-Many**: `categories` $\leftrightarrow$ `products` (FK `category_id` dengan `ON DELETE RESTRICT`).
   - **Many-to-Many**: `products` $\leftrightarrow$ `suppliers` dijembatani oleh junction table `product_suppliers` dengan composite primary key `(product_id, supplier_id)`.
   - **Foreign Key Constraints**: Histori transaksi mutasi stok (`stock_movements`) menggunakan `ON DELETE RESTRICT` agar data audit tidak terhapus sembarangan saat master data diakses.

2. **Transaksi Stok Masuk & Keluar dengan ACID Transaction (20%)**:
   - Menggunakan `$pdo->beginTransaction()`, `$pdo->commit()`, dan `$pdo->rollBack()`.
   - Pencegahan Race Condition dengan **Pessimistic Row Locking** (`SELECT ... FOR UPDATE`).
   - **Validasi Stok Tidak Boleh Negatif**: Pengeluaran barang dicek secara semantik di sisi server; transaksi otomatis di-rollback jika kuantitas keluar melebihi stok fisik.

3. **Autentikasi & Otorisasi Berbasis Role (10%)**:
   - Login aman dengan `password_hash()` (Bcrypt) dan `password_verify()`.
   - Pencegahan Session Fixation dengan `session_regenerate_id(true)`.
   - Otorisasi di tingkat server (`require_role()`):
     - **Admin**: Akses penuh ke Manajemen Pengguna, Master Kategori, Master Produk, Master Supplier, Transaksi Stok, dan Laporan.
     - **Staff**: Hanya dapat melihat katalog produk dan mencatat transaksi stok masuk/keluar (akses menu manajemen user diblokir dengan HTTP 403 Forbidden).

4. **Pencarian, Filter, Sorting Allowlist & Pagination (10%)**:
   - Pencarian berdasarkan Nama Produk atau SKU (`LIKE :search`).
   - Filter dropdown dinamis berdasarkan Kategori.
   - Sorting aman dengan Whitelist Kolom (`sku`, `name`, `price`, `stock`, `category`, `created_at`) dan arah (`ASC`/`DESC`).
   - Pagination efisien (`LIMIT :limit OFFSET :offset`) dengan URL parameter tetap terjaga saat berpindah halaman.

5. **Dashboard & Laporan Agregat Bisnis (15%)**:
   - Indikator metrik ringkasan: Total Produk (`COUNT`), Total Valuasi Aset (`SUM(stock * price)`), Produk Stok Rendah/Kritis (`stock <= minimum_stock`), dan Total Mutasi Fisik Masuk vs Keluar.
   - Peringatan dini stok rendah secara real-time.
   - Rekapitulasi mutasi per produk menggunakan agregat bersyarat (`SUM(CASE WHEN type = 'IN' THEN quantity ELSE 0 END)`).
   - Ringkasan valuasi dan rata-rata harga per kategori (`AVG`, `SUM`, `GROUP BY`).
   - Fitur cetak laporan langsung (`window.print()`).

6. **Keamanan Aplikasi Berlapis (15%)**:
   - **Anti SQL Injection**: 100% query menggunakan Prepared Statements (PDO native, `PDO::ATTR_EMULATE_PREPARES => false`).
   - **Anti XSS**: Output encoding konsisten menggunakan `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
   - **Anti CSRF**: Validasi token CSRF berbasis sesi pada seluruh formulir POST.

---

## 📁 Struktur Folder Proyek

```
project-3/
├── config/
│   └── database.php         # Koneksi PDO singleton & konfigurasi DB
├── database/
│   └── schema.sql           # DDL Skema tabel, foreign keys, indeks, & seed data
├── helpers/
│   ├── auth.php             # Session, otentikasi, otorisasi role, flash message
│   ├── escape.php           # Anti-XSS (e()), format rupiah & tanggal
│   └── validation.php       # Validasi server, CSRF token, allowlist sorting
├── views/
│   ├── header.php           # Template navigasi dan layout atas
│   ├── footer.php           # Template footer dan script JS
│   └── errors/
│       └── 403.php          # Tampilan error akses ditolak (Forbidden)
├── public/
│   ├── index.php            # Entry point publik (redirector ke login/dashboard)
│   ├── login.php            # Halaman login dengan proteksi brute-force & CSRF
│   ├── logout.php           # Proses destroy sesi dan cookie secara aman
│   ├── dashboard.php        # Dashboard statistik & alert stok rendah
│   ├── products/            # CRUD Master Produk (Search, Filter, Sort, Paging)
│   ├── categories/          # CRUD Master Kategori (Agregat produk)
│   ├── suppliers/           # CRUD Master Supplier (Relasi many-to-many)
│   ├── stock/               # Transaksi Stok (in.php, out.php, history.php)
│   ├── reports/             # Laporan agregat inventory & valuasi
│   └── users/               # Manajemen Pengguna (Khusus Admin)
├── tests/
│   └── test_suite.php       # Skrip pengujian otomatis (Testing Suite)
├── index.php                # Root redirector menuju public/
└── README.md                # Dokumentasi sistem lengkap
```

---

## 🔑 Kredensial Login Bawaan (Seed Data)

| Role | Username | Password | Keterangan Hak Akses |
|---|---|---|---|
| **Admin** | `admin` | `admin123` | Akses penuh seluruh sistem termasuk kelola user |
| **Staff** | `staff` | `staff123` | Akses operasional stok & melihat katalog barang |

---

## 🛠️ Cara Menjalankan Proyek di XAMPP

1. **Jalankan XAMPP Control Panel**:
   - Start modul **Apache** dan **MySQL**.

2. **Import Database**:
   - Buka phpMyAdmin (`http://localhost/phpmyadmin/`) atau MySQL CLI.
   - Buat database `inventory_db` dan import file `database/schema.sql`.

3. **Buka Aplikasi di Browser**:
   - Akses: [http://localhost/project-3/](http://localhost/project-3/)
   - Sistem akan langsung mengarahkan Anda ke halaman login.

4. **Menjalankan Pengujian Otomatis**:
   ```bash
   php tests/test_suite.php
   ```
