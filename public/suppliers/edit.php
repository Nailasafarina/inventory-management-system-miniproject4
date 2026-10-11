<?php
/**
 * Mini Project 3: Inventory Management System
 * Edit Data Supplier (Khusus Admin)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_role('admin');

$activeNav = 'suppliers';
$pageTitle = 'Edit Supplier';

$id = validate_int($_GET['id'] ?? null, 1);
if (!$id) {
    set_flash('danger', 'ID Supplier tidak valid.');
    header('Location: ' . url('suppliers/index.php'));
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = :id');
$stmt->execute(['id' => $id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    set_flash('danger', 'Data supplier tidak ditemukan.');
    header('Location: ' . url('suppliers/index.php'));
    exit;
}

$errors = [];
$name = $supplier['name'];
$contactPerson = $supplier['contact_person'];
$phone = $supplier['phone'];
$address = $supplier['address'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $name = sanitize_text($_POST['name'] ?? '');
        $contactPerson = sanitize_text($_POST['contact_person'] ?? '');
        $phone = sanitize_text($_POST['phone'] ?? '');
        $address = sanitize_text($_POST['address'] ?? '');

        if (empty($name)) {
            $errors[] = 'Nama supplier wajib diisi.';
        }

        if (empty($errors)) {
            $stmtUpdate = $pdo->prepare('
                UPDATE suppliers 
                SET name = :name,
                    contact_person = :contact_person,
                    phone = :phone,
                    address = :address
                WHERE id = :id
            ');
            $stmtUpdate->execute([
                'name'           => $name,
                'contact_person' => $contactPerson ?: null,
                'phone'          => $phone ?: null,
                'address'        => $address ?: null,
                'id'             => $id
            ]);

            set_flash('success', 'Data supplier "' . $name . '" berhasil diperbarui!');
            header('Location: ' . url('suppliers/index.php'));
            exit;
        }
    }
}

include __DIR__ . '/../../views/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">Edit Supplier</h5>
                <a href="<?= url('suppliers/index.php') ?>" class="btn btn-sm btn-outline-secondary">Kembali</a>
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

                <form method="POST" action="<?= url('suppliers/edit.php?id=' . $id) ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold">Nama Perusahaan / Supplier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="contact_person" class="form-label small fw-semibold">Contact Person (PIC)</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person" value="<?= e($contactPerson) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label small fw-semibold">No. Telepon / WhatsApp</label>
                            <input type="text" class="form-control font-monospace" id="phone" name="phone" value="<?= e($phone) ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="address" class="form-label small fw-semibold">Alamat Kantor / Gudang</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?= e($address) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= url('suppliers/index.php') ?>" class="btn btn-light px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../views/footer.php'; ?>
