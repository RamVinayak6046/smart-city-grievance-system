<?php
/**
 * Citizen Registration
 */
require_once __DIR__ . '/../includes/boot.php';

// Already logged in → redirect
if (isLoggedIn()) {
    redirectTo(APP_URL . '/citizen/dashboard.php');
}

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name     = sanitize($_POST['name']     ?? '');
    $email    = strtolower(trim($_POST['email']    ?? ''));
    $phone    = sanitize($_POST['phone']    ?? '');
    $ward_id  = (int)($_POST['ward_id']  ?? 0);
    $address  = sanitize($_POST['address']  ?? '');
    $password = $_POST['password']  ?? '';
    $confirm  = $_POST['confirm']   ?? '';

    $old = compact('name', 'email', 'phone', 'ward_id', 'address');

    // ── Validation ────────────────────────────────────────────
    if (empty($name) || strlen($name) < 2)  $errors[] = 'Full name must be at least 2 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8)              $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)             $errors[] = 'Passwords do not match.';
    if ($ward_id < 1)                       $errors[] = 'Please select your ward.';

    if (empty($errors)) {
        $pdo = db();

        // Check duplicate email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn()) {
            $errors[] = 'An account with this email already exists. <a href="' . APP_URL . '/auth/citizen_login.php">Login instead?</a>';
        }
    }

    if (empty($errors)) {
        $pdo  = db();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, phone, password_hash, ward_id, address)
             VALUES (:name, :email, :phone, :hash, :ward_id, :address)"
        );
        $stmt->execute([
            ':name'    => $name,
            ':email'   => $email,
            ':phone'   => $phone ?: null,
            ':hash'    => $hash,
            ':ward_id' => $ward_id,
            ':address' => $address ?: null,
        ]);

        flash('success', 'Account created! Please log in.');
        redirectTo(APP_URL . '/auth/citizen_login.php');
    }
}

// Load wards for dropdown
$wards = [];
try {
    $wards = db()->query("SELECT id, ward_name FROM wards WHERE status='active' ORDER BY ward_name")->fetchAll();
} catch (PDOException) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Registration — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width:520px">
        <div class="auth-logo">
            <i class="bi bi-buildings-fill"></i>
            <h2><?= e(APP_NAME) ?></h2>
            <p>Create your citizen account</p>
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
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Full Name <span class="required-star">*</span></label>
                    <input type="text" name="name" class="form-control" maxlength="120"
                           value="<?= e($old['name'] ?? '') ?>" required autofocus>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address <span class="required-star">*</span></label>
                    <input type="email" name="email" class="form-control"
                           value="<?= e($old['email'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mobile Number</label>
                    <input type="tel" name="phone" class="form-control" maxlength="20"
                           value="<?= e($old['phone'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Ward / Area <span class="required-star">*</span></label>
                    <select name="ward_id" class="form-select" required>
                        <option value="">— Select your ward —</option>
                        <?php foreach ($wards as $w): ?>
                            <option value="<?= (int)$w['id'] ?>"
                                <?= (isset($old['ward_id']) && $old['ward_id'] == $w['id']) ? 'selected' : '' ?>>
                                <?= e($w['ward_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Address <small class="text-muted">(optional)</small></label>
                    <textarea name="address" class="form-control" rows="2" maxlength="500"><?= e($old['address'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password <span class="required-star">*</span></label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                    <div class="form-text">Minimum 8 characters.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password <span class="required-star">*</span></label>
                    <input type="password" name="confirm" class="form-control" required>
                </div>
                <div class="col-12 d-grid">
                    <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                </div>
            </div>
        </form>

        <div class="text-center mt-3 small">
            Already have an account?
            <a href="<?= APP_URL ?>/auth/citizen_login.php" class="fw-semibold">Login here</a>
        </div>
        <div class="text-center mt-2 small">
            <a href="<?= APP_URL ?>/">← Back to Home</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
