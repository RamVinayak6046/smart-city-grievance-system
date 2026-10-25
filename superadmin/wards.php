<?php
/**
 * Super Admin — Wards CRUD
 */
$requiredRole = 'super_admin';
require_once __DIR__ . '/../middleware/role_guard.php';
require_once __DIR__ . '/../services/AuditService.php';

$pdo   = db();
$audit = new AuditService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $name   = sanitize($_POST['ward_name'] ?? '');
        $code   = strtoupper(sanitize($_POST['ward_code'] ?? ''));
        $zone   = sanitize($_POST['zone']      ?? '');
        $wardId = (int)($_POST['ward_id']      ?? 0);

        if (empty($name) || empty($code)) {
            flash('error', 'Ward name and code are required.', 'error');
        } else {
            if ($action === 'create') {
                try {
                    $pdo->prepare(
                        "INSERT INTO wards (ward_name, ward_code, zone) VALUES (:name, :code, :zone)"
                    )->execute([':name'=>$name,':code'=>$code,':zone'=>$zone?:null]);
                    $audit->logFromSession('ward_created','ward',(int)$pdo->lastInsertId(),['name'=>$name]);
                    flash('success',"Ward \"$name\" created.",'success');
                } catch (PDOException) {
                    flash('error','Ward code already exists.','error');
                }
            } else {
                try {
                    $pdo->prepare(
                        "UPDATE wards SET ward_name=:name, ward_code=:code, zone=:zone WHERE id=:id"
                    )->execute([':name'=>$name,':code'=>$code,':zone'=>$zone?:null,':id'=>$wardId]);
                    $audit->logFromSession('ward_updated','ward',$wardId,['name'=>$name]);
                    flash('success','Ward updated.','success');
                } catch (PDOException) {
                    flash('error','Ward code already in use.','error');
                }
            }
        }
    } elseif ($action === 'toggle') {
        $wardId    = (int)($_POST['ward_id']    ?? 0);
        $newStatus = $_POST['new_status']       ?? '';
        if (in_array($newStatus,['active','inactive']) && $wardId > 0) {
            $pdo->prepare("UPDATE wards SET status=:s WHERE id=:id")->execute([':s'=>$newStatus,':id'=>$wardId]);
            flash('success','Ward status updated.','success');
        }
    }
    redirectTo(APP_URL . '/superadmin/wards.php');
}

$wards = $pdo->query(
    "SELECT w.*, COUNT(DISTINCT c.id) AS complaint_count, COUNT(DISTINCT u.id) AS citizen_count
     FROM wards w
     LEFT JOIN complaints c ON c.ward_id = w.id
     LEFT JOIN users u      ON u.ward_id = w.id
     GROUP BY w.id ORDER BY w.ward_code"
)->fetchAll();

$pageTitle   = 'Wards & Zones';
$breadcrumbs = [
    ['label'=>'Dashboard','href'=> APP_URL.'/superadmin/dashboard.php'],
    ['label'=>'Wards'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex justify-content-end mb-4">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-circle me-2"></i>New Ward
    </button>
</div>

<div class="data-card">
    <div class="data-card-header"><h2 class="data-card-title"><?= count($wards) ?> Wards</h2></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr>
                <th>Code</th><th>Ward Name</th><th>Zone</th>
                <th class="text-center">Citizens</th><th class="text-center">Complaints</th>
                <th class="text-center">Status</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($wards as $w): ?>
                <tr>
                    <td><code><?= e($w['ward_code']) ?></code></td>
                    <td class="fw-semibold"><?= e($w['ward_name']) ?></td>
                    <td><?= e($w['zone'] ?? '—') ?></td>
                    <td class="text-center"><?= (int)$w['citizen_count'] ?></td>
                    <td class="text-center"><?= (int)$w['complaint_count'] ?></td>
                    <td class="text-center">
                        <span class="badge <?= $w['status']==='active'?'bg-success':'bg-secondary' ?>"><?= ucfirst($w['status']) ?></span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1"
                                data-bs-toggle="modal" data-bs-target="#editModal"
                                data-id="<?= (int)$w['id'] ?>"
                                data-name="<?= e($w['ward_name']) ?>"
                                data-code="<?= e($w['ward_code']) ?>"
                                data-zone="<?= e($w['zone']??'') ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="post" class="d-inline" data-confirm="Toggle ward status?">
                            <?= csrfField() ?>
                            <input type="hidden" name="action"     value="toggle">
                            <input type="hidden" name="ward_id"    value="<?= (int)$w['id'] ?>">
                            <input type="hidden" name="new_status" value="<?= $w['status']==='active'?'inactive':'active' ?>">
                            <button class="btn btn-sm <?= $w['status']==='active'?'btn-outline-warning':'btn-outline-success' ?>">
                                <i class="bi bi-<?= $w['status']==='active'?'pause-circle':'play-circle' ?>"></i>
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
    <div class="modal-dialog"><div class="modal-content">
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-header"><h5 class="modal-title">New Ward</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Ward Name <span class="required-star">*</span></label>
                    <input type="text" name="ward_name" class="form-control" maxlength="100" required></div>
                <div class="mb-3"><label class="form-label">Ward Code <span class="required-star">*</span></label>
                    <input type="text" name="ward_code" class="form-control" maxlength="20" required style="text-transform:uppercase"></div>
                <div class="mb-3"><label class="form-label">Zone</label>
                    <input type="text" name="zone" class="form-control" maxlength="60"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="ward_id" id="editWardId">
            <div class="modal-header"><h5 class="modal-title">Edit Ward</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Ward Name</label>
                    <input type="text" name="ward_name" id="editWardName" class="form-control" maxlength="100" required></div>
                <div class="mb-3"><label class="form-label">Ward Code</label>
                    <input type="text" name="ward_code" id="editWardCode" class="form-control" maxlength="20" required style="text-transform:uppercase"></div>
                <div class="mb-3"><label class="form-label">Zone</label>
                    <input type="text" name="zone" id="editWardZone" class="form-control" maxlength="60"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div></div>
</div>

<script>
document.getElementById('editModal').addEventListener('show.bs.modal', function(e) {
    var b = e.relatedTarget;
    document.getElementById('editWardId').value   = b.dataset.id;
    document.getElementById('editWardName').value = b.dataset.name;
    document.getElementById('editWardCode').value = b.dataset.code;
    document.getElementById('editWardZone').value = b.dataset.zone;
});
</script>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
