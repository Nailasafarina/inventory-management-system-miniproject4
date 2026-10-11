<?php
/**
 * Mini Project 3: Inventory Management System
 * Helper: Input Validation, Sanitization & CSRF Protection
 *
 * Referensi Slide 16, 21, 33:
 * - Syntactic validation: tipe data, format, panjang, nilai positif
 * - Semantic validation: stok cukup, SKU unik, allowlist sorting
 * - CSRF token untuk perlindungan form POST
 */

/**
 * Mendapatkan atau menghasilkan token CSRF aktif.
 *
 * @return string
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Menghasilkan input hidden untuk token CSRF dalam form HTML.
 *
 * @return string
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Memvalidasi apakah request POST menyertakan token CSRF yang valid.
 *
 * @return bool
 */
function verify_csrf(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }

    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Sanitasi string input dasar (menghapus whitespace berlebih).
 *
 * @param mixed $value
 * @return string
 */
function sanitize_text($value): string
{
    return trim((string) ($value ?? ''));
}

/**
 * Validasi apakah integer bernilai valid dan memenuhi batas nilai (opsional).
 *
 * @param mixed $value
 * @param int|null $min
 * @param int|null $max
 * @return int|false
 */
function validate_int($value, ?int $min = null, ?int $max = null)
{
    $options = [];
    if ($min !== null) $options['min_range'] = $min;
    if ($max !== null) $options['max_range'] = $max;

    $filterOptions = !empty($options) ? ['options' => $options] : 0;
    return filter_var($value, FILTER_VALIDATE_INT, $filterOptions);
}

/**
 * Validasi apakah angka float bernilai valid dan non-negatif.
 *
 * @param mixed $value
 * @param float|null $min
 * @return float|false
 */
function validate_float($value, ?float $min = null)
{
    if (!is_numeric($value)) {
        return false;
    }
    $val = (float) $value;
    if ($min !== null && $val < $min) {
        return false;
    }
    return $val;
}

/**
 * Validasi keunikan SKU produk di database (Prepared Statement).
 * Referensi Slide 33: "SKU harus unik."
 *
 * @param PDO $pdo
 * @param string $sku
 * @param int|null $excludeProductId (Diisi saat proses edit agar tidak mendeteksi diri sendiri)
 * @return bool True jika SKU unik (belum dipakai)
 */
function is_sku_unique(PDO $pdo, string $sku, ?int $excludeProductId = null): bool
{
    if ($excludeProductId) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE sku = :sku AND id != :id');
        $stmt->execute(['sku' => $sku, 'id' => $excludeProductId]);
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE sku = :sku');
        $stmt->execute(['sku' => $sku]);
    }
    return ((int) $stmt->fetchColumn()) === 0;
}

/**
 * Validasi nama kolom sorting menggunakan allowlist (Whitelisting).
 * Referensi Slide 16: "Kolom sorting harus dipilih dari allowlist, bukan input mentah."
 *
 * @param string|null $column
 * @param array $allowedColumns
 * @param string $defaultColumn
 * @return string
 */
function validate_sort_column(?string $column, array $allowedColumns, string $defaultColumn): string
{
    if ($column && in_array($column, $allowedColumns, true)) {
        return $column;
    }
    return $defaultColumn;
}

/**
 * Validasi arah sorting (ASC atau DESC).
 *
 * @param string|null $direction
 * @param string $defaultDirection
 * @return string
 */
function validate_sort_direction(?string $direction, string $defaultDirection = 'ASC'): string
{
    $dir = strtoupper(trim((string) $direction));
    return in_array($dir, ['ASC', 'DESC'], true) ? $dir : $defaultDirection;
}
