<?php
/**
 * Kambarių paieškos testavimas
 * Paleisti: php tests/test_room_search.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

echo "Starting room search tests...\n\n";

try {
    $pdo = getPDO();
    echo "  Connected to database '" . DB_NAME . "'.\n\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Test 1: Search by city
echo "--- Test #1: Search by City ---\n";
$city = 'Vilnius';
echo "Miestas: {$city}\n";

$sql = 'SELECT r.*, h.name as hotel_name, h.city FROM rooms r JOIN hotels h ON r.hotel_id = h.id WHERE h.city LIKE ?';
$stmt = $pdo->prepare($sql);
$stmt->execute(["%{$city}%"]);
$rooms = $stmt->fetchAll();

echo "  Found " . count($rooms) . " room(s) in '{$city}'.\n";
foreach ($rooms as $room) {
    echo "    - {$room['hotel_name']}, {$room['places']} vietos, {$room['price']} €/naktį\n";
}
echo "\n";

// Test 2: Search by number of places
echo "--- Test #2: Search by Number of Places ---\n";
$places = 2;
echo "Vietų sk.: {$places}\n";

$sql = 'SELECT r.*, h.name as hotel_name FROM rooms r JOIN hotels h ON r.hotel_id = h.id WHERE r.places >= ?';
$stmt = $pdo->prepare($sql);
$stmt->execute([$places]);
$rooms = $stmt->fetchAll();

echo "  Found " . count($rooms) . " room(s) with {$places}+ places.\n";
foreach ($rooms as $room) {
    echo "    - {$room['hotel_name']}, {$room['places']} vietos\n";
}
echo "\n";

// Test 3: Search by maximum price
echo "--- Test #3: Search by Maximum Price ---\n";
$maxPrice = 100;
echo "Maksimali kaina: {$maxPrice} €\n";

$sql = 'SELECT r.*, h.name as hotel_name FROM rooms r JOIN hotels h ON r.hotel_id = h.id WHERE r.price <= ?';
$stmt = $pdo->prepare($sql);
$stmt->execute([$maxPrice]);
$rooms = $stmt->fetchAll();

echo "  Found " . count($rooms) . " room(s) under {$maxPrice} €.\n";
foreach ($rooms as $room) {
    echo "    - {$room['hotel_name']}, {$room['price']} €/naktį\n";
}
echo "\n";

// Test 4: Search by hotel rating
echo "--- Test #4: Search by Hotel Rating ---\n";
$minRating = 4.0;
echo "Minimalus įvertinimas: {$minRating}\n";

$sql = 'SELECT r.*, h.name as hotel_name, h.rating FROM rooms r JOIN hotels h ON r.hotel_id = h.id WHERE h.rating >= ?';
$stmt = $pdo->prepare($sql);
$stmt->execute([$minRating]);
$rooms = $stmt->fetchAll();

echo "  Found " . count($rooms) . " room(s) in hotels rated {$minRating}+.\n";
foreach ($rooms as $room) {
    echo "    - {$room['hotel_name']} ({$room['rating']} ★), {$room['price']} €/naktį\n";
}
echo "\n";

// Test 5: Search with date availability
echo "--- Test #5: Search with Date Availability ---\n";
$startDate = date('Y-m-d', strtotime('+7 days'));
$endDate = date('Y-m-d', strtotime('+10 days'));
echo "Data nuo: {$startDate}\n";
echo "Data iki: {$endDate}\n";

$sql = "SELECT r.*, h.name as hotel_name FROM rooms r 
        JOIN hotels h ON r.hotel_id = h.id 
        WHERE r.id NOT IN (
            SELECT room_id FROM reservations 
            WHERE NOT (end_date <= ? OR start_date >= ?) 
            AND status <> 'cancelled'
        )";
$stmt = $pdo->prepare($sql);
$stmt->execute([$startDate, $endDate]);
$rooms = $stmt->fetchAll();

echo "  Found " . count($rooms) . " available room(s) for this period.\n";
foreach ($rooms as $room) {
    echo "    - {$room['hotel_name']}, {$room['places']} vietos, {$room['price']} €/naktį\n";
}
echo "\n";

// Test 6: Combined search
echo "--- Test #6: Combined Search (City + Places + Price) ---\n";
$city = 'Kaunas';
$places = 2;
$maxPrice = 150;
echo "Miestas: {$city}\n";
echo "Vietų sk.: {$places}+\n";
echo "Maksimali kaina: {$maxPrice} €\n";

$sql = 'SELECT r.*, h.name as hotel_name, h.city FROM rooms r 
        JOIN hotels h ON r.hotel_id = h.id 
        WHERE h.city LIKE ? AND r.places >= ? AND r.price <= ?';
$stmt = $pdo->prepare($sql);
$stmt->execute(["%{$city}%", $places, $maxPrice]);
$rooms = $stmt->fetchAll();

echo "  Found " . count($rooms) . " room(s) matching all criteria.\n";
foreach ($rooms as $room) {
    echo "    - {$room['hotel_name']}, {$room['places']} vietos, {$room['price']} €/naktį\n";
}
echo "\n";

echo "All room search tests completed.\n";
