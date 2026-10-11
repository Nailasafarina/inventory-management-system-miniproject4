<?php
/**
 * Mini Project 3: Inventory Management System
 * Halaman Login - Tema Estetik Warm Caramel & Dark Chocolate
 *
 * Sesuai referensi visual: Screenshot 2026-10-11 180846.png
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/validation.php';

if (is_logged_in()) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$error = '';
$loginInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Token keamanan CSRF tidak valid. Silakan coba lagi.';
    } else {
        $loginInput = sanitize_text($_POST['login'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if (empty($loginInput) || empty($password)) {
            $error = 'Alamat email / username dan kata sandi wajib diisi.';
        } else {
            // Prepared statement mendukung login via username ATAU email dengan parameter terpisah
            $stmt = $pdo->prepare('
                SELECT u.id, u.username, u.password_hash, u.role, p.full_name, p.email
                FROM users u 
                LEFT JOIN user_profiles p ON p.user_id = u.id 
                WHERE u.username = :username OR p.email = :email
                LIMIT 1
            ');
            $stmt->execute([
                'username' => $loginInput,
                'email'    => $loginInput
            ]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                login_user($user);
                set_flash('success', 'Selamat datang kembali, ' . ($user['full_name'] ?: $user['username']) . '!');
                header('Location: ' . url('dashboard.php'));
                exit;
            } else {
                $error = 'Email / username atau kata sandi yang Anda masukkan salah.';
            }
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Manajemen Inventori & Cemilan</title>
    <!-- Google Font Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Theme CSS -->
    <link href="<?= url('css/style.css') ?>" rel="stylesheet">
    <style>
        .input-group-theme {
            background-color: #faf7f4;
            border: 1px solid #ede4db;
            border-radius: 0.85rem;
            display: flex;
            align-items: center;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        .input-group-theme:focus-within {
            background-color: #ffffff;
            border-color: #9c7152;
            box-shadow: 0 0 0 3px rgba(156, 113, 82, 0.14);
        }
        .input-group-theme .input-icon {
            padding: 0.8rem 1rem;
            color: #9e8e82;
            font-size: 1.1rem;
        }
        .input-group-theme input {
            border: none;
            background: transparent;
            padding: 0.8rem 1rem 0.8rem 0;
            width: 100%;
            font-size: 0.95rem;
            color: #2d241e;
            outline: none;
        }
        .input-group-theme input::placeholder {
            color: #b5a89e;
        }
        .alert-warm {
            background-color: #f7f1ea;
            border: 1px solid #ede4db;
            border-radius: 0.85rem;
            color: #724f35;
            padding: 0.85rem 1.15rem;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
        }
        .btn-caramel-pill {
            background-color: #9c7152;
            color: #ffffff;
            border: none;
            border-radius: 0.85rem;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.8rem 1.5rem;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 3px 12px rgba(156, 113, 82, 0.22);
        }
        .btn-caramel-pill:hover {
            background-color: #865e41;
            color: #ffffff;
            box-shadow: 0 5px 16px rgba(156, 113, 82, 0.3);
        }
        .badge-demo-box {
            background-color: #fcfaf8;
            border: 1px solid #ede4db;
            border-radius: 0.75rem;
            padding: 0.65rem 0.75rem;
            font-size: 0.78rem;
            color: #59473b;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .badge-demo-box:hover {
            background-color: #f7f1ea;
            border-color: #9c7152;
        }
    </style>
</head>
<body class="login-page-bg">

<div class="container d-flex justify-content-center py-4">
    <div class="login-card-theme">
        <!-- Top Dark Chocolate Header Banner (Sesuai Gambar) -->
        <div class="login-header-banner">
            <div class="icon-holder">
                <i class="bi bi-cookie"></i>
            </div>
            <h2>Manajemen Cemilan & Inventori</h2>
            <p>Sistem Pengelolaan Stok & Inventori (PHP Native)</p>
        </div>

        <div class="login-body">
            <!-- Alert Notifikasi Sesi / Flash (Sesuai Gambar) -->
            <?php if ($flash): ?>
                <div class="alert-warm mb-3">
                    <i class="bi bi-info-circle-fill fs-5 me-2 text-warning"></i>
                    <div><?= e($flash['message']) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 px-3 rounded-3 small mb-3 d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('login.php') ?>">
                <?= csrf_field() ?>

                <!-- ALAMAT EMAIL / USERNAME -->
                <div class="mb-3">
                    <label class="form-label-custom">ALAMAT EMAIL / USERNAME</label>
                    <div class="input-group-theme">
                        <span class="input-icon"><i class="bi bi-envelope"></i></span>
                        <input type="text" name="login" value="<?= e($loginInput) ?>" placeholder="admin@inventory.com" required autofocus>
                    </div>
                </div>

                <!-- KATA SANDI (PASSWORD) -->
                <div class="mb-4">
                    <label class="form-label-custom">KATA SANDI (PASSWORD)</label>
                    <div class="input-group-theme">
                        <span class="input-icon"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <!-- Tombol Submit Caramel -->
                <button type="submit" class="btn btn-caramel-pill">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Masuk ke Dashboard
                </button>
            </form>

            <!-- Akun Uji Bawaan (Default Demo) -->
            <div class="mt-4 pt-3 border-top text-center">
                <div class="small fw-bold text-muted text-uppercase mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                    AKUN UJI BAWAAN (DEFAULT):
                </div>
                <div class="row g-2 justify-content-center">
                    <div class="col-6">
                        <div class="badge-demo-box text-start" onclick="fillDemo('admin', 'admin123')">
                            <div class="fw-bold mb-1">👑 Role: Admin</div>
                            <div class="text-muted" style="font-size: 0.72rem;">User: <code>admin</code></div>
                            <div class="text-muted" style="font-size: 0.72rem;">Pass: <code>admin123</code></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="badge-demo-box text-start" onclick="fillDemo('staff', 'staff123')">
                            <div class="fw-bold mb-1">🍪 Role: Staff</div>
                            <div class="text-muted" style="font-size: 0.72rem;">User: <code>staff</code></div>
                            <div class="text-muted" style="font-size: 0.72rem;">Pass: <code>staff123</code></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillDemo(login, pass) {
    document.querySelector('input[name="login"]').value = login;
    document.querySelector('input[name="password"]').value = pass;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
