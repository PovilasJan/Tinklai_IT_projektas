<?php
/**
 * Rezervacijų operacijų testavimas (sukurti/patvirtinti/atšaukti)
 * Paleisti: php tests/test_reservation_operations.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

echo "Starting reservation operations tests...\n\n";

try {
    $pdo = getPDO();
    echo "  Connected to database '" . DB_NAME . "'.\n\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Get test user
$userStmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$userStmt->execute(['client@hotel.lt']);
$testUser = $userStmt->fetch();

if (!$testUser) {
    die("Test user not found. Please run setup.php first.\n");
}

// Get test room
$roomStmt = $pdo->query('SELECT r.*, h.name as hotel_name FROM rooms r JOIN hotels h ON r.hotel_id = h.id LIMIT 1');
$testRoom = $roomStmt->fetch();

if (!$testRoom) {
    die("Test room not found. Please run setup.php first.\n");
}

echo "Test User: {$testUser['name']} (ID: {$testUser['id']})\n";
echo "Test Room: {$testRoom['hotel_name']} - Room ID: {$testRoom['id']}\n\n";

// Test 1: Create valid reservation
echo "--- Test #1: Create Valid Reservation ---\n";
$startDate = date('Y-m-d', strtotime('+7 days'));
$endDate = date('Y-m-d', strtotime('+10 days'));
$days = 3;
$totalPrice = $testRoom['price'] * $days;
$deposit = $totalPrice * 0.20;

echo "User ID:     {$testUser['id']}\n";
echo "Room ID:     {$testRoom['id']}\n";
echo "Start Date:  {$startDate}\n";
echo "End Date:    {$endDate}\n";
echo "Total Price: {$totalPrice} €\n";
echo "Deposit:     {$deposit} €\n";

try {
    $stmt = $pdo->prepare('INSERT INTO reservations (user_id, room_id, start_date, end_date, status, total_price, deposit_amount, payment_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$testUser['id'], $testRoom['id'], $startDate, $endDate, 'pending', $totalPrice, $deposit, $deposit]);
    $reservationId = $pdo->lastInsertId();
    echo "  Reservation created successfully (ID: {$reservationId}, Status: pending).\n";
    
    // Cleanup
    $pdo->prepare('DELETE FROM reservations WHERE id = ?')->execute([$reservationId]);
    echo "  Reservation cleaned up.\n";
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Confirm reservation
echo "--- Test #2: Confirm Reservation ---\n";
try {
    // Create reservation
    $stmt = $pdo->prepare('INSERT INTO reservations (user_id, room_id, start_date, end_date, status, total_price, deposit_amount, payment_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$testUser['id'], $testRoom['id'], $startDate, $endDate, 'pending', $totalPrice, $deposit, $deposit]);
    $reservationId = $pdo->lastInsertId();
    echo "  Created reservation ID: {$reservationId} (Status: pending)\n";
    
    // Confirm it
    $stmt = $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ?');
    $stmt->execute(['confirmed', $reservationId]);
    echo "  Reservation confirmed successfully.\n";
    
    // Verify
    $check = $pdo->prepare('SELECT status FROM reservations WHERE id = ?');
    $check->execute([$reservationId]);
    $res = $check->fetch();
    echo "  Verified: Status is '{$res['status']}'.\n";
    
    // Cleanup
    $pdo->prepare('DELETE FROM reservations WHERE id = ?')->execute([$reservationId]);
    echo "  Reservation cleaned up.\n";
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Cancel reservation
echo "--- Test #3: Cancel Reservation ---\n";
try {
    // Create reservation
    $stmt = $pdo->prepare('INSERT INTO reservations (user_id, room_id, start_date, end_date, status, total_price, deposit_amount, payment_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$testUser['id'], $testRoom['id'], $startDate, $endDate, 'confirmed', $totalPrice, $deposit, $deposit]);
    $reservationId = $pdo->lastInsertId();
    echo "  Created reservation ID: {$reservationId} (Status: confirmed)\n";
    
    // Cancel it
    $stmt = $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ?');
    $stmt->execute(['cancelled', $reservationId]);
    echo "  Reservation cancelled successfully.\n";
    
    // Verify
    $check = $pdo->prepare('SELECT status FROM reservations WHERE id = ?');
    $check->execute([$reservationId]);
    $res = $check->fetch();
    echo "  Verified: Status is '{$res['status']}'.\n";
    
    // Cleanup
    $pdo->prepare('DELETE FROM reservations WHERE id = ?')->execute([$reservationId]);
    echo "  Reservation cleaned up.\n";
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Invalid date range
echo "--- Test #4: Invalid Date Range ---\n";
$startDate = date('Y-m-d', strtotime('+10 days'));
$endDate = date('Y-m-d', strtotime('+7 days'));

echo "Start Date: {$startDate}\n";
echo "End Date:   {$endDate}\n";

if ($startDate >= $endDate) {
    echo "  Invalid date range: End date must be after start date.\n";
} else {
    echo "  Date range valid.\n";
}
echo "\n";

// Test 5: Overlapping reservations
echo "--- Test #5: Overlapping Reservations ---\n";
$startDate1 = date('Y-m-d', strtotime('+14 days'));
$endDate1 = date('Y-m-d', strtotime('+17 days'));

try {
    // Create first reservation
    $stmt = $pdo->prepare('INSERT INTO reservations (user_id, room_id, start_date, end_date, status, total_price, deposit_amount, payment_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$testUser['id'], $testRoom['id'], $startDate1, $endDate1, 'confirmed', 300, 60, 60]);
    $res1 = $pdo->lastInsertId();
    echo "  Created first reservation (ID: {$res1}): {$startDate1} to {$endDate1}\n";
    
    // Try to create overlapping reservation
    $startDate2 = date('Y-m-d', strtotime('+15 days'));
    $endDate2 = date('Y-m-d', strtotime('+18 days'));
    echo "  Attempting overlapping reservation: {$startDate2} to {$endDate2}\n";
    
    // Check for overlap
    $check = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE room_id = ? AND status IN ('pending','confirmed') AND NOT (end_date <= ? OR start_date >= ?)");
    $check->execute([$testRoom['id'], $startDate2, $endDate2]);
    $overlap = (int)$check->fetchColumn();
    
    if ($overlap > 0) {
        echo "  Room is already reserved for this period. Cannot create overlapping reservation.\n";
    } else {
        echo "  No overlap detected (should not happen).\n";
    }
    
    // Cleanup
    $pdo->prepare('DELETE FROM reservations WHERE id = ?')->execute([$res1]);
    echo "  Reservation cleaned up.\n";
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "All reservation operations tests completed.\n";
