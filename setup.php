<?php
// Setup script to populate demo data and demo users with password_hash()
require_once __DIR__ . '/db.php';

$pdo = getPDO();

try{
    // Try to run basic inserts (hotels, rooms, users)
    $pdo->beginTransaction();

    // Sample hotels
    $pdo->exec("INSERT INTO hotels (name, city, rating) VALUES
        ('Grand Vilnius', 'Vilnius', 4.5),
        ('Kauno Viešbutis', 'Kaunas', 4.0),
        ('Palanga Resort', 'Palanga', 4.2)");

    // Sample rooms
    $stmtH = $pdo->query('SELECT id FROM hotels');
    $hotels = $stmtH->fetchAll(PDO::FETCH_COLUMN);
    $photos = ['https://picsum.photos/seed/1/400/300','https://picsum.photos/seed/2/400/300','https://picsum.photos/seed/3/400/300'];
    $roomInsert = $pdo->prepare('INSERT INTO rooms (hotel_id, places, price, photo, status) VALUES (?,?,?,?,?)');
    foreach ($hotels as $i => $hid){
        $roomInsert->execute([$hid, 2 + ($i%3), 50 + $i*30, $photos[$i%3], 'available']);
        $roomInsert->execute([$hid, 4, 120 + $i*20, $photos[($i+1)%3], 'available']);
    }

    // Demo users
    $userInsert = $pdo->prepare('INSERT INTO users (name,email,password,role,reservation_count,total_spent) VALUES (?,?,?,?,?,?)');
    $userInsert->execute(['Administratorius','admin@hotel.lt', password_hash('admin123', PASSWORD_DEFAULT),'admin', 0, 0.00]);
    $userInsert->execute(['Darbuotojas','employee@hotel.lt', password_hash('darbuotojas123', PASSWORD_DEFAULT),'employee', 0, 0.00]);
    $userInsert->execute(['Klientas','client@hotel.lt', password_hash('klientas123', PASSWORD_DEFAULT),'client', 5, 800.00]);

    // Sample reservation for client
    $clientId = $pdo->lastInsertId();
    // Find a room
    $room = $pdo->query('SELECT id FROM rooms LIMIT 1')->fetchColumn();
    if ($room) {
        $res = $pdo->prepare('INSERT INTO reservations (user_id, room_id, start_date, end_date, status, payment_amount) VALUES (?,?,?,?,?,?)');
        $res->execute([$clientId, $room, date('Y-m-d',strtotime('+7 days')), date('Y-m-d',strtotime('+9 days')), 'confirmed', 200.00]);
    }

    // Sample newsletter subscriber
    $pdo->exec("INSERT INTO newsletter_subscribers (user_id,email) VALUES (". (int)$clientId .", 'client@hotel.lt')");

    $pdo->commit();
    $msg = 'Pavyzdiniai duomenys sukurti sėkmingai. Galite prisijungti su demo paskyromis.';
} catch (Exception $e){
    $pdo->rollBack();
    $msg = 'Klaida: ' . $e->getMessage();
}
?>
<?php include 'header.php'; ?>
<h1>Sistemos inicializavimas</h1>
<p><?php echo htmlspecialchars($msg); ?></p>
<p><a class="btn btn-primary" href="index.php">Grįžti į pradžią</a></p>
<?php include 'footer.php'; ?>
