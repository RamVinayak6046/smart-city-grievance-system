<?php
/**
 * Role guard helper — used inside pages that need a specific role.
 *
 * Usage example in any dashboard page:
 *   $requiredRole = 'citizen';
 *   require_once __DIR__ . '/../middleware/role_guard.php';
 */
require_once __DIR__ . '/auth.php';  // ensures logged in

$roleRedirectMap = [
    'citizen'          => APP_URL . '/citizen/dashboard.php',
    'work_official'    => APP_URL . '/official/dashboard.php',
    'department_admin' => APP_URL . '/department/dashboard.php',
    'super_admin'      => APP_URL . '/superadmin/dashboard.php',
];

if (!isset($requiredRole)) {
    // No role specified — just ensure login (already done via auth.php)
    return;
}

$actualRole = $_SESSION['role'] ?? '';

if ($actualRole !== $requiredRole) {
    // Redirect to the correct dashboard for their role
    $dest = $roleRedirectMap[$actualRole] ?? APP_URL . '/';
    flash('error', 'You do not have permission to access that page.', 'error');
    redirectTo($dest);
}
