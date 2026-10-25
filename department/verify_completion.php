<?php
/**
 * Department Admin — Completion Verification
 */
$requiredRole = 'department_admin';
require_once __DIR__ . '/../middleware/role_guard.php';
require_once __DIR__ . '/../services/AssignmentService.php';

$deptId  = $_SESSION['dept_id'];
$adminId = $_SESSION['user_id'];
$pdo     = db();

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $complaintId = (int)($_POST['complaint_id'] ?? 0);
    $decision    = $_POST['decision'] ?? '';
    $remarks     = sanitize($_POST['remarks'] ?? '');

    // Validate that complaint belongs to this department
    $chk = $pdo->prepare("SELECT id, complaint_code, status FROM complaints WHERE id = :id AND department_id = :did");
    $chk->execute([':id' => $complaintId, ':did' => $deptId]);
    $complaint = $chk->fetch();

    if ($complaint && $complaint['status'] === 'awaiting_verification') {
        $service = new AssignmentService($pdo);
        if ($decision === 'approve') {
            $res = $service->transition($complaintId, 'resolved', $adminId, 'department_admin', $remarks ?: 'Completion verified and approved by Department Admin.');
            if ($res['ok']) {
                flash('success', "Complaint {$complaint['complaint_code']} resolved and verified successfully.", 'success');
            } else {
                flash('error', $res['error'], 'error');
            }
        } elseif ($decision === 'reject') {
            if (empty($remarks)) {
                flash('error', 'Rejection explanation is mandatory.', 'error');
            } else {
                $res = $service->transition($complaintId, 'rework_required', $adminId, 'department_admin', "Rework requested: $remarks");
                if ($res['ok']) {
                    // Notify official
                    $offStmt = $pdo->prepare("SELECT official_id FROM complaint_assignments WHERE complaint_id = :cid AND assignment_status = 'active'");
                    $offStmt->execute([':cid' => $complaintId]);
                    $offId = $offStmt->fetchColumn();
                    if ($offId) {
                        $notif = new NotificationService($pdo);
                        $notif->workRejected((int)$offId, $complaint['complaint_code'], $complaintId, $remarks);
                    }
                    flash('success', "Complaint {$complaint['complaint_code']} returned for rework.", 'warning');
                } else {
                    flash('error', $res['error'], 'error');
                }
            }
        }
    }
    redirectTo(APP_URL . '/department/verify_completion.php');
}

// Fetch all complaints awaiting verification for this department
$stmt = $pdo->prepare(
    "SELECT c.*, cat.category_name, w.ward_name, s.name AS official_name, s.id AS official_id,
            ca.assigned_at, ca.completed_at
     FROM complaints c
     JOIN complaint_assignments ca ON ca.complaint_id = c.id AND ca.assignment_status = 'active'
     LEFT JOIN staff s ON s.id = ca.official_id
     LEFT JOIN categories cat ON cat.id = c.category_id
     LEFT JOIN wards w ON w.id = c.ward_id
     WHERE c.department_id = :did AND c.status = 'awaiting_verification'
     ORDER BY ca.completed_at ASC"
);
$stmt->execute([':did' => $deptId]);
$awaitingList = $stmt->fetchAll();

$pageTitle   = 'Verify Work Completion';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/department/dashboard.php'],
    ['label' => 'Verify Completion'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="data-card">
    <div class="data-card-header">
        <h2 class="data-card-title"><?= count($awaitingList) ?> Submissions Awaiting Verification</h2>
    </div>
    <?php if (empty($awaitingList)): ?>
        <div class="empty-state py-5">
            <i class="bi bi-shield-check text-success fs-1"></i>
            <p>No completion submissions currently waiting for review.</p>
        </div>
    <?php else: ?>
        <div class="p-3">
            <?php foreach ($awaitingList as $c): ?>
                <?php
                // Get evidence photos
                $photos = $pdo->prepare("SELECT * FROM complaint_attachments WHERE complaint_id = :cid ORDER BY created_at ASC");
                $photos->execute([':cid' => $c['id']]);
                $photos = $photos->fetchAll();

                // Get latest official completion remark from history
                $hist = $pdo->prepare("SELECT remarks, created_at FROM complaint_history WHERE complaint_id = :cid AND new_status = 'awaiting_verification' ORDER BY id DESC LIMIT 1");
                $hist->execute([':cid' => $c['id']]);
                $latestRemark = $hist->fetch();
                ?>
                <div class="card mb-4 border">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><code><?= e($c['complaint_code']) ?></code> — <?= e($c['title']) ?></span>
                        <div>
                            <?= priorityBadge($c['priority']) ?>
                            <span class="badge bg-info text-white ms-1">Submitted <?= timeAgo($c['completed_at'] ?: $c['updated_at']) ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Work Official</small>
                                <strong><?= e($c['official_name']) ?></strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Ward / Area</small>
                                <strong><?= e($c['ward_name']) ?></strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Category</small>
                                <strong><?= e($c['category_name']) ?></strong>
                            </div>
                            <div class="col-12">
                                <small class="text-muted d-block">Official's Completion Remarks</small>
                                <div class="p-2 bg-light rounded text-dark">
                                    <?= nl2br(e($latestRemark['remarks'] ?? 'No remark provided.')) ?>
                                </div>
                            </div>
                            <?php if (!empty($photos)): ?>
                                <div class="col-12">
                                    <small class="text-muted d-block mb-1">Attached Evidence (Initial & Completion)</small>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($photos as $p): ?>
                                            <div class="text-center">
                                                <a href="<?= UPLOAD_URL . e($p['file_path']) ?>" target="_blank">
                                                    <img src="<?= UPLOAD_URL . e($p['file_path']) ?>" class="img-thumbnail" style="height: 110px; width: 110px; object-fit: cover;">
                                                </a>
                                                <div class="small text-muted mt-1"><?= ucfirst(str_replace('_', ' ', $p['attachment_type'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <hr>

                        <!-- Decision form -->
                        <form method="post" class="mt-3">
                            <?= csrfField() ?>
                            <input type="hidden" name="complaint_id" value="<?= (int)$c['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Verification Decision Remarks</label>
                                <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Mandatory if rejecting; optional for approval..."></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="decision" value="approve" class="btn btn-success fw-bold px-4" data-confirm="Approve this work and mark complaint as Resolved?">
                                    <i class="bi bi-check-circle me-1"></i> Approve & Resolve
                                </button>
                                <button type="submit" name="decision" value="reject" class="btn btn-outline-danger fw-bold px-4" data-confirm="Reject this completion and send back for rework?">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reject (Require Rework)
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
