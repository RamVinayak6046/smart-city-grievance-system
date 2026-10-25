<?php
require_once __DIR__ . '/../config/app.php';
header('Location: ' . APP_URL . '/official/assignments.php?status=resolved');
exit;