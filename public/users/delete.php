<?php
/**
 * Mini Project 3: Inventory Management System
 * Hapus Akun Pengguna (Khusus Admin)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_role('admin');

$id = validate_int($_GET['id'] ?? null, 1);
if (!$id) {
    set_flash('danger', 'ID User tidak valid.');
    header('Location: ' . url('users/index.php'));
    exit;
}

if ($id === current_user()['id']) {
    set_flash('danger', 'Anda tidak dapat menghapus akun Anda sendiri saat sedang login!');
    header('Location: ' . url('users/index.php'));
    exit;
}

$stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = :id');
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'User tidak ditemukan.');
    header('Location: ' . url('users/index.php'));
    exit;
}

// Cek apakah user pernah mencatat transaksi di stock_movements
$stmtCheckMovements = $pdo->prepare('SELECT COUNT(*) FROM stock_movements WHERE user_id = :id');
$stmtCheckMovements->execute(['id' => $id]);
$movementCount = (int) $stmtCheckMovements->fetchColumn();

if ($movementCount > 0) {
    set_flash('danger', 'User "' . $user['username'] . '" tidak dapat dihapus karena tercatat sebagai petugas pada ' . $movementCount . ' riwayat transaksi mutasi stok. Audit trail dilindungi.');
    header('Location: ' . url('users/index.php'));
    exit;
}

try {
    $stmtDelete = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmtDelete->execute(['id' => $id]);

    set_flash('success', 'User "' . $user['username'] . '" berhasil dihapus.');
} catch (PDOException $e) {
    error_log('Error delete user: ' . $e->getMessage());
    set_flash('danger', 'Gagal menghapus user: ' . $e->getMessage());
}

header('Location: ' . url('users/index.php'));
exit;
