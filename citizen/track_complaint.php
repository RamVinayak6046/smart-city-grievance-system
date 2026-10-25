<?php
/**
 * Citizen — Track Complaint by Code
 */
$requiredRole = 'citizen';
require_once __DIR__ . '/../middleware/role_guard.php';

$citizenId = $_SESSION['user_id'];
$pdo       = db();
$complaint = null;
$error     = null;
$code      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
} elseif (!empty($_GET['code']) || !empty($_GET['complaint_code'])) {
    $code = strtoupper(sanitize($_GET['code'] ?? $_GET['complaint_code'] ?? ''));
}

if (!empty($code)) {
    $stmt = $pdo->prepare(
        "SELECT c.id, c.complaint_code, c.title, c.status, c.priority, c.created_at,
                cat.category_name, d.department_name, w.ward_name,
                s.name AS official_name
         FROM complaints c
         LEFT JOIN categories  cat ON cat.id = c.category_id
         LEFT JOIN departments d   ON d.id   = c.department_id
         LEFT JOIN wards       w   ON w.id   = c.ward_id
         LEFT JOIN complaint_assignments ca ON ca.complaint_id = c.id AND ca.assignment_status = 'active'
         LEFT JOIN staff s ON s.id = ca.official_id
         WHERE c.complaint_code = :code AND c.citizen_id = :cid
         LIMIT 1"
    );
    $stmt->execute([':code' => $code, ':cid' => $citizenId]);
    $complaint = $stmt->fetch();

    if (!$complaint) {
        $error = 'No complaint found with that ID in your account. Check the ID and try again.';
    }
}

$pageTitle   = 'Track Complaint';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/citizen/dashboard.php'],
    ['label' => 'Track Complaint'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-search me-2"></i>Track Your Complaint</div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-hash"></i></span>
                        <input type="text" name="code" class="form-control form-control-lg"
                               placeholder="e.g. CFX-2026-000001"
                               value="<?= e($_POST['code'] ?? '') ?>"
                               required autofocus
                               style="text-transform:uppercase; letter-spacing:.05em">
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">Track</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($complaint): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-bold"><?= e($complaint['complaint_code']) ?></span>
                    <div class="d-flex gap-2">
                        <?= priorityBadge($complaint['priority']) ?>
                        <?= statusBadge($complaint['status']) ?>
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="fw-bold"><?= e($complaint['title']) ?></h5>
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <small class="text-muted d-block">Category</small>
                            <?= e($complaint['category_name']) ?>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Department</small>
                            <?= e($complaint['department_name'] ?? 'Pending Routing') ?>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Ward</small>
                            <?= e($complaint['ward_name'] ?? '—') ?>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Assigned To</small>
                            <?= $complaint['official_name'] ? e($complaint['official_name']) : '<span class="text-muted">Pending</span>' ?>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Submitted</small>
                            <?= formatDate($complaint['created_at']) ?>
                        </div>
                    </div>
                    <a href="<?= APP_URL ?>/citizen/complaint_details.php?id=<?= (int)$complaint['id'] ?>"
                       class="btn btn-primary">
                        <i class="bi bi-eye me-2"></i>View Full Details & History
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
