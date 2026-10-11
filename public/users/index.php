<?php
/**
 * Mini Project 3: Inventory Management System
 * Manajemen Pengguna (Khusus Admin)
 *
 * Referensi Slide 24 & 33:
 * "Staff tidak boleh mengakses administrasi user."
 * if ($_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';

require_role('admin');

$activeNav = 'users';
$pageTitle = 'Manajemen Pengguna';

$sql = '
    SELECT u.id, u.username, u.role, u.created_at,
           p.full_name, p.email, p.phone
    FROM users u
    LEFT JOIN user_profiles p ON p.user_id = u.id
    ORDER BY u.id ASC
';
$users = $pdo->query($sql)->fetchAll();

include __DIR__ . '/../../views/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Manajemen Akun Pengguna</h4>
        <p class="text-muted small mb-0">Kelola akun staf dan administrator sistem inventori.</p>
    </div>
    <a href="<?= url('users/create.php') ?>" class="btn btn-coksu btn-sm">
        <i class="bi bi-person-plus me-1"></i> Tambah User Baru
    </a>
</div>

<div class="card card-clean">
    <div class="table-responsive">
        <table class="table-clean align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Role Akses</th>
                    <th>Email / Telepon</th>
                    <th>Dibuat Pada</th>
                    <th class="text-center" style="width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <span class="font-monospace fw-bold text-dark"><?= e($u['username']) ?></span>
                            <?php if ($u['id'] === current_user()['id']): ?>
                                <span class="badge bg-secondary-subtle text-secondary ms-1">Anda</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-medium"><?= e($u['full_name'] ?: '-') ?></td>
                        <td>
                            <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?> px-2 py-1">
                                <?= strtoupper(e($u['role'])) ?>
                            </span>
                        </td>
                        <td class="small text-muted">
                            <div><?= e($u['email'] ?: '-') ?></div>
                            <small class="font-monospace"><?= e($u['phone'] ?: '') ?></small>
                        </td>
                        <td class="small text-muted"><?= format_date($u['created_at'], false) ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= url('users/edit.php?id=' . $u['id']) ?>" class="btn btn-outline-primary" title="Edit Akun">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($u['id'] !== current_user()['id']): ?>
                                    <a href="<?= url('users/delete.php?id=' . $u['id']) ?>" class="btn btn-outline-danger" title="Hapus Akun" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../views/footer.php'; ?>
