<?php
/**
 * Registracijos testavimas
 * Paleisti: php tests/test_register.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

echo "Starting registration tests...\n\n";

try {
    $pdo = getPDO();
    echo "  Connected to database '" . DB_NAME . "'.\n\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

$tests = [
    [
        'name' => 'Valid Registration',
        'vardas' => 'TestVartotojas',
        'email' => 'test@example.com',
        'password' => 'secret123',
        'expected' => 'success'
    ],
    [
        'name' => 'Invalid Email Format',
        'vardas' => 'ValidUser',
        'email' => 'invalid@',
        'password' => 'validpass123',
        'expected' => 'invalid_email'
    ],
    [
        'name' => 'Empty Name',
        'vardas' => '',
        'email' => 'empty@example.com',
        'password' => 'pass123',
        'expected' => 'empty_name'
    ],
    [
        'name' => 'Short Password',
        'vardas' => 'ShortPass',
        'email' => 'short@example.com',
        'password' => '123',
        'expected' => 'short_password'
    ],
    [
        'name' => 'Duplicate Email',
        'vardas' => 'DuplicateUser',
        'email' => 'admin@hotel.lt',
        'password' => 'validpass123',
        'expected' => 'duplicate_email'
    ],
    [
        'name' => 'Missing Password',
        'vardas' => 'NoPassword',
        'email' => 'nopass@example.com',
        'password' => '',
        'expected' => 'empty_password'
    ]
];

$testNum = 1;
foreach ($tests as $test) {
    echo "--- Test #{$testNum} ---\n";
    echo "Vardas:   {$test['vardas']}\n";
    echo "Email:    {$test['email']}\n";
    echo "Password: {$test['password']}\n";
    
    $errors = [];
    
    // Validation logic (same as register.php)
    if ($test['vardas'] === '') {
        $errors[] = "Invalid vardas: '". $test['vardas'] ."'. Name is required.";
        echo "  Invalid vardas: '". $test['vardas'] ."'. Name is required.\n";
    }
    
    if (!filter_var($test['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email: '". $test['email'] ."'.";
        echo "  Invalid email: '". $test['email'] ."'.\n";
    }
    
    if (strlen($test['password']) < 6) {
        $errors[] = "Password too short: '". $test['password'] ."'. Minimum 6 characters.";
        echo "  Password too short: '". $test['password'] ."'. Minimum 6 characters.\n";
    }
    
    if (empty($errors)) {
        // Check for duplicate email
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$test['email']]);
        if ($stmt->fetch()) {
            echo "  Email already in use: '{$test['email']}'.\n";
        } else {
            // Insert user
            try {
                $hash = password_hash($test['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
                $stmt->execute([$test['vardas'], $test['email'], $hash, 'client']);
                $userId = $pdo->lastInsertId();
                echo "  User '{$test['vardas']}' registered successfully (ID: {$userId}).\n";
                
                // Cleanup
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
                echo "  User cleaned up.\n";
            } catch (Exception $e) {
                echo "  Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n";
    $testNum++;
}

echo "All registration tests completed.\n";
