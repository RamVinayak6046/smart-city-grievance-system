<?php
/**
 * Super Admin Login Page (Role-specific route: super-admin/login.php)
 */
require_once __DIR__ . '/../includes/boot.php';

if (isLoggedIn()) {
    if (($_SESSION['role'] ?? '') === 'super_admin') {
        redirectTo(APP_URL . '/superadmin/dashboard.php');
    } else {
        $_SESSION = [];
        if (session_id()) {
            session_destroy();
        }
        session_start();
    }
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'Email and password are required.';
    } else {
        $pdo  = db();
        $stmt = $pdo->prepare(
            "SELECT s.id, s.name, s.password_hash, s.account_status,
                    s.department_id, r.role_name
             FROM staff s
             JOIN roles r ON r.id = s.role_id
             WHERE s.email = :email AND r.role_name = 'super_admin' LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        $staff = $stmt->fetch();

        if (!$staff || !password_verify($password, $staff['password_hash'])) {
            $errors[] = 'Invalid Super Admin credentials.';
        } elseif ($staff['account_status'] !== 'active') {
            $errors[] = 'This administrative account is suspended.';
        } else {
            session_regenerate_id(true);

            $_SESSION['user_id']   = (int) $staff['id'];
            $_SESSION['user_name'] = $staff['name'];
            $_SESSION['role']      = 'super_admin';
            $_SESSION['dept_id']   = null;

            redirectTo(APP_URL . '/superadmin/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-light bg-white border-bottom shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 text-primary-custom fw-bold" href="<?= APP_URL ?>/">
            <i class="bi bi-buildings-fill fs-4 text-primary"></i>
            <span><?= e(APP_NAME) ?></span>
        </a>
        <a href="<?= APP_URL ?>/" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Home
        </a>
    </div>
</nav>

<div class="auth-wrapper py-5">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; background-color: #f8fafc; border: 2px solid #cbd5e1;">
                <i class="bi bi-gear-wide-connected fs-1 text-primary"></i>
            </div>
            <h3 class="fw-bold text-primary">Super Admin Portal</h3>
            <p class="text-muted small">High-privilege system administration, audit logs, and global configuration.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger small">
                <?php foreach ($errors as $err): ?>
                    <div><i class="bi bi-exclamation-circle me-1"></i><?= $err ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Super Admin Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-shield-lock text-muted"></i></span>
                    <input type="email" name="email" class="form-control" value="<?= e($email) ?>" placeholder="admin@civicfix.local" required autofocus autocomplete="email">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Master Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-key text-muted"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In as Super Admin
                </button>
            </div>
        </form>

        <div class="card mt-4 bg-light border-0">
            <div class="card-body p-3 text-muted small">
                <i class="bi bi-info-circle me-1 text-primary"></i>
                <strong>Sample Demo Account:</strong><br>
                <code>admin@civicfix.local</code> &bull; Password: <code>Admin@123</code>
            </div>
        </div>

        <div class="text-center mt-3 small">
            <a href="<?= APP_URL ?>/" class="text-decoration-none text-muted">
                <i class="bi bi-house me-1"></i> Back to Role Selection
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
