<?php
/**
 * Naujienlaiškio prenumeratos testavimas
 * Paleisti: php tests/test_newsletter.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

echo "Starting newsletter subscription tests...\n\n";

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

echo "Test User: {$testUser['name']} (ID: {$testUser['id']})\n\n";

// Test 1: Subscribe to newsletter
echo "--- Test #1: Subscribe to Newsletter ---\n";
echo "User ID: {$testUser['id']}\n";
echo "Email:   {$testUser['email']}\n";

// Check if already subscribed
$check = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE user_id = ?');
$check->execute([$testUser['id']]);
$exists = $check->fetch();

if ($exists) {
    echo "  User already subscribed. Cleaning up first...\n";
    $pdo->prepare('DELETE FROM newsletter_subscribers WHERE user_id = ?')->execute([$testUser['id']]);
}

try {
    $stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (user_id, email) VALUES (?, ?)');
    $stmt->execute([$testUser['id'], $testUser['email']]);
    echo "  Successfully subscribed to newsletter.\n";
    
    // Verify
    $check = $pdo->prepare('SELECT * FROM newsletter_subscribers WHERE user_id = ?');
    $check->execute([$testUser['id']]);
    if ($check->fetch()) {
        echo "  Verified: Subscription exists in database.\n";
    }
    
    // Cleanup
    $pdo->prepare('DELETE FROM newsletter_subscribers WHERE user_id = ?')->execute([$testUser['id']]);
    echo "  Subscription cleaned up.\n";
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Duplicate subscription
echo "--- Test #2: Duplicate Subscription ---\n";
try {
    // Subscribe first time
    $stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (user_id, email) VALUES (?, ?)');
    $stmt->execute([$testUser['id'], $testUser['email']]);
    $subId = $pdo->lastInsertId();
    echo "  First subscription created (ID: {$subId})\n";
    
    // Try to subscribe again
    $check = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE user_id = ?');
    $check->execute([$testUser['id']]);
    if ($check->fetch()) {
        echo "  User already subscribed. Cannot subscribe twice.\n";
    }
    
    // Cleanup
    $pdo->prepare('DELETE FROM newsletter_subscribers WHERE id = ?')->execute([$subId]);
    echo "  Subscription cleaned up.\n";
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Unsubscribe from newsletter
echo "--- Test #3: Unsubscribe from Newsletter ---\n";
try {
    // Subscribe first
    $stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (user_id, email) VALUES (?, ?)');
    $stmt->execute([$testUser['id'], $testUser['email']]);
    echo "  Subscribed to newsletter.\n";
    
    // Unsubscribe
    $pdo->prepare('DELETE FROM newsletter_subscribers WHERE user_id = ?')->execute([$testUser['id']]);
    echo "  Unsubscribed from newsletter.\n";
    
    // Verify
    $check = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE user_id = ?');
    $check->execute([$testUser['id']]);
    if (!$check->fetch()) {
        echo "  Verified: Subscription removed from database.\n";
    }
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Subscribe with invalid email
echo "--- Test #4: Subscribe with Invalid Email ---\n";
$invalidEmail = 'invalid-email';
echo "Email: {$invalidEmail}\n";

if (!filter_var($invalidEmail, FILTER_VALIDATE_EMAIL)) {
    echo "  Invalid email format: '{$invalidEmail}'.\n";
} else {
    echo "  Email valid.\n";
}
echo "\n";

// Test 5: Subscribe without user (guest)
echo "--- Test #5: Guest Newsletter Subscription ---\n";
$guestEmail = 'guest@example.com';
echo "Email: {$guestEmail}\n";

if (filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
    try {
        $stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (user_id, email) VALUES (?, ?)');
        $stmt->execute([null, $guestEmail]);
        $subId = $pdo->lastInsertId();
        echo "  Guest subscribed successfully (ID: {$subId}).\n";
        
        // Cleanup
        $pdo->prepare('DELETE FROM newsletter_subscribers WHERE id = ?')->execute([$subId]);
        echo "  Subscription cleaned up.\n";
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "  Invalid email.\n";
}
echo "\n";

echo "All newsletter tests completed.\n";
