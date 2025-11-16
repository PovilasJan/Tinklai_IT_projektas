<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$pdo = getPDO();
$user = currentUser();
$success = null;
$errors = [];

// Get fresh user data from database
$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$userStmt->execute([$user['id']]);
$userFull = $userStmt->fetch();

// Handle newsletter subscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter'])){
  $subscribe = !empty($_POST['subscribe']);
  
  // Check if already subscribed
  $check = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE user_id = ?');
  $check->execute([$user['id']]);
  $exists = $check->fetch();
  
  if ($subscribe && !$exists){
    // Subscribe
    $pdo->prepare('INSERT INTO newsletter_subscribers (user_id, email) VALUES (?,?)')->execute([$user['id'], $user['email']]);
    $success = 'Sėkmingai užsiprenumeravote naujienlaiškį!';
  } elseif (!$subscribe && $exists){
    // Unsubscribe
    $pdo->prepare('DELETE FROM newsletter_subscribers WHERE user_id = ?')->execute([$user['id']]);
    $success = 'Atsisakėte naujienlaiškio prenumeratos.';
  }
}

// Check current subscription status
$subCheck = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE user_id = ?');
$subCheck->execute([$user['id']]);
$isSubscribed = $subCheck->fetch() !== false;

// Get user's reservations
$stmt = $pdo->prepare('SELECT r.*, rooms.places, rooms.price, hotels.name as hotel_name, hotels.city 
  FROM reservations r 
  JOIN rooms ON r.room_id = rooms.id 
  JOIN hotels ON rooms.hotel_id = hotels.id 
  WHERE r.user_id = ? 
  ORDER BY r.start_date DESC');
$stmt->execute([$user['id']]);
$reservations = $stmt->fetchAll();

include 'header.php';
?>
<h2>Mano profilis</h2>

<?php if($success): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body">
    <h5 class="card-title">Vartotojo informacija</h5>
    <p><strong>Vardas:</strong> <?php echo htmlspecialchars($userFull['name']); ?></p>
    <p><strong>El. paštas:</strong> <?php echo htmlspecialchars($userFull['email']); ?></p>
    <p><strong>Rolė:</strong> <?php echo htmlspecialchars($userFull['role']); ?></p>
    <p><strong>Rezervacijų skaičius:</strong> <?php echo $userFull['reservation_count']; ?></p>
    <p><strong>Iš viso išleista:</strong> <?php echo number_format($userFull['total_spent'], 2); ?> €</p>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <h5 class="card-title">Naujienlaiškis</h5>
    <form method="post">
      <input type="hidden" name="newsletter" value="1">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="subscribe" id="subscribe" <?php echo $isSubscribed ? 'checked' : ''; ?>>
        <label class="form-check-label" for="subscribe">
          Noriu gauti naujienlaiškį su specialiais pasiūlymais ir nuolaidomis
        </label>
      </div>
      <button class="btn btn-primary mt-2">Išsaugoti</button>
    </form>
  </div>
</div>

<h3>Mano rezervacijos</h3>
<?php if(empty($reservations)): ?>
  <div class="alert alert-info">Neturite rezervacijų.</div>
<?php else: ?>
<table class="table table-striped">
  <thead>
    <tr>
      <th>Viešbutis</th>
      <th>Miestas</th>
      <th>Data nuo</th>
      <th>Data iki</th>
      <th>Bendra suma</th>
      <th>Užstatas</th>
      <th>Sumokėta</th>
      <th>Statusas</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($reservations as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['hotel_name']); ?></td>
        <td><?php echo htmlspecialchars($r['city']); ?></td>
        <td><?php echo $r['start_date']; ?></td>
        <td><?php echo $r['end_date']; ?></td>
        <td><?php echo number_format($r['total_price'], 2); ?> €</td>
        <td><?php echo number_format($r['deposit_amount'], 2); ?> €</td>
        <td><?php echo number_format($r['payment_amount'], 2); ?> €</td>
        <td>
          <?php if($r['status'] === 'confirmed'): ?>
            <span class="badge bg-success">Confirmed</span>
          <?php elseif($r['status'] === 'cancelled'): ?>
            <span class="badge bg-secondary">Cancelled</span>
          <?php else: ?>
            <span class="badge bg-warning text-dark">Pending</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php include 'footer.php'; ?>
