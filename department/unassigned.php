<?php
/**
 * Department Admin — Unassigned Complaints List
 */
$requiredRole = 'department_admin';
require_once __DIR__ . '/../middleware/role_guard.php';

$deptId = $_SESSION['dept_id'];
$pdo    = db();

$stmt = $pdo->prepare(
    "SELECT c.id, c.complaint_code, c.title, c.priority, c.created_at, w.ward_name, cat.category_name, c.address
     FROM complaints c
     LEFT JOIN wards w ON w.id = c.ward_id
     LEFT JOIN categories cat ON cat.id = c.category_id
     WHERE c.department_id = :did AND c.status = 'pending'
     ORDER BY CASE c.priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END, c.created_at ASC"
);
$stmt->execute([':did' => $deptId]);
$unassigned = $stmt->fetchAll();

$pageTitle   = 'Unassigned Complaints';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/department/dashboard.php'],
    ['label' => 'Unassigned Complaints'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="data-card">
    <div class="data-card-header"><h2 class="data-card-title"><?= count($unassigned) ?> Unassigned Tasks in Department</h2></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Ward</th>
                    <th>Location</th>
                    <th>Priority</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($unassigned)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-success"><i class="bi bi-check-circle me-1"></i>All departmental complaints are assigned!</td></tr>
                <?php else: ?>
                    <?php foreach ($unassigned as $u): ?>
                        <tr>
                            <td><code><?= e($u['complaint_code']) ?></code></td>
                            <td class="fw-semibold"><?= e(mb_strimwidth($u['title'], 0, 30, '…')) ?></td>
                            <td><small><?= e($u['category_name']) ?></small></td>
                            <td><small><?= e($u['ward_name']) ?></small></td>
                            <td><small class="text-muted"><?= e(mb_strimwidth($u['address'] ?? '—', 0, 25, '…')) ?></small></td>
                            <td><?= priorityBadge($u['priority']) ?></td>
                            <td><small class="text-muted"><?= timeAgo($u['created_at']) ?></small></td>
                            <td>
                                <a href="<?= APP_URL ?>/department/assignments.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-primary">
                                    Assign Now
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
