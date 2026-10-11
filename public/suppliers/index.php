<?php
/**
 * Mini Project 3: Inventory Management System
 * Master Data Supplier: Listing dengan Relasi Many-to-Many
 *
 * Referensi Slide 8:
 * Relasi many-to-many produk dan supplier melalui junction table product_suppliers
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';

require_login();

$activeNav = 'suppliers';
$pageTitle = 'Master Data Supplier';

// Query supplier dengan hitungan relasi barang yang dipasok dari junction table
$sql = '
    SELECT s.id, s.name, s.contact_person, s.phone, s.address, s.created_at,
           COUNT(ps.product_id) AS total_supplied_products
    FROM suppliers s
    LEFT JOIN product_suppliers ps ON ps.supplier_id = s.id
    GROUP BY s.id, s.name, s.contact_person, s.phone, s.address, s.created_at
    ORDER BY s.name ASC
';
$suppliers = $pdo->query($sql)->fetchAll();

include __DIR__ . '/../../views/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Master Data Supplier (Pemasok)</h4>
        <p class="text-muted small mb-0">Daftar mitra distributor penyuplai persediaan gudang.</p>
    </div>
    <?php if (has_role('admin')): ?>
        <a href="<?= url('suppliers/create.php') ?>" class="btn btn-coksu btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Tambah Supplier
        </a>
    <?php endif; ?>
</div>

<div class="card card-clean">
    <div class="table-responsive">
        <table class="table-clean align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Nama Supplier</th>
                    <th>Contact Person</th>
                    <th>No. Telepon</th>
                    <th>Alamat</th>
                    <th class="text-center">Produk Terkait</th>
                    <th class="text-center" style="width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada data supplier yang tersimpan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $i => $s): ?>
                        <tr>
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td class="fw-semibold text-dark"><?= e($s['name']) ?></td>
                            <td><?= e($s['contact_person'] ?: '-') ?></td>
                            <td class="font-monospace small"><?= e($s['phone'] ?: '-') ?></td>
                            <td class="text-muted small text-truncate" style="max-width: 200px;"><?= e($s['address'] ?: '-') ?></td>
                            <td class="text-center">
                                <span class="badge bg-info-subtle text-info-emphasis border px-2">
                                    <?= number_format($s['total_supplied_products']) ?> SKU
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if (has_role('admin')): ?>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('suppliers/edit.php?id=' . $s['id']) ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= url('suppliers/delete.php?id=' . $s['id']) ?>" class="btn btn-outline-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus supplier ini?');">
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
