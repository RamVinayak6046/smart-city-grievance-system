<?php
/**
 * Auth middleware — include at top of protected pages.
 * Usage: require_once __DIR__ . '/../middleware/auth.php';
 * Optionally set $requiredRole before including.
 */
require_once __DIR__ . '/../includes/boot.php';

if (!isLoggedIn()) {
    // Remember where the user was trying to go (for post-login redirect)
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
    flash('error', 'Please login to access that page.', 'error');
    redirectTo(APP_URL . '/auth/citizen_login.php');
}

// Check account status for citizens
if ($_SESSION['role'] === 'citizen') {
    try {
        $stmt = db()->prepare("SELECT account_status FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $status = $stmt->fetchColumn();
        if ($status && $status !== 'active') {
            session_destroy();
            redirectTo(APP_URL . '/auth/citizen_login.php?suspended=1');
        }
    } catch (PDOException) {}
} elseif (in_array($_SESSION['role'], ['work_official', 'department_admin', 'super_admin'])) {
    try {
        $stmt = db()->prepare("SELECT account_status FROM staff WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $status = $stmt->fetchColumn();
        if ($status && $status !== 'active') {
            session_destroy();
            redirectTo(APP_URL . '/auth/staff_login.php?suspended=1');
        }
    } catch (PDOException) {}
}

// Enforce role if $requiredRole is set
if (isset($requiredRole) && $_SESSION['role'] !== $requiredRole) {
    http_response_code(403);
    include __DIR__ . '/../includes/403.php';
    exit;
}
