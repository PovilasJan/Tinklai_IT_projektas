<?php
require_once __DIR__ . '/db.php';

$pdo = getPDO();
$users = [
    ['name'=>'Administratorius','email'=>'admin@admin.admin','password'=>'admin','role'=>'admin'],
    ['name'=>'Darbuotojas','email'=>'employee@employee.employee','password'=>'employee','role'=>'employee']
];

foreach ($users as $u) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$u['email']]);
    $existing = $stmt->fetchColumn();
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    if ($existing) {
        $upd = $pdo->prepare('UPDATE users SET name=?, password=?, role=? WHERE id = ?');
        $upd->execute([$u['name'],$hash,$u['role'],$existing]);
        echo "Updated user: {$u['email']}\n";
    } else {
        $ins = $pdo->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
        $ins->execute([$u['name'],$u['email'],$hash,$u['role']]);
        echo "Created user: {$u['email']}\n";
    }
}

echo "Done.\n";
