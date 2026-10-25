<?php
/**
 * Logout — destroys session and redirects home.
 */
require_once __DIR__ . '/../includes/boot.php';

if (isLoggedIn()) {
    // Log the logout event
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $role   = $_SESSION['role']    ?? 'citizen';
        $pdo    = db();
        $stmt   = $pdo->prepare(
            "INSERT INTO audit_logs (actor_id, actor_role, action, ip_address)
             VALUES (:uid, :role, 'logout', :ip)"
        );
        $stmt->execute([
            ':uid'  => $userId,
            ':role' => $role,
            ':ip'   => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (PDOException) {}
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

$redirect = APP_URL . '/';
if (!empty($_GET['redirect'])) {
    $target = $_GET['redirect'];
    if (str_starts_with($target, APP_URL) || (str_starts_with($target, '/') && !str_starts_with($target, '//'))) {
        $redirect = $target;
    }
}
header('Location: ' . $redirect);
exit;
