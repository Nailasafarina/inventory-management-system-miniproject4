<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | InventPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('css/style.css') ?>" rel="stylesheet">
</head>
<body class="login-page-bg">
    <div class="container text-center py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0 rounded-4 p-4 text-center">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 76px; height: 76px; background-color: #fae8e8; color: #9c2727;">
                            <i class="bi bi-shield-lock-fill fs-1"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark">403 - Akses Ditolak</h3>
                    <p class="text-muted small mt-2">
                        Akun Anda tidak memiliki hak otorisasi untuk mengakses fitur ini. Halaman ini memerlukan hak akses 
                        <span class="badge-theme-admin"><?= e($allowedRoles ?? 'Administrator') ?></span>.
                    </p>
                    <div class="mt-4">
                        <a href="<?= url('dashboard.php') ?>" class="btn btn-caramel btn-pill px-4">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
