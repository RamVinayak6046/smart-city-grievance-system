<?php
/**
 * Official — Complaint Details & Progress Actions
 */
$requiredRole = 'work_official';
require_once __DIR__ . '/../middleware/role_guard.php';
require_once __DIR__ . '/../services/AssignmentService.php';

$officialId  = $_SESSION['user_id'];
$complaintId = (int)($_GET['id'] ?? 0);
$pdo         = db();

// Check if this complaint is currently assigned to this official
$stmt = $pdo->prepare(
    "SELECT c.*, cat.category_name, cat.verification_required, d.department_name, w.ward_name, u.name AS citizen_name, u.phone AS citizen_phone
     FROM complaints c
     JOIN complaint_assignments ca ON ca.complaint_id = c.id AND ca.assignment_status = 'active'
     LEFT JOIN categories cat ON cat.id = c.category_id
     LEFT JOIN departments d ON d.id = c.department_id
     LEFT JOIN wards w ON w.id = c.ward_id
     LEFT JOIN users u ON u.id = c.citizen_id
     WHERE c.id = :cid AND ca.official_id = :oid LIMIT 1"
);
$stmt->execute([':cid' => $complaintId, ':oid' => $officialId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    flash('error', 'Task not found or access denied.', 'error');
    redirectTo(APP_URL . '/official/dashboard.php');
}

// Handle Task Actions (Accept, Start, Progress, Submit Verification)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action  = $_POST['action'] ?? '';
    $remarks = sanitize($_POST['remarks'] ?? '');
    $service = new AssignmentService($pdo);

    if ($action === 'accept') {
        if ($complaint['status'] === 'assigned') {
            $pdo->prepare("UPDATE complaint_assignments SET accepted_at = CURRENT_TIMESTAMP WHERE complaint_id = :cid AND official_id = :oid AND assignment_status = 'active'")
                ->execute([':cid' => $complaintId, ':oid' => $officialId]);
            
            // Add history entry
            $pdo->prepare("INSERT INTO complaint_history (complaint_id, actor_id, actor_role, old_status, new_status, remarks) VALUES (:cid, :aid, 'work_official', 'assigned', 'assigned', 'Assignment accepted by official.')")
                ->execute([':cid' => $complaintId, ':aid' => $officialId]);

            flash('success', 'Assignment accepted! You can now start work.', 'success');
        }
    } elseif ($action === 'start') {
        if (in_array($complaint['status'], ['assigned', 'rework_required'])) {
            $res = $service->transition($complaintId, 'in_progress', $officialId, 'work_official', $remarks ?: 'Work started by official.');
            if ($res['ok']) {
                flash('success', 'Status updated to In Progress.', 'success');
            } else {
                flash('error', $res['error'], 'error');
            }
        }
    } elseif ($action === 'submit_verification') {
        if (in_array($complaint['status'], ['in_progress', 'rework_required'])) {
            // Check photos
            $uploadedPath = null;
            if (!empty($_FILES['completion_photo']['name'])) {
                $file = $_FILES['completion_photo'];
                $errs = validateUpload($file);
                if (empty($errs)) {
                    $saved = saveUpload($file, 'completion');
                    if ($saved) {
                        $uploadedPath = $saved;
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime  = $finfo->file($file['tmp_name']);
                        $pdo->prepare(
                            "INSERT INTO complaint_attachments (complaint_id, uploaded_by, uploaded_by_role, file_path, original_filename, mime_type, file_size, attachment_type)
                             VALUES (:cid, :uid, 'work_official', :path, :orig, :mime, :size, 'completion_evidence')"
                        )->execute([
                            ':cid'  => $complaintId,
                            ':uid'  => $officialId,
                            ':path' => $saved,
                            ':orig' => $file['name'],
                            ':mime' => $mime,
                            ':size' => $file['size']
                        ]);
                    }
                } else {
                    flash('error', implode(' ', $errs), 'error');
                    redirectTo(APP_URL . "/official/complaint_details.php?id=$complaintId");
                }
            }

            $res = $service->transition($complaintId, 'awaiting_verification', $officialId, 'work_official', $remarks ?: 'Work completed and submitted for verification.');
            if ($res['ok']) {
                $notif = new NotificationService($pdo);
                $notif->awaitingVerification((int)$complaint['department_id'], $complaint['complaint_code'], $complaintId);
                flash('success', 'Work submitted for verification.', 'success');
            } else {
                flash('error', $res['error'], 'error');
            }
        }
    } elseif ($action === 'add_progress_note') {
        if (!empty($remarks)) {
            $pdo->prepare("INSERT INTO complaint_history (complaint_id, actor_id, actor_role, old_status, new_status, remarks) VALUES (:cid, :aid, 'work_official', :st, :st, :rem)")
                ->execute([':cid' => $complaintId, ':aid' => $officialId, ':st' => $complaint['status'], ':rem' => "Progress update: $remarks"]);
            flash('success', 'Progress note added.', 'success');
        }
    }
    redirectTo(APP_URL . "/official/complaint_details.php?id=$complaintId");
}

// Fetch Attachments & History
$attachments = $pdo->prepare("SELECT * FROM complaint_attachments WHERE complaint_id = :cid ORDER BY created_at ASC");
$attachments->execute([':cid' => $complaintId]);
$attachments = $attachments->fetchAll();

$history = $pdo->prepare("SELECT * FROM complaint_history WHERE complaint_id = :cid ORDER BY created_at ASC");
$history->execute([':cid' => $complaintId]);
$history = $history->fetchAll();

$pageTitle   = "Task " . $complaint['complaint_code'];
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/official/dashboard.php'],
    ['label' => 'Assignments', 'href' => APP_URL . '/official/assignments.php'],
    ['label' => $complaint['complaint_code']],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="row g-4">
    <!-- Complaint Overview -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><?= e($complaint['complaint_code']) ?></span>
                <div>
                    <?= priorityBadge($complaint['priority']) ?>
                    <?= statusBadge($complaint['status']) ?>
                </div>
            </div>
            <div class="card-body">
                <h4 class="fw-bold"><?= e($complaint['title']) ?></h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Category</small>
                        <strong><?= e($complaint['category_name']) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Ward</small>
                        <strong><?= e($complaint['ward_name']) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Reported Date</small>
                        <strong><?= formatDate($complaint['created_at']) ?></strong>
                    </div>
                    <?php if ($complaint['address']): ?>
                        <div class="col-12">
                            <small class="text-muted d-block">Location Details</small>
                            <div><?= e($complaint['address']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <small class="text-muted d-block mb-1">Issue Description</small>
                    <div class="p-3 bg-light rounded"><?= nl2br(e($complaint['description'])) ?></div>
                </div>

                <!-- Photos -->
                <?php if (!empty($attachments)): ?>
                    <div>
                        <small class="text-muted d-block mb-2">Attached Evidence / Photos</small>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($attachments as $a): ?>
                                <div class="text-center">
                                    <a href="<?= UPLOAD_URL . e($a['file_path']) ?>" target="_blank">
                                        <img src="<?= UPLOAD_URL . e($a['file_path']) ?>" class="img-thumbnail" style="height: 100px; width: 100px; object-fit: cover;">
                                    </a>
                                    <div class="small text-muted mt-1"><?= ucfirst(str_replace('_', ' ', $a['attachment_type'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- History Timeline -->
        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Task Timeline</div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($history as $h): ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-time"><?= formatDate($h['created_at']) ?></div>
                            <div class="timeline-title"><?= statusBadge($h['new_status']) ?> <span class="small text-muted">by <?= ucfirst(e($h['actor_role'])) ?></span></div>
                            <?php if ($h['remarks']): ?>
                                <div class="timeline-body"><?= e($h['remarks']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Official Actions Panel -->
    <div class="col-lg-4">
        <div class="card border-primary mb-4">
            <div class="card-header bg-primary text-white"><i class="bi bi-lightning-charge me-2"></i>Action Panel</div>
            <div class="card-body">
                <?php if ($complaint['status'] === 'assigned'): ?>
                    <p class="small text-muted mb-3">You have been assigned this complaint. Accept it to acknowledge receipt.</p>
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="accept">
                        <button type="submit" class="btn btn-success w-100 mb-2 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Accept Assignment
                        </button>
                    </form>
                <?php elseif ($complaint['status'] === 'in_progress' || $complaint['status'] === 'rework_required'): ?>
                    <?php if ($complaint['status'] === 'rework_required'): ?>
                        <div class="alert alert-warning small mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Admin requested rework. Please complete the adjustments and re-submit verification.
                        </div>
                    <?php endif; ?>

                    <!-- Add Progress Note -->
                    <form method="post" class="mb-4">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="add_progress_note">
                        <label class="form-label small fw-bold">Post Progress Note</label>
                        <textarea name="remarks" class="form-control form-control-sm mb-2" rows="2" placeholder="e.g. Materials delivered, work underway..." required></textarea>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">Add Progress Note</button>
                    </form>

                    <hr>

                    <!-- Submit Verification -->
                    <form method="post" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="submit_verification">
                        <label class="form-label small fw-bold text-success">Complete & Submit for Verification</label>
                        <div class="mb-2">
                            <label class="form-label small text-muted">Completion Photo Evidence</label>
                            <input type="file" name="completion_photo" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Completion Remarks</label>
                            <textarea name="remarks" class="form-control form-control-sm" rows="3" placeholder="Summary of work done to resolve issue..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold">
                            <i class="bi bi-patch-check me-1"></i> Submit for Verification
                        </button>
                    </form>
                <?php elseif ($complaint['status'] === 'awaiting_verification'): ?>
                    <div class="alert alert-info mb-0 text-center">
                        <i class="bi bi-hourglass-split fs-3 d-block mb-2"></i>
                        <strong>Under Review</strong>
                        <p class="small mb-0 mt-1">Submitted for verification. Awaiting Department Admin approval.</p>
                    </div>
                <?php elseif ($complaint['status'] === 'resolved'): ?>
                    <div class="alert alert-success mb-0 text-center">
                        <i class="bi bi-check-circle-fill fs-3 d-block mb-2"></i>
                        <strong>Resolved & Approved</strong>
                        <p class="small mb-0 mt-1">This work has been verified and closed.</p>
                    </div>
                <?php else: ?>
                    <p class="small text-muted mb-0">No actions available for status: <strong><?= e($complaint['status']) ?></strong></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Citizen Contact -->
        <div class="card">
            <div class="card-header"><i class="bi bi-person me-2"></i>Citizen Information</div>
            <div class="card-body">
                <div><strong><?= e($complaint['citizen_name']) ?></strong></div>
                <?php if ($complaint['citizen_phone']): ?>
                    <small class="text-muted"><i class="bi bi-telephone me-1"></i> <?= e($complaint['citizen_phone']) ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
