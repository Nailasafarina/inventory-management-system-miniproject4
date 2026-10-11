<?php
/**
 * Mini Project 3: Inventory Management System
 * Dashboard Ringkasan Stok - Desain Minimalis & Modern "Coksu"
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/auth.php';

require_login();

$activeNav = 'dashboard';
$pageTitle = 'Dashboard Inventori';

// 1. Widget Total Produk
$stmtTotalProducts = $pdo->query('SELECT COUNT(*) FROM products');
$totalProducts = (int) $stmtTotalProducts->fetchColumn();

// 2. Widget Nilai Total Inventory: SUM(stock * price)
$stmtTotalValue = $pdo->query('SELECT COALESCE(SUM(stock * price), 0) FROM products');
$totalInventoryValue = (float) $stmtTotalValue->fetchColumn();

// 3. Widget Produk Stok Rendah: stock <= minimum_stock
$stmtLowStockCount = $pdo->query('SELECT COUNT(*) FROM products WHERE stock <= minimum_stock');
$lowStockCount = (int) $stmtLowStockCount->fetchColumn();

// 4. Widget Total Barang Masuk & Keluar
$stmtMovements = $pdo->query("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'IN' THEN quantity ELSE 0 END), 0) AS total_in,
        COALESCE(SUM(CASE WHEN type = 'OUT' THEN quantity ELSE 0 END), 0) AS total_out
    FROM stock_movements
");
$movementStats = $stmtMovements->fetch();
$totalIn = (int) $movementStats['total_in'];
$totalOut = (int) $movementStats['total_out'];

// 5. Daftar Produk dengan Stok Rendah (Urgent Alert)
$stmtLowStockList = $pdo->query('
    SELECT p.id, p.sku, p.name, p.stock, p.minimum_stock, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.stock <= p.minimum_stock
    ORDER BY p.stock ASC
    LIMIT 5
');
$lowStockProducts = $stmtLowStockList->fetchAll();

// 6. Histori Transaksi Terbaru
$stmtRecentMovements = $pdo->query('
    SELECT sm.id, sm.type, sm.quantity, sm.reference_note, sm.created_at,
           p.name AS product_name, p.sku,
           u.username, pr.full_name
    FROM stock_movements sm
    JOIN products p ON p.id = sm.product_id
    JOIN users u ON u.id = sm.user_id
    LEFT JOIN user_profiles pr ON pr.user_id = u.id
    ORDER BY sm.created_at DESC
    LIMIT 5
');
$recentMovements = $stmtRecentMovements->fetchAll();

include __DIR__ . '/../views/header.php';
?>

<!-- Header Halaman: Lega, Bersih, & Minimalis -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-1 gap-3">
    <div>
        <h3 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2" style="letter-spacing: -0.02em;">
            <span>Snack Corner</span>
            <span style="font-size: 1.5rem;">🍪</span>
        </h3>
        <p class="text-muted small mb-0">Sistem Pengelolaan Stok & Inventori Cemilan, Kue, dan Minuman Manis.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('stock/in.php') ?>" class="btn btn-coksu shadow-none">
            <i class="bi bi-arrow-down-left me-1"></i> Catat Masuk
        </a>
        <a href="<?= url('stock/out.php') ?>" class="btn btn-outline-coksu shadow-none">
            <i class="bi bi-arrow-up-right me-1"></i> Catat Keluar
        </a>
    </div>
</div>

<!-- 4 Kotak KPI Widget: Putih Bersih, Soft Shadow, Ikon Minimalis -->
<div class="row g-3 mb-4">
    <!-- Total Produk -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <span class="stat-label">TOTAL VARIAN</span>
                <div class="stat-value"><?= number_format($totalProducts) ?></div>
                <div class="stat-sub">Varian cemilan terdaftar</div>
            </div>
            <div class="stat-icon-box">
                <i class="bi bi-cookie"></i>
            </div>
        </div>
    </div>

    <!-- Valuasi Nilai Aset -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <span class="stat-label">VALUASI ASET</span>
                <div class="stat-value" style="font-size: 1.35rem; color: var(--accent-success);"><?= format_rupiah($totalInventoryValue) ?></div>
                <div class="stat-sub">Total nilai buku fisik</div>
            </div>
            <div class="stat-icon-box stat-icon-success">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
    </div>

    <!-- Produk Stok Rendah -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <span class="stat-label" style="color: var(--accent-danger);">STOK KRITIS</span>
                <div class="stat-value" style="color: var(--accent-danger);"><?= number_format($lowStockCount) ?></div>
                <div class="stat-sub text-danger" style="font-size: 0.78rem;">&le; Batas minimum stok</div>
            </div>
            <div class="stat-icon-box stat-icon-danger">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
        </div>
    </div>

    <!-- Total Mutasi Fisik -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <span class="stat-label">MUTASI FISIK</span>
                <div class="stat-value" style="font-size: 1.35rem;">
                    <span style="color: var(--accent-success);">+<?= number_format($totalIn) ?></span>
                    <span class="text-muted fw-normal" style="font-size: 1rem;">/</span>
                    <span style="color: var(--accent-danger);">-<?= number_format($totalOut) ?></span>
                </div>
                <div class="stat-sub">Total pergerakan unit</div>
            </div>
            <div class="stat-icon-box stat-icon-info">
                <i class="bi bi-arrow-left-right"></i>
            </div>
        </div>
    </div>
</div>

<!-- Area Data: Card Putih Bersih & Tabel Clean Bergaris Tipis -->
<div class="row g-4">
    <!-- Tabel Peringatan Stok Rendah -->
    <div class="col-lg-6">
        <div class="card card-clean h-100">
            <div class="card-header card-header-clean d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center" style="font-size: 0.92rem;">
                    <i class="bi bi-exclamation-circle text-danger me-2"></i> Peringatan Stok Rendah
                </h6>
                <a href="<?= url('products/index.php') ?>" class="btn-link-clean">
                    Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table-clean align-middle">
                    <thead>
                        <tr>
                            <th>Menu Cemilan</th>
                            <th>Kategori</th>
                            <th class="text-center">Sisa</th>
                            <th class="text-center">Min</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lowStockProducts)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted small">
                                    <i class="bi bi-check-circle text-success fs-3 d-block mb-1"></i>
                                    Semua persediaan berada dalam batas aman.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lowStockProducts as $p): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($p['name']) ?></div>
                                        <div class="text-muted font-monospace" style="font-size: 0.72rem;"><?= e($p['sku']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge-clean badge-clean-category"><?= e($p['category_name']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold text-danger"><?= number_format($p['stock']) ?></span>
                                    </td>
                                    <td class="text-center text-muted small">
                                        <?= number_format($p['minimum_stock']) ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= url('stock/in.php?product_id=' . $p['id']) ?>" class="btn-action-sm btn-action-outline-success">
                                            <i class="bi bi-plus"></i> Masuk
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tabel Riwayat Mutasi Terkini -->
    <div class="col-lg-6">
        <div class="card card-clean h-100">
            <div class="card-header card-header-clean d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center" style="font-size: 0.92rem;">
                    <i class="bi bi-clock-history text-muted me-2"></i> Mutasi Transaksi Terkini
                </h6>
                <a href="<?= url('stock/history.php') ?>" class="btn-link-clean">
                    Riwayat Lengkap <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table-clean align-middle">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th class="text-center">Tipe</th>
                            <th>Menu Cemilan</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentMovements)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted small">
                                    Belum ada catatan mutasi transaksi.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentMovements as $m): ?>
                                <tr>
                                    <td class="text-muted small" style="font-size: 0.8rem; white-space: nowrap;">
                                        <?= format_date($m['created_at']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($m['type'] === 'IN'): ?>
                                            <span class="badge-clean badge-clean-success">IN</span>
                                        <?php else: ?>
                                            <span class="badge-clean badge-clean-danger">OUT</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small text-truncate" style="max-width: 160px;"><?= e($m['product_name']) ?></div>
                                        <div class="text-muted font-monospace" style="font-size: 0.72rem;"><?= e($m['sku']) ?></div>
                                    </td>
                                    <td class="text-center fw-semibold <?= $m['type'] === 'IN' ? 'text-success' : 'text-danger' ?>">
                                        <?= $m['type'] === 'IN' ? '+' : '-' ?><?= number_format($m['quantity']) ?>
                                    </td>
                                    <td class="text-end small text-muted">
                                        <?= e($m['full_name'] ?: $m['username']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
