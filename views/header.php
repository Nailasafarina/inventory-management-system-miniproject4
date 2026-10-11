<?php
/**
 * Header template - Tema Minimalis & Modern "Coksu" (Cokelat Susu)
 * Latar belakang putih bersih, border halus, aksen hangat coksu
 */
$currentUser = current_user();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>Snack Corner</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Theme: Coksu Minimalist -->
    <link href="<?= url('css/style.css') ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-clean sticky-top">
    <div class="container">
        <!-- Logo Bersih & Elegan: Snack Corner -->
        <a class="navbar-brand-clean" href="<?= url('dashboard.php') ?>">
            <span style="font-size: 1.35rem; line-height: 1;">🍪</span>
            <span class="ms-1 fw-bold" style="color: var(--text-heading); font-size: 1.15rem;">Snack <span style="color: var(--coksu-primary);">Corner</span></span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= url('dashboard.php') ?>">
                        <i class="bi bi-grid me-1"></i> Dashboard
                    </a>
                </li>
                
                <!-- Master Data Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activeNav ?? '', ['products', 'categories', 'suppliers']) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-folder2 me-1"></i> Master Data
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= url('products/index.php') ?>"><i class="bi bi-boxes me-2 text-muted"></i>Katalog Produk</a></li>
                        <li><a class="dropdown-item" href="<?= url('categories/index.php') ?>"><i class="bi bi-tags me-2 text-muted"></i>Kategori</a></li>
                        <li><a class="dropdown-item" href="<?= url('suppliers/index.php') ?>"><i class="bi bi-truck me-2 text-muted"></i>Supplier</a></li>
                    </ul>
                </li>

                <!-- Transaksi Stok Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= ($activeNav ?? '') === 'stock' ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-arrow-left-right me-1"></i> Transaksi
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= url('stock/in.php') ?>"><i class="bi bi-arrow-down-left me-2 text-success"></i>Barang Masuk (IN)</a></li>
                        <li><a class="dropdown-item" href="<?= url('stock/out.php') ?>"><i class="bi bi-arrow-up-right me-2 text-danger"></i>Barang Keluar (OUT)</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item" href="<?= url('stock/history.php') ?>"><i class="bi bi-clock-history me-2 text-muted"></i>Riwayat Mutasi</a></li>
                    </ul>
                </li>

                <!-- Laporan -->
                <li class="nav-item">
                    <a class="nav-link <?= ($activeNav ?? '') === 'reports' ? 'active' : '' ?>" href="<?= url('reports/index.php') ?>">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i> Laporan
                    </a>
                </li>

                <!-- Menu Khusus Admin -->
                <?php if (has_role('admin')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeNav ?? '') === 'users' ? 'active' : '' ?>" href="<?= url('users/index.php') ?>">
                        <i class="bi bi-people me-1"></i> User
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Profil User & Logout Sederhana -->
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-3 d-flex align-items-center">
                    <div class="text-end me-2 d-none d-sm-block">
                        <div class="fw-semibold small text-dark lh-1"><?= e($currentUser['full_name'] ?? $currentUser['username'] ?? 'User') ?></div>
                        <small class="text-muted" style="font-size: 0.72rem;">@<?= e($currentUser['username'] ?? '') ?></small>
                    </div>
                    <span class="badge-role-pill">
                        <?= ($currentUser['role'] ?? '') === 'admin' ? 'Admin' : 'Staff' ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a href="<?= url('logout.php') ?>" class="btn btn-outline-coksu btn-sm py-1 px-3" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                        <i class="bi bi-box-arrow-right me-1"></i> Keluar
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="main-wrapper py-4">
    <div class="container">
        <!-- Flash Alert Minimalis -->
        <?php if ($flash): ?>
            <div class="alert-clean alert-dismissible fade show mb-4 d-flex align-items-center justify-content-between" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle me-2 text-muted"></i>
                    <div><?= e($flash['message']) ?></div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
