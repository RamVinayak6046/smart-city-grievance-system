<?php
/**
 * Reports & Analytics (Shared across Super Admin & Dept Admin)
 */
require_once __DIR__ . '/../includes/boot.php';
requireLogin();

$role   = $_SESSION['role'];
$deptId = $_SESSION['dept_id'] ?? null;
$pdo    = db();

if (!in_array($role, ['super_admin', 'department_admin'])) {
    http_response_code(403);
    die("Access denied.");
}

// Scope condition based on role
$whereDept = "";
$params = [];
if ($role === 'department_admin') {
    $whereDept = "WHERE c.department_id = :did";
    $params[':did'] = $deptId;
}

// Export logic (CSV / Excel format)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=civicfix_report_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Complaint Code', 'Title', 'Category', 'Department', 'Ward', 'Priority', 'Status', 'Submitted At', 'Resolved At']);

    $expSql = "SELECT c.complaint_code, c.title, cat.category_name, d.department_name, w.ward_name, c.priority, c.status, c.created_at, c.resolved_at
               FROM complaints c
               LEFT JOIN categories cat ON cat.id = c.category_id
               LEFT JOIN departments d ON d.id = c.department_id
               LEFT JOIN wards w ON w.id = c.ward_id
               $whereDept
               ORDER BY c.created_at DESC";
    $expStmt = $pdo->prepare($expSql);
    $expStmt->execute($params);
    while ($row = $expStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Analytics Queries
// 1. Status distribution
$statusStmt = $pdo->prepare(
    "SELECT c.status, COUNT(*) AS cnt
     FROM complaints c
     $whereDept
     GROUP BY c.status"
);
$statusStmt->execute($params);
$statusRows = $statusStmt->fetchAll();
$statusLabels = array_map(fn($r) => ucfirst(str_replace('_', ' ', $r['status'])), $statusRows);
$statusCounts = array_column($statusRows, 'cnt');

// 2. Ward breakdown
$wardStmt = $pdo->prepare(
    "SELECT w.ward_code, COUNT(c.id) AS cnt
     FROM complaints c
     JOIN wards w ON w.id = c.ward_id
     $whereDept
     GROUP BY w.id, w.ward_code
     ORDER BY cnt DESC LIMIT 10"
);
$wardStmt->execute($params);
$wardRows = $wardStmt->fetchAll();
$wardLabels = array_column($wardRows, 'ward_code');
$wardCounts = array_column($wardRows, 'cnt');

$pageTitle   = 'Analytics & Reports';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => $role === 'super_admin' ? APP_URL . '/superadmin/dashboard.php' : APP_URL . '/department/dashboard.php'],
    ['label' => 'Reports'],
];

$extraJs = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function () {
    CivicChart.doughnut('chartStatus',
        <?= json_encode($statusLabels) ?>,
        <?= json_encode($statusCounts) ?>
    );
    CivicChart.bar('chartWards',
        <?= json_encode($wardLabels) ?>,
        [{
            label: 'Complaints',
            data: <?= json_encode($wardCounts) ?>,
            backgroundColor: '#0d9488'
        }]
    );
});
</script>
JS;

include __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Generated from persistent database records in real time.</p>
    <a href="?export=csv" class="btn btn-success fw-semibold">
        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export to CSV / Excel
    </a>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="data-card">
            <div class="data-card-header"><h2 class="data-card-title">Complaints by Lifecycle Status</h2></div>
            <div class="p-3">
                <?php if (empty($statusRows)): ?>
                    <div class="empty-state py-4"><p class="text-muted">No data available.</p></div>
                <?php else: ?>
                    <canvas id="chartStatus" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="data-card">
            <div class="data-card-header"><h2 class="data-card-title">Top Wards by Complaint Volume</h2></div>
            <div class="p-3">
                <?php if (empty($wardRows)): ?>
                    <div class="empty-state py-4"><p class="text-muted">No data available.</p></div>
                <?php else: ?>
                    <canvas id="chartWards" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
