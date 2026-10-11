<?php
/**
 * Mini Project 3: Inventory Management System
 * Laporan Inventory & Analisis Agregat Bisnis
 *
 * Referensi Slide 14 & 28:
 * - Query Agregat Per Produk (Slide 28):
 *   SUM(CASE WHEN sm.type='IN' THEN sm.quantity ELSE 0 END) AS total_in,
 *   SUM(CASE WHEN sm.type='OUT' THEN sm.quantity ELSE 0 END) AS total_out
 * - Query Agregat Per Kategori (Slide 14):
 *   COUNT(p.id), SUM(p.stock), AVG(p.price), SUM(p.stock * p.price)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_login();

$activeNav = 'reports';
$pageTitle = 'Laporan Persediaan & Mutasi Inventory';

$filterCategory = validate_int($_GET['category_id'] ?? null, 1);

// 1. Ringkasan Per Kategori (Slide 14)
$sqlCategorySummary = '
    SELECT c.id, c.name,
           COUNT(p.id) AS product_count,
           COALESCE(SUM(p.stock), 0) AS total_stock,
           COALESCE(AVG(p.price), 0) AS avg_price,
           COALESCE(SUM(p.stock * p.price), 0) AS category_valuation
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name
    ORDER BY category_valuation DESC
';
$categorySummaries = $pdo->query($sqlCategorySummary)->fetchAll();

// 2. Laporan Lengkap Mutasi per Produk (Slide 28)
$whereClause = '';
$params = [];
if ($filterCategory) {
    $whereClause = ' WHERE p.category_id = :cat_id ';
    $params['cat_id'] = $filterCategory;
}

$sqlProductReport = "
    SELECT p.id, p.sku, p.name, p.price, p.stock AS current_stock, p.minimum_stock,
           c.name AS category_name,
           COALESCE(SUM(CASE WHEN sm.type = 'IN' THEN sm.quantity ELSE 0 END), 0) AS total_in,
           COALESCE(SUM(CASE WHEN sm.type = 'OUT' THEN sm.quantity ELSE 0 END), 0) AS total_out,
           (p.stock * p.price) AS asset_value
    FROM products p
    JOIN categories c ON c.id = p.category_id
    LEFT JOIN stock_movements sm ON sm.product_id = p.id
    {$whereClause}
    GROUP BY p.id, p.sku, p.name, p.price, p.stock, p.minimum_stock, c.name
    ORDER BY p.name ASC
";
$stmtProd = $pdo->prepare($sqlProductReport);
$stmtProd->execute($params);
$productReports = $stmtProd->fetchAll();

// 3. Agregat Total Keseluruhan
$totalStockAll = 0;
$totalInAll = 0;
$totalOutAll = 0;
$totalAssetValueAll = 0;

foreach ($productReports as $pr) {
    $totalStockAll += $pr['current_stock'];
    $totalInAll += $pr['total_in'];
    $totalOutAll += $pr['total_out'];
    $totalAssetValueAll += $pr['asset_value'];
}

$categoriesList = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

include __DIR__ . '/../../views/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Laporan Persediaan & Mutasi Inventory</h4>
        <p class="text-muted small mb-0">Analisis agregat stok barang, akumulasi mutasi masuk/keluar, dan valuasi aset.</p>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Cetak / Ekspor PDF
        </button>
    </div>
</div>

<!-- Laporan 1: Ringkasan per Kategori (Slide 14) -->
<div class="card card-clean mb-4">
    <div class="card-header card-header-clean py-3">
        <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.92rem;">
            <i class="bi bi-pie-chart me-2" style="color: var(--coksu-primary);"></i>Ringkasan Valuasi Berdasarkan Kategori
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table-clean align-middle">
            <thead>
                <tr>
                    <th>Nama Kategori</th>
                    <th class="text-center">Jumlah Jenis SKU</th>
                    <th class="text-center">Total Fisik Unit</th>
                    <th class="text-end">Rata-rata Harga Satuan</th>
                    <th class="text-end">Total Valuasi Nilai Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorySummaries as $cs): ?>
                    <tr>
                        <td class="fw-semibold text-dark"><?= e($cs['name']) ?></td>
                        <td class="text-center"><?= number_format($cs['product_count']) ?> SKU</td>
                        <td class="text-center fw-medium"><?= number_format($cs['total_stock']) ?> Unit</td>
                        <td class="text-end text-muted small"><?= format_rupiah($cs['avg_price']) ?></td>
                        <td class="text-end fw-bold text-success"><?= format_rupiah($cs['category_valuation']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Filter Kategori untuk Laporan Produk -->
<div class="card mb-4 bg-white d-print-none">
    <div class="card-body">
        <form method="GET" action="<?= url('reports/index.php') ?>" class="row g-2 align-items-center">
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-secondary mb-1">Filter Menurut Kategori Produk</label>
                <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Kategori --</option>
                    <?php foreach ($categoriesList as $cl): ?>
                        <option value="<?= $cl['id'] ?>" <?= $filterCategory == $cl['id'] ? 'selected' : '' ?>>
                            <?= e($cl['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mt-auto">
                <?php if ($filterCategory): ?>
                    <a href="<?= url('reports/index.php') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Reset Filter
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Laporan 2: Detail Akumulasi Mutasi per Produk (Slide 28) -->
<div class="card card-clean">
    <div class="card-header card-header-clean py-3">
        <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.92rem;">
            <i class="bi bi-table me-2" style="color: var(--coksu-primary);"></i>Rekapitulasi Mutasi & Sisa Stok Akhir per Produk
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table-clean align-middle">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-center text-success">Total Masuk (IN)</th>
                    <th class="text-center text-danger">Total Keluar (OUT)</th>
                    <th class="text-center fw-bold">Sisa Stok</th>
                    <th class="text-end fw-bold">Valuasi Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productReports)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Tidak ada data untuk kategori yang dipilih.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($productReports as $pr): ?>
                        <tr>
                            <td class="font-monospace small fw-bold text-secondary"><?= e($pr['sku']) ?></td>
                            <td class="fw-medium text-dark"><?= e($pr['name']) ?></td>
                            <td><span class="badge bg-light text-secondary border"><?= e($pr['category_name']) ?></span></td>
                            <td class="text-end small"><?= format_rupiah($pr['price']) ?></td>
                            <td class="text-center fw-bold text-success">+<?= number_format($pr['total_in']) ?></td>
                            <td class="text-center fw-bold text-danger">-<?= number_format($pr['total_out']) ?></td>
                            <td class="text-center">
                                <span class="badge <?= $pr['current_stock'] <= $pr['minimum_stock'] ? 'bg-danger' : 'bg-primary' ?> px-2 py-1 fs-6">
                                    <?= number_format($pr['current_stock']) ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark"><?= format_rupiah($pr['asset_value']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold border-top-2">
                <tr>
                    <td colspan="4" class="text-end">TOTAL KESELURUHAN:</td>
                    <td class="text-center text-success">+<?= number_format($totalInAll) ?></td>
                    <td class="text-center text-danger">-<?= number_format($totalOutAll) ?></td>
                    <td class="text-center text-primary"><?= number_format($totalStockAll) ?> Unit</td>
                    <td class="text-end text-success fs-6"><?= format_rupiah($totalAssetValueAll) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../views/footer.php'; ?>
