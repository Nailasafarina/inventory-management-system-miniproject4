-- ==============================================================
-- MINI PROJECT 3: SNACK CORNER (INVENTORY MANAGEMENT SYSTEM)
-- Database Schema & Initial Seed Data (Tema: Cemilan & Minuman)
-- ==============================================================

CREATE DATABASE IF NOT EXISTS inventory_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE inventory_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS product_suppliers;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Tabel Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabel User Profiles (1:1)
CREATE TABLE user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Tabel Categories (1:N)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Tabel Products (Master Produk Cemilan)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    minimum_stock INT NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 5. Tabel Suppliers
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. Tabel Product_Suppliers (Many-to-Many Junction)
CREATE TABLE product_suppliers (
    product_id INT NOT NULL,
    supplier_id INT NOT NULL,
    supplier_price DECIMAL(12,2) NULL,
    PRIMARY KEY (product_id, supplier_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Tabel Stock Movements (Audit Trail Mutasi Stok)
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('IN', 'OUT') NOT NULL,
    quantity INT NOT NULL,
    reference_note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Indexing
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_stock_movements_product ON stock_movements(product_id);
CREATE INDEX idx_stock_movements_type ON stock_movements(type);
CREATE INDEX idx_stock_movements_created ON stock_movements(created_at);

-- ==============================================================
-- SEED DATA TEMA CEMILAN & SNACK CORNER
-- Admin: admin / admin123
-- Staff: staff / staff123
-- ==============================================================

-- 1. Users
INSERT INTO users (id, username, password_hash, role) VALUES
(1, 'admin', '$2y$10$dseXXpIyLve2vrAbEE7ZcuOai4L6r/7P51qdPiQ.K0EfpJu/xCj.u', 'admin'),
(2, 'staff', '$2y$10$rvHlGcfp9q/c8deEkkEeP.97wehR5waXR/WWSE8Lx69qmuWKV546m', 'staff');

INSERT INTO user_profiles (user_id, full_name, email, phone) VALUES
(1, 'Manager Snack Corner', 'admin@inventory.com', '081234567890'),
(2, 'Staff Kasir & Gudang', 'staff@inventory.com', '081298765432');

-- 2. Categories Cemilan
INSERT INTO categories (id, name, description) VALUES
(1, 'Kue & Bakery (Cake)', 'Kue basah, cookies renyah, dan pastry lezat'),
(2, 'Keripik & Snack Gurih (Chips)', 'Cemilan gurih, keripik kentang, dan aneka kerupuk renyah'),
(3, 'Minuman Manis & Segar (Drinks)', 'Matcha latte, milk tea boba, dan minuman dingin segar'),
(4, 'Cemilan Siap Saji (Ready to Eat)', 'Sosis bakar, risoles mayo, dan frozen snack gurih');

-- 3. Suppliers
INSERT INTO suppliers (id, name, contact_person, phone, address) VALUES
(1, 'PT Dapur Cemilan Nusantara', 'Ibu Rahmawati', '021-5551234', 'Sentra Kuliner Blok C No. 12, Jakarta Selatan'),
(2, 'CV Sentosa Bakery & Pastry', 'Pak Budi Hartono', '021-5557890', 'Jl. Roti Kencana No. 8, Tangerang'),
(3, 'PT Sukses Minuman Segar', 'Rian Saputra', '021-5554321', 'Kawasan Industri Beverage Hijau Kav. 4, Bekasi');

-- 4. Products (Sesuai Screenshot Referensi Snack Corner)
INSERT INTO products (id, category_id, sku, name, price, stock, minimum_stock) VALUES
(1, 1, 'SNK-CKI-001', 'Chocolate Chip Cookies (Box 6pcs)', 28000.00, 25, 5),
(2, 1, 'SNK-MCH-002', 'Peanut Filled Mochi (Isi 4pcs)', 18000.00, 15, 5),
(3, 3, 'DRK-MTC-003', 'Iced Matcha Latte 250ml', 16000.00, 3, 5),       -- Low Stock!
(4, 4, 'SNK-RSL-004', 'Risoles Mayo Keju Beef (Isi 3pcs)', 21000.00, 20, 5),
(5, 4, 'SNK-SSG-005', 'Jumbo Grilled Sausage', 15000.00, 4, 5),               -- Low Stock!
(6, 2, 'SNK-KPK-006', 'Keripik Kentang Balado Premium 100g', 17500.00, 35, 8),
(7, 1, 'SNK-BWN-007', 'Fudgy Brownies Shiny Crust Mini', 24000.00, 2, 4),       -- Low Stock!
(8, 3, 'DRK-MLK-008', 'Brown Sugar Boba Milk Tea 250ml', 18500.00, 16, 5);

-- 5. Product Suppliers
INSERT INTO product_suppliers (product_id, supplier_id, supplier_price) VALUES
(1, 2, 22000.00),
(2, 1, 13000.00),
(3, 3, 11500.00),
(4, 1, 16000.00),
(5, 1, 11000.00),
(6, 1, 13000.00),
(7, 2, 18500.00),
(8, 3, 13500.00);

-- 6. Initial Stock Movements
INSERT INTO stock_movements (product_id, user_id, type, quantity, reference_note, created_at) VALUES
(1, 1, 'IN', 30, 'Penerimaan Stok Awal Dapur', NOW() - INTERVAL 7 DAY),
(1, 2, 'OUT', 5, 'Pesanan Catering Acara Kantor', NOW() - INTERVAL 5 DAY),
(2, 1, 'IN', 20, 'Produksi Fresh Batch Pagi', NOW() - INTERVAL 6 DAY),
(2, 2, 'OUT', 5, 'Penjualan Outlet Cabang', NOW() - INTERVAL 3 DAY),
(3, 1, 'IN', 8, 'Batch Minuman Botol Dingin', NOW() - INTERVAL 5 DAY),
(3, 2, 'OUT', 5, 'Pesanan Minuman Takeaway', NOW() - INTERVAL 1 DAY),
(4, 1, 'IN', 25, 'Stok Frozen Risoles Masuk', NOW() - INTERVAL 6 DAY),
(4, 2, 'OUT', 5, 'Penggorengan Display Etalase', NOW() - INTERVAL 2 DAY),
(5, 1, 'IN', 10, 'Pengadaan Sosis Dapur', NOW() - INTERVAL 4 DAY),
(5, 2, 'OUT', 6, 'Pesanan Bakar Snack Sore', NOW() - INTERVAL 1 DAY),
(6, 1, 'IN', 40, 'Pengiriman Supplier Snack Gurih', NOW() - INTERVAL 8 DAY),
(6, 2, 'OUT', 5, 'Distribusi Rak Display Depan', NOW() - INTERVAL 2 DAY),
(7, 1, 'IN', 6, 'Produksi Brownies Batch 1', NOW() - INTERVAL 3 DAY),
(7, 2, 'OUT', 4, 'Pesanan Paket Hampers', NOW() - INTERVAL 1 DAY),
(8, 1, 'IN', 20, 'Pengadaan Minuman Segar Botol', NOW() - INTERVAL 5 DAY),
(8, 2, 'OUT', 4, 'Penjualan Reguler Kasir', NOW() - INTERVAL 2 DAY);
