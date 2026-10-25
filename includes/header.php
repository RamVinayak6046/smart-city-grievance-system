<?php
/**
 * Public site header — used by landing pages (index, about, contact …).
 * Pass $pageTitle before including this file.
 */
$pageTitle = $pageTitle ?? APP_NAME;
$currentPage = $currentPage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  PUBLIC NAVIGATION BAR                                    -->
<!-- ═══════════════════════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= APP_URL ?>/">
            <i class="bi bi-buildings-fill fs-4"></i>
            <span class="fw-bold"><?= e(APP_NAME) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'home' ? 'active' : '' ?>" href="<?= APP_URL ?>/">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'about' ? 'active' : '' ?>" href="<?= APP_URL ?>/about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'how' ? 'active' : '' ?>" href="<?= APP_URL ?>/#how-it-works">How It Works</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>" href="<?= APP_URL ?>/#categories">Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'contact' ? 'active' : '' ?>" href="<?= APP_URL ?>/contact.php">Contact</a>
                </li>
            </ul>

            <div class="d-flex gap-2">
                <?php if (isLoggedIn()): ?>
                    <?php
                    $dash = match($_SESSION['role']) {
                        'citizen'          => APP_URL . '/citizen/dashboard.php',
                        'work_official'    => APP_URL . '/official/dashboard.php',
                        'department_admin' => APP_URL . '/department/dashboard.php',
                        'super_admin'      => APP_URL . '/superadmin/dashboard.php',
                        default            => APP_URL . '/',
                    };
                    ?>
                    <a href="<?= $dash ?>" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-grid me-1"></i>Dashboard
                    </a>
                    <a href="<?= APP_URL ?>/auth/logout.php" class="btn btn-light btn-sm text-primary">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/auth/staff_login.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-person-badge me-1"></i>Staff Login
                    </a>
                    <a href="<?= APP_URL ?>/auth/citizen_login.php" class="btn btn-light btn-sm text-primary fw-semibold">
                        <i class="bi bi-person me-1"></i>Citizen Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
