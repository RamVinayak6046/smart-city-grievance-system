<?php
/**
 * CivicFix Pro — Professional System Reset & Data Cleansing
 * Removes all automated test data, resets complaint counters to 0,
 * and ensures standard professional municipal configurations.
 */

require_once __DIR__ . '/../config/database.php';

$pdo = db();
$pdo->beginTransaction();

try {
    echo "1. Purging test complaints, assignments, attachments, history, notifications & audit logs...\n";
    $pdo->exec("DELETE FROM complaints;");
    $pdo->exec("DELETE FROM complaint_assignments;");
    $pdo->exec("DELETE FROM complaint_history;");
    $pdo->exec("DELETE FROM complaint_attachments;");
    $pdo->exec("DELETE FROM notifications;");
    $pdo->exec("DELETE FROM audit_logs;");

    // Reset SQLite sequences
    $tablesToReset = ['complaints', 'complaint_assignments', 'complaint_history', 'complaint_attachments', 'notifications', 'audit_logs'];
    foreach ($tablesToReset as $tbl) {
        $pdo->exec("DELETE FROM sqlite_sequence WHERE name = '$tbl';");
    }

    echo "2. Cleaning uploaded test files in uploads directory...\n";
    $uploadDirs = [
        __DIR__ . '/../uploads/complaints',
        __DIR__ . '/../uploads/completion'
    ];
    foreach ($uploadDirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== '.gitkeep') {
                    @unlink($file);
                }
            }
        }
    }

    echo "3. Removing QA test departments...\n";
    $pdo->exec("DELETE FROM departments WHERE department_name LIKE 'QA %' OR id > 8;");
    $pdo->exec("UPDATE sqlite_sequence SET seq = 8 WHERE name = 'departments';");

    echo "4. Removing QA test categories...\n";
    $pdo->exec("DELETE FROM categories WHERE category_name LIKE 'QA %' OR id > 8;");
    $pdo->exec("UPDATE sqlite_sequence SET seq = 8 WHERE name = 'categories';");

    echo "5. Removing QA test wards...\n";
    $pdo->exec("DELETE FROM wards WHERE ward_name LIKE 'Ward W%' OR id > 12;");
    $pdo->exec("UPDATE sqlite_sequence SET seq = 12 WHERE name = 'wards';");

    echo "6. Removing QA test staff & resetting workloads...\n";
    $pdo->exec("DELETE FROM staff WHERE email LIKE 'qa_%' OR id > 11;");
    $pdo->exec("UPDATE staff SET last_assigned_at = NULL;");
    $pdo->exec("DELETE FROM staff_wards WHERE staff_id > 11 OR ward_id > 12;");
    $pdo->exec("UPDATE sqlite_sequence SET seq = 11 WHERE name = 'staff';");

    echo "7. Cleaning citizen users...\n";
    // Delete all QA citizen entries
    $pdo->exec("DELETE FROM users WHERE email LIKE 'qa_%' OR email LIKE 'debug_%';");

    // Standard demo citizen password hash: Admin@123
    $demoCitizenHash = password_hash('Admin@123', PASSWORD_BCRYPT);

    // Ensure citizen@civicfix.local exists as a pristine demo account
    $chkCit = $pdo->prepare("SELECT id FROM users WHERE email = 'citizen@civicfix.local' OR email = 'test@citizen.local'");
    $chkCit->execute();
    $existingCitId = $chkCit->fetchColumn();

    if ($existingCitId) {
        $pdo->prepare("UPDATE users SET name = 'Rajesh Sharma', email = 'citizen@civicfix.local', phone = '9876543210', password_hash = :hash, ward_id = 1, address = '102 Royal Residency, Rajendra Nagar, Ward 1', account_status = 'active' WHERE id = :id")
            ->execute([':hash' => $demoCitizenHash, ':id' => $existingCitId]);
    } else {
        $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, ward_id, address, account_status) VALUES ('Rajesh Sharma', 'citizen@civicfix.local', '9876543210', :hash, 1, '102 Royal Residency, Rajendra Nagar, Ward 1', 'active')")
            ->execute([':hash' => $demoCitizenHash]);
    }

    // Ensure Vinayak Chundru account is active
    $vinayakHash = password_hash('Admin@123', PASSWORD_BCRYPT);
    $chkVin = $pdo->prepare("SELECT id FROM users WHERE email = 'chundruvinayak3@gmail.com'");
    $chkVin->execute();
    $vinId = $chkVin->fetchColumn();
    if ($vinId) {
        $pdo->prepare("UPDATE users SET name = 'Vinayak Chundru', account_status = 'active', password_hash = :h WHERE id = :id")
            ->execute([':h' => $vinayakHash, ':id' => $vinId]);
    } else {
        $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, ward_id, address, account_status) VALUES ('Vinayak Chundru', 'chundruvinayak3@gmail.com', '9876543210', :h, 1, 'Ward 1 - Rajendra Nagar', 'active')")
            ->execute([':h' => $vinayakHash]);
    }

    echo "8. Logging professional system initialization in audit logs...\n";
    $pdo->exec("INSERT INTO audit_logs (actor_id, actor_role, action, entity_type, entity_id, details, ip_address)
        VALUES (1, 'super_admin', 'system_initialized', 'system', 1, '{\"status\": \"pristine\", \"message\": \"Municipal grievance system initialized for production use.\"}', '127.0.0.1');");

    $pdo->commit();
    echo "\n=== SYSTEM RESET & CLEANSING COMPLETE: ZERO COMPLAINTS, 100% PRISTINE ===\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "[ERROR] Cleansing failed: " . $e->getMessage() . "\n";
    exit(1);
}
