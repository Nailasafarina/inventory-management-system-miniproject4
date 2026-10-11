<?php
/**
 * Mini Project 3: Inventory Management System
 * Master Data Kategori: Listing dengan Query Agregat
 *
 * Referensi Slide 14:
 * SELECT c.name, COUNT(p.id) AS total_products, SUM(p.stock) AS total_stock
 * FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id;
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';

require_login();

$activeNav = 'categories';
$pageTitle = 'Master Data Kategori';

// Query agregat kategori dengan jumlah produk dan total stok
$sql = '
    SELECT c.id, c.name, c.description, c.created_at,
           COUNT(p.id) AS total_products,
           COALESCE(SUM(p.stock), 0) AS total_stock
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name, c.description, c.created_at
    ORDER BY c.name ASC
';
$categories = $pdo->query($sql)->fetchAll();

include __DIR__ . '/../../views/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Master Data Kategori</h4>
        <p class="text-muted small mb-0">Klasifikasi kelompok produk untuk memudahkan manajemen barang.</p>
    </div>
    <?php if (has_role('admin')): ?>
        <a href="<?= url('categories/create.php') ?>" class="btn btn-coksu btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Tambah Kategori
        </a>
    <?php endif; ?>
</div>

<div class="card card-clean">
    <div class="table-responsive">
        <table class="table-clean align-middle">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th class="text-center">Jumlah Produk</th>
                    <th class="text-center">Total Fisik Stok</th>
                    <th class="text-center" style="width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada kategori yang ditambahkan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $index => $cat): ?>
                        <tr>
                            <td class="text-muted small"><?= $index + 1 ?></td>
                            <td class="fw-semibold text-dark"><?= e($cat['name']) ?></td>
                            <td class="text-muted small"><?= e($cat['description'] ?: '-') ?></td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary border px-2">
                                    <?= number_format($cat['total_products']) ?> Produk
                                </span>
                            </td>
                            <td class="text-center fw-medium text-dark">
                                <?= number_format($cat['total_stock']) ?> Unit
                            </td>
                            <td class="text-center">
                                <?php if (has_role('admin')): ?>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('categories/edit.php?id=' . $cat['id']) ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= url('categories/delete.php?id=' . $cat['id']) ?>" class="btn btn-outline-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../views/footer.php'; ?>
