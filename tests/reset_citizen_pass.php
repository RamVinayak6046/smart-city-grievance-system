<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$hash = password_hash('Admin@123', PASSWORD_BCRYPT);
$pdo->prepare("UPDATE users SET password_hash = :hash WHERE email = 'test@citizen.local'")->execute([':hash' => $hash]);
echo "Updated test@citizen.local password to Admin@123\n";
