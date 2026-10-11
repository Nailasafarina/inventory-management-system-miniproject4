<?php
/**
 * Mini Project 3: Inventory Management System
 * Tambah Produk Baru (Khusus Admin)
 *
 * Menerapkan:
 * - Role Authorization: Admin only
 * - Database Transaction (Products + Product Suppliers + Initial Movement)
 * - Server-side Validation: Unique SKU, Positive Price & Stock
 * - Output Encoding & CSRF
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

// Hak akses khusus admin (Slide 24 & 33)
require_role('admin');

$activeNav = 'products';
$pageTitle = 'Tambah Produk Baru';

$errors = [];
$sku = '';
$name = '';
$categoryId = '';
$price = '';
$initialStock = 0;
$minimumStock = 5;
$selectedSuppliers = [];

// Ambil Kategori & Supplier untuk Form
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
$suppliers = $pdo->query('SELECT id, name FROM suppliers ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Token CSRF tidak valid. Silakan submit kembali.';
    } else {
        $sku = strtoupper(sanitize_text($_POST['sku'] ?? ''));
        $name = sanitize_text($_POST['name'] ?? '');
        $categoryId = validate_int($_POST['category_id'] ?? null, 1);
        $price = validate_float($_POST['price'] ?? null, 0);
        $initialStock = validate_int($_POST['initial_stock'] ?? 0, 0);
        $minimumStock = validate_int($_POST['minimum_stock'] ?? 5, 0);
        $selectedSuppliers = isset($_POST['suppliers']) && is_array($_POST['suppliers']) ? $_POST['suppliers'] : [];

        // Validasi Bisnis (Slide 33)
        if (empty($sku)) {
            $errors[] = 'SKU wajib diisi.';
        } elseif (!is_sku_unique($pdo, $sku)) {
            $errors[] = 'SKU "' . $sku . '" sudah digunakan oleh produk lain. SKU harus unik.';
        }

        if (empty($name)) {
            $errors[] = 'Nama produk wajib diisi.';
        }

        if (!$categoryId) {
            $errors[] = 'Pilih kategori produk yang valid.';
        }

        if ($price === false) {
            $errors[] = 'Harga produk harus berupa angka dan tidak boleh negatif.';
        }

        if ($initialStock === false) {
            $errors[] = 'Stok awal harus berupa bilangan bulat non-negatif.';
        }

        if ($minimumStock === false) {
            $errors[] = 'Batas minimum stok harus berupa bilangan bulat non-negatif.';
        }

        // Jika tidak ada error, eksekusi transaksi database
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // 1. Insert ke tabel products
                $stmtInsertProduct = $pdo->prepare('
                    INSERT INTO products (category_id, sku, name, price, stock, minimum_stock)
                    VALUES (:category_id, :sku, :name, :price, :stock, :minimum_stock)
                ');
                $stmtInsertProduct->execute([
                    'category_id'   => $categoryId,
                    'sku'           => $sku,
                    'name'          => $name,
                    'price'         => $price,
                    'stock'         => $initialStock,
                    'minimum_stock' => $minimumStock
                ]);
                $productId = (int) $pdo->lastInsertId();

                // 2. Hubungkan ke Supplier (Junction Table: product_suppliers - Relasi Many-to-Many)
                if (!empty($selectedSuppliers)) {
                    $stmtJunction = $pdo->prepare('
                        INSERT INTO product_suppliers (product_id, supplier_id, supplier_price)
                        VALUES (:product_id, :supplier_id, :supplier_price)
                    ');
                    foreach ($selectedSuppliers as $supId) {
                        $supId = validate_int($supId, 1);
                        if ($supId) {
                            $stmtJunction->execute([
                                'product_id'     => $productId,
                                'supplier_id'    => $supId,
                                'supplier_price' => $price // Default harga beli
                            ]);
                        }
                    }
                }

                // 3. Jika ada stok awal > 0, catat mutasi masuk ke stock_movements
                if ($initialStock > 0) {
                    $stmtMovement = $pdo->prepare('
                        INSERT INTO stock_movements (product_id, user_id, type, quantity, reference_note)
                        VALUES (:product_id, :user_id, "IN", :quantity, :reference_note)
                    ');
                    $stmtMovement->execute([
                        'product_id'     => $productId,
                        'user_id'        => current_user()['id'],
                        'quantity'       => $initialStock,
                        'reference_note' => 'Saldo Stok Awal Produk Baru'
                    ]);
                }

                $pdo->commit();

                set_flash('success', 'Produk "' . $name . '" berhasil ditambahkan ke inventori!');
                header('Location: ' . url('products/index.php'));
                exit;

            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('Error create product: ' . $e->getMessage());
                $errors[] = 'Gagal menyimpan produk ke database: ' . $e->getMessage();
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
                    <i class="bi bi-box-seam me-2 text-primary"></i>Tambah Produk Baru
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

                <form method="POST" action="<?= url('products/create.php') ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="sku" class="form-label small fw-semibold">SKU (Kode Produk) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" id="sku" name="sku" value="<?= e($sku) ?>" placeholder="Contoh: ELK-LOG-001" required>
                            <div class="form-text" style="font-size: 0.75rem;">SKU bersifat unik untuk tiap barang.</div>
                        </div>
                        <div class="col-md-8">
                            <label for="name" class="form-label small fw-semibold">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" placeholder="Nama lengkap produk..." required>
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
                            <label for="price" class="form-label small fw-semibold">Harga Jual / Nilai Satuan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="100" class="form-control" id="price" name="price" value="<?= e($price) ?>" placeholder="0" required min="0">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="initial_stock" class="form-label small fw-semibold">Stok Awal</label>
                            <input type="number" class="form-control" id="initial_stock" name="initial_stock" value="<?= e($initialStock) ?>" min="0">
                            <div class="form-text" style="font-size: 0.75rem;">Akan otomatis dicatat sebagai mutasi IN di riwayat.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="minimum_stock" class="form-label small fw-semibold">Batas Minimum Stok (Alert)</label>
                            <input type="number" class="form-control" id="minimum_stock" name="minimum_stock" value="<?= e($minimumStock) ?>" min="0">
                            <div class="form-text" style="font-size: 0.75rem;">Sistem akan memberi peringatan jika stok &le; angka ini.</div>
                        </div>
                    </div>

                    <!-- Relasi Many-to-Many: Pilihan Supplier -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold d-block">Pemasok / Supplier Terkait (Many-to-Many):</label>
                        <div class="p-3 border rounded bg-light">
                            <?php if (empty($suppliers)): ?>
                                <small class="text-muted">Belum ada data supplier. Tambahkan di menu Master Supplier terlebih dahulu.</small>
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
                            <i class="bi bi-save me-1"></i> Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../views/footer.php'; ?>
