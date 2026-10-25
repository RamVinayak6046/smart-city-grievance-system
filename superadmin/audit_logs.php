<?php
/**
 * Super Admin — Audit Logs
 */
$requiredRole = 'super_admin';
require_once __DIR__ . '/../middleware/role_guard.php';

$pdo = db();

$stmt = $pdo->query(
    "SELECT a.*, 
            CASE a.actor_role
                WHEN 'citizen' THEN (SELECT name FROM users WHERE id = a.actor_id)
                ELSE (SELECT name FROM staff WHERE id = a.actor_id)
            END AS actor_name
     FROM audit_logs a
     ORDER BY a.created_at DESC LIMIT 100"
);
$logs = $stmt->fetchAll();

$pageTitle   = 'System Audit Logs';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/superadmin/dashboard.php'],
    ['label' => 'Audit Logs'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="data-card">
    <div class="data-card-header"><h2 class="data-card-title">Recent System Activity (Last 100 Events)</h2></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Actor</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No audit logs recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><span class="text-muted"><?= formatDate($l['created_at'], 'd M Y, H:i:s') ?></span></td>
                            <td class="fw-semibold"><?= e($l['actor_name'] ?? 'System') ?></td>
                            <td><span class="badge bg-secondary"><?= e($l['actor_role']) ?></span></td>
                            <td><code><?= e($l['action']) ?></code></td>
                            <td><?= e($l['entity_type'] ? "{$l['entity_type']} #{$l['entity_id']}" : '—') ?></td>
                            <td><small class="text-muted"><?= e($l['details'] ?? '—') ?></small></td>
                            <td><small><?= e($l['ip_address'] ?? '—') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
