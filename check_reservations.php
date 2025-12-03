<?php
require_once __DIR__ . '/functions.php';

$pdo = getPDO();

echo "Recent 10 reservations:\n";
echo str_repeat("=", 80) . "\n";

$res = $pdo->query('SELECT id, user_id, room_id, start_date, end_date, status FROM reservations ORDER BY id DESC LIMIT 10');
foreach($res as $r) {
    echo "ID: {$r['id']} | User: {$r['user_id']} | Room: {$r['room_id']} | {$r['start_date']} to {$r['end_date']} | Status: {$r['status']}\n";
}
