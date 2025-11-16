<?php
require_once __DIR__ . '/db.php';
$pdo = getPDO();
$q = $pdo->prepare("SELECT id,email,name,role,reservation_count,total_spent FROM users WHERE email IN (?,?)");
$q->execute(['admin@admin.admin','employee@employee.employee']);
$rows = $q->fetchAll(PDO::FETCH_ASSOC);
if (!$rows) {
    echo "No matching users found\n";
} else {
    foreach ($rows as $r) {
        echo "id={$r['id']} email={$r['email']} name={$r['name']} role={$r['role']} reservations={$r['reservation_count']} spent={$r['total_spent']}\n";
    }
}
