<?php
/**
 * Department Admin — Assign or Reassign Complaints
 */
$requiredRole = 'department_admin';
require_once __DIR__ . '/../middleware/role_guard.php';
require_once __DIR__ . '/../services/AssignmentService.php';

$deptId  = $_SESSION['dept_id'];
$adminId = $_SESSION['user_id'];
$pdo     = db();

// Handle Manual Assignment / Reassignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $complaintId = (int)($_POST['complaint_id'] ?? 0);
    $officialId  = (int)($_POST['official_id'] ?? 0);
    $reason      = sanitize($_POST['reason'] ?? 'Manual assignment by department admin');

    // Verify complaint belongs to this department
    $chk = $pdo->prepare("SELECT id, complaint_code FROM complaints WHERE id = :id AND department_id = :did");
    $chk->execute([':id' => $complaintId, ':did' => $deptId]);
    $complaint = $chk->fetch();

    // Verify official is in this department and active
    $offChk = $pdo->prepare("SELECT id, name FROM staff WHERE id = :id AND department_id = :did AND role_id = 2 AND account_status = 'active'");
    $offChk->execute([':id' => $officialId, ':did' => $deptId]);
    $official = $offChk->fetch();

    if ($complaint && $official) {
        $service = new AssignmentService($pdo);
        $ok = $service->reassign($complaintId, $officialId, $adminId, 'department_admin', $reason);
        if ($ok) {
            flash('success', "Complaint {$complaint['complaint_code']} assigned to {$official['name']}.", 'success');
        } else {
            flash('error', 'Failed to assign complaint.', 'error');
        }
    } else {
        flash('error', 'Invalid complaint or official selected.', 'error');
    }
    redirectTo(APP_URL . '/department/assignments.php');
}

// Target complaint if directed from elsewhere
$targetId = (int)($_GET['id'] ?? 0);

// Active department complaints
$stmt = $pdo->prepare(
    "SELECT c.id, c.complaint_code, c.title, c.priority, c.status, c.created_at, w.ward_name,
            s.name AS current_official, s.id AS current_official_id
     FROM complaints c
     LEFT JOIN complaint_assignments ca ON ca.complaint_id = c.id AND ca.assignment_status = 'active'
     LEFT JOIN staff s ON s.id = ca.official_id
     LEFT JOIN wards w ON w.id = c.ward_id
     WHERE c.department_id = :did AND c.status IN ('pending', 'assigned', 'in_progress', 'rework_required')
     ORDER BY CASE c.status WHEN 'pending' THEN 1 WHEN 'rework_required' THEN 2 WHEN 'assigned' THEN 3 WHEN 'in_progress' THEN 4 ELSE 5 END, c.created_at DESC"
);
$stmt->execute([':did' => $deptId]);
$complaints = $stmt->fetchAll();

// Available officials in this department
$offStmt = $pdo->prepare(
    "SELECT s.id, s.name, s.availability_status, s.max_active_workload,
            COUNT(ca.id) AS current_workload
     FROM staff s
     LEFT JOIN complaint_assignments ca ON ca.official_id = s.id AND ca.assignment_status = 'active'
     WHERE s.department_id = :did AND s.role_id = 2 AND s.account_status = 'active'
     GROUP BY s.id
     ORDER BY s.name ASC"
);
$offStmt->execute([':did' => $deptId]);
$officials = $offStmt->fetchAll();

$pageTitle   = 'Complaint Assignments';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/department/dashboard.php'],
    ['label' => 'Assignments'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="data-card">
    <div class="data-card-header"><h2 class="data-card-title">Manage Active Assignments (<?= count($complaints) ?>)</h2></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Ward</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($complaints)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No active complaints pending assignment.</td></tr>
                <?php else: ?>
                    <?php foreach ($complaints as $c): ?>
                        <tr class="<?= $targetId === (int)$c['id'] ? 'table-warning' : '' ?>">
                            <td><code><?= e($c['complaint_code']) ?></code></td>
                            <td class="fw-semibold"><?= e(mb_strimwidth($c['title'], 0, 35, '…')) ?></td>
                            <td><small><?= e($c['ward_name']) ?></small></td>
                            <td><?= priorityBadge($c['priority']) ?></td>
                            <td><?= statusBadge($c['status']) ?></td>
                            <td><?= $c['current_official'] ? e($c['current_official']) : '<span class="text-danger fw-bold">Unassigned</span>' ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal" data-bs-target="#assignModal"
                                        data-id="<?= (int)$c['id'] ?>"
                                        data-code="<?= e($c['complaint_code']) ?>">
                                    <?= $c['current_official'] ? 'Reassign' : 'Assign' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="complaint_id" id="modalComplaintId">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Complaint <span id="modalComplaintCode"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Official <span class="required-star">*</span></label>
                        <select name="official_id" class="form-select" required>
                            <option value="">— Select Official —</option>
                            <?php foreach ($officials as $o): ?>
                                <option value="<?= (int)$o['id'] ?>">
                                    <?= e($o['name']) ?> (Load: <?= (int)$o['current_workload'] ?>/<?= (int)$o['max_active_workload'] ?>) — <?= ucfirst($o['availability_status']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason / Instructions</label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="e.g. Ward priority rebalancing, urgent task"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('assignModal').addEventListener('show.bs.modal', function(e) {
    var b = e.relatedTarget;
    document.getElementById('modalComplaintId').value = b.dataset.id;
    document.getElementById('modalComplaintCode').textContent = b.dataset.code;
});
</script>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
