<?php
/**
 * Citizen Login Page (Role-specific route: citizen/login.php)
 */
require_once __DIR__ . '/../includes/boot.php';

if (isLoggedIn()) {
    if (($_SESSION['role'] ?? '') === 'citizen') {
        redirectTo(APP_URL . '/citizen/dashboard.php');
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

$attemptKey = 'login_attempts_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
$lockKey    = 'login_locked_until_' . md5($_SERVER['REMOTE_ADDR'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (!empty($_SESSION[$lockKey]) && time() < $_SESSION[$lockKey]) {
        $wait = ceil(($_SESSION[$lockKey] - time()) / 60);
        $errors[] = "Too many failed attempts. Try again in {$wait} minute(s).";
    } else {
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = 'Email and password are required.';
        } else {
            $pdo  = db();
            $stmt = $pdo->prepare(
                "SELECT id, name, password_hash, account_status FROM users WHERE email = :email LIMIT 1"
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Invalid email or password.';
                $_SESSION[$attemptKey] = ($_SESSION[$attemptKey] ?? 0) + 1;
                if ($_SESSION[$attemptKey] >= 10) {
                    $_SESSION[$lockKey] = time() + 900;
                    unset($_SESSION[$attemptKey]);
                }
            } elseif ($user['account_status'] !== 'active') {
                $errors[] = 'Your account has been ' . e($user['account_status']) . '. Please contact municipal support.';
            } else {
                unset($_SESSION[$attemptKey], $_SESSION[$lockKey]);
                session_regenerate_id(true);

                $_SESSION['user_id']   = (int) $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role']      = 'citizen';
                $_SESSION['dept_id']   = null;

                redirectTo(APP_URL . '/citizen/dashboard.php');
            }
        }
    }
}

$flashSuccess = getFlash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Login — <?= e(APP_NAME) ?></title>
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
            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                <i class="bi bi-person-fill fs-1 text-primary"></i>
            </div>
            <h3 class="fw-bold text-primary">Citizen Portal</h3>
            <p class="text-muted small">Sign in to report civic problems and track resolution progress.</p>
        </div>

        <?php if ($flashSuccess): ?>
            <div class="alert alert-success small">
                <i class="bi bi-check-circle me-1"></i><?= e($flashSuccess['message']) ?>
            </div>
        <?php endif; ?>

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
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control" value="<?= e($email) ?>" placeholder="citizen@example.com" required autofocus autocomplete="email">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In as Citizen
                </button>
            </div>
        </form>

        <div class="card mt-4 border-dashed bg-light">
            <div class="card-body text-center p-3">
                <span class="small text-muted d-block mb-1">New citizen resident?</span>
                <a href="<?= APP_URL ?>/auth/citizen_register.php" class="btn btn-sm btn-outline-primary fw-semibold w-100">
                    <i class="bi bi-person-plus me-1"></i> Register a New Account
                </a>
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
