<?php
/**
 * Citizen Dashboard
 */
$requiredRole = 'citizen';
require_once __DIR__ . '/../middleware/role_guard.php';

$citizenId = $_SESSION['user_id'];
$pdo       = db();

// ── Fetch stats ────────────────────────────────────────────────
$statsSql = $pdo->prepare(
    "SELECT
        COUNT(*)                                                     AS total,
        SUM(status = 'pending')                                      AS pending,
        SUM(status = 'assigned')                                     AS assigned,
        SUM(status = 'in_progress')                                  AS in_progress,
        SUM(status = 'awaiting_verification')                        AS awaiting,
        SUM(status = 'rework_required')                              AS rework,
        SUM(status = 'resolved')                                     AS resolved,
        SUM(status IN ('rejected','cancelled'))                      AS closed
     FROM complaints WHERE citizen_id = :cid"
);
$statsSql->execute([':cid' => $citizenId]);
$stats = $statsSql->fetch();

// ── Recent complaints ──────────────────────────────────────────
$recentSql = $pdo->prepare(
    "SELECT c.id, c.complaint_code, c.title, c.status, c.priority, c.created_at,
            cat.category_name, d.department_name,
            s.name AS official_name
     FROM complaints c
     LEFT JOIN categories cat ON cat.id = c.category_id
     LEFT JOIN departments d  ON d.id  = c.department_id
     LEFT JOIN complaint_assignments ca ON ca.complaint_id = c.id AND ca.assignment_status = 'active'
     LEFT JOIN staff s ON s.id = ca.official_id
     WHERE c.citizen_id = :cid
     ORDER BY c.created_at DESC
     LIMIT 5"
);
$recentSql->execute([':cid' => $citizenId]);
$recent = $recentSql->fetchAll();

// ── Latest notifications ───────────────────────────────────────
$notifSql = $pdo->prepare(
    "SELECT id, title, message, is_read, created_at
     FROM notifications
     WHERE recipient_type = 'citizen' AND recipient_id = :cid
     ORDER BY created_at DESC LIMIT 5"
);
$notifSql->execute([':cid' => $citizenId]);
$notifications = $notifSql->fetchAll();

// ── Page setup ─────────────────────────────────────────────────
$pageTitle   = 'My Dashboard';
$breadcrumbs = [['label' => 'Dashboard']];
include __DIR__ . '/../includes/dash_header.php';
?>

<!-- ── Stats Row ──────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <a href="<?= APP_URL ?>/citizen/my_complaints.php" class="text-decoration-none">
        <div class="stat-card stat-navy">
            <div class="stat-icon"><i class="bi bi-clipboard2-pulse"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$stats['total'] ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div></a>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card stat-amber">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$stats['pending'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card stat-blue">
            <div class="stat-icon"><i class="bi bi-person-gear"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$stats['assigned'] ?></div>
                <div class="stat-label">Assigned</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card stat-purple">
            <div class="stat-icon"><i class="bi bi-tools"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$stats['in_progress'] ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card stat-cyan">
            <div class="stat-icon"><i class="bi bi-patch-check"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$stats['awaiting'] ?></div>
                <div class="stat-label">Verifying</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card stat-green">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$stats['resolved'] ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
    </div>
</div>

<!-- ── Quick action ───────────────────────────────────────────── -->
<div class="mb-4">
    <a href="<?= APP_URL ?>/citizen/submit_complaint.php" class="btn btn-primary btn-lg px-5">
        <i class="bi bi-plus-circle me-2"></i>Report a New Issue
    </a>
    <a href="<?= APP_URL ?>/citizen/track_complaint.php" class="btn btn-outline-secondary btn-lg ms-2">
        <i class="bi bi-search me-2"></i>Track Complaint
    </a>
</div>

<div class="row g-4">
    <!-- Recent Complaints -->
    <div class="col-lg-8">
        <div class="data-card">
            <div class="data-card-header">
                <h2 class="data-card-title">Recent Complaints</h2>
                <a href="<?= APP_URL ?>/citizen/my_complaints.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <?php if (empty($recent)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>You haven't submitted any complaints yet.</p>
                    <a href="<?= APP_URL ?>/citizen/submit_complaint.php" class="btn btn-primary">Report an Issue</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $c): ?>
                                <tr>
                                    <td><code class="small"><?= e($c['complaint_code']) ?></code></td>
                                    <td class="fw-semibold"><?= e(mb_strimwidth($c['title'], 0, 40, '…')) ?></td>
                                    <td><small><?= e($c['category_name']) ?></small></td>
                                    <td><?= statusBadge($c['status']) ?></td>
                                    <td><small class="text-muted"><?= formatDate($c['created_at'], 'd M Y') ?></small></td>
                                    <td>
                                        <a href="<?= APP_URL ?>/citizen/complaint_details.php?id=<?= (int)$c['id'] ?>"
                                           class="btn btn-sm btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notifications -->
    <div class="col-lg-4">
        <div class="data-card">
            <div class="data-card-header">
                <h2 class="data-card-title">Notifications</h2>
                <a href="<?= APP_URL ?>/citizen/notifications.php" class="btn btn-sm btn-outline-primary">All</a>
            </div>
            <?php if (empty($notifications)): ?>
                <div class="empty-state" style="padding: 2rem 1rem">
                    <i class="bi bi-bell-slash"></i>
                    <p class="small">No notifications yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                    <a href="<?= APP_URL ?>/citizen/notifications.php" class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                        <?php if (!$n['is_read']): ?><div class="notif-dot"></div><?php endif; ?>
                        <div>
                            <div class="fw-semibold small"><?= e($n['title']) ?></div>
                            <div class="text-muted" style="font-size:.8rem"><?= e(mb_strimwidth($n['message'], 0, 70, '…')) ?></div>
                            <div class="text-muted" style="font-size:.75rem"><?= timeAgo($n['created_at']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
