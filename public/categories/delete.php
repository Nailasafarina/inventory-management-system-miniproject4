<?php
/**
 * Mini Project 3: Inventory Management System
 * Hapus Kategori (Khusus Admin)
 *
 * Menerapkan:
 * - Referential Integrity (Slide 9):
 *   Tabel categories dilindungi oleh FOREIGN KEY RESTRICT dari tabel products.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_role('admin');

$id = validate_int($_GET['id'] ?? null, 1);
if (!$id) {
    set_flash('danger', 'ID Kategori tidak valid.');
    header('Location: ' . url('categories/index.php'));
    exit;
}

$stmt = $pdo->prepare('SELECT id, name FROM categories WHERE id = :id');
$stmt->execute(['id' => $id]);
$category = $stmt->fetch();

if (!$category) {
    set_flash('danger', 'Kategori tidak ditemukan.');
    header('Location: ' . url('categories/index.php'));
    exit;
}

// Cek apakah ada produk di bawah kategori ini
$stmtCount = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
$stmtCount->execute(['id' => $id]);
$productCount = (int) $stmtCount->fetchColumn();

if ($productCount > 0) {
    set_flash('danger', 'Kategori "' . $category['name'] . '" tidak dapat dihapus karena masih menaungi ' . $productCount . ' produk aktif. Pindahkan produk terlebih dahulu.');
    header('Location: ' . url('categories/index.php'));
    exit;
}

try {
    $stmtDelete = $pdo->prepare('DELETE FROM categories WHERE id = :id');
    $stmtDelete->execute(['id' => $id]);
    set_flash('success', 'Kategori "' . $category['name'] . '" berhasil dihapus.');
} catch (PDOException $e) {
    error_log('Error delete category: ' . $e->getMessage());
    set_flash('danger', 'Gagal menghapus kategori: Terkunci oleh foreign key constraint.');
}

header('Location: ' . url('categories/index.php'));
exit;
