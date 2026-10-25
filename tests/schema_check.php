<?php
$p = new PDO('sqlite:C:/xampp/htdocs/civicfix/database/civicfix.sqlite');
foreach ($p->query("PRAGMA table_info(departments)")->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "{$col['name']} ({$col['type']}) notnull={$col['notnull']} dflt={$col['dflt_value']}\n";
}
echo "\nCATEGORIES:\n";
foreach ($p->query("PRAGMA table_info(categories)")->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "{$col['name']} ({$col['type']}) notnull={$col['notnull']} dflt={$col['dflt_value']}\n";
}
echo "\nWARDS:\n";
foreach ($p->query("PRAGMA table_info(wards)")->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "{$col['name']} ({$col['type']}) notnull={$col['notnull']} dflt={$col['dflt_value']}\n";
}