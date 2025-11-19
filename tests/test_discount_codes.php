<?php
/**
 * Nuolaidų kodų kūrimo testavimas
 * Paleisti: php tests/test_discount_codes.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

echo "Starting discount code creation tests...\n\n";

try {
    $pdo = getPDO();
    echo "  Connected to database '" . DB_NAME . "'.\n\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Get test employee
$userStmt = $pdo->prepare('SELECT * FROM users WHERE role = ? LIMIT 1');
$userStmt->execute(['employee']);
$testEmployee = $userStmt->fetch();

if (!$testEmployee) {
    die("Test employee not found. Please run setup.php first.\n");
}

echo "Test Employee: {$testEmployee['name']} (ID: {$testEmployee['id']})\n\n";

// Test 1: Create valid discount code
echo "--- Test #1: Create Valid Discount Code ---\n";
$code = 'TESTAS2025';
$discount = 15;
$expiresAt = date('Y-m-d', strtotime('+30 days'));
$usageLimit = 100;

echo "Kodas:         {$code}\n";
echo "Nuolaida:      {$discount}%\n";
echo "Galioja iki:   {$expiresAt}\n";
echo "Naudojimų max: {$usageLimit}\n";

try {
    $stmt = $pdo->prepare('INSERT INTO discount_codes (code, discount_percent, created_by, expires_at, usage_limit) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$code, $discount, $testEmployee['id'], $expiresAt, $usageLimit]);
    $codeId = $pdo->lastInsertId();
    echo "  Discount code '{$code}' created successfully (ID: {$codeId}).\n";
    
    // Cleanup
    $pdo->prepare('DELETE FROM discount_codes WHERE id = ?')->execute([$codeId]);
    echo "  Discount code cleaned up.\n";
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Invalid discount percentage
echo "--- Test #2: Invalid Discount Percentage ---\n";
$code = 'INVALID150';
$discount = 150;

echo "Kodas:    {$code}\n";
echo "Nuolaida: {$discount}%\n";

if ($discount <= 0 || $discount > 100) {
    echo "  Invalid discount: '{$discount}%'. Must be between 1-100%.\n";
} else {
    echo "  Discount valid.\n";
}
echo "\n";

// Test 3: Duplicate code
echo "--- Test #3: Duplicate Discount Code ---\n";
try {
    // Create first code
    $code = 'DUPLICATE2025';
    $stmt = $pdo->prepare('INSERT INTO discount_codes (code, discount_percent, created_by) VALUES (?, ?, ?)');
    $stmt->execute([$code, 10, $testEmployee['id']]);
    $codeId = $pdo->lastInsertId();
    echo "  Created code '{$code}' (ID: {$codeId})\n";
    
    // Try to create duplicate
    echo "  Attempting to create duplicate code '{$code}'...\n";
    try {
        $stmt->execute([$code, 20, $testEmployee['id']]);
        echo "  Duplicate created (should not happen).\n";
    } catch (Exception $e) {
        echo "  Error: Code already exists (expected behavior).\n";
    }
    
    // Cleanup
    $pdo->prepare('DELETE FROM discount_codes WHERE id = ?')->execute([$codeId]);
    echo "  Discount code cleaned up.\n";
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Empty code
echo "--- Test #4: Empty Discount Code ---\n";
$code = '';
$discount = 20;

echo "Kodas:    '{$code}'\n";
echo "Nuolaida: {$discount}%\n";

if (empty($code)) {
    echo "  Invalid code: Code cannot be empty.\n";
} else {
    echo "  Code valid.\n";
}
echo "\n";

// Test 5: Activate/Deactivate code
echo "--- Test #5: Activate/Deactivate Code ---\n";
try {
    // Create code
    $code = 'ACTIVATE2025';
    $stmt = $pdo->prepare('INSERT INTO discount_codes (code, discount_percent, created_by, is_active) VALUES (?, ?, ?, ?)');
    $stmt->execute([$code, 25, $testEmployee['id'], 1]);
    $codeId = $pdo->lastInsertId();
    echo "  Created active code '{$code}' (ID: {$codeId})\n";
    
    // Deactivate
    $pdo->prepare('UPDATE discount_codes SET is_active = 0 WHERE id = ?')->execute([$codeId]);
    echo "  Code deactivated.\n";
    
    // Verify
    $check = $pdo->prepare('SELECT is_active FROM discount_codes WHERE id = ?');
    $check->execute([$codeId]);
    $active = $check->fetchColumn();
    echo "  Verified: is_active = {$active}.\n";
    
    // Reactivate
    $pdo->prepare('UPDATE discount_codes SET is_active = 1 WHERE id = ?')->execute([$codeId]);
    echo "  Code reactivated.\n";
    
    // Cleanup
    $pdo->prepare('DELETE FROM discount_codes WHERE id = ?')->execute([$codeId]);
    echo "  Discount code cleaned up.\n";
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "All discount code tests completed.\n";
