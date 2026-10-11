<?php
/**
 * Mini Project 3: Inventory Management System
 * Tambah Kategori Baru (Khusus Admin)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_role('admin');

$activeNav = 'categories';
$pageTitle = 'Tambah Kategori';

$errors = [];
$name = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $name = sanitize_text($_POST['name'] ?? '');
        $description = sanitize_text($_POST['description'] ?? '');

        if (empty($name)) {
            $errors[] = 'Nama kategori wajib diisi.';
        } else {
            // Cek keunikan nama kategori
            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = :name');
            $stmtCheck->execute(['name' => $name]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $errors[] = 'Kategori dengan nama "' . $name . '" sudah ada.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');
            $stmt->execute([
                'name'        => $name,
                'description' => $description ?: null
            ]);

            set_flash('success', 'Kategori "' . $name . '" berhasil ditambahkan!');
            header('Location: ' . url('categories/index.php'));
            exit;
        }
    }
}

include __DIR__ . '/../../views/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">Tambah Kategori Baru</h5>
                <a href="<?= url('categories/index.php') ?>" class="btn btn-sm btn-outline-secondary">Kembali</a>
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

                <form method="POST" action="<?= url('categories/create.php') ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" placeholder="Contoh: Aksesoris Komputer" required autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label small fw-semibold">Deskripsi (Opsional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Keterangan singkat kategori..."><?= e($description) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= url('categories/index.php') ?>" class="btn btn-light px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">Simpan Kategori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../views/footer.php'; ?>
