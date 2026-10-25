<?php
/**
 * Super Admin — Departments CRUD
 */
$requiredRole = 'super_admin';
require_once __DIR__ . '/../middleware/role_guard.php';
require_once __DIR__ . '/../services/AuditService.php';

$pdo   = db();
$audit = new AuditService($pdo);

// ── Handle actions ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $name  = sanitize($_POST['dept_name']  ?? '');
        $desc  = sanitize($_POST['description'] ?? '');
        $deptId = (int)($_POST['dept_id'] ?? 0);

        if (empty($name)) {
            flash('error', 'Department name is required.', 'error');
        } else {
            if ($action === 'create') {
                $stmt = $pdo->prepare(
                    "INSERT INTO departments (department_name, description) VALUES (:name, :desc)"
                );
                $stmt->execute([':name' => $name, ':desc' => $desc ?: null]);
                $audit->logFromSession('department_created', 'department', (int)$pdo->lastInsertId(), ['name' => $name]);
                flash('success', "Department \"$name\" created.", 'success');
            } else {
                $pdo->prepare(
                    "UPDATE departments SET department_name=:name, description=:desc WHERE id=:id"
                )->execute([':name' => $name, ':desc' => $desc ?: null, ':id' => $deptId]);
                $audit->logFromSession('department_updated', 'department', $deptId, ['name' => $name]);
                flash('success', 'Department updated.', 'success');
            }
        }
    } elseif ($action === 'toggle') {
        $deptId = (int)($_POST['dept_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        if (in_array($newStatus, ['active', 'inactive']) && $deptId > 0) {
            $pdo->prepare("UPDATE departments SET status=:s WHERE id=:id")->execute([':s' => $newStatus, ':id' => $deptId]);
            $audit->logFromSession('department_status_changed', 'department', $deptId, ['status' => $newStatus]);
            flash('success', 'Department status updated.', 'success');
        }
    }
    redirectTo(APP_URL . '/superadmin/departments.php');
}

// ── Fetch departments with complaint counts ─────────────────────
$departments = $pdo->query(
    "SELECT d.*, 
            COUNT(DISTINCT c.id)  AS complaint_count,
            COUNT(DISTINCT s.id)  AS staff_count
     FROM departments d
     LEFT JOIN complaints c ON c.department_id = d.id
     LEFT JOIN staff      s ON s.department_id = d.id AND s.account_status='active'
     GROUP BY d.id
     ORDER BY d.department_name"
)->fetchAll();

$pageTitle   = 'Departments';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/superadmin/dashboard.php'],
    ['label' => 'Departments'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-circle me-2"></i>New Department
    </button>
</div>

<div class="data-card">
    <div class="data-card-header"><h2 class="data-card-title"><?= count($departments) ?> Departments</h2></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Description</th>
                    <th class="text-center">Staff</th>
                    <th class="text-center">Complaints</th>
                    <th class="text-center">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $d): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($d['department_name']) ?></td>
                        <td class="text-muted small"><?= e(mb_strimwidth($d['description'] ?? '', 0, 60, '…')) ?></td>
                        <td class="text-center"><?= (int)$d['staff_count'] ?></td>
                        <td class="text-center"><?= (int)$d['complaint_count'] ?></td>
                        <td class="text-center">
                            <?php if ($d['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1"
                                    data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-id="<?= (int)$d['id'] ?>"
                                    data-name="<?= e($d['department_name']) ?>"
                                    data-desc="<?= e($d['description'] ?? '') ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="post" class="d-inline"
                                  data-confirm="Change status of this department?">
                                <?= csrfField() ?>
                                <input type="hidden" name="action"     value="toggle">
                                <input type="hidden" name="dept_id"   value="<?= (int)$d['id'] ?>">
                                <input type="hidden" name="new_status" value="<?= $d['status'] === 'active' ? 'inactive' : 'active' ?>">
                                <button type="submit" class="btn btn-sm <?= $d['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                    <i class="bi bi-<?= $d['status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">New Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Department Name <span class="required-star">*</span></label>
                        <input type="text" name="dept_name" class="form-control" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="dept_id" id="editDeptId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Department Name <span class="required-star">*</span></label>
                        <input type="text" name="dept_name" id="editDeptName" class="form-control" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="editDeptDesc" class="form-control" rows="3" maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('editModal').addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('editDeptId').value   = btn.dataset.id;
    document.getElementById('editDeptName').value = btn.dataset.name;
    document.getElementById('editDeptDesc').value = btn.dataset.desc;
});
</script>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
