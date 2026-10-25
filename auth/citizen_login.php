<?php
/**
 * Citizen Login
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

// Simple rate-limit: max 10 attempts per IP per 15 minutes via session
$attemptKey = 'login_attempts_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
$lockKey    = 'login_locked_until_' . md5($_SERVER['REMOTE_ADDR'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Check lockout
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
                    $_SESSION[$lockKey] = time() + 900; // 15 min
                    unset($_SESSION[$attemptKey]);
                }
            } elseif ($user['account_status'] !== 'active') {
                $errors[] = 'Your account has been ' . e($user['account_status']) . '. Please contact support.';
            } else {
                // Successful login
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
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <i class="bi bi-person-circle"></i>
            <h2>Citizen Login</h2>
            <p><?= e(APP_NAME) ?></p>
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
                <input type="email" name="email" class="form-control" value="<?= e($email) ?>"
                       required autofocus autocomplete="email">
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       required autocomplete="current-password">
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </button>
            </div>
        </form>

        <div class="text-center mt-3 small">
            New citizen?
            <a href="<?= APP_URL ?>/auth/citizen_register.php" class="fw-semibold">Register here</a>
        </div>
        <div class="text-center mt-2 small">
            Staff member?
            <a href="<?= APP_URL ?>/auth/staff_login.php">Staff Login</a>
        </div>
        <div class="text-center mt-2 small">
            <a href="<?= APP_URL ?>/">← Back to Home</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
