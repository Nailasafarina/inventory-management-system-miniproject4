<?php
/**
 * Public Index
 * Redirects to dashboard if logged in, otherwise to login.php
 */
require_once __DIR__ . '/../helpers/auth.php';

if (is_logged_in()) {
    header('Location: ' . url('dashboard.php'));
} else {
    header('Location: ' . url('login.php'));
}
exit;
