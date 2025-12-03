<?php
require_once __DIR__ . '/functions.php';

$pdo = getPDO();

echo "=== RESERVATION DEBUG TEST ===\n\n";

// Check if reservations table exists and has data
echo "1. Checking reservations table:\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reservations");
    $count = $stmt->fetchColumn();
    echo "   Total reservations: $count\n";
    
    $recent = $pdo->query("SELECT id, user_id, room_id, start_date, end_date, status, created_at FROM reservations ORDER BY id DESC LIMIT 5");
    echo "\n   Recent 5 reservations:\n";
    foreach ($recent as $r) {
        echo "   - ID: {$r['id']}, User: {$r['user_id']}, Room: {$r['room_id']}, Dates: {$r['start_date']} to {$r['end_date']}, Status: {$r['status']}\n";
    }
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n2. Checking table structure:\n";
try {
    $cols = $pdo->query("DESCRIBE reservations");
    foreach ($cols as $col) {
        echo "   - {$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n3. Testing insert:\n";
try {
    // Get a test user and room
    $user = $pdo->query("SELECT id FROM users WHERE role = 'client' LIMIT 1")->fetch();
    $room = $pdo->query("SELECT id, price FROM rooms LIMIT 1")->fetch();
    
    if ($user && $room) {
        echo "   Using User ID: {$user['id']}, Room ID: {$room['id']}\n";
        
        $start_date = date('Y-m-d', strtotime('+7 days'));
        $end_date = date('Y-m-d', strtotime('+10 days'));
        $days = 3;
        $total = $room['price'] * $days;
        $deposit = $total * 0.3;
        
        echo "   Attempting to insert reservation: $start_date to $end_date\n";
        
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO reservations (user_id, room_id, start_date, end_date, status, total_price, deposit_amount, payment_amount) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)");
        $stmt->execute([$user['id'], $room['id'], $start_date, $end_date, $total, $deposit, $deposit]);
        
        $newId = $pdo->lastInsertId();
        echo "   SUCCESS! New reservation ID: $newId\n";
        
        $pdo->rollBack(); // Don't actually save the test reservation
        echo "   (Rolled back for testing purposes)\n";
    } else {
        echo "   ERROR: Could not find test user or room\n";
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== END TEST ===\n";
