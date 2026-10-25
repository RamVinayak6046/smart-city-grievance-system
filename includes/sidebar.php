<?php
/**
 * Dashboard sidebar — included inside all dashboard pages.
 * Requires $_SESSION['role'] to be set.
 */
$role    = $_SESSION['role']      ?? '';
$userId  = $_SESSION['user_id']   ?? 0;
$deptId  = $_SESSION['dept_id']   ?? null;
$notifCount = unreadNotificationCount(
    $userId,
    roleToRecipientType($role)
);

// ── Build nav menu per role ────────────────────────────────────
$nav = [];

if ($role === 'citizen') {
    $base = APP_URL . '/citizen';
    $nav  = [
        ['icon' => 'bi-speedometer2',  'label' => 'Dashboard',         'href' => "$base/dashboard.php"],
        ['icon' => 'bi-plus-circle',   'label' => 'Report New Issue',  'href' => "$base/submit_complaint.php"],
        ['icon' => 'bi-list-ul',       'label' => 'My Complaints',     'href' => "$base/my_complaints.php"],
        ['icon' => 'bi-search',        'label' => 'Track Complaint',   'href' => "$base/track_complaint.php"],
        ['icon' => 'bi-bell',          'label' => 'Notifications',     'href' => "$base/notifications.php",  'badge' => $notifCount],
        ['icon' => 'bi-person-circle', 'label' => 'My Profile',        'href' => "$base/profile.php"],
    ];
} elseif ($role === 'work_official') {
    $base = APP_URL . '/official';
    $nav  = [
        ['icon' => 'bi-speedometer2',  'label' => 'Dashboard',         'href' => "$base/dashboard.php"],
        ['icon' => 'bi-clipboard-check','label'=> 'My Assignments',    'href' => "$base/assignments.php"],
        ['icon' => 'bi-bell',          'label' => 'Notifications',     'href' => "$base/notifications.php", 'badge' => $notifCount],
        ['icon' => 'bi-person-circle', 'label' => 'My Profile',        'href' => "$base/profile.php"],
    ];
} elseif ($role === 'department_admin') {
    $base = APP_URL . '/department';
    $nav  = [
        ['icon' => 'bi-speedometer2',    'label' => 'Dashboard',          'href' => "$base/dashboard.php"],
        ['icon' => 'bi-inbox',           'label' => 'Complaint Queue',     'href' => "$base/complaints.php"],
        ['icon' => 'bi-exclamation-circle','label'=> 'Unassigned',         'href' => "$base/unassigned.php"],
        ['icon' => 'bi-diagram-3',       'label' => 'Assignments',         'href' => "$base/assignments.php"],
        ['icon' => 'bi-patch-check',     'label' => 'Verify Completion',   'href' => "$base/verify_completion.php"],
        ['icon' => 'bi-people',          'label' => 'Officials',           'href' => "$base/officials.php"],
        ['icon' => 'bi-bar-chart',       'label' => 'Reports',             'href' => "$base/reports.php"],
        ['icon' => 'bi-bell',            'label' => 'Notifications',       'href' => "$base/notifications.php", 'badge' => $notifCount],
    ];
} elseif ($role === 'super_admin') {
    $base = APP_URL . '/superadmin';
    $nav  = [
        ['icon' => 'bi-speedometer2',   'label' => 'Dashboard',          'href' => "$base/dashboard.php"],
        ['icon' => 'bi-building',       'label' => 'Departments',        'href' => "$base/departments.php"],
        ['icon' => 'bi-tags',           'label' => 'Categories',         'href' => "$base/categories.php"],
        ['icon' => 'bi-geo-alt',        'label' => 'Wards',              'href' => "$base/wards.php"],
        ['icon' => 'bi-person-badge',   'label' => 'Dept Admins',        'href' => "$base/manage_admins.php"],
        ['icon' => 'bi-person-gear',    'label' => 'Work Officials',     'href' => "$base/manage_officials.php"],
        ['icon' => 'bi-people',         'label' => 'Citizens',           'href' => "$base/manage_citizens.php"],
        ['icon' => 'bi-inbox-fill',     'label' => 'All Complaints',     'href' => "$base/complaints.php"],
        ['icon' => 'bi-bar-chart-line', 'label' => 'Reports',            'href' => "$base/reports.php"],
        ['icon' => 'bi-journal-text',   'label' => 'Audit Logs',         'href' => "$base/audit_logs.php"],
        ['icon' => 'bi-gear',           'label' => 'Settings',           'href' => "$base/settings.php"],
        ['icon' => 'bi-bell',           'label' => 'Notifications',      'href' => "$base/notifications.php", 'badge' => $notifCount],
    ];
}

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
?>

<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <i class="bi bi-buildings-fill"></i>
        <span><?= e(APP_NAME) ?></span>
    </div>

    <!-- User info -->
    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= e($_SESSION['user_name'] ?? '') ?></div>
            <div class="sidebar-user-role"><?= e(ucwords(str_replace('_', ' ', $role))) ?></div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <?php foreach ($nav as $item): ?>
            <?php
            $isActive = str_contains($currentUri, basename($item['href']));
            ?>
            <a href="<?= e($item['href']) ?>"
               class="sidebar-nav-item <?= $isActive ? 'active' : '' ?>">
                <i class="bi <?= e($item['icon']) ?>"></i>
                <span><?= e($item['label']) ?></span>
                <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto"><?= (int)$item['badge'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Logout -->
    <div class="sidebar-footer">
        <a href="<?= APP_URL ?>/auth/logout.php" class="sidebar-nav-item text-danger-light">
            <i class="bi bi-box-arrow-left"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
