<?php
/**
 * Mini Project 3: Inventory Management System
 * Hapus Supplier (Khusus Admin)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_role('admin');

$id = validate_int($_GET['id'] ?? null, 1);
if (!$id) {
    set_flash('danger', 'ID Supplier tidak valid.');
    header('Location: ' . url('suppliers/index.php'));
    exit;
}

$stmt = $pdo->prepare('SELECT id, name FROM suppliers WHERE id = :id');
$stmt->execute(['id' => $id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    set_flash('danger', 'Data supplier tidak ditemukan.');
    header('Location: ' . url('suppliers/index.php'));
    exit;
}

try {
    $stmtDelete = $pdo->prepare('DELETE FROM suppliers WHERE id = :id');
    $stmtDelete->execute(['id' => $id]);

    set_flash('success', 'Supplier "' . $supplier['name'] . '" berhasil dihapus.');
} catch (PDOException $e) {
    error_log('Error delete supplier: ' . $e->getMessage());
    set_flash('danger', 'Gagal menghapus supplier: ' . $e->getMessage());
}

header('Location: ' . url('suppliers/index.php'));
exit;
