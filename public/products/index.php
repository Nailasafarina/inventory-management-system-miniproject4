<?php
/**
 * Mini Project 3: Inventory Management System
 * Modul Produk: Listing dengan Search, Filter, Sorting & Pagination (Tema Estetik)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_login();

$activeNav = 'products';
$pageTitle = 'Katalog Master Produk';

// 1. Ambil Parameter Pencarian & Filter
$search = sanitize_text($_GET['search'] ?? '');
$categoryId = validate_int($_GET['category_id'] ?? null, 1);

// 2. Ambil Parameter Sorting dengan White-list
$allowedSortColumns = [
    'sku'        => 'p.sku',
    'name'       => 'p.name',
    'price'      => 'p.price',
    'stock'      => 'p.stock',
    'category'   => 'c.name',
    'created_at' => 'p.created_at'
];
$sort = sanitize_text($_GET['sort'] ?? 'name');
if (!array_key_exists($sort, $allowedSortColumns)) {
    $sort = 'name';
}
$sortColumn = $allowedSortColumns[$sort];
$order = validate_sort_direction($_GET['order'] ?? 'ASC');

// 3. Konfigurasi Pagination
$perPage = 8;
$page = max(1, validate_int($_GET['page'] ?? 1, 1) ?: 1);
$offset = ($page - 1) * $perPage;

// 4. Siapkan Klausul WHERE Dinamis
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = '(p.name LIKE :search_name OR p.sku LIKE :search_sku)';
    $params['search_name'] = '%' . $search . '%';
    $params['search_sku'] = '%' . $search . '%';
}

if ($categoryId) {
    $whereClauses[] = 'p.category_id = :category_id';
    $params['category_id'] = $categoryId;
}

$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// 5. Hitung Total Data untuk Pagination
$countSql = "SELECT COUNT(*) FROM products p" . $whereSql;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages && $totalRows > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// 6. Query Ambil Data Produk
$dataSql = "
    SELECT p.id, p.sku, p.name, p.price, p.stock, p.minimum_stock, p.created_at,
           c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    {$whereSql}
    ORDER BY {$sortColumn} {$order}
    LIMIT :limit OFFSET :offset
";

$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $key => $val) {
    $dataStmt->bindValue(':' . $key, $val);
}
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$products = $dataStmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

function getSortUrl(string $column, string $currentSort, string $currentOrder): string
{
    $params = $_GET;
    $params['sort'] = $column;
    $params['order'] = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    return '?' . http_build_query($params);
}

function getSortIcon(string $column, string $currentSort, string $currentOrder): string
{
    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up text-muted opacity-50 small ms-1"></i>';
    }
    return $currentOrder === 'ASC' 
        ? '<i class="bi bi-sort-alpha-down text-warning ms-1"></i>' 
        : '<i class="bi bi-sort-alpha-up-alt text-warning ms-1"></i>';
}

include __DIR__ . '/../../views/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.02em;">Katalog Master Produk</h4>
        <p class="text-muted small mb-0">Kelola master barang, pantau jumlah stok dan nilai satuan barang.</p>
    </div>
    <div>
        <?php if (has_role('admin')): ?>
            <a href="<?= url('products/create.php') ?>" class="btn btn-caramel btn-pill btn-sm px-3 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Tambah Produk Baru
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Form Search & Filter -->
<div class="card card-clean mb-4 p-2">
    <div class="card-body p-2">
        <form method="GET" action="<?= url('products/index.php') ?>" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 0.75rem 0 0 0.75rem;"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama produk atau kode SKU..." value="<?= e($search) ?>" style="border-radius: 0 0.75rem 0.75rem 0;">
                </div>
            </div>

            <div class="col-md-4">
                <select name="category_id" class="form-select">
                    <option value="">-- Semua Kategori --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="order" value="<?= e($order) ?>">

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-coksu btn-sm flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Terapkan
                </button>
                <?php if (!empty($search) || $categoryId): ?>
                    <a href="<?= url('products/index.php') ?>" class="btn btn-outline-secondary btn-sm rounded-pill" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Data Produk -->
<div class="card card-clean">
    <div class="table-responsive">
        <table class="table-clean align-middle">
            <thead>
                <tr>
                    <th>
                        <a href="<?= getSortUrl('sku', $sort, $order) ?>" class="text-decoration-none text-muted">
                            SKU <?= getSortIcon('sku', $sort, $order) ?>
                        </a>
                    </th>
                    <th>
                        <a href="<?= getSortUrl('name', $sort, $order) ?>" class="text-decoration-none text-muted">
                            Nama Produk <?= getSortIcon('name', $sort, $order) ?>
                        </a>
                    </th>
                    <th>
                        <a href="<?= getSortUrl('category', $sort, $order) ?>" class="text-decoration-none text-muted">
                            Kategori <?= getSortIcon('category', $sort, $order) ?>
                        </a>
                    </th>
                    <th class="text-end">
                        <a href="<?= getSortUrl('price', $sort, $order) ?>" class="text-decoration-none text-muted">
                            Harga Satuan <?= getSortIcon('price', $sort, $order) ?>
                        </a>
                    </th>
                    <th class="text-center">
                        <a href="<?= getSortUrl('stock', $sort, $order) ?>" class="text-decoration-none text-muted">
                            Sisa Stok <?= getSortIcon('stock', $sort, $order) ?>
                        </a>
                    </th>
                    <th class="text-center">Min</th>
                    <th class="text-center" style="width: 170px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Tidak ada produk yang cocok dengan pencarian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <?php $isLow = ($p['stock'] <= $p['minimum_stock']); ?>
                        <tr>
                            <td class="font-monospace small fw-bold text-muted"><?= e($p['sku']) ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($p['name']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($p['category_name']) ?></span>
                            </td>
                            <td class="text-end fw-semibold text-dark"><?= format_rupiah($p['price']) ?></td>
                            <td class="text-center">
                                <?php if ($isLow): ?>
                                    <span class="badge-out fw-bold fs-6"><?= number_format($p['stock']) ?></span>
                                    <div class="text-danger fw-bold" style="font-size: 0.68rem;">Stok Menipis</div>
                                <?php else: ?>
                                    <span class="badge-in fw-bold fs-6"><?= number_format($p['stock']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-muted small"><?= number_format($p['minimum_stock']) ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('stock/in.php?product_id=' . $p['id']) ?>" class="btn btn-outline-success" title="Tambah Stok (IN)">
                                        <i class="bi bi-box-arrow-in-down"></i>
                                    </a>
                                    <a href="<?= url('stock/out.php?product_id=' . $p['id']) ?>" class="btn btn-outline-warning" title="Keluarkan Stok (OUT)">
                                        <i class="bi bi-box-arrow-up"></i>
                                    </a>
                                    <?php if (has_role('admin')): ?>
                                        <a href="<?= url('products/edit.php?id=' . $p['id']) ?>" class="btn btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= url('products/delete.php?id=' . $p['id']) ?>" class="btn btn-outline-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center py-3 px-4">
        <div class="small text-muted mb-2 mb-md-0">
            Menampilkan <strong><?= count($products) ?></strong> dari <strong><?= $totalRows ?></strong> total barang
            (Halaman <strong><?= $page ?></strong> / <strong><?= $totalPages ?></strong>)
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
                        <a class="page-link" href="?<?= http_build_query($prev) ?>">&laquo;</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php 
                            $curr = $_GET;
                            $curr['page'] = $i;
                        ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link <?= ($i === $page) ? 'bg-caramel border-0 text-white' : '' ?>" href="?<?= http_build_query($curr) ?>" style="<?= ($i === $page) ? 'background-color: var(--color-primary); border-color: var(--color-primary);' : '' ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query($next) ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../views/footer.php'; ?>
