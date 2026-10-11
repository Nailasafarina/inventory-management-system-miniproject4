<?php
/**
 * Mini Project 3: Inventory Management System
 * Helper: Output Encoding & Data Formatting (Anti-XSS)
 *
 * Referensi Slide 20:
 * Data dari user harus dianggap tidak tepercaya ketika ditampilkan.
 * Gunakan htmlspecialchars($string, ENT_QUOTES, 'UTF-8').
 */

/**
 * Escape output string untuk mencegah serangan Cross-Site Scripting (XSS).
 *
 * @param mixed $value
 * @return string
 */
function e($value): string
{
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Format angka ke format mata uang Rupiah (IDR).
 *
 * @param float|int|string|null $amount
 * @return string
 */
function format_rupiah($amount): string
{
    $num = (float) ($amount ?? 0);
    return 'Rp ' . number_format($num, 0, ',', '.');
}

/**
 * Format timestamp MySQL ke format tanggal & waktu Indonesia yang ramah pengguna.
 *
 * @param string|null $datetime
 * @param bool $withTime
 * @return string
 */
function format_date(?string $datetime, bool $withTime = true): string
{
    if (!$datetime) {
        return '-';
    }
    $timestamp = strtotime($datetime);
    if (!$timestamp) {
        return '-';
    }
    $format = $withTime ? 'd M Y, H:i' : 'd M Y';
    return date($format, $timestamp);
}
