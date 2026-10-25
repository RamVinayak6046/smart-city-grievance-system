<?php
$p = new PDO('sqlite:C:/xampp/htdocs/civicfix/database/civicfix.sqlite');
echo "Current journal mode: " . $p->query("PRAGMA journal_mode")->fetchColumn() . "\n";
$p->exec("PRAGMA journal_mode = WAL;");
echo "New journal mode: " . $p->query("PRAGMA journal_mode")->fetchColumn() . "\n";