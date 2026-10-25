<?php
/**
 * Super Admin — Categories CRUD
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
        $name     = sanitize($_POST['category_name'] ?? '');
        $desc     = sanitize($_POST['description']   ?? '');
        $deptId   = (int)($_POST['department_id']    ?? 0);
        $priority = $_POST['default_priority'] ?? 'medium';
        $verif    = isset($_POST['verification_required']) ? 1 : 0;
        $catId    = (int)($_POST['cat_id'] ?? 0);

        if (empty($name)) {
            flash('error', 'Category name is required.', 'error');
        } elseif (!in_array($priority, ['low','medium','high','critical'])) {
            flash('error', 'Invalid priority.', 'error');
        } else {
            if ($action === 'create') {
                $pdo->prepare(
                    "INSERT INTO categories (category_name, description, department_id, default_priority, verification_required)
                     VALUES (:name, :desc, :deptid, :pri, :verif)"
                )->execute([':name'=>$name,':desc'=>$desc?:null,':deptid'=>$deptId?:null,':pri'=>$priority,':verif'=>$verif]);
                $audit->logFromSession('category_created','category',(int)$pdo->lastInsertId(),['name'=>$name]);
                flash('success',"Category \"$name\" created.",'success');
            } else {
                $pdo->prepare(
                    "UPDATE categories SET category_name=:name, description=:desc, department_id=:deptid,
                     default_priority=:pri, verification_required=:verif WHERE id=:id"
                )->execute([':name'=>$name,':desc'=>$desc?:null,':deptid'=>$deptId?:null,':pri'=>$priority,':verif'=>$verif,':id'=>$catId]);
                $audit->logFromSession('category_updated','category',$catId,['name'=>$name]);
                flash('success','Category updated.','success');
            }
        }
    } elseif ($action === 'toggle') {
        $catId     = (int)($_POST['cat_id']    ?? 0);
        $newStatus = $_POST['new_status']      ?? '';
        if (in_array($newStatus,['active','inactive']) && $catId > 0) {
            $pdo->prepare("UPDATE categories SET status=:s WHERE id=:id")->execute([':s'=>$newStatus,':id'=>$catId]);
            $audit->logFromSession('category_status_changed','category',$catId,['status'=>$newStatus]);
            flash('success','Status updated.','success');
        }
    }
    redirectTo(APP_URL . '/superadmin/categories.php');
}

$categories  = $pdo->query(
    "SELECT c.*, d.department_name, COUNT(cmp.id) AS complaint_count
     FROM categories c
     LEFT JOIN departments d   ON d.id = c.department_id
     LEFT JOIN complaints  cmp ON cmp.category_id = c.id
     GROUP BY c.id ORDER BY c.category_name"
)->fetchAll();

$departments = $pdo->query("SELECT id, department_name FROM departments WHERE status='active' ORDER BY department_name")->fetchAll();

$pageTitle   = 'Complaint Categories';
$breadcrumbs = [
    ['label'=>'Dashboard','href'=> APP_URL.'/superadmin/dashboard.php'],
    ['label'=>'Categories'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex justify-content-end mb-4">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-circle me-2"></i>New Category
    </button>
</div>

<div class="data-card">
    <div class="data-card-header"><h2 class="data-card-title"><?= count($categories) ?> Categories</h2></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr>
                <th>Category</th><th>Department</th><th>Priority</th>
                <th class="text-center">Verify?</th><th class="text-center">Complaints</th>
                <th class="text-center">Status</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td class="fw-semibold"><?= e($cat['category_name']) ?></td>
                    <td><small><?= e($cat['department_name'] ?? '— Unrouted —') ?></small></td>
                    <td><?= priorityBadge($cat['default_priority']) ?></td>
                    <td class="text-center">
                        <?= $cat['verification_required'] ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>' ?>
                    </td>
                    <td class="text-center"><?= (int)$cat['complaint_count'] ?></td>
                    <td class="text-center">
                        <span class="badge <?= $cat['status']==='active'?'bg-success':'bg-secondary' ?>">
                            <?= ucfirst($cat['status']) ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1"
                                data-bs-toggle="modal" data-bs-target="#editModal"
                                data-id="<?= (int)$cat['id'] ?>"
                                data-name="<?= e($cat['category_name']) ?>"
                                data-desc="<?= e($cat['description']??'') ?>"
                                data-dept="<?= (int)$cat['department_id'] ?>"
                                data-pri="<?= e($cat['default_priority']) ?>"
                                data-verif="<?= (int)$cat['verification_required'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="post" class="d-inline" data-confirm="Toggle status?">
                            <?= csrfField() ?>
                            <input type="hidden" name="action"     value="toggle">
                            <input type="hidden" name="cat_id"     value="<?= (int)$cat['id'] ?>">
                            <input type="hidden" name="new_status" value="<?= $cat['status']==='active'?'inactive':'active' ?>">
                            <button type="submit" class="btn btn-sm <?= $cat['status']==='active'?'btn-outline-warning':'btn-outline-success' ?>">
                                <i class="bi bi-<?= $cat['status']==='active'?'pause-circle':'play-circle' ?>"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$modalFields = function(string $prefix='') use ($departments, ): void {
    ?>
    <div class="mb-3">
        <label class="form-label">Category Name <span class="required-star">*</span></label>
        <input type="text" name="category_name" id="<?= $prefix ?>CatName" class="form-control" maxlength="100" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Department (for routing)</label>
        <select name="department_id" id="<?= $prefix ?>CatDept" class="form-select">
            <option value="">— No department / manual routing —</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= (int)$d['id'] ?>"><?= e($d['department_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label">Default Priority</label>
            <select name="default_priority" id="<?= $prefix ?>CatPri" class="form-select">
                <?php foreach (['low','medium','high','critical'] as $p): ?>
                    <option value="<?= $p ?>"><?= ucfirst($p) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="verification_required" id="<?= $prefix ?>CatVerif" value="1" checked>
                <label class="form-check-label" for="<?= $prefix ?>CatVerif">Verification Required</label>
            </div>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" id="<?= $prefix ?>CatDesc" class="form-control" rows="2" maxlength="500"></textarea>
    </div>
    <?php
};
?>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-header"><h5 class="modal-title">New Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><?php $modalFields('create'); ?></div>
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
                <input type="hidden" name="cat_id" id="editCatId">
                <div class="modal-header"><h5 class="modal-title">Edit Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><?php $modalFields('edit'); ?></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('editModal').addEventListener('show.bs.modal', function(e) {
    var b = e.relatedTarget;
    document.getElementById('editCatId').value    = b.dataset.id;
    document.getElementById('editCatName').value  = b.dataset.name;
    document.getElementById('editCatDesc').value  = b.dataset.desc;
    document.getElementById('editCatPri').value   = b.dataset.pri;
    document.getElementById('editCatVerif').checked = b.dataset.verif === '1';
    var deptSel = document.getElementById('editCatDept');
    deptSel.value = b.dataset.dept;
});
</script>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
