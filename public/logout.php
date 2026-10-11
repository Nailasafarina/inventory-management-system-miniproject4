<?php
/**
 * Mini Project 3: Inventory Management System
 * Halaman Logout
 */

require_once __DIR__ . '/../helpers/auth.php';

logout_user();
set_flash('info', 'Anda telah berhasil keluar dari sistem.');
header('Location: ' . url('login.php'));
exit;
