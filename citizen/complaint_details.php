<?php
/**
 * Citizen — Complaint Details
 */
$requiredRole = 'citizen';
require_once __DIR__ . '/../middleware/role_guard.php';

$citizenId   = $_SESSION['user_id'];
$complaintId = (int)($_GET['id'] ?? 0);
$isNew       = isset($_GET['new']);
$pdo         = db();

// Fetch complaint — enforce ownership
$stmt = $pdo->prepare(
    "SELECT c.*, cat.category_name, d.department_name, w.ward_name
     FROM complaints c
     LEFT JOIN categories  cat ON cat.id = c.category_id
     LEFT JOIN departments d   ON d.id   = c.department_id
     LEFT JOIN wards       w   ON w.id   = c.ward_id
     WHERE c.id = :id AND c.citizen_id = :cid
     LIMIT 1"
);
$stmt->execute([':id' => $complaintId, ':cid' => $citizenId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    flash('error', 'Complaint not found or access denied.', 'error');
    redirectTo(APP_URL . '/citizen/my_complaints.php');
}

// Active assignment
$assignStmt = $pdo->prepare(
    "SELECT ca.*, s.name AS official_name, s.phone AS official_phone, s.email AS official_email
     FROM complaint_assignments ca
     LEFT JOIN staff s ON s.id = ca.official_id
     WHERE ca.complaint_id = :cid AND ca.assignment_status = 'active'
     ORDER BY ca.assigned_at DESC LIMIT 1"
);
$assignStmt->execute([':cid' => $complaintId]);
$assignment = $assignStmt->fetch();

// History
$histStmt = $pdo->prepare(
    "SELECT ch.*, 
            CASE ch.actor_role
                WHEN 'citizen' THEN (SELECT name FROM users  WHERE id = ch.actor_id)
                ELSE                (SELECT name FROM staff  WHERE id = ch.actor_id)
            END AS actor_name
     FROM complaint_history ch
     WHERE ch.complaint_id = :cid
     ORDER BY ch.created_at ASC"
);
$histStmt->execute([':cid' => $complaintId]);
$history = $histStmt->fetchAll();

// Attachments
$attachStmt = $pdo->prepare(
    "SELECT * FROM complaint_attachments WHERE complaint_id = :cid ORDER BY created_at ASC"
);
$attachStmt->execute([':cid' => $complaintId]);
$attachments = $attachStmt->fetchAll();

$pageTitle   = $complaint['complaint_code'];
$breadcrumbs = [
    ['label' => 'Dashboard',      'href' => APP_URL . '/citizen/dashboard.php'],
    ['label' => 'My Complaints',  'href' => APP_URL . '/citizen/my_complaints.php'],
    ['label' => $complaint['complaint_code']],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<?php if ($isNew): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill me-2"></i>
        <strong>Complaint submitted!</strong>
        Your complaint ID is <strong><?= e($complaint['complaint_code']) ?></strong>.
        <?php if ($complaint['status'] === 'assigned'): ?>
            It has been automatically assigned to an official.
        <?php else: ?>
            Assignment is pending — an official will be assigned shortly.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Main Details -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-bold"><i class="bi bi-file-text me-2"></i><?= e($complaint['complaint_code']) ?></span>
                <div class="d-flex gap-2 align-items-center">
                    <?= priorityBadge($complaint['priority']) ?>
                    <?= statusBadge($complaint['status']) ?>
                </div>
            </div>
            <div class="card-body">
                <h5 class="fw-bold mb-3"><?= e($complaint['title']) ?></h5>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Category</small>
                        <span class="fw-semibold"><?= e($complaint['category_name']) ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Department</small>
                        <span class="fw-semibold"><?= e($complaint['department_name'] ?? 'Pending Routing') ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Ward</small>
                        <span class="fw-semibold"><?= e($complaint['ward_name'] ?? '—') ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Submitted</small>
                        <span class="fw-semibold"><?= formatDate($complaint['created_at']) ?></span>
                    </div>
                    <?php if ($complaint['address']): ?>
                    <div class="col-12">
                        <small class="text-muted d-block">Address</small>
                        <span><?= e($complaint['address']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Description</small>
                    <div class="bg-light rounded p-3"><?= nl2br(e($complaint['description'])) ?></div>
                </div>

                <!-- Attachments -->
                <?php if (!empty($attachments)): ?>
                <div>
                    <small class="text-muted d-block mb-2">Attached Photos</small>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($attachments as $att): ?>
                            <a href="<?= UPLOAD_URL . e($att['file_path']) ?>" target="_blank" rel="noopener">
                                <img src="<?= UPLOAD_URL . e($att['file_path']) ?>"
                                     alt="<?= e($att['original_filename']) ?>"
                                     class="img-thumbnail"
                                     style="height:100px; width:auto; object-fit:cover">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- History Timeline -->
        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Status History</div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <p class="text-muted small">No history yet.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($history as $h): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-time"><?= formatDate($h['created_at']) ?></div>
                                <div class="timeline-title">
                                    <?= statusBadge($h['new_status']) ?>
                                    <span class="ms-2 small text-muted">by <?= e($h['actor_name'] ?? ucfirst($h['actor_role'])) ?></span>
                                </div>
                                <?php if ($h['remarks']): ?>
                                    <div class="timeline-body"><?= e($h['remarks']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Assignment Sidebar -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person-gear me-2"></i>Assignment</div>
            <div class="card-body">
                <?php if ($assignment): ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="sidebar-user-avatar bg-primary text-white">
                            <?= strtoupper(substr($assignment['official_name'] ?? 'O', 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-semibold"><?= e($assignment['official_name']) ?></div>
                            <small class="text-muted">Work Official</small>
                        </div>
                    </div>
                    <ul class="list-unstyled small text-muted">
                        <li><i class="bi bi-clock me-2"></i>Assigned: <?= formatDate($assignment['assigned_at'], 'd M Y') ?></li>
                        <?php if ($assignment['accepted_at']): ?>
                            <li><i class="bi bi-check me-2"></i>Accepted: <?= formatDate($assignment['accepted_at'], 'd M Y') ?></li>
                        <?php endif; ?>
                        <li>
                            <i class="bi bi-tag me-2"></i>Type:
                            <?= $assignment['assignment_type'] === 'automatic' ? '<span class="badge bg-success">Auto</span>' : '<span class="badge bg-warning text-dark">Manual</span>' ?>
                        </li>
                    </ul>
                <?php elseif ($complaint['status'] === 'pending'): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-hourglass me-2"></i>
                        <strong>Assignment Pending.</strong><br>
                        <small>No official is currently available for your ward. An assignment will be made soon.</small>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">No active assignment.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($complaint['resolved_at']): ?>
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="bi bi-check-circle-fill text-success fs-1"></i>
                <div class="fw-bold text-success mt-2">Resolved</div>
                <small class="text-muted"><?= formatDate($complaint['resolved_at']) ?></small>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-3 d-grid">
            <a href="<?= APP_URL ?>/citizen/my_complaints.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to My Complaints
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
