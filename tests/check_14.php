<?php
$p = new PDO('sqlite:C:/xampp/htdocs/civicfix/database/civicfix.sqlite');
$c = $p->query("SELECT * FROM complaints WHERE id = 14")->fetch(PDO::FETCH_ASSOC);
echo "Complaint 14 code: " . $c['complaint_code'] . "\n";
$att = $p->query("SELECT COUNT(*) FROM complaint_attachments WHERE complaint_id = 14")->fetchColumn();
echo "Complaint 14 attachments: " . $att . "\n";