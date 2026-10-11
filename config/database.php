<?php
/**
 * Mini Project 3: Inventory Management System
 * Database Configuration & PDO Connection
 *
 * Menerapkan Best Practices:
 * 1. PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION (Exception handling untuk Transaction)
 * 2. PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC (Format array asosiatif)
 * 3. PDO::ATTR_EMULATE_PREPARES => false (Native prepared statements mencegah SQL Injection)
 * 4. Charset utf8mb4 untuk kompatibilitas data modern
 */

// Konfigurasi Database
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'inventory_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Mendapatkan instance koneksi PDO tunggal (Singleton pattern sederhana)
 * 
 * @return PDO
 * @throws PDOException
 */
function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Catat log error teknis di server
            error_log('Database Connection Error: ' . $e->getMessage());
            
            // Tampilkan pesan yang aman bagi pengguna (tidak membocorkan kredensial)
            http_response_code(500);
            die('Gagal terhubung ke database. Silakan pastikan server MySQL di XAMPP telah berjalan.');
        }
    }

    return $pdo;
}

// Inisialisasi variabel global $pdo agar mudah digunakan langsung di skrip PHP
$pdo = getDBConnection();
