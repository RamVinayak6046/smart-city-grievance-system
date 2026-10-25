<?php
http_response_code(403);
require_once __DIR__ . '/boot.php';
$pageTitle = '403 Access Restricted';

$currentRole = $_SESSION['role'] ?? null;
$currentUserName = $_SESSION['user_name'] ?? 'User';

$dashMap = [
    'citizen'          => ['label' => 'Citizen Dashboard', 'url' => APP_URL . '/citizen/dashboard.php'],
    'work_official'    => ['label' => 'Work Official Dashboard', 'url' => APP_URL . '/official/dashboard.php'],
    'department_admin' => ['label' => 'Department Admin Dashboard', 'url' => APP_URL . '/department/dashboard.php'],
    'super_admin'      => ['label' => 'Super Admin Dashboard', 'url' => APP_URL . '/superadmin/dashboard.php'],
];

$myDash = $currentRole ? ($dashMap[$currentRole] ?? null) : null;
$reqRoleName = isset($requiredRole) ? ucwords(str_replace('_', ' ', $requiredRole)) : '';
$currRoleName = $currentRole ? ucwords(str_replace('_', ' ', $currentRole)) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Access Restricted — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        body {
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .error-card {
            max-width: 560px;
            width: 100%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2.5rem 2rem;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.06);
            text-align: center;
        }
        .error-icon-box {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background-color: #fef2f2;
            color: #dc2626;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon-box">
            <i class="bi bi-shield-x"></i>
        </div>
        <div class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill mb-2">HTTP 403 Forbidden</div>
        <h2 class="fw-bold text-dark mb-2">Access Restricted</h2>

        <?php if ($currentRole): ?>
            <p class="text-muted mb-3">
                You are currently signed in as <strong><?= e($currentUserName) ?></strong> with the
                <span class="badge bg-secondary"><?= e($currRoleName) ?></span> role.
            </p>
            <?php if ($reqRoleName): ?>
                <div class="alert alert-warning text-start small mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i>
                    This section is restricted to <strong><?= e($reqRoleName) ?></strong> accounts.
                </div>
            <?php else: ?>
                <p class="text-muted small mb-4">Your current role does not have authorization to access this page.</p>
            <?php endif; ?>

            <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                <?php if ($myDash): ?>
                    <a href="<?= e($myDash['url']) ?>" class="btn btn-primary px-3">
                        <i class="bi bi-speedometer2 me-1"></i> <?= e($myDash['label']) ?>
                    </a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/auth/logout.php" class="btn btn-outline-danger px-3">
                    <i class="bi bi-box-arrow-right me-1"></i> Switch Account / Logout
                </a>
                <a href="<?= APP_URL ?>/" class="btn btn-light border px-3">
                    <i class="bi bi-house me-1"></i> Home
                </a>
            </div>
        <?php else: ?>
            <p class="text-muted mb-4">You do not have permission to access this page or resource.</p>
            <div class="d-flex justify-content-center gap-2">
                <a href="<?= APP_URL ?>/" class="btn btn-primary px-4">
                    <i class="bi bi-house me-1"></i> Return Home
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>