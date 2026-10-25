<?php
$p = new PDO('sqlite:C:/xampp/htdocs/civicfix/database/civicfix.sqlite');
$rows = $p->query("SELECT * FROM complaint_attachments ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);