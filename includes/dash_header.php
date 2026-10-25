<?php
/**
 * Dashboard HTML wrapper — opens <html> through sidebar.
 * Include BEFORE page content. Close with dash_footer.php.
 *
 * Required before including this:
 *   $pageTitle   (string)
 *   $breadcrumbs (array)  – optional
 *   $extraCss    (string) – optional additional <link> tags
 */
require_once __DIR__ . '/../includes/boot.php';
requireLogin();
$pageTitle   = $pageTitle   ?? APP_NAME;
$breadcrumbs = $breadcrumbs ?? [];
$extraCss    = $extraCss    ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/dashboard.css">
    <?= $extraCss ?>
</head>
<body class="dash-body">

<div class="dash-wrapper" id="dashWrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="dash-main" id="dashMain">
        <?php include __DIR__ . '/navbar.php'; ?>

        <main class="dash-content">
            <!-- Page Title -->
            <div class="dash-page-header">
                <h1 class="dash-page-title"><?= e($pageTitle) ?></h1>
            </div>

            <!-- Flash Messages -->
            <?php
            foreach (['success', 'error', 'warning', 'info'] as $type) {
                $msg = getFlash($type);
                if ($msg) {
                    $bsType = match($type) {
                        'error'   => 'danger',
                        'warning' => 'warning',
                        'success' => 'success',
                        default   => 'info',
                    };
                    echo '<div class="alert alert-' . $bsType . ' alert-dismissible fade show" role="alert">'
                       . '<i class="bi bi-' . ($type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-triangle' : 'info-circle')) . ' me-2"></i>'
                       . e($msg['message'])
                       . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                       . '</div>';
                }
            }
            ?>
