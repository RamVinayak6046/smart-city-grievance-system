<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
echo "Connected to DB\n";
echo "Tables:\n";
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo "  - $t: " . $pdo->query("SELECT count(*) FROM $t")->fetchColumn() . " rows\n";
}

echo "\nSample Staff Accounts:\n";
$staff = $pdo->query("SELECT s.id, s.name, s.email, r.role_name, s.department_id, s.account_status, s.availability_status FROM staff s JOIN roles r ON r.id = s.role_id")->fetchAll();
foreach ($staff as $s) {
    echo "  [ID: {$s['id']}] {$s['name']} | {$s['email']} | Role: {$s['role_name']} | Dept: " . ($s['department_id'] ?? 'None') . " | Status: {$s['account_status']}\n";
}

echo "\nSample Users (Citizens):\n";
$users = $pdo->query("SELECT id, name, email, account_status, ward_id FROM users")->fetchAll();
foreach ($users as $u) {
    echo "  [ID: {$u['id']}] {$u['name']} | {$u['email']} | Ward: {$u['ward_id']} | Status: {$u['account_status']}\n";
}

echo "\nComplaints:\n";
$complaints = $pdo->query("SELECT id, complaint_code, title, status, priority, department_id, ward_id FROM complaints")->fetchAll();
foreach ($complaints as $c) {
    echo "  [ID: {$c['id']}] {$c['complaint_code']} | {$c['title']} | Status: {$c['status']} | Dept: {$c['department_id']} | Ward: {$c['ward_id']}\n";
}
