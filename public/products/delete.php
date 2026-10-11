<?php
/**
 * Mini Project 3: Inventory Management System
 * Hapus Data Produk (Khusus Admin)
 *
 * Menerapkan:
 * - Role Authorization: Admin only
 * - Integritas Referensial (Slide 9 & 33):
 *   "Produk yang memiliki histori transaksi tidak boleh dihapus sembarangan."
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_role('admin');

$id = validate_int($_GET['id'] ?? null, 1);
if (!$id) {
    set_flash('danger', 'ID Produk tidak valid.');
    header('Location: ' . url('products/index.php'));
    exit;
}

// Cek keberadaan produk
$stmt = $pdo->prepare('SELECT id, name FROM products WHERE id = :id');
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Produk tidak ditemukan.');
    header('Location: ' . url('products/index.php'));
    exit;
}

// Cek apakah produk memiliki histori pergerakan stok di stock_movements
$stmtCheckMovements = $pdo->prepare('SELECT COUNT(*) FROM stock_movements WHERE product_id = :id');
$stmtCheckMovements->execute(['id' => $id]);
$movementCount = (int) $stmtCheckMovements->fetchColumn();

if ($movementCount > 0) {
    // Larang penghapusan untuk menjaga konsistensi audit dan data relasional
    set_flash('danger', 'Produk "' . $product['name'] . '" TIDAK DAPAT DIHAPUS karena memiliki ' . $movementCount . ' riwayat transaksi mutasi stok. Integritas data gudang dilindungi.');
    header('Location: ' . url('products/index.php'));
    exit;
}

try {
    $stmtDelete = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $stmtDelete->execute(['id' => $id]);

    set_flash('success', 'Produk "' . $product['name'] . '" berhasil dihapus dari sistem.');
} catch (PDOException $e) {
    error_log('Error delete product: ' . $e->getMessage());
    set_flash('danger', 'Gagal menghapus produk karena kendala relasi foreign key.');
}

header('Location: ' . url('products/index.php'));
exit;
