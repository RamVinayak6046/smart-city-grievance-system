<?php
/**
 * Staff Login (work_official / department_admin / super_admin)
 */
require_once __DIR__ . '/../includes/boot.php';

$portalType = $_GET['portal_type'] ?? $_POST['portal_type'] ?? '';
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    if ($portalType && $portalType !== $role) {
        $_SESSION = [];
        if (session_id()) {
            session_destroy();
        }
        session_start();
    } else {
        $dash = match($role) {
            'work_official'    => APP_URL . '/official/dashboard.php',
            'department_admin' => APP_URL . '/department/dashboard.php',
            'super_admin'      => APP_URL . '/superadmin/dashboard.php',
            default            => APP_URL . '/',
        };
        redirectTo($dash);
    }
}

$errors = [];
$email  = '';

$attemptKey = 'staff_login_attempts_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
$lockKey    = 'staff_login_locked_until_' . md5($_SERVER['REMOTE_ADDR'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (!empty($_SESSION[$lockKey]) && time() < $_SESSION[$lockKey]) {
        $wait     = ceil(($_SESSION[$lockKey] - time()) / 60);
        $errors[] = "Too many failed attempts. Try again in {$wait} minute(s).";
    } else {
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
                 WHERE s.email = :email LIMIT 1"
            );
            $stmt->execute([':email' => $email]);
            $staff = $stmt->fetch();

            if (!$staff || !password_verify($password, $staff['password_hash'])) {
                $errors[] = 'Invalid email or password.';
                $_SESSION[$attemptKey] = ($_SESSION[$attemptKey] ?? 0) + 1;
                if ($_SESSION[$attemptKey] >= 10) {
                    $_SESSION[$lockKey] = time() + 900;
                    unset($_SESSION[$attemptKey]);
                }
            } elseif ($staff['account_status'] !== 'active') {
                $errors[] = 'This account is ' . e($staff['account_status']) . '. Contact Super Admin.';
            } else {
                unset($_SESSION[$attemptKey], $_SESSION[$lockKey]);
                session_regenerate_id(true);

                $_SESSION['user_id']   = (int) $staff['id'];
                $_SESSION['user_name'] = $staff['name'];
                $_SESSION['role']      = $staff['role_name'];
                $_SESSION['dept_id']   = $staff['department_id'] ? (int)$staff['department_id'] : null;

                $dash = match($staff['role_name']) {
                    'work_official'    => APP_URL . '/official/dashboard.php',
                    'department_admin' => APP_URL . '/department/dashboard.php',
                    'super_admin'      => APP_URL . '/superadmin/dashboard.php',
                    default            => APP_URL . '/',
                };
                redirectTo($dash);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper" style="background:linear-gradient(135deg, #0d9488 0%, #1a3c6e 100%)">
    <div class="auth-card">
        <div class="auth-logo">
            <i class="bi bi-person-badge" style="color:#0d9488"></i>
            <h2>Staff Login</h2>
            <p><?= e(APP_NAME) ?> — Municipal Portal</p>
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
                <label class="form-label">Staff Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($email) ?>"
                       required autofocus autocomplete="email">
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       required autocomplete="current-password">
            </div>
            <div class="d-grid">
                <button type="submit" class="btn fw-semibold btn-lg" style="background:#0d9488;color:#fff;">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login to Staff Portal
                </button>
            </div>
        </form>

        <div class="text-center mt-3 small">
            <a href="<?= APP_URL ?>/auth/citizen_login.php">Citizen Login</a>
            &nbsp;·&nbsp;
            <a href="<?= APP_URL ?>/">← Home</a>
        </div>

        <div class="mt-3 p-3 bg-light rounded small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Demo credentials (seed data):</strong><br>
            Super Admin: <code>admin@civicfix.local</code> / <code>Admin@123</code>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
