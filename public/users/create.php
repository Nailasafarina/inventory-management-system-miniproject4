<?php
/**
 * Mini Project 3: Inventory Management System
 * Tambah Pengguna Baru (Khusus Admin)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_role('admin');

$activeNav = 'users';
$pageTitle = 'Tambah User Baru';

$errors = [];
$username = '';
$fullName = '';
$email = '';
$phone = '';
$role = 'staff';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $username = strtolower(sanitize_text($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $fullName = sanitize_text($_POST['full_name'] ?? '');
        $email = sanitize_text($_POST['email'] ?? '');
        $phone = sanitize_text($_POST['phone'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin', 'staff'], true) ? $_POST['role'] : 'staff';

        if (empty($username)) {
            $errors[] = 'Username wajib diisi.';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username minimal 3 karakter.';
        } else {
            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :u');
            $stmtCheck->execute(['u' => $username]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $errors[] = 'Username "' . $username . '" sudah terdaftar.';
            }
        }

        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'Password minimal 6 karakter.';
        }

        if (empty($fullName)) {
            $errors[] = 'Nama lengkap wajib diisi.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $stmtUser = $pdo->prepare('
                    INSERT INTO users (username, password_hash, role)
                    VALUES (:username, :password_hash, :role)
                ');
                $stmtUser->execute([
                    'username'      => $username,
                    'password_hash' => $passwordHash,
                    'role'          => $role
                ]);
                $newUserId = (int) $pdo->lastInsertId();

                $stmtProfile = $pdo->prepare('
                    INSERT INTO user_profiles (user_id, full_name, email, phone)
                    VALUES (:user_id, :full_name, :email, :phone)
                ');
                $stmtProfile->execute([
                    'user_id'   => $newUserId,
                    'full_name' => $fullName,
                    'email'     => $email ?: null,
                    'phone'     => $phone ?: null
                ]);

                $pdo->commit();

                set_flash('success', 'User "' . $username . '" berhasil didaftarkan!');
                header('Location: ' . url('users/index.php'));
                exit;

            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('Error create user: ' . $e->getMessage());
                $errors[] = 'Gagal menyimpan user: ' . $e->getMessage();
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
                <h5 class="fw-bold mb-0 text-dark">Tambah Akun Pengguna</h5>
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

                <form method="POST" action="<?= url('users/create.php') ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= e($username) ?>" required autofocus>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6" placeholder="Minimal 6 karakter">
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
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="user@contoh.com">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label small fw-semibold">No. Telepon / WhatsApp</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= e($phone) ?>" placeholder="0812xxxx">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= url('users/index.php') ?>" class="btn btn-light px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">Simpan Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../views/footer.php'; ?>
