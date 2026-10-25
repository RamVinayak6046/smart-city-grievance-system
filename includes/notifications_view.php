<?php
/**
 * Shared Notifications View
 */
require_once __DIR__ . '/../includes/boot.php';
requireLogin();

$role   = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$pdo    = db();

$recipientType = roleToRecipientType($role);

// Mark all read
if (isset($_GET['mark_all'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE recipient_type = :rt AND recipient_id = :rid AND is_read = 0")
        ->execute([':rt' => $recipientType, ':rid' => $userId]);
    redirectTo($_SERVER['PHP_SELF']);
}

// Mark single
if (isset($_GET['read'])) {
    $nid = (int)$_GET['read'];
    $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE id = :id AND recipient_type = :rt AND recipient_id = :rid")
        ->execute([':id' => $nid, ':rt' => $recipientType, ':rid' => $userId]);
}

$stmt = $pdo->prepare(
    "SELECT * FROM notifications 
     WHERE recipient_type = :rt AND recipient_id = :rid 
     ORDER BY created_at DESC LIMIT 50"
);
$stmt->execute([':rt' => $recipientType, ':rid' => $userId]);
$notifications = $stmt->fetchAll();

$pageTitle   = 'Notifications';
$dashUrl = match($role) {
    'work_official'    => APP_URL . '/official/dashboard.php',
    'department_admin' => APP_URL . '/department/dashboard.php',
    'super_admin'      => APP_URL . '/superadmin/dashboard.php',
    default            => APP_URL . '/citizen/dashboard.php',
};

$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => $dashUrl],
    ['label' => 'Notifications'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted small"><?= count($notifications) ?> notifications</span>
    <?php if (!empty($notifications)): ?>
        <a href="?mark_all=1" class="btn btn-sm btn-outline-secondary">Mark All Read</a>
    <?php endif; ?>
</div>

<div class="data-card">
    <?php if (empty($notifications)): ?>
        <div class="empty-state py-5"><i class="bi bi-bell-slash"></i><p>No notifications.</p></div>
    <?php else: ?>
        <?php foreach ($notifications as $n): ?>
            <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                <?php if (!$n['is_read']): ?><div class="notif-dot"></div><?php endif; ?>
                <div class="flex-grow-1">
                    <div class="fw-semibold small"><?= e($n['title']) ?></div>
                    <div class="text-muted small"><?= e($n['message']) ?></div>
                    <div class="text-muted" style="font-size: .75rem;"><?= timeAgo($n['created_at']) ?></div>
                </div>
                <?php if (!$n['is_read']): ?>
                    <a href="?read=<?= (int)$n['id'] ?>" class="btn btn-sm btn-link text-decoration-none">Mark read</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
