<?php
/**
 * Official Profile & Work History
 */
$requiredRole = 'work_official';
require_once __DIR__ . '/../middleware/role_guard.php';

$staffId = $_SESSION['user_id'];
$pdo     = db();

$stmt = $pdo->prepare("SELECT s.*, d.department_name FROM staff s LEFT JOIN departments d ON d.id = s.department_id WHERE s.id = :id");
$stmt->execute([':id' => $staffId]);
$staff = $stmt->fetch();

$wards = $pdo->prepare("SELECT w.ward_name, w.ward_code FROM staff_wards sw JOIN wards w ON w.id = sw.ward_id WHERE sw.staff_id = :sid");
$wards->execute([':sid' => $staffId]);
$wardsServed = $wards->fetchAll();

// Work History Summary & Completed Tasks
$historyStmt = $pdo->prepare(
    "SELECT c.id, c.complaint_code, c.title, c.priority, c.status, c.created_at, c.resolved_at,
            w.ward_name, cat.category_name, ca.assigned_at, ca.completed_at
     FROM complaint_assignments ca
     JOIN complaints c ON c.id = ca.complaint_id
     LEFT JOIN wards w ON w.id = c.ward_id
     LEFT JOIN categories cat ON cat.id = c.category_id
     WHERE ca.official_id = :sid AND ca.assignment_status = 'completed'
     ORDER BY ca.completed_at DESC LIMIT 20"
);
$historyStmt->execute([':sid' => $staffId]);
$workHistory = $historyStmt->fetchAll();

$pageTitle   = 'My Profile & Work History';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/official/dashboard.php'],
    ['label' => 'Profile & History'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-person me-2"></i>Official Profile Details</div>
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <div class="sidebar-user-avatar bg-primary text-white mx-auto mb-2" style="width: 65px; height: 65px; font-size: 1.6rem;">
                        <?= strtoupper(substr($staff['name'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold mb-0"><?= e($staff['name']) ?></h5>
                    <span class="badge bg-secondary mt-1"><?= e($staff['department_name'] ?? 'General') ?></span>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Email</span>
                        <strong><?= e($staff['email']) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Phone</span>
                        <strong><?= e($staff['phone'] ?? '—') ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Availability</span>
                        <span class="badge bg-success"><?= ucfirst($staff['availability_status']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Workload Limit</span>
                        <strong><?= (int)$staff['max_active_workload'] ?> tasks</strong>
                    </li>
                    <li class="list-group-item">
                        <span class="text-muted d-block mb-1">Assigned Wards</span>
                        <?php if (empty($wardsServed)): ?>
                            <small class="text-muted">Serves all wards / unassigned</small>
                        <?php else: ?>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($wardsServed as $w): ?>
                                    <span class="badge bg-light text-dark border"><?= e($w['ward_code']) ?> (<?= e($w['ward_name']) ?>)</span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Personal Work History -->
    <div class="col-lg-7">
        <div class="data-card h-100">
            <div class="data-card-header d-flex justify-content-between align-items-center">
                <h2 class="data-card-title mb-0"><i class="bi bi-clock-history me-2"></i>Resolved Work History</h2>
                <span class="badge bg-success"><?= count($workHistory) ?> Completed Tasks</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Complaint ID</th>
                            <th>Title</th>
                            <th>Ward</th>
                            <th>Completed Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($workHistory)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No completed task history recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($workHistory as $h): ?>
                                <tr>
                                    <td><code><?= e($h['complaint_code']) ?></code></td>
                                    <td class="fw-semibold"><?= e(mb_strimwidth($h['title'], 0, 30, '…')) ?></td>
                                    <td><small><?= e($h['ward_name']) ?></small></td>
                                    <td><small class="text-muted"><?= formatDate($h['completed_at'] ?? $h['resolved_at'], 'd M Y') ?></small></td>
                                    <td>
                                        <a href="<?= APP_URL ?>/official/complaint_details.php?id=<?= (int)$h['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
