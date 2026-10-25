<?php
/**
 * Super Admin — Manage Work Officials
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
        $maxLoad  = (int)($_POST['max_workload'] ?? 5);
        $password = $_POST['password'] ?? '';
        $wardIds  = $_POST['ward_ids'] ?? [];

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
                    "INSERT INTO staff (name, email, phone, password_hash, role_id, department_id, max_active_workload, account_status, availability_status)
                     VALUES (:name, :email, :phone, :hash, 2, :dept_id, :max_load, 'active', 'available')"
                );
                $stmt->execute([
                    ':name'     => $name,
                    ':email'    => $email,
                    ':phone'    => $phone ?: null,
                    ':hash'     => $hash,
                    ':dept_id'  => $deptId,
                    ':max_load' => $maxLoad
                ]);
                $newStaffId = (int)$pdo->lastInsertId();

                if (!empty($wardIds)) {
                    $wStmt = $pdo->prepare("INSERT INTO staff_wards (staff_id, ward_id) VALUES (:sid, :wid)");
                    foreach ($wardIds as $wid) {
                        $wStmt->execute([':sid' => $newStaffId, ':wid' => (int)$wid]);
                    }
                }

                $audit->logFromSession('work_official_created', 'staff', $newStaffId, ['email' => $email]);
                flash('success', "Work Official $name created successfully.", 'success');
            }
        }
    } elseif ($action === 'toggle_status') {
        $staffId   = (int)($_POST['staff_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        if (in_array($newStatus, ['active', 'inactive', 'suspended']) && $staffId > 0) {
            $pdo->prepare("UPDATE staff SET account_status = :s WHERE id = :id AND role_id = 2")
                ->execute([':s' => $newStatus, ':id' => $staffId]);
            $audit->logFromSession('official_status_changed', 'staff', $staffId, ['status' => $newStatus]);
            flash('success', 'Official status updated.', 'success');
        }
    } elseif ($action === 'toggle_avail') {
        $staffId   = (int)($_POST['staff_id'] ?? 0);
        $newAvail  = $_POST['new_avail'] ?? '';
        if (in_array($newAvail, ['available', 'unavailable', 'on_leave']) && $staffId > 0) {
            $pdo->prepare("UPDATE staff SET availability_status = :s WHERE id = :id AND role_id = 2")
                ->execute([':s' => $newAvail, ':id' => $staffId]);
            $audit->logFromSession('official_avail_changed', 'staff', $staffId, ['availability' => $newAvail]);
            flash('success', 'Official availability updated.', 'success');
        }
    }
    redirectTo(APP_URL . '/superadmin/manage_officials.php');
}

$isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
$wardsExpr = $isSqlite ? "GROUP_CONCAT(w.ward_code, ', ')" : "GROUP_CONCAT(w.ward_code ORDER BY w.ward_code SEPARATOR ', ')";
$officials = $pdo->query(
    "SELECT s.*, d.department_name,
            $wardsExpr AS wards_served,
            COUNT(DISTINCT ca.id) AS active_assignments
     FROM staff s
     LEFT JOIN departments d ON d.id = s.department_id
     LEFT JOIN staff_wards sw ON sw.staff_id = s.id
     LEFT JOIN wards w ON w.id = sw.ward_id
     LEFT JOIN complaint_assignments ca ON ca.official_id = s.id AND ca.assignment_status = 'active'
     WHERE s.role_id = 2
     GROUP BY s.id
     ORDER BY s.name ASC"
)->fetchAll();

$departments = $pdo->query("SELECT id, department_name FROM departments WHERE status='active' ORDER BY department_name")->fetchAll();
$wards       = $pdo->query("SELECT id, ward_name, ward_code FROM wards WHERE status='active' ORDER BY ward_code")->fetchAll();

$pageTitle   = 'Manage Work Officials';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/superadmin/dashboard.php'],
    ['label' => 'Work Officials'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex justify-content-end mb-4">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-person-plus me-2"></i>Add Work Official
    </button>
</div>

<div class="data-card">
    <div class="data-card-header"><h2 class="data-card-title"><?= count($officials) ?> Work Officials</h2></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Wards</th>
                    <th class="text-center">Workload</th>
                    <th class="text-center">Availability</th>
                    <th class="text-center">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($officials as $off): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($off['name']) ?></td>
                        <td><?= e($off['email']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($off['department_name'] ?? 'None') ?></span></td>
                        <td><small class="text-muted"><?= e($off['wards_served'] ?: 'All Wards') ?></small></td>
                        <td class="text-center">
                            <span class="badge <?= $off['active_assignments'] >= $off['max_active_workload'] ? 'bg-danger' : 'bg-primary' ?>">
                                <?= (int)$off['active_assignments'] ?> / <?= (int)$off['max_active_workload'] ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $off['availability_status'] === 'available' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= ucfirst(str_replace('_', ' ', $off['availability_status'])) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $off['account_status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= ucfirst($off['account_status']) ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle_avail">
                                <input type="hidden" name="staff_id" value="<?= (int)$off['id'] ?>">
                                <input type="hidden" name="new_avail" value="<?= $off['availability_status'] === 'available' ? 'unavailable' : 'available' ?>">
                                <button type="submit" class="btn btn-sm btn-outline-info me-1" title="Toggle Availability">
                                    <i class="bi bi-person-check"></i>
                                </button>
                            </form>
                            <form method="post" class="d-inline" data-confirm="Toggle status of this official?">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="staff_id" value="<?= (int)$off['id'] ?>">
                                <input type="hidden" name="new_status" value="<?= $off['account_status'] === 'active' ? 'inactive' : 'active' ?>">
                                <button class="btn btn-sm <?= $off['account_status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                    <i class="bi bi-<?= $off['account_status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-header"><h5 class="modal-title">New Work Official</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="required-star">*</span></label>
                            <input type="text" name="name" class="form-control" maxlength="120" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="required-star">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department <span class="required-star">*</span></label>
                            <select name="department_id" class="form-select" required>
                                <option value="">— Select Department —</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>"><?= e($d['department_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Active Workload</label>
                            <input type="number" name="max_workload" class="form-control" value="5" min="1" max="20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="required-star">*</span></label>
                            <input type="password" name="password" class="form-control" minlength="8" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Assigned Wards (Hold Ctrl/Cmd to select multiple)</label>
                            <select name="ward_ids[]" class="form-select" multiple size="5">
                                <?php foreach ($wards as $w): ?>
                                    <option value="<?= (int)$w['id'] ?>"><?= e($w['ward_code']) ?> — <?= e($w['ward_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">If none selected, official will not be auto-assigned to specific wards.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Official</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
