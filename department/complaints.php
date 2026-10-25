<?php
/**
 * Department Admin — View All Department Complaints with Pagination
 */
$requiredRole = 'department_admin';
require_once __DIR__ . '/../middleware/role_guard.php';

$deptId = $_SESSION['dept_id'];
$pdo    = db();

$statusFilter = sanitize($_GET['status'] ?? '');
$priorityFilter = sanitize($_GET['priority'] ?? '');
$search       = sanitize($_GET['q'] ?? '');
$wardFilter   = (int)($_GET['ward_id'] ?? 0);
$page         = max(1, (int)($_GET['page'] ?? 1));

$where = ["c.department_id = :did"];
$params = [':did' => $deptId];

if (!empty($statusFilter)) {
    $where[] = "c.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($priorityFilter)) {
    $where[] = "c.priority = :priority";
    $params[':priority'] = $priorityFilter;
}

if ($wardFilter > 0) {
    $where[] = "c.ward_id = :wid";
    $params[':wid'] = $wardFilter;
}

if (!empty($search)) {
    $where[] = "(c.complaint_code LIKE :q OR c.title LIKE :q)";
    $params[':q'] = "%$search%";
}

$whereSql = implode(' AND ', $where);

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints c WHERE $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = paginate($total, ITEMS_PER_PAGE, $page);

$stmt = $pdo->prepare(
    "SELECT c.id, c.complaint_code, c.title, c.priority, c.status, c.created_at,
            w.ward_name, cat.category_name, s.name AS official_name
     FROM complaints c
     LEFT JOIN wards w ON w.id = c.ward_id
     LEFT JOIN categories cat ON cat.id = c.category_id
     LEFT JOIN complaint_assignments ca ON ca.complaint_id = c.id AND ca.assignment_status = 'active'
     LEFT JOIN staff s ON s.id = ca.official_id
     WHERE $whereSql
     ORDER BY c.created_at DESC
     LIMIT " . ITEMS_PER_PAGE . " OFFSET " . $pagination['offset']
);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$wards = $pdo->query("SELECT id, ward_name FROM wards WHERE status='active' ORDER BY ward_name")->fetchAll();

$pageTitle   = 'Department Complaints';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/department/dashboard.php'],
    ['label' => 'All Complaints'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="card mb-4">
    <div class="card-body p-3">
        <form method="get" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by ID or title…" value="<?= e($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="assigned" <?= $statusFilter === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                    <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="awaiting_verification" <?= $statusFilter === 'awaiting_verification' ? 'selected' : '' ?>>Awaiting Verification</option>
                    <option value="rework_required" <?= $statusFilter === 'rework_required' ? 'selected' : '' ?>>Rework Required</option>
                    <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priorities</option>
                    <option value="critical" <?= $priorityFilter === 'critical' ? 'selected' : '' ?>>Critical</option>
                    <option value="high" <?= $priorityFilter === 'high' ? 'selected' : '' ?>>High</option>
                    <option value="medium" <?= $priorityFilter === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="low" <?= $priorityFilter === 'low' ? 'selected' : '' ?>>Low</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="ward_id" class="form-select form-select-sm">
                    <option value="">All Wards</option>
                    <?php foreach ($wards as $w): ?>
                        <option value="<?= (int)$w['id'] ?>" <?= $wardFilter === (int)$w['id'] ? 'selected' : '' ?>><?= e($w['ward_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="data-card">
    <div class="data-card-header d-flex justify-content-between align-items-center">
        <h2 class="data-card-title mb-0"><?= $total ?> Complaints in Department</h2>
        <?php if ($total > 0): ?>
            <span class="text-muted small">Showing Page <?= $page ?> of <?= $pagination['total_pages'] ?></span>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Ward</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Official</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($complaints)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">No complaints found.</td></tr>
                <?php else: ?>
                    <?php foreach ($complaints as $c): ?>
                        <tr>
                            <td><code><?= e($c['complaint_code']) ?></code></td>
                            <td class="fw-semibold"><?= e(mb_strimwidth($c['title'], 0, 30, '…')) ?></td>
                            <td><small><?= e($c['category_name']) ?></small></td>
                            <td><small><?= e($c['ward_name']) ?></small></td>
                            <td><?= priorityBadge($c['priority']) ?></td>
                            <td><?= statusBadge($c['status']) ?></td>
                            <td><small><?= e($c['official_name'] ?? '—') ?></small></td>
                            <td><small class="text-muted"><?= formatDate($c['created_at'], 'd M Y') ?></small></td>
                            <td>
                                <a href="<?= APP_URL ?>/department/assignments.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="p-3 border-top">
            <nav>
                <ul class="pagination pagination-sm mb-0 justify-content-center">
                    <?php if ($pagination['has_prev']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&ward_id=<?= $wardFilter ?>">
                                &laquo; Prev
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($pagination['total_pages'], $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&ward_id=<?= $wardFilter ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($pagination['has_next']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&ward_id=<?= $wardFilter ?>">
                                Next &raquo;
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
