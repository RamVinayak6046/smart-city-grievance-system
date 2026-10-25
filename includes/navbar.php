<?php
/**
 * Dashboard top navbar — included inside all dashboard pages.
 * Expects: $pageTitle (string), $breadcrumbs (array of ['label'=>'','href'=>''])
 */
$pageTitle   = $pageTitle   ?? 'Dashboard';
$breadcrumbs = $breadcrumbs ?? [];
$userId      = $_SESSION['user_id']  ?? 0;
$role        = $_SESSION['role']     ?? 'citizen';
$notifType   = roleToRecipientType($role);
$notifCount  = unreadNotificationCount($userId, $notifType);

$notifUrl = match($role) {
    'work_official'    => APP_URL . '/official/notifications.php',
    'department_admin' => APP_URL . '/department/notifications.php',
    'super_admin'      => APP_URL . '/superadmin/notifications.php',
    default            => APP_URL . '/citizen/notifications.php',
};
?>
<!-- ═══ DASHBOARD TOP NAVBAR ═══ -->
<header class="dash-topbar">
    <!-- Sidebar toggle (mobile) -->
    <button class="btn btn-sm btn-outline-secondary d-lg-none me-2" id="sidebarToggle" aria-label="Toggle Sidebar">
        <i class="bi bi-list fs-5"></i>
    </button>

    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="flex-grow-1 d-none d-md-block">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item">
                <a href="<?= APP_URL ?>/" class="text-decoration-none">
                    <i class="bi bi-house"></i>
                </a>
            </li>
            <?php foreach ($breadcrumbs as $crumb): ?>
                <?php if (!empty($crumb['href'])): ?>
                    <li class="breadcrumb-item">
                        <a href="<?= e($crumb['href']) ?>" class="text-decoration-none"><?= e($crumb['label']) ?></a>
                    </li>
                <?php else: ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($crumb['label']) ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>

    <!-- Right side actions -->
    <div class="d-flex align-items-center gap-2 ms-auto">
        <!-- Notification bell -->
        <a href="<?= e($notifUrl) ?>" class="btn btn-sm btn-outline-secondary position-relative" title="Notifications">
            <i class="bi bi-bell fs-6"></i>
            <?php if ($notifCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $notifCount > 99 ? '99+' : $notifCount ?>
                    <span class="visually-hidden">unread notifications</span>
                </span>
            <?php endif; ?>
        </a>

        <!-- User dropdown -->
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-primary dropdown-toggle d-flex align-items-center gap-1"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="dash-avatar-xs">
                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                </span>
                <span class="d-none d-sm-inline"><?= e($_SESSION['user_name'] ?? 'User') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li>
                    <span class="dropdown-item-text small text-muted">
                        <?= e(ucwords(str_replace('_', ' ', $role))) ?>
                    </span>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <?php
                $profileUrl = match($role) {
                    'citizen'       => APP_URL . '/citizen/profile.php',
                    'work_official' => APP_URL . '/official/profile.php',
                    default         => '#',
                };
                if ($role === 'citizen' || $role === 'work_official'): ?>
                    <li><a class="dropdown-item" href="<?= $profileUrl ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
                <?php endif; ?>
                <li>
                    <a class="dropdown-item text-danger" href="<?= APP_URL ?>/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
