<?php
/**
 * Super Admin — Manage Registered Citizens
 */
$requiredRole = 'super_admin';
require_once __DIR__ . '/../middleware/role_guard.php';
require_once __DIR__ . '/../services/AuditService.php';

$pdo   = db();
$audit = new AuditService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_status') {
        $citizenId = (int)($_POST['citizen_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        if (in_array($newStatus, ['active', 'suspended', 'pending']) && $citizenId > 0) {
            $pdo->prepare("UPDATE users SET account_status = :s WHERE id = :id")
                ->execute([':s' => $newStatus, ':id' => $citizenId]);
            $audit->logFromSession('citizen_status_changed', 'citizen', $citizenId, ['status' => $newStatus]);
            flash('success', 'Citizen account status updated.', 'success');
        }
    }
    redirectTo(APP_URL . '/superadmin/manage_citizens.php');
}

$search = sanitize($_GET['q'] ?? '');
$where = "1=1";
$params = [];

if (!empty($search)) {
    $where = "(u.name LIKE :q OR u.email LIKE :q OR u.phone LIKE :q)";
    $params[':q'] = "%$search%";
}

$stmt = $pdo->prepare(
    "SELECT u.*, w.ward_name, COUNT(c.id) AS total_complaints
     FROM users u
     LEFT JOIN wards w ON w.id = u.ward_id
     LEFT JOIN complaints c ON c.citizen_id = u.id
     WHERE $where
     GROUP BY u.id
     ORDER BY u.created_at DESC"
);
$stmt->execute($params);
$citizens = $stmt->fetchAll();

$pageTitle   = 'Manage Citizens';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/superadmin/dashboard.php'],
    ['label' => 'Citizens'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="card mb-4">
    <div class="card-body p-3">
        <form method="get" class="row g-2">
            <div class="col-md-10">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by name, email, or phone…" value="<?= e($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="data-card">
    <div class="data-card-header"><h2 class="data-card-title"><?= count($citizens) ?> Registered Citizens</h2></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Ward</th>
                    <th class="text-center">Complaints</th>
                    <th class="text-center">Account Status</th>
                    <th>Registered</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($citizens)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No citizens found.</td></tr>
                <?php else: ?>
                    <?php foreach ($citizens as $u): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($u['name']) ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td><?= e($u['phone'] ?? '—') ?></td>
                            <td><small><?= e($u['ward_name'] ?? '—') ?></small></td>
                            <td class="text-center"><span class="badge bg-secondary"><?= (int)$u['total_complaints'] ?></span></td>
                            <td class="text-center">
                                <span class="badge <?= $u['account_status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= ucfirst($u['account_status']) ?>
                                </span>
                            </td>
                            <td><small class="text-muted"><?= formatDate($u['created_at'], 'd M Y') ?></small></td>
                            <td>
                                <form method="post" class="d-inline" data-confirm="Change status of this citizen?">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="citizen_id" value="<?= (int)$u['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $u['account_status'] === 'active' ? 'suspended' : 'active' ?>">
                                    <button class="btn btn-sm <?= $u['account_status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                        <?= $u['account_status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
