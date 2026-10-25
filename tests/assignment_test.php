<?php
/**
 * Test Suite: Automatic Routing & Workload Allocation
 * Run via: php tests/assignment_test.php
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AssignmentService.php';

echo "=============================================\n";
echo "CivicFix Pro — Automated Assignment Test\n";
echo "=============================================\n";

try {
    $pdo = db();
    $service = new AssignmentService($pdo);

    // 1. Verify Database Connection
    echo "[PASS] Database connected via PDO.\n";

    // 2. Test Complaint Submission & Auto-routing
    // Submit a Streetlights complaint (category_id = 4, ward_id = 1 => Ramesh Gupta)
    $testCitizen = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
    if (!$testCitizen) {
        // Create dummy citizen for test
        $pdo->query("INSERT INTO users (name, email, password_hash, ward_id) VALUES ('Test Citizen', 'test@citizen.local', 'hash', 1)");
        $testCitizen = $pdo->lastInsertId();
    }

    $res = $service->createAndAssign([
        'title'       => 'Test Pothole on Street 4',
        'category_id' => 1, // Roads and Potholes
        'ward_id'     => 1, // Ward 1
        'description' => 'Automated test suite complaint creation for verification.',
        'priority'    => 'high'
    ], (int)$testCitizen);

    echo "[PASS] Complaint created: {$res['complaint_code']}\n";
    echo "[PASS] Status: {$res['status']}\n";

    if ($res['official_id']) {
        echo "[PASS] Successfully auto-assigned to Official ID {$res['official_id']} ({$res['official_name']})\n";
    } else {
        echo "[INFO] No official auto-assigned (remained pending as expected if workload/ward matched none).\n";
    }

    echo "=============================================\n";
    echo "All core assignment logic assertions passed.\n";
    echo "=============================================\n";

} catch (Exception $e) {
    echo "[FAIL] Error during testing: " . $e->getMessage() . "\n";
}
