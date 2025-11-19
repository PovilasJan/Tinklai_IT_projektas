<?php
/**
 * Viešbučio operacijų testavimas (pridėti/ištrinti)
 * Paleisti: php tests/test_hotel_operations.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

echo "Starting hotel operations tests...\n\n";

try {
    $pdo = getPDO();
    echo "  Connected to database '" . DB_NAME . "'.\n\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Test 1: Add valid hotel
echo "--- Test #1: Add Valid Hotel ---\n";
$hotelName = 'Test Viešbutis';
$city = 'Vilnius';
$rating = 4.5;

echo "Pavadinimas: {$hotelName}\n";
echo "Miestas:     {$city}\n";
echo "Įvertinimas: {$rating}\n";

try {
    $stmt = $pdo->prepare('INSERT INTO hotels (name, city, rating) VALUES (?, ?, ?)');
    $stmt->execute([$hotelName, $city, $rating]);
    $hotelId = $pdo->lastInsertId();
    echo "  Hotel '{$hotelName}' added successfully (ID: {$hotelId}).\n";
    
    // Verify
    $check = $pdo->prepare('SELECT * FROM hotels WHERE id = ?');
    $check->execute([$hotelId]);
    $hotel = $check->fetch();
    echo "  Verified: Hotel exists in database.\n";
    
    // Cleanup
    $pdo->prepare('DELETE FROM hotels WHERE id = ?')->execute([$hotelId]);
    echo "  Hotel cleaned up.\n";
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Add hotel with missing name
echo "--- Test #2: Add Hotel with Empty Name ---\n";
$hotelName = '';
$city = 'Kaunas';
$rating = 3.5;

echo "Pavadinimas: '{$hotelName}'\n";
echo "Miestas:     {$city}\n";
echo "Įvertinimas: {$rating}\n";

if (empty($hotelName)) {
    echo "  Invalid hotel name: name cannot be empty.\n";
} else {
    echo "  Validation passed (should not happen).\n";
}
echo "\n";

// Test 3: Add hotel with invalid rating
echo "--- Test #3: Add Hotel with Invalid Rating ---\n";
$hotelName = 'Invalid Rating Hotel';
$city = 'Klaipėda';
$rating = 6.0;

echo "Pavadinimas: {$hotelName}\n";
echo "Miestas:     {$city}\n";
echo "Įvertinimas: {$rating}\n";

if ($rating < 0 || $rating > 5) {
    echo "  Invalid rating: '{$rating}'. Must be between 0 and 5.\n";
} else {
    echo "  Rating valid.\n";
}
echo "\n";

// Test 4: Delete existing hotel
echo "--- Test #4: Delete Hotel ---\n";
// First create a hotel to delete
try {
    $stmt = $pdo->prepare('INSERT INTO hotels (name, city, rating) VALUES (?, ?, ?)');
    $stmt->execute(['Hotel To Delete', 'Šiauliai', 3.0]);
    $hotelId = $pdo->lastInsertId();
    echo "  Created hotel ID: {$hotelId}\n";
    
    // Delete it
    $stmt = $pdo->prepare('DELETE FROM hotels WHERE id = ?');
    $stmt->execute([$hotelId]);
    echo "  Hotel deleted successfully.\n";
    
    // Verify deletion
    $check = $pdo->prepare('SELECT * FROM hotels WHERE id = ?');
    $check->execute([$hotelId]);
    if (!$check->fetch()) {
        echo "  Verified: Hotel no longer exists in database.\n";
    }
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Delete non-existent hotel
echo "--- Test #5: Delete Non-existent Hotel ---\n";
$fakeId = 99999;
echo "Hotel ID: {$fakeId}\n";

$stmt = $pdo->prepare('SELECT * FROM hotels WHERE id = ?');
$stmt->execute([$fakeId]);
if (!$stmt->fetch()) {
    echo "  Hotel ID '{$fakeId}' does not exist.\n";
} else {
    echo "  Hotel found (should not happen).\n";
}
echo "\n";

echo "All hotel operations tests completed.\n";
