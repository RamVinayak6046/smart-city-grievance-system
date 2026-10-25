<?php
/**
 * Department Admin Dashboard
 */
$requiredRole = 'department_admin';
require_once __DIR__ . '/../middleware/role_guard.php';

$deptId = $_SESSION['dept_id'];
$pdo    = db();

// Ensure admin has a department
if (!$deptId) {
    die("Error: No department associated with this admin account.");
}

$deptName = $pdo->prepare("SELECT department_name FROM departments WHERE id = :id");
$deptName->execute([':id' => $deptId]);
$departmentName = $deptName->fetchColumn();

// Dept Stats
$stats = $pdo->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS unassigned,
        SUM(status = 'assigned') AS assigned,
        SUM(status = 'in_progress') AS in_progress,
        SUM(status = 'awaiting_verification') AS awaiting_verification,
        SUM(status = 'rework_required') AS rework_required,
        SUM(status = 'resolved') AS resolved
     FROM complaints
     WHERE department_id = :did"
);
$stats->execute([':did' => $deptId]);
$st = $stats->fetch();

// Awaiting verification complaints
$verifStmt = $pdo->prepare(
    "SELECT c.id, c.complaint_code, c.title, c.priority, c.updated_at, s.name AS official_name
     FROM complaints c
     JOIN complaint_assignments ca ON ca.complaint_id = c.id AND ca.assignment_status = 'active'
     LEFT JOIN staff s ON s.id = ca.official_id
     WHERE c.department_id = :did AND c.status = 'awaiting_verification'
     ORDER BY c.updated_at ASC LIMIT 5"
);
$verifStmt->execute([':did' => $deptId]);
$awaiting = $verifStmt->fetchAll();

// Unassigned complaints
$unassignedStmt = $pdo->prepare(
    "SELECT c.id, c.complaint_code, c.title, c.priority, c.created_at, w.ward_name, cat.category_name
     FROM complaints c
     LEFT JOIN wards w ON w.id = c.ward_id
     LEFT JOIN categories cat ON cat.id = c.category_id
     WHERE c.department_id = :did AND c.status = 'pending'
     ORDER BY CASE c.priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END, c.created_at ASC LIMIT 5"
);
$unassignedStmt->execute([':did' => $deptId]);
$unassigned = $unassignedStmt->fetchAll();

$pageTitle   = "$departmentName Dashboard";
$breadcrumbs = [['label' => 'Dashboard']];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-lg-2">
        <div class="stat-card stat-navy">
            <div class="stat-icon"><i class="bi bi-inbox-fill"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$st['total'] ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="stat-card stat-amber">
            <div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$st['unassigned'] ?></div>
                <div class="stat-label">Unassigned</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="stat-card stat-blue">
            <div class="stat-icon"><i class="bi bi-person-gear"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$st['assigned'] ?></div>
                <div class="stat-label">Assigned</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="stat-card stat-purple">
            <div class="stat-icon"><i class="bi bi-tools"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$st['in_progress'] ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="stat-card stat-cyan">
            <div class="stat-icon"><i class="bi bi-patch-check"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$st['awaiting_verification'] ?></div>
                <div class="stat-label">Verify Req.</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="stat-card stat-green">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$st['resolved'] ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Unassigned Complaints -->
    <div class="col-lg-6">
        <div class="data-card">
            <div class="data-card-header">
                <h2 class="data-card-title text-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i> Unassigned Complaints (<?= count($unassigned) ?>)
                </h2>
                <a href="<?= APP_URL ?>/department/unassigned.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <?php if (empty($unassigned)): ?>
                <div class="empty-state py-4"><p class="text-success small mb-0"><i class="bi bi-check-circle me-1"></i>No unassigned complaints!</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>ID</th><th>Title</th><th>Priority</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($unassigned as $u): ?>
                                <tr>
                                    <td><code><?= e($u['complaint_code']) ?></code></td>
                                    <td><?= e(mb_strimwidth($u['title'], 0, 25, '…')) ?></td>
                                    <td><?= priorityBadge($u['priority']) ?></td>
                                    <td>
                                        <a href="<?= APP_URL ?>/department/assignments.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-warning">
                                            Assign
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Awaiting Verification -->
    <div class="col-lg-6">
        <div class="data-card">
            <div class="data-card-header">
                <h2 class="data-card-title text-info">
                    <i class="bi bi-patch-check me-1"></i> Awaiting Verification (<?= count($awaiting) ?>)
                </h2>
                <a href="<?= APP_URL ?>/department/verify_completion.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <?php if (empty($awaiting)): ?>
                <div class="empty-state py-4"><p class="text-muted small mb-0">No completion requests waiting for approval.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>ID</th><th>Title</th><th>Official</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($awaiting as $v): ?>
                                <tr>
                                    <td><code><?= e($v['complaint_code']) ?></code></td>
                                    <td><?= e(mb_strimwidth($v['title'], 0, 25, '…')) ?></td>
                                    <td><small><?= e($v['official_name']) ?></small></td>
                                    <td>
                                        <a href="<?= APP_URL ?>/department/verify_completion.php?id=<?= (int)$v['id'] ?>" class="btn btn-sm btn-info text-white">
                                            Review
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
