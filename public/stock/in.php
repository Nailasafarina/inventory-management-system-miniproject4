<?php
/**
 * Mini Project 3: Inventory Management System
 * Transaksi: Barang Masuk (Stock IN) - Tema Estetik
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_login();

$activeNav = 'stock';
$pageTitle = 'Catat Barang Masuk (Stock IN)';

$errors = [];
$productId = validate_int($_GET['product_id'] ?? ($_POST['product_id'] ?? null), 1);
$quantity = '';
$referenceNote = '';

$productsList = $pdo->query('
    SELECT p.id, p.sku, p.name, p.stock, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    ORDER BY p.name ASC
')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Token CSRF tidak valid. Silakan submit kembali.';
    } else {
        $productId = validate_int($_POST['product_id'] ?? null, 1);
        $quantity = validate_int($_POST['quantity'] ?? null, 1);
        $referenceNote = sanitize_text($_POST['reference_note'] ?? '');

        if (!$productId) {
            $errors[] = 'Pilih produk yang akan ditambahkan stoknya.';
        }

        if ($quantity === false || $quantity <= 0) {
            $errors[] = 'Jumlah barang masuk (quantity) harus lebih dari 0.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmtLock = $pdo->prepare('SELECT id, sku, name, stock FROM products WHERE id = :id FOR UPDATE');
                $stmtLock->execute(['id' => $productId]);
                $product = $stmtLock->fetch();

                if (!$product) {
                    throw new Exception('Data produk tidak ditemukan.');
                }

                $stmtUpdate = $pdo->prepare('UPDATE products SET stock = stock + :qty WHERE id = :id');
                $stmtUpdate->execute([
                    'qty' => $quantity,
                    'id'  => $productId
                ]);

                $stmtMovement = $pdo->prepare('
                    INSERT INTO stock_movements (product_id, user_id, type, quantity, reference_note)
                    VALUES (:product_id, :user_id, "IN", :quantity, :reference_note)
                ');
                $stmtMovement->execute([
                    'product_id'     => $productId,
                    'user_id'        => current_user()['id'],
                    'quantity'       => $quantity,
                    'reference_note' => !empty($referenceNote) ? $referenceNote : 'Penerimaan Barang Masuk'
                ]);

                $pdo->commit();

                $newStock = $product['stock'] + $quantity;
                set_flash('success', "Sukses mencatat barang masuk! Stok [{$product['sku']}] {$product['name']} bertambah dari {$product['stock']} menjadi {$newStock}.");
                header('Location: ' . url('stock/history.php'));
                exit;

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Stock IN Transaction Error: ' . $e->getMessage());
                $errors[] = 'Gagal memproses transaksi: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../views/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                <div class="d-flex align-items-center">
                    <span class="badge-in me-2 fs-6"><i class="bi bi-box-arrow-in-down"></i> IN</span>
                    <h5 class="fw-bold mb-0 text-dark">Pencatatan Barang Masuk</h5>
                </div>
                <a href="<?= url('stock/history.php') ?>" class="btn btn-sm btn-outline-secondary btn-pill">Riwayat Mutasi</a>
            </div>
            <div class="card-body p-4">
                <div class="alert-theme py-2 px-3 small mb-4 d-flex align-items-center">
                    <i class="bi bi-shield-lock-fill me-2 fs-5 text-warning"></i>
                    <div>
                        Transaksi diamankan oleh <strong>Database Transaction (ACID)</strong>. Penambahan stok dan histori mutasi dijamin konsisten.
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= url('stock/in.php') ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label-custom">PILIH PRODUK <span class="text-danger">*</span></label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">-- Pilih Barang yang Diterima --</option>
                            <?php foreach ($productsList as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $productId == $p['id'] ? 'selected' : '' ?>>
                                    [<?= e($p['sku']) ?>] <?= e($p['name']) ?> (Sisa Fisik: <?= number_format($p['stock']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">JUMLAH BARANG MASUK (QUANTITY) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="quantity" name="quantity" value="<?= e($quantity) ?>" placeholder="Contoh: 10" required min="1">
                        <small class="text-muted" style="font-size: 0.75rem;">Harus berupa bilangan bulat positif lebih dari nol.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">CATATAN / SURAT JALAN / SUPPLIER</label>
                        <textarea class="form-control" id="reference_note" name="reference_note" rows="2" placeholder="Contoh: PO-2026-001 dari Supplier"><?= e($referenceNote) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= url('dashboard.php') ?>" class="btn btn-light btn-pill px-4">Batal</a>
                        <button type="submit" class="btn btn-caramel btn-pill px-4">
                            <i class="bi bi-check2-circle me-1"></i> Proses Barang Masuk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../views/footer.php'; ?>
