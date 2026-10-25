<?php
/**
 * Department Admin — View Department Officials & Workloads
 */
$requiredRole = 'department_admin';
require_once __DIR__ . '/../middleware/role_guard.php';

$deptId = $_SESSION['dept_id'];
$pdo    = db();

$isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
$wardsExpr = $isSqlite ? "GROUP_CONCAT(w.ward_code, ', ')" : "GROUP_CONCAT(w.ward_code ORDER BY w.ward_code SEPARATOR ', ')";
$officials = $pdo->prepare(
    "SELECT s.*, 
            $wardsExpr AS wards_served,
            COUNT(DISTINCT ca.id) AS active_load
     FROM staff s
     LEFT JOIN staff_wards sw ON sw.staff_id = s.id
     LEFT JOIN wards w ON w.id = sw.ward_id
     LEFT JOIN complaint_assignments ca ON ca.official_id = s.id AND ca.assignment_status = 'active'
     WHERE s.department_id = :did AND s.role_id = 2
     GROUP BY s.id
     ORDER BY s.name ASC"
);
$officials->execute([':did' => $deptId]);
$officials = $officials->fetchAll();

$pageTitle   = 'Department Work Officials';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/department/dashboard.php'],
    ['label' => 'Officials'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="data-card">
    <div class="data-card-header"><h2 class="data-card-title"><?= count($officials) ?> Work Officials in Department</h2></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Official Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Wards Covered</th>
                    <th class="text-center">Workload</th>
                    <th class="text-center">Availability</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($officials)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No officials registered in this department yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($officials as $o): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($o['name']) ?></td>
                            <td><?= e($o['email']) ?></td>
                            <td><?= e($o['phone'] ?? '—') ?></td>
                            <td><small class="text-muted"><?= e($o['wards_served'] ?: 'All Wards') ?></small></td>
                            <td class="text-center">
                                <span class="badge <?= $o['active_load'] >= $o['max_active_workload'] ? 'bg-danger' : 'bg-primary' ?>">
                                    <?= (int)$o['active_load'] ?> / <?= (int)$o['max_active_workload'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $o['availability_status'] === 'available' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= ucfirst(str_replace('_', ' ', $o['availability_status'])) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $o['account_status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= ucfirst($o['account_status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
