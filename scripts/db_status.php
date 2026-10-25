<?php
require_once __DIR__ . '/../config/database.php';
$p = db();
$tables = ['departments', 'categories', 'wards', 'staff', 'users', 'complaints', 'complaint_assignments', 'complaint_history', 'complaint_attachments', 'notifications', 'audit_logs'];
foreach ($tables as $t) {
    $count = $p->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    echo str_pad($t, 25) . ": $count\n";
}
