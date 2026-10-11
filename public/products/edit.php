<?php
/**
 * Mini Project 3: Inventory Management System
 * Edit Data Produk (Khusus Admin)
 *
 * Menerapkan:
 * - Role Authorization: Admin only
 * - Database Transaction (Products + Product Suppliers Sync)
 * - Server-side Validation: Unique SKU check excluding current ID
 * - Output Encoding & CSRF
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_role('admin');

$activeNav = 'products';
$pageTitle = 'Edit Produk';

$id = validate_int($_GET['id'] ?? null, 1);
if (!$id) {
    set_flash('danger', 'ID Produk tidak valid.');
    header('Location: ' . url('products/index.php'));
    exit;
}

// Ambil data produk saat ini
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Data produk tidak ditemukan di database.');
    header('Location: ' . url('products/index.php'));
    exit;
}

// Ambil supplier yang sudah terhubung dengan produk ini
$stmtSup = $pdo->prepare('SELECT supplier_id FROM product_suppliers WHERE product_id = :id');
$stmtSup->execute(['id' => $id]);
$currentSuppliers = $stmtSup->fetchAll(PDO::FETCH_COLUMN);

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
$suppliers = $pdo->query('SELECT id, name FROM suppliers ORDER BY name ASC')->fetchAll();

$errors = [];
$sku = $product['sku'];
$name = $product['name'];
$categoryId = $product['category_id'];
$price = $product['price'];
$minimumStock = $product['minimum_stock'];
$selectedSuppliers = $currentSuppliers;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $sku = strtoupper(sanitize_text($_POST['sku'] ?? ''));
        $name = sanitize_text($_POST['name'] ?? '');
        $categoryId = validate_int($_POST['category_id'] ?? null, 1);
        $price = validate_float($_POST['price'] ?? null, 0);
        $minimumStock = validate_int($_POST['minimum_stock'] ?? 5, 0);
        $selectedSuppliers = isset($_POST['suppliers']) && is_array($_POST['suppliers']) ? $_POST['suppliers'] : [];

        // Validasi
        if (empty($sku)) {
            $errors[] = 'SKU wajib diisi.';
        } elseif (!is_sku_unique($pdo, $sku, $id)) {
            $errors[] = 'SKU "' . $sku . '" sudah digunakan oleh produk lain.';
        }

        if (empty($name)) {
            $errors[] = 'Nama produk wajib diisi.';
        }

        if (!$categoryId) {
            $errors[] = 'Kategori produk harus dipilih.';
        }

        if ($price === false) {
            $errors[] = 'Harga produk harus berupa angka dan tidak boleh negatif.';
        }

        if ($minimumStock === false) {
            $errors[] = 'Batas minimum stok harus berupa angka non-negatif.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // 1. Update data produk
                $stmtUpdate = $pdo->prepare('
                    UPDATE products 
                    SET category_id = :category_id,
                        sku = :sku,
                        name = :name,
                        price = :price,
                        minimum_stock = :minimum_stock
                    WHERE id = :id
                ');
                $stmtUpdate->execute([
                    'category_id'   => $categoryId,
                    'sku'           => $sku,
                    'name'          => $name,
                    'price'         => $price,
                    'minimum_stock' => $minimumStock,
                    'id'            => $id
                ]);

                // 2. Sinkronisasi many-to-many suppliers
                $stmtDeleteJunction = $pdo->prepare('DELETE FROM product_suppliers WHERE product_id = :id');
                $stmtDeleteJunction->execute(['id' => $id]);

                if (!empty($selectedSuppliers)) {
                    $stmtInsertJunction = $pdo->prepare('
                        INSERT INTO product_suppliers (product_id, supplier_id, supplier_price)
                        VALUES (:product_id, :supplier_id, :supplier_price)
                    ');
                    foreach ($selectedSuppliers as $supId) {
                        $supId = validate_int($supId, 1);
                        if ($supId) {
                            $stmtInsertJunction->execute([
                                'product_id'     => $id,
                                'supplier_id'    => $supId,
                                'supplier_price' => $price
                            ]);
                        }
                    }
                }

                $pdo->commit();

                set_flash('success', 'Data produk "' . $name . '" berhasil diperbarui!');
                header('Location: ' . url('products/index.php'));
                exit;

            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('Error update product: ' . $e->getMessage());
                $errors[] = 'Gagal menyimpan perubahan: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../views/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Data Produk
                </h5>
                <a href="<?= url('products/index.php') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= url('products/edit.php?id=' . $id) ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="sku" class="form-label small fw-semibold">SKU (Kode Produk) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" id="sku" name="sku" value="<?= e($sku) ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label for="name" class="form-label small fw-semibold">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label small fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="price" class="form-label small fw-semibold">Harga Satuan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="100" class="form-control" id="price" name="price" value="<?= e($price) ?>" required min="0">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Stok Saat Ini (Read-Only)</label>
                            <input type="text" class="form-control bg-light" value="<?= number_format($product['stock']) ?>" readonly>
                            <div class="form-text" style="font-size: 0.75rem;">Stok hanya dapat diubah melalui menu Transaksi Masuk / Keluar.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="minimum_stock" class="form-label small fw-semibold">Batas Minimum Stok (Alert) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="minimum_stock" name="minimum_stock" value="<?= e($minimumStock) ?>" required min="0">
                        </div>
                    </div>

                    <!-- Relasi Many-to-Many: Pilihan Supplier -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold d-block">Pemasok / Supplier Terkait:</label>
                        <div class="p-3 border rounded bg-light">
                            <?php if (empty($suppliers)): ?>
                                <small class="text-muted">Belum ada data supplier.</small>
                            <?php else: ?>
                                <div class="row g-2">
                                    <?php foreach ($suppliers as $sup): ?>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="suppliers[]" value="<?= $sup['id'] ?>" id="sup_<?= $sup['id'] ?>" <?= in_array($sup['id'], $selectedSuppliers) ? 'checked' : '' ?>>
                                                <label class="form-check-label small" for="sup_<?= $sup['id'] ?>">
                                                    <?= e($sup['name']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= url('products/index.php') ?>" class="btn btn-light px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../views/footer.php'; ?>
