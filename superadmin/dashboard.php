<?php
/**
 * Super Admin — Dashboard
 */
$requiredRole = 'super_admin';
require_once __DIR__ . '/../middleware/role_guard.php';

$pdo = db();

// ── Global stats ───────────────────────────────────────────────
$stats = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM users       WHERE account_status='active')           AS citizens,
        (SELECT COUNT(*) FROM departments WHERE status='active')                   AS departments,
        (SELECT COUNT(*) FROM staff       WHERE role_id=2 AND account_status='active') AS officials,
        (SELECT COUNT(*) FROM complaints)                                           AS total_complaints,
        (SELECT COUNT(*) FROM complaints  WHERE status='pending')                  AS pending,
        (SELECT COUNT(*) FROM complaints  WHERE status='assigned')                 AS assigned,
        (SELECT COUNT(*) FROM complaints  WHERE status='in_progress')              AS in_progress,
        (SELECT COUNT(*) FROM complaints  WHERE status='awaiting_verification')    AS awaiting,
        (SELECT COUNT(*) FROM complaints  WHERE status='resolved')                 AS resolved"
)->fetch();

// ── Monthly trend (last 6 months) ─────────────────────────────
$isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
if ($isSqlite) {
    $trendStmt = $pdo->query(
        "SELECT strftime('%Y-%m', created_at) AS month,
                COUNT(*) AS count
         FROM complaints
         WHERE created_at >= datetime('now', '-6 months')
         GROUP BY month
         ORDER BY month ASC"
    );
} else {
    $trendStmt = $pdo->query(
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                COUNT(*) AS count
         FROM complaints
         WHERE created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 6 MONTH)
         GROUP BY month
         ORDER BY month ASC"
    );
}
$trendRows = $trendStmt->fetchAll();
$trendLabels = array_column($trendRows, 'month');
$trendData   = array_column($trendRows, 'count');

// ── Complaints by category ─────────────────────────────────────
$catStats = $pdo->query(
    "SELECT cat.category_name, COUNT(c.id) AS cnt
     FROM complaints c
     JOIN categories cat ON cat.id = c.category_id
     GROUP BY cat.id, cat.category_name
     ORDER BY cnt DESC LIMIT 8"
)->fetchAll();

// ── Dept performance ───────────────────────────────────────────
$deptPerf = $pdo->query(
    "SELECT d.department_name,
            COUNT(c.id)                                        AS total,
            SUM(c.status = 'resolved')                        AS resolved,
            SUM(c.status IN ('pending','assigned','in_progress')) AS active
     FROM departments d
     LEFT JOIN complaints c ON c.department_id = d.id
     WHERE d.status = 'active'
     GROUP BY d.id, d.department_name
     ORDER BY total DESC"
)->fetchAll();

// ── Recent complaints ──────────────────────────────────────────
$recent = $pdo->query(
    "SELECT c.complaint_code, c.title, c.status, c.priority, c.created_at,
            cat.category_name, d.department_name
     FROM complaints c
     LEFT JOIN categories  cat ON cat.id = c.category_id
     LEFT JOIN departments d   ON d.id   = c.department_id
     ORDER BY c.created_at DESC LIMIT 8"
)->fetchAll();

$pageTitle   = 'Super Admin Dashboard';
$breadcrumbs = [['label' => 'Dashboard']];
$extraJs = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Monthly trend
    CivicChart.line('chartTrend',
        <?= json_encode($trendLabels) ?>,
        [{
            label: 'Complaints',
            data: <?= json_encode($trendData) ?>,
            borderColor: '#1a3c6e',
            backgroundColor: 'rgba(26,60,110,.08)',
            fill: true,
        }]
    );
    // By category
    CivicChart.doughnut('chartCategories',
        <?= json_encode(array_column($catStats, 'category_name')) ?>,
        <?= json_encode(array_column($catStats, 'cnt')) ?>
    );
});
</script>
JS;
include __DIR__ . '/../includes/dash_header.php';
?>

<!-- Stat cards row 1 -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-3">
        <div class="stat-card stat-navy">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format((int)$stats['citizens']) ?></div>
                <div class="stat-label">Citizens</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="stat-card stat-teal">
            <div class="stat-icon"><i class="bi bi-building"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$stats['departments'] ?></div>
                <div class="stat-label">Departments</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="stat-card stat-blue">
            <div class="stat-icon"><i class="bi bi-person-gear"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$stats['officials'] ?></div>
                <div class="stat-label">Officials</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="stat-card stat-purple">
            <div class="stat-icon"><i class="bi bi-clipboard2-pulse"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format((int)$stats['total_complaints']) ?></div>
                <div class="stat-label">Total Complaints</div>
            </div>
        </div>
    </div>
</div>

<!-- Stat cards row 2 -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-2">
        <div class="stat-card stat-amber">
            <div class="stat-icon"><i class="bi bi-hourglass"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$stats['pending'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card stat-blue">
            <div class="stat-icon"><i class="bi bi-diagram-3"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$stats['assigned'] ?></div>
                <div class="stat-label">Assigned</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card stat-purple">
            <div class="stat-icon"><i class="bi bi-tools"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$stats['in_progress'] ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card stat-cyan">
            <div class="stat-icon"><i class="bi bi-patch-check"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$stats['awaiting'] ?></div>
                <div class="stat-label">Awaiting Verify</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card stat-green">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)$stats['resolved'] ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
    </div>
    <?php if ((int)$stats['total_complaints'] > 0): ?>
    <div class="col-6 col-lg-2">
        <?php $rate = round($stats['resolved'] / $stats['total_complaints'] * 100); ?>
        <div class="stat-card stat-green">
            <div class="stat-icon"><i class="bi bi-bar-chart"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $rate ?>%</div>
                <div class="stat-label">Resolution Rate</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row g-4 mb-4">
    <!-- Monthly Trend -->
    <div class="col-lg-8">
        <div class="data-card">
            <div class="data-card-header">
                <h2 class="data-card-title">Monthly Complaint Trend (Last 6 Months)</h2>
            </div>
            <div class="p-3">
                <?php if (empty($trendRows)): ?>
                    <div class="empty-state"><i class="bi bi-bar-chart-line"></i><p>No data yet.</p></div>
                <?php else: ?>
                    <canvas id="chartTrend" height="100"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- By Category -->
    <div class="col-lg-4">
        <div class="data-card">
            <div class="data-card-header"><h2 class="data-card-title">By Category</h2></div>
            <div class="p-3">
                <?php if (empty($catStats)): ?>
                    <div class="empty-state"><i class="bi bi-pie-chart"></i><p>No data yet.</p></div>
                <?php else: ?>
                    <canvas id="chartCategories" height="200"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Department Performance -->
    <div class="col-lg-6">
        <div class="data-card">
            <div class="data-card-header">
                <h2 class="data-card-title">Department Performance</h2>
                <a href="<?= APP_URL ?>/superadmin/departments.php" class="btn btn-sm btn-outline-primary">Manage</a>
            </div>
            <?php if (empty($deptPerf)): ?>
                <div class="empty-state"><i class="bi bi-building"></i><p>No departments.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0 table-hover small">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Active</th>
                                <th class="text-center">Resolved</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deptPerf as $d): ?>
                                <tr>
                                    <td><?= e($d['department_name']) ?></td>
                                    <td class="text-center"><?= (int)$d['total'] ?></td>
                                    <td class="text-center"><span class="badge badge-assigned"><?= (int)$d['active'] ?></span></td>
                                    <td class="text-center"><span class="badge badge-resolved"><?= (int)$d['resolved'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Complaints -->
    <div class="col-lg-6">
        <div class="data-card">
            <div class="data-card-header">
                <h2 class="data-card-title">Recent Complaints</h2>
                <a href="<?= APP_URL ?>/superadmin/complaints.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <?php if (empty($recent)): ?>
                <div class="empty-state"><i class="bi bi-inbox"></i><p>No complaints yet.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0 table-hover small">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $c): ?>
                                <tr>
                                    <td><code><?= e($c['complaint_code']) ?></code></td>
                                    <td><?= e(mb_strimwidth($c['title'], 0, 30, '…')) ?></td>
                                    <td><?= statusBadge($c['status']) ?></td>
                                    <td><?= formatDate($c['created_at'], 'd M') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
