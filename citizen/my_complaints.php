<?php
/**
 * Citizen — My Complaints (list with search/filter)
 */
$requiredRole = 'citizen';
require_once __DIR__ . '/../middleware/role_guard.php';

$citizenId = $_SESSION['user_id'];
$pdo       = db();

// ── Filters ────────────────────────────────────────────────────
$search    = sanitize($_GET['q']        ?? '');
$status    = sanitize($_GET['status']   ?? '');
$category  = (int)($_GET['category']   ?? 0);
$sort      = $_GET['sort'] ?? 'newest';
$page      = max(1, (int)($_GET['page'] ?? 1));

$where    = ['c.citizen_id = :cid'];
$params   = [':cid' => $citizenId];

if ($search) {
    $where[] = '(c.complaint_code LIKE :q OR c.title LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}
if ($status) {
    $where[] = 'c.status = :status';
    $params[':status'] = $status;
}
if ($category > 0) {
    $where[] = 'c.category_id = :cat';
    $params[':cat'] = $category;
}

$orderBy = $sort === 'oldest' ? 'c.created_at ASC' : 'c.created_at DESC';
$whereStr = 'WHERE ' . implode(' AND ', $where);

// Count
$countSql = $pdo->prepare("SELECT COUNT(*) FROM complaints c $whereStr");
$countSql->execute($params);
$total = (int)$countSql->fetchColumn();
$paginationData = paginate($total, ITEMS_PER_PAGE, $page);

// Fetch
$sql = "SELECT c.id, c.complaint_code, c.title, c.status, c.priority, c.created_at,
               cat.category_name, d.department_name, w.ward_name,
               s.name AS official_name
        FROM complaints c
        LEFT JOIN categories cat  ON cat.id = c.category_id
        LEFT JOIN departments d   ON d.id   = c.department_id
        LEFT JOIN wards       w   ON w.id   = c.ward_id
        LEFT JOIN complaint_assignments ca ON ca.complaint_id = c.id AND ca.assignment_status = 'active'
        LEFT JOIN staff s ON s.id = ca.official_id
        $whereStr
        ORDER BY $orderBy
        LIMIT " . ITEMS_PER_PAGE . " OFFSET " . $paginationData['offset'];
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// For filter dropdown
$categoriesList = $pdo->query("SELECT id, category_name FROM categories WHERE status='active' ORDER BY category_name")->fetchAll();

$statuses = [
    'pending', 'assigned', 'in_progress', 'awaiting_verification',
    'rework_required', 'resolved', 'rejected', 'cancelled'
];

$pageTitle   = 'My Complaints';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/citizen/dashboard.php'],
    ['label' => 'My Complaints'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="Search by ID or title…" value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>>
                            <?= ucwords(str_replace('_', ' ', $s)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categoriesList as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= $category === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select form-select-sm">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<div class="data-card">
    <div class="data-card-header">
        <h2 class="data-card-title">
            <?= $total ?> Complaint<?= $total !== 1 ? 's' : '' ?>
        </h2>
        <a href="<?= APP_URL ?>/citizen/submit_complaint.php" class="btn btn-sm btn-primary">
            <i class="bi bi-plus me-1"></i>New Complaint
        </a>
    </div>

    <?php if (empty($complaints)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p><?= $search || $status || $category ? 'No complaints match your filters.' : 'No complaints submitted yet.' ?></p>
            <?php if (!$search && !$status && !$category): ?>
                <a href="<?= APP_URL ?>/citizen/submit_complaint.php" class="btn btn-primary">Report an Issue</a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/citizen/my_complaints.php" class="btn btn-outline-secondary">Clear Filters</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-stack">
                <thead>
                    <tr>
                        <th>Complaint ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Ward</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complaints as $c): ?>
                        <tr>
                            <td data-label="ID"><code class="small"><?= e($c['complaint_code']) ?></code></td>
                            <td data-label="Title" class="fw-semibold"><?= e(mb_strimwidth($c['title'], 0, 45, '…')) ?></td>
                            <td data-label="Category"><small><?= e($c['category_name']) ?></small></td>
                            <td data-label="Ward"><small><?= e($c['ward_name']) ?></small></td>
                            <td data-label="Priority"><?= priorityBadge($c['priority']) ?></td>
                            <td data-label="Status"><?= statusBadge($c['status']) ?></td>
                            <td data-label="Date"><small class="text-muted"><?= formatDate($c['created_at'], 'd M Y') ?></small></td>
                            <td data-label="">
                                <a href="<?= APP_URL ?>/citizen/complaint_details.php?id=<?= (int)$c['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($paginationData['total_pages'] > 1): ?>
            <div class="p-3 border-top">
                <nav>
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <?php if ($paginationData['has_prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page-1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&category=<?= $category ?>&sort=<?= urlencode($sort) ?>">
                                    &laquo; Prev
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page-2); $i <= min($paginationData['total_pages'], $page+2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&category=<?= $category ?>&sort=<?= urlencode($sort) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($paginationData['has_next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page+1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&category=<?= $category ?>&sort=<?= urlencode($sort) ?>">
                                    Next &raquo;
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
