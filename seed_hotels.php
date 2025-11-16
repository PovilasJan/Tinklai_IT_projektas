<?php
// Seed script: create 50 hotels and random rooms for testing
require_once __DIR__ . '/db.php';

$pdo = getPDO();
$cities = ['Vilnius','Kaunas','Klaipėda','Šiauliai','Panevėžys','Palanga','Druskininkai','Nida','Trakai','Marijampolė'];

try{
    $pdo->beginTransaction();
    $hstmt = $pdo->prepare('INSERT INTO hotels (name,city,rating) VALUES (?,?,?)');
    $rstmt = $pdo->prepare('INSERT INTO rooms (hotel_id,places,price,photo,status) VALUES (?,?,?,?,?)');
    for ($i=1;$i<=50;$i++){
        $city = $cities[array_rand($cities)];
        $name = "Hotel " . $i . " " . $city;
        $rating = round(3 + mt_rand(0,20)/10,1); // 3.0-5.0
        $hstmt->execute([$name,$city,$rating]);
        $hid = $pdo->lastInsertId();
        // add 1-4 rooms
        $rooms = rand(1,4);
        for ($r=0;$r<$rooms;$r++){
            $places = [1,2,3,4][array_rand([0,1,2,3])];
            $price = round(30 + mt_rand(0,170) + ($places*10),2);
            $photo = 'https://picsum.photos/seed/'.($i*10+$r).'/400/300';
            $status = 'available';
            $rstmt->execute([$hid,$places,$price,$photo,$status]);
        }
    }
    $pdo->commit();
    echo "Seeded 50 hotels with rooms successfully.\n";
} catch (Exception $e){
    $pdo->rollBack();
    echo "Seeding failed: " . $e->getMessage() . "\n";
}
