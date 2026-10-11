<?php
/**
 * Mini Project 3: Inventory Management System
 * Helper: Authentication, Authorization & Session Management
 *
 * Referensi Slide 22, 23, 24:
 * - Session & Password Hashing
 * - session_regenerate_id(true) setelah login
 * - Role-based Access Control (admin vs staff)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/project-3/public');
}

/**
 * Membuat absolute path URL aplikasi.
 *
 * @param string $path
 * @return string
 */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_URL . ($path ? '/' . $path : '');
}

/**
 * Mengecek apakah user saat ini sedang login.
 *
 * @return bool
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Mengambil data user yang sedang login saat ini.
 *
 * @return array|null
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Mengecek apakah user memiliki role tertentu.
 *
 * @param string|array $roles
 * @return bool
 */
function has_role($roles): bool
{
    if (!is_logged_in()) {
        return false;
    }

    $currentRole = $_SESSION['user']['role'] ?? '';
    if (is_array($roles)) {
        return in_array($currentRole, $roles, true);
    }

    return $currentRole === $roles;
}

/**
 * Memastikan halaman hanya dapat diakses oleh user yang sudah login.
 * Jika belum login, redirect ke halaman login.
 *
 * @return void
 */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('danger', 'Silakan login terlebih dahulu untuk mengakses sistem.');
        header('Location: ' . url('login.php'));
        exit;
    }
}

/**
 * Memastikan user memiliki hak akses (role) yang diizinkan.
 * Jika tidak diizinkan, kembalikan HTTP 403 Forbidden.
 *
 * Referensi Slide 24:
 * if ($_SESSION['user']['role'] !== 'admin') {
 *     http_response_code(403);
 *     exit('Forbidden');
 * }
 *
 * @param string|array $roles
 * @return void
 */
function require_role($roles): void
{
    require_login();

    if (!has_role($roles)) {
        http_response_code(403);
        $allowedRoles = is_array($roles) ? implode(', ', $roles) : $roles;
        
        // Tampilkan halaman error 403 yang informatif
        include __DIR__ . '/../views/errors/403.php';
        exit;
    }
}

/**
 * Menyimpan data session user saat login berhasil dan meregenerasi session id.
 *
 * Referensi Slide 22 & 23:
 * session_regenerate_id(true) untuk mencegah Session Fixation.
 *
 * @param array $user
 * @return void
 */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'        => (int) $user['id'],
        'username'  => $user['username'],
        'role'      => $user['role'],
        'full_name' => $user['full_name'] ?? $user['username']
    ];
}

/**
 * Menghapus data session dan logout secara bersih.
 *
 * @return void
 */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Set flash message (notifikasi satu kali tayang).
 *
 * @param string $type ('success', 'danger', 'warning', 'info')
 * @param string $message
 * @return void
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Ambil flash message jika ada, kemudian hapus dari session.
 *
 * @return array|null
 */
function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
