<?php
/**
 * Super Admin — Manage Department Admins
 */
$requiredRole = 'super_admin';
require_once __DIR__ . '/../middleware/role_guard.php';
require_once __DIR__ . '/../services/AuditService.php';

$pdo   = db();
$audit = new AuditService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name     = sanitize($_POST['name'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $phone    = sanitize($_POST['phone'] ?? '');
        $deptId   = (int)($_POST['department_id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $deptId < 1) {
            flash('error', 'Please fill all required fields correctly (password min 8 chars).', 'error');
        } else {
            $stmt = $pdo->prepare("SELECT id FROM staff WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                flash('error', 'Staff email already exists.', 'error');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare(
                    "INSERT INTO staff (name, email, phone, password_hash, role_id, department_id, account_status)
                     VALUES (:name, :email, :phone, :hash, 3, :dept_id, 'active')"
                );
                $stmt->execute([
                    ':name'    => $name,
                    ':email'   => $email,
                    ':phone'   => $phone ?: null,
                    ':hash'    => $hash,
                    ':dept_id' => $deptId
                ]);
                $audit->logFromSession('dept_admin_created', 'staff', (int)$pdo->lastInsertId(), ['email' => $email]);
                flash('success', "Department Admin $name created successfully.", 'success');
            }
        }
    } elseif ($action === 'toggle') {
        $staffId   = (int)($_POST['staff_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        if (in_array($newStatus, ['active', 'inactive', 'suspended']) && $staffId > 0) {
            $pdo->prepare("UPDATE staff SET account_status = :s WHERE id = :id AND role_id = 3")
                ->execute([':s' => $newStatus, ':id' => $staffId]);
            $audit->logFromSession('dept_admin_status_changed', 'staff', $staffId, ['status' => $newStatus]);
            flash('success', 'Admin status updated.', 'success');
        }
    } elseif ($action === 'reset_password') {
        $staffId = (int)($_POST['staff_id'] ?? 0);
        $newPass = $_POST['new_password'] ?? '';
        if (strlen($newPass) >= 8 && $staffId > 0) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE staff SET password_hash = :hash WHERE id = :id AND role_id = 3")
                ->execute([':hash' => $hash, ':id' => $staffId]);
            $audit->logFromSession('dept_admin_password_reset', 'staff', $staffId);
            flash('success', 'Password reset successfully.', 'success');
        } else {
            flash('error', 'Password must be at least 8 characters.', 'error');
        }
    }
    redirectTo(APP_URL . '/superadmin/manage_admins.php');
}

$admins = $pdo->query(
    "SELECT s.*, d.department_name
     FROM staff s
     LEFT JOIN departments d ON d.id = s.department_id
     WHERE s.role_id = 3
     ORDER BY s.name ASC"
)->fetchAll();

$departments = $pdo->query("SELECT id, department_name FROM departments WHERE status='active' ORDER BY department_name")->fetchAll();

$pageTitle   = 'Manage Department Admins';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/superadmin/dashboard.php'],
    ['label' => 'Department Admins'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex justify-content-end mb-4">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-person-plus me-2"></i>Add Department Admin
    </button>
</div>

<div class="data-card">
    <div class="data-card-header"><h2 class="data-card-title"><?= count($admins) ?> Department Admins</h2></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Department</th>
                    <th class="text-center">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $adm): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($adm['name']) ?></td>
                        <td><?= e($adm['email']) ?></td>
                        <td><?= e($adm['phone'] ?? '—') ?></td>
                        <td><span class="badge bg-info text-dark"><?= e($adm['department_name'] ?? 'None') ?></span></td>
                        <td class="text-center">
                            <span class="badge <?= $adm['account_status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= ucfirst($adm['account_status']) ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-warning me-1"
                                    data-bs-toggle="modal" data-bs-target="#resetModal"
                                    data-id="<?= (int)$adm['id'] ?>"
                                    data-name="<?= e($adm['name']) ?>" title="Reset Password">
                                <i class="bi bi-key"></i>
                            </button>
                            <form method="post" class="d-inline" data-confirm="Change status of this admin?">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="staff_id" value="<?= (int)$adm['id'] ?>">
                                <input type="hidden" name="new_status" value="<?= $adm['account_status'] === 'active' ? 'inactive' : 'active' ?>">
                                <button class="btn btn-sm <?= $adm['account_status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                    <i class="bi bi-<?= $adm['account_status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i>
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
                <div class="modal-header"><h5 class="modal-title">New Department Admin</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="required-star">*</span></label>
                        <input type="text" name="name" class="form-control" maxlength="120" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="required-star">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department <span class="required-star">*</span></label>
                        <select name="department_id" class="form-select" required>
                            <option value="">— Select Department —</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= (int)$d['id'] ?>"><?= e($d['department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="required-star">*</span></label>
                        <input type="password" name="password" class="form-control" minlength="8" required>
                        <small class="text-muted">Minimum 8 characters.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="staff_id" id="resetStaffId">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password for <span id="resetStaffName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="required-star">*</span></label>
                        <input type="password" name="new_password" class="form-control" minlength="8" required>
                        <small class="text-muted">Minimum 8 characters.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('resetModal').addEventListener('show.bs.modal', function(e) {
    var b = e.relatedTarget;
    document.getElementById('resetStaffId').value = b.dataset.id;
    document.getElementById('resetStaffName').textContent = b.dataset.name;
});
</script>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
