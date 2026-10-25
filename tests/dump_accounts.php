<?php
require_once __DIR__ . '/../config/database.php';
$pdo = db();
$staff = $pdo->query("SELECT s.id, s.name, s.email, r.role_name, s.account_status FROM staff s JOIN roles r ON r.id = s.role_id")->fetchAll(PDO::FETCH_ASSOC);
echo "STAFF:\n";
foreach ($staff as $s) {
    echo "  [{$s['role_name']}] {$s['name']} - {$s['email']}\n";
}
$citizens = $pdo->query("SELECT id, name, email, account_status FROM users LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "\nCITIZENS:\n";
foreach ($citizens as $c) {
    echo "  {$c['name']} - {$c['email']}\n";
}
