<?php
/**
 * Citizen — Profile & Password Change
 */
$requiredRole = 'citizen';
require_once __DIR__ . '/../middleware/role_guard.php';

$citizenId = $_SESSION['user_id'];
$pdo       = db();

// Load current data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $citizenId]);
$user = $stmt->fetch();

$wards = $pdo->query("SELECT id, ward_name FROM wards WHERE status='active' ORDER BY ward_name")->fetchAll();

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name    = sanitize($_POST['name']    ?? '');
        $phone   = sanitize($_POST['phone']   ?? '');
        $wardId  = (int)($_POST['ward_id']   ?? 0);
        $address = sanitize($_POST['address'] ?? '');

        if (empty($name) || strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';
        if ($wardId < 1) $errors[] = 'Please select a ward.';

        if (empty($errors)) {
            $pdo->prepare(
                "UPDATE users SET name = :name, phone = :phone, ward_id = :wid, address = :addr WHERE id = :id"
            )->execute([':name' => $name, ':phone' => $phone ?: null, ':wid' => $wardId, ':addr' => $address ?: null, ':id' => $citizenId]);

            // Refresh session name
            $_SESSION['user_name'] = $name;
            $success = 'Profile updated successfully.';
            $user['name'] = $name;
            $user['phone'] = $phone;
            $user['ward_id'] = $wardId;
            $user['address'] = $address;
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }
        if (strlen($newPass) < 8) $errors[] = 'New password must be at least 8 characters.';
        if ($newPass !== $confirm) $errors[] = 'New passwords do not match.';

        if (empty($errors)) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id")
                ->execute([':hash' => $hash, ':id' => $citizenId]);
            $success = 'Password changed successfully.';
        }
    }
}

$pageTitle   = 'My Profile';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/citizen/dashboard.php'],
    ['label' => 'Profile'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= e($success) ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Profile Info -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-person me-2"></i>Personal Information</div>
            <div class="card-body p-4">
                <form method="post" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="required-star">*</span></label>
                        <input type="text" name="name" class="form-control" maxlength="120"
                               value="<?= e($user['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" disabled>
                        <div class="form-text">Email cannot be changed here.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="tel" name="phone" class="form-control" maxlength="20"
                               value="<?= e($user['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ward <span class="required-star">*</span></label>
                        <select name="ward_id" class="form-select" required>
                            <?php foreach ($wards as $w): ?>
                                <option value="<?= (int)$w['id'] ?>" <?= $user['ward_id'] == $w['id'] ? 'selected' : '' ?>>
                                    <?= e($w['ward_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3" maxlength="500"><?= e($user['address'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                        <small class="text-muted">Member since <?= formatDate($user['created_at'], 'd M Y') ?></small>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-lock me-2"></i>Change Password</div>
            <div class="card-body p-4">
                <form method="post" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label">Current Password <span class="required-star">*</span></label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="required-star">*</span></label>
                        <input type="password" name="new_password" class="form-control" minlength="8" required>
                        <div class="form-text">Minimum 8 characters.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password <span class="required-star">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-warning px-4">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
