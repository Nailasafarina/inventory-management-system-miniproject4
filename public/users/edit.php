<?php
/**
 * Mini Project 3: Inventory Management System
 * Edit Akun Pengguna (Khusus Admin)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_role('admin');

$activeNav = 'users';
$pageTitle = 'Edit User';

$id = validate_int($_GET['id'] ?? null, 1);
if (!$id) {
    set_flash('danger', 'ID User tidak valid.');
    header('Location: ' . url('users/index.php'));
    exit;
}

$stmt = $pdo->prepare('
    SELECT u.id, u.username, u.role, p.full_name, p.email, p.phone
    FROM users u
    LEFT JOIN user_profiles p ON p.user_id = u.id
    WHERE u.id = :id
');
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'User tidak ditemukan.');
    header('Location: ' . url('users/index.php'));
    exit;
}

$errors = [];
$role = $user['role'];
$fullName = $user['full_name'];
$email = $user['email'];
$phone = $user['phone'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $newPassword = (string) ($_POST['password'] ?? '');
        $fullName = sanitize_text($_POST['full_name'] ?? '');
        $email = sanitize_text($_POST['email'] ?? '');
        $phone = sanitize_text($_POST['phone'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin', 'staff'], true) ? $_POST['role'] : 'staff';

        if (empty($fullName)) {
            $errors[] = 'Nama lengkap wajib diisi.';
        }

        if (!empty($newPassword) && strlen($newPassword) < 6) {
            $errors[] = 'Password baru minimal 6 karakter.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                if (!empty($newPassword)) {
                    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                    $stmtU = $pdo->prepare('UPDATE users SET role = :role, password_hash = :hash WHERE id = :id');
                    $stmtU->execute(['role' => $role, 'hash' => $newHash, 'id' => $id]);
                } else {
                    $stmtU = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
                    $stmtU->execute(['role' => $role, 'id' => $id]);
                }

                // Update Profile (Upsert)
                $stmtCheckP = $pdo->prepare('SELECT COUNT(*) FROM user_profiles WHERE user_id = :id');
                $stmtCheckP->execute(['id' => $id]);
                if ((int)$stmtCheckP->fetchColumn() > 0) {
                    $stmtP = $pdo->prepare('
                        UPDATE user_profiles 
                        SET full_name = :full_name, email = :email, phone = :phone
                        WHERE user_id = :id
                    ');
                } else {
                    $stmtP = $pdo->prepare('
                        INSERT INTO user_profiles (user_id, full_name, email, phone)
                        VALUES (:id, :full_name, :email, :phone)
                    ');
                }
                $stmtP->execute([
                    'full_name' => $fullName,
                    'email'     => $email ?: null,
                    'phone'     => $phone ?: null,
                    'id'        => $id
                ]);

                $pdo->commit();

                // Jika mengedit profil diri sendiri, update nama di session
                if ($id === current_user()['id']) {
                    $_SESSION['user']['full_name'] = $fullName;
                    $_SESSION['user']['role'] = $role;
                }

                set_flash('success', 'Data user "' . $user['username'] . '" berhasil diperbarui!');
                header('Location: ' . url('users/index.php'));
                exit;

            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('Error update user: ' . $e->getMessage());
                $errors[] = 'Gagal menyimpan perubahan: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../views/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">Edit Pengguna: <?= e($user['username']) ?></h5>
                <a href="<?= url('users/index.php') ?>" class="btn btn-sm btn-outline-secondary">Kembali</a>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= url('users/edit.php?id=' . $id) ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Username</label>
                            <input type="text" class="form-control bg-light" value="<?= e($user['username']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label small fw-semibold">Ganti Password (Opsional)</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Kosongkan jika tidak diubah" minlength="6">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($fullName) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="role" class="form-label small fw-semibold">Role Akses <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="staff" <?= $role === 'staff' ? 'selected' : '' ?>>Staff (Operasional Gudang)</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin (Full Akses)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="email" class="form-label small fw-semibold">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label small fw-semibold">No. Telepon</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= e($phone) ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= url('users/index.php') ?>" class="btn btn-light px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../views/footer.php'; ?>
