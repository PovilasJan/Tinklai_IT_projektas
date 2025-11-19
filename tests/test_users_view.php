<?php
/**
 * Naudotojų peržiūros testavimas
 * Paleisti: php tests/test_users_view.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

echo "Starting users view tests...\n\n";

try {
    $pdo = getPDO();
    echo "  Connected to database '" . DB_NAME . "'.\n\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Test 1: View all users
echo "--- Test #1: View All Users ---\n";
$stmt = $pdo->query('SELECT * FROM users ORDER BY id ASC');
$users = $stmt->fetchAll();

echo "Total users: " . count($users) . "\n";
echo "\n";
echo str_pad("ID", 5) . str_pad("Name", 20) . str_pad("Email", 30) . str_pad("Role", 15) . "Reservations\n";
echo str_repeat("-", 80) . "\n";

foreach ($users as $user) {
    echo str_pad($user['id'], 5) . 
         str_pad($user['name'], 20) . 
         str_pad($user['email'], 30) . 
         str_pad($user['role'], 15) . 
         $user['reservation_count'] . "\n";
}
echo "\n";

// Test 2: View users by role
echo "--- Test #2: View Users by Role (admin) ---\n";
$role = 'admin';
$stmt = $pdo->prepare('SELECT * FROM users WHERE role = ?');
$stmt->execute([$role]);
$admins = $stmt->fetchAll();

echo "Found " . count($admins) . " admin(s):\n";
foreach ($admins as $admin) {
    echo "  - {$admin['name']} ({$admin['email']})\n";
}
echo "\n";

// Test 3: View users by role (employee)
echo "--- Test #3: View Users by Role (employee) ---\n";
$role = 'employee';
$stmt = $pdo->prepare('SELECT * FROM users WHERE role = ?');
$stmt->execute([$role]);
$employees = $stmt->fetchAll();

echo "Found " . count($employees) . " employee(s):\n";
foreach ($employees as $emp) {
    echo "  - {$emp['name']} ({$emp['email']})\n";
}
echo "\n";

// Test 4: View users by role (client)
echo "--- Test #4: View Users by Role (client) ---\n";
$role = 'client';
$stmt = $pdo->prepare('SELECT * FROM users WHERE role = ?');
$stmt->execute([$role]);
$clients = $stmt->fetchAll();

echo "Found " . count($clients) . " client(s):\n";
foreach ($clients as $client) {
    echo "  - {$client['name']} ({$client['email']}) - {$client['reservation_count']} reservations, {$client['total_spent']} € spent\n";
}
echo "\n";

// Test 5: Search user by email
echo "--- Test #5: Search User by Email ---\n";
$searchEmail = 'admin@hotel.lt';
echo "Searching for: {$searchEmail}\n";

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$searchEmail]);
$user = $stmt->fetch();

if ($user) {
    echo "  Found user:\n";
    echo "    Name:  {$user['name']}\n";
    echo "    Email: {$user['email']}\n";
    echo "    Role:  {$user['role']}\n";
    echo "    Reservations: {$user['reservation_count']}\n";
    echo "    Total spent:  {$user['total_spent']} €\n";
} else {
    echo "  User not found.\n";
}
echo "\n";

// Test 6: View newsletter subscribers
echo "--- Test #6: View Newsletter Subscribers ---\n";
$stmt = $pdo->query('SELECT ns.*, u.name FROM newsletter_subscribers ns LEFT JOIN users u ON ns.user_id = u.id');
$subscribers = $stmt->fetchAll();

echo "Total newsletter subscribers: " . count($subscribers) . "\n";
foreach ($subscribers as $sub) {
    $userName = $sub['name'] ?? 'Guest';
    echo "  - {$sub['email']} ({$userName})\n";
}
echo "\n";

echo "All users view tests completed.\n";
