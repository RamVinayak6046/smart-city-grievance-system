<?php
/**
 * Super Admin — System Settings
 */
$requiredRole = 'super_admin';
require_once __DIR__ . '/../middleware/role_guard.php';
require_once __DIR__ . '/../services/AuditService.php';

$pdo   = db();
$audit = new AuditService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $settings = $_POST['settings'] ?? [];

    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = :val, updated_by = :uid WHERE setting_key = :k");
    foreach ($settings as $k => $val) {
        $stmt->execute([
            ':val' => sanitize($val),
            ':uid' => $_SESSION['user_id'],
            ':k'   => sanitize($k)
        ]);
    }

    $audit->logFromSession('settings_updated', 'system_settings');
    flash('success', 'System settings saved successfully.', 'success');
    redirectTo(APP_URL . '/superadmin/settings.php');
}

$rows = $pdo->query("SELECT * FROM system_settings ORDER BY setting_key ASC")->fetchAll();
$settingsMap = [];
foreach ($rows as $r) {
    $settingsMap[$r['setting_key']] = $r['setting_value'];
}

$pageTitle   = 'System Configuration';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/superadmin/dashboard.php'],
    ['label' => 'Settings'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header"><i class="bi bi-gear me-2"></i>Global System Configuration</div>
            <div class="card-body p-4">
                <form method="post">
                    <?= csrfField() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Application Name</label>
                            <input type="text" name="settings[app_name]" class="form-control" value="<?= e($settingsMap['app_name'] ?? 'CivicFix Pro') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Complaint Code Prefix</label>
                            <input type="text" name="settings[complaint_id_prefix]" class="form-control" value="<?= e($settingsMap['complaint_id_prefix'] ?? 'CFX') ?>" required style="text-transform: uppercase;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Active Workload per Official</label>
                            <input type="number" name="settings[default_max_workload]" class="form-control" value="<?= e($settingsMap['default_max_workload'] ?? '5') ?>" min="1" max="20" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Upload Size (MB)</label>
                            <input type="number" name="settings[max_upload_size_mb]" class="form-control" value="<?= e($settingsMap['max_upload_size_mb'] ?? '5') ?>" min="1" max="50" required>
                        </div>
                        
                        <div class="col-12 mt-4"><h6 class="fw-bold border-bottom pb-2">Resolution SLA Targets (Days)</h6></div>
                        <div class="col-md-3">
                            <label class="form-label small">Critical Priority</label>
                            <input type="number" name="settings[sla_critical_days]" class="form-control" value="<?= e($settingsMap['sla_critical_days'] ?? '1') ?>" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">High Priority</label>
                            <input type="number" name="settings[sla_high_days]" class="form-control" value="<?= e($settingsMap['sla_high_days'] ?? '3') ?>" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Medium Priority</label>
                            <input type="number" name="settings[sla_medium_days]" class="form-control" value="<?= e($settingsMap['sla_medium_days'] ?? '5') ?>" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Low Priority</label>
                            <input type="number" name="settings[sla_low_days]" class="form-control" value="<?= e($settingsMap['sla_low_days'] ?? '7') ?>" min="1" required>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4 fw-semibold">Save Settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
