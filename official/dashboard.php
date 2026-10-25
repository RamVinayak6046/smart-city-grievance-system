<?php
/**
 * Official Dashboard
 */
$requiredRole = 'work_official';
require_once __DIR__ . '/../middleware/role_guard.php';

$officialId = $_SESSION['user_id'];
$pdo        = db();

// Official stats
$stats = $pdo->prepare(
    "SELECT
        COUNT(ca.id) AS total_assigned,
        SUM(c.status = 'assigned') AS pending_acceptance,
        SUM(c.status = 'in_progress') AS in_progress,
        SUM(c.status = 'awaiting_verification') AS awaiting_verification,
        SUM(c.status = 'rework_required') AS rework_required,
        SUM(c.status = 'resolved') AS resolved
     FROM complaint_assignments ca
     JOIN complaints c ON c.id = ca.complaint_id
     WHERE ca.official_id = :oid AND ca.assignment_status = 'active'"
);
$stats->execute([':oid' => $officialId]);
$st = $stats->fetch();

// Active work assignments
$stmt = $pdo->prepare(
    "SELECT c.id, c.complaint_code, c.title, c.priority, c.status, c.created_at,
            w.ward_name, cat.category_name, ca.assigned_at, ca.accepted_at
     FROM complaint_assignments ca
     JOIN complaints c ON c.id = ca.complaint_id
     LEFT JOIN wards w ON w.id = c.ward_id
     LEFT JOIN categories cat ON cat.id = c.category_id
     WHERE ca.official_id = :oid AND ca.assignment_status = 'active'
       AND c.status IN ('assigned', 'in_progress', 'rework_required', 'awaiting_verification')
     ORDER BY CASE c.priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END, ca.assigned_at DESC"
);
$stmt->execute([':oid' => $officialId]);
$assignments = $stmt->fetchAll();

$pageTitle   = 'Work Official Dashboard';
$breadcrumbs = [['label' => 'Dashboard']];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-blue">
            <div class="stat-icon"><i class="bi bi-inbox"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)($st['pending_acceptance'] ?? 0) ?></div>
                <div class="stat-label">New Tasks</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-purple">
            <div class="stat-icon"><i class="bi bi-tools"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)($st['in_progress'] ?? 0) ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-amber">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)($st['rework_required'] ?? 0) ?></div>
                <div class="stat-label">Rework Needed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-green">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)($st['resolved'] ?? 0) ?></div>
                <div class="stat-label">Completed & Verified</div>
            </div>
        </div>
    </div>
</div>

<div class="data-card">
    <div class="data-card-header">
        <h2 class="data-card-title">My Active Assignments</h2>
        <a href="<?= APP_URL ?>/official/assignments.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <?php if (empty($assignments)): ?>
        <div class="empty-state">
            <i class="bi bi-check2-circle text-success fs-1"></i>
            <p>You have no active pending tasks! Great job.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Complaint ID</th>
                        <th>Title</th>
                        <th>Ward</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td><code><?= e($a['complaint_code']) ?></code></td>
                            <td class="fw-semibold"><?= e(mb_strimwidth($a['title'], 0, 35, '…')) ?></td>
                            <td><small><?= e($a['ward_name']) ?></small></td>
                            <td><?= priorityBadge($a['priority']) ?></td>
                            <td><?= statusBadge($a['status']) ?></td>
                            <td><small class="text-muted"><?= timeAgo($a['assigned_at']) ?></small></td>
                            <td>
                                <a href="<?= APP_URL ?>/official/complaint_details.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-primary">
                                    Manage Task
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
