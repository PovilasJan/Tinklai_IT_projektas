<?php
/**
 * Prisijungimo testavimas
 * Paleisti: php tests/test_login.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

echo "Starting login tests...\n\n";

try {
    $pdo = getPDO();
    echo "  Connected to database '" . DB_NAME . "'.\n\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

$tests = [
    [
        'name' => 'Valid User Login',
        'email' => 'jankauskpov3@gmail.com',
        'password' => 'povilas',
        'expected' => 'success'
    ],
    [
        'name' => 'Wrong Password',
        'email' => 'jankauskpov3@gmail.com',
        'password' => 'wrongpass',
        'expected' => 'wrong_password'
    ],
    [
        'name' => 'Non-existent User',
        'email' => 'nonexistent@hotel.lt',
        'password' => 'anything',
        'expected' => 'no_user'
    ],
    [
        'name' => 'Empty Email',
        'email' => '',
        'password' => '123456',
        'expected' => 'empty_email'
    ],
    [
        'name' => 'Valid Test User',
        'email' => 'jankauskpov3@gmail.com',
        'password' => 'povilas',
        'expected' => 'success'
    ],
    [
        'name' => 'Invalid Email Format',
        'email' => 'invalid-email',
        'password' => 'pass123',
        'expected' => 'invalid_email'
    ]
];

$testNum = 1;
foreach ($tests as $test) {
    echo "--- Test #{$testNum} ---\n";
    echo "Email:    {$test['email']}\n";
    echo "Password: {$test['password']}\n";
    
    // Validation
    if (empty($test['email']) || empty($test['password'])) {
        echo "  Email or password is empty.\n\n";
        $testNum++;
        continue;
    }
    
    if (!filter_var($test['email'], FILTER_VALIDATE_EMAIL)) {
        echo "  Invalid email format: '{$test['email']}'.\n\n";
        $testNum++;
        continue;
    }
    
    // Check credentials
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$test['email']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "  No user found with email '{$test['email']}'.\n\n";
    } elseif (!password_verify($test['password'], $user['password'])) {
        echo "  Incorrect password for '{$test['email']}'.\n\n";
    } else {
        echo "  Login successful for '{$user['name']}' ({$user['role']}).\n\n";
    }
    
    $testNum++;
}

echo "All login tests completed.\n";
