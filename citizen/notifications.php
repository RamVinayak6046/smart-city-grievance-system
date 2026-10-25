<?php
/**
 * Citizen — Notifications
 */
$requiredRole = 'citizen';
require_once __DIR__ . '/../middleware/role_guard.php';

$citizenId = $_SESSION['user_id'];
$pdo       = db();

// Mark all as read
if (isset($_GET['mark_all'])) {
    $pdo->prepare(
        "UPDATE notifications SET is_read=1, read_at=CURRENT_TIMESTAMP
         WHERE recipient_type='citizen' AND recipient_id=:rid AND is_read=0"
    )->execute([':rid' => $citizenId]);
    redirectTo(APP_URL . '/citizen/notifications.php');
}

// Mark single as read via GET
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $pdo->prepare(
        "UPDATE notifications SET is_read=1, read_at=CURRENT_TIMESTAMP
         WHERE id=:id AND recipient_type='citizen' AND recipient_id=:rid"
    )->execute([':id' => (int)$_GET['read'], ':rid' => $citizenId]);
}

$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;

$total = (int)$pdo->prepare(
    "SELECT COUNT(*) FROM notifications WHERE recipient_type='citizen' AND recipient_id=:rid"
)->execute([':rid' => $citizenId]) ? $pdo->query(
    "SELECT COUNT(*) FROM notifications WHERE recipient_type='citizen' AND recipient_id=$citizenId"
)->fetchColumn() : 0;

$stmt = $pdo->prepare(
    "SELECT * FROM notifications
     WHERE recipient_type='citizen' AND recipient_id=:rid
     ORDER BY created_at DESC
     LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset"
);
$stmt->execute([':rid' => $citizenId]);
$notifs = $stmt->fetchAll();

$pg = paginate($total, ITEMS_PER_PAGE, $page);

$pageTitle   = 'Notifications';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/citizen/dashboard.php'],
    ['label' => 'Notifications'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted small"><?= $total ?> notification<?= $total !== 1 ? 's' : '' ?></span>
    <?php if ($total > 0): ?>
        <a href="?mark_all=1" class="btn btn-sm btn-outline-secondary"
           data-confirm="Mark all notifications as read?">
            <i class="bi bi-check-all me-1"></i>Mark All Read
        </a>
    <?php endif; ?>
</div>

<div class="data-card">
    <?php if (empty($notifs)): ?>
        <div class="empty-state">
            <i class="bi bi-bell-slash"></i>
            <p>No notifications yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notifs as $n): ?>
            <a href="<?php
                $dest = $n['complaint_id']
                    ? APP_URL . '/citizen/complaint_details.php?id=' . (int)$n['complaint_id']
                    : APP_URL . '/citizen/notifications.php?read=' . (int)$n['id'];
                echo e($dest);
            ?>" class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                <?php if (!$n['is_read']): ?><div class="notif-dot"></div><?php endif; ?>
                <div class="flex-grow-1">
                    <div class="fw-semibold small"><?= e($n['title']) ?></div>
                    <div class="text-muted small"><?= e($n['message']) ?></div>
                    <div class="text-muted" style="font-size:.75rem"><?= timeAgo($n['created_at']) ?></div>
                </div>
                <?php if ($n['is_read']): ?>
                    <i class="bi bi-check text-success small"></i>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

        <?php if ($pg['total_pages'] > 1): ?>
            <div class="p-3 text-center">
                <?php if ($pg['has_prev']): ?>
                    <a href="?page=<?= $page-1 ?>" class="btn btn-sm btn-outline-secondary me-1">← Prev</a>
                <?php endif; ?>
                <span class="small text-muted">Page <?= $page ?> / <?= $pg['total_pages'] ?></span>
                <?php if ($pg['has_next']): ?>
                    <a href="?page=<?= $page+1 ?>" class="btn btn-sm btn-outline-secondary ms-1">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
