<?php
/**
 * Mini Project 3: Inventory Management System
 * Riwayat Mutasi Stok (Audit Trail & Transaksi Histori)
 *
 * Menerapkan:
 * - Search: nama produk, SKU, nomor catatan
 * - Filter: tipe transaksi (IN / OUT), rentang tanggal
 * - Sorting & Pagination
 * - Prepared Statements & Output Encoding
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_login();

$activeNav = 'stock';
$pageTitle = 'Riwayat Transaksi Mutasi Stok';

// 1. Parameter Filter
$search = sanitize_text($_GET['search'] ?? '');
$typeFilter = sanitize_text($_GET['type'] ?? '');
if (!in_array($typeFilter, ['IN', 'OUT'], true)) {
    $typeFilter = '';
}
$startDate = sanitize_text($_GET['start_date'] ?? '');
$endDate = sanitize_text($_GET['end_date'] ?? '');

// 2. Pagination
$perPage = 10;
$page = max(1, validate_int($_GET['page'] ?? 1, 1) ?: 1);
$offset = ($page - 1) * $perPage;

// 3. Bangun Query Dinamis
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = '(p.name LIKE :search_name OR p.sku LIKE :search_sku OR sm.reference_note LIKE :search_note)';
    $params['search_name'] = '%' . $search . '%';
    $params['search_sku']  = '%' . $search . '%';
    $params['search_note'] = '%' . $search . '%';
}

if (!empty($typeFilter)) {
    $whereClauses[] = 'sm.type = :type';
    $params['type'] = $typeFilter;
}

if (!empty($startDate)) {
    $whereClauses[] = 'DATE(sm.created_at) >= :start_date';
    $params['start_date'] = $startDate;
}

if (!empty($endDate)) {
    $whereClauses[] = 'DATE(sm.created_at) <= :end_date';
    $params['end_date'] = $endDate;
}

$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// 4. Hitung Total Data
$countSql = "
    SELECT COUNT(*) 
    FROM stock_movements sm
    JOIN products p ON p.id = sm.product_id
    {$whereSql}
";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalRows = (int) $stmtCount->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

// 5. Ambil Data
$dataSql = "
    SELECT sm.id, sm.type, sm.quantity, sm.reference_note, sm.created_at,
           p.sku, p.name AS product_name, c.name AS category_name,
           u.username, pr.full_name
    FROM stock_movements sm
    JOIN products p ON p.id = sm.product_id
    JOIN categories c ON c.id = p.category_id
    JOIN users u ON u.id = sm.user_id
    LEFT JOIN user_profiles pr ON pr.user_id = u.id
    {$whereSql}
    ORDER BY sm.created_at DESC, sm.id DESC
    LIMIT :limit OFFSET :offset
";

$stmtData = $pdo->prepare($dataSql);
foreach ($params as $k => $v) {
    $stmtData->bindValue(':' . $k, $v);
}
$stmtData->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtData->execute();
$movements = $stmtData->fetchAll();

include __DIR__ . '/../../views/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Riwayat Mutasi Stok Gudang</h4>
        <p class="text-muted small mb-0">Catatan lengkap pergerakan barang masuk dan keluar.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('stock/in.php') ?>" class="btn btn-coksu btn-sm">
            <i class="bi bi-arrow-down-left me-1"></i> Catat Barang Masuk
        </a>
        <a href="<?= url('stock/out.php') ?>" class="btn btn-outline-coksu btn-sm">
            <i class="bi bi-arrow-up-right me-1"></i> Catat Barang Keluar
        </a>
    </div>
</div>

<!-- Form Filter & Pencarian -->
<div class="card card-clean mb-4 p-2">
    <div class="card-body">
        <form method="GET" action="<?= url('stock/history.php') ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Cari Kata Kunci</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="SKU, nama barang, catatan..." value="<?= e($search) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary">Jenis Mutasi</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">-- Semua Jenis --</option>
                    <option value="IN" <?= $typeFilter === 'IN' ? 'selected' : '' ?>>Barang Masuk (IN)</option>
                    <option value="OUT" <?= $typeFilter === 'OUT' ? 'selected' : '' ?>>Barang Keluar (OUT)</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>">
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Terapkan
                </button>
                <?php if (!empty($search) || !empty($typeFilter) || !empty($startDate) || !empty($endDate)): ?>
                    <a href="<?= url('stock/history.php') ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Histori -->
<div class="card card-clean">
    <div class="table-responsive">
        <table class="table-clean align-middle">
            <thead>
                <tr>
                    <th>Waktu & Tanggal</th>
                    <th class="text-center">Tipe</th>
                    <th>SKU & Produk</th>
                    <th>Kategori</th>
                    <th class="text-center">Jumlah (Qty)</th>
                    <th>Keterangan / Referensi</th>
                    <th>Petugas (User)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                            Tidak ada riwayat mutasi stok yang sesuai dengan filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td class="small text-muted text-nowrap"><?= format_date($m['created_at']) ?></td>
                            <td class="text-center">
                                <?php if ($m['type'] === 'IN'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-box-arrow-in-down me-1"></i> MASUK
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                        <i class="bi bi-box-arrow-up me-1"></i> KELUAR
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($m['product_name']) ?></div>
                                <small class="text-muted font-monospace"><?= e($m['sku']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border"><?= e($m['category_name']) ?></span>
                            </td>
                            <td class="text-center fw-bold fs-6 <?= $m['type'] === 'IN' ? 'text-success' : 'text-danger' ?>">
                                <?= $m['type'] === 'IN' ? '+' : '-' ?><?= number_format($m['quantity']) ?>
                            </td>
                            <td class="small text-secondary"><?= e($m['reference_note'] ?: '-') ?></td>
                            <td class="small text-dark">
                                <i class="bi bi-person me-1 text-muted"></i>
                                <?= e($m['full_name'] ?: $m['username']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center py-2">
        <div class="small text-muted mb-2 mb-md-0">
            Total <strong><?= $totalRows ?></strong> riwayat transaksi
            (Halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong>)
        </div>
        
        <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php 
                        $prev = $_GET;
                        $prev['page'] = $page - 1;
                        $next = $_GET;
                        $next['page'] = $page + 1;
                    ?>
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query($prev) ?>">&laquo; Prev</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php 
                            $curr = $_GET;
                            $curr['page'] = $i;
                        ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query($curr) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query($next) ?>">Next &raquo;</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../views/footer.php'; ?>
