<?php
require_once __DIR__ . '/../functions.php';
requireLogin(); 
// Allow both admin and employee to access reservations
if (!hasRole('admin') && !hasRole('employee')){ header('Location: ../index.php'); exit; }
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
  // Confirm reservation (no stats update needed - already updated when created)
  if (!empty($_POST['confirm_id'])){
    $rid = (int)$_POST['confirm_id'];
    $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ?')->execute(['confirmed', $rid]);
  }
  // Cancel reservation and refund stats
  if (!empty($_POST['cancel_id'])){
    $rid = (int)$_POST['cancel_id'];
    // Get reservation details to refund user stats
    $cur = $pdo->prepare('SELECT status,user_id,payment_amount FROM reservations WHERE id = ?'); 
    $cur->execute([$rid]);
    $rrow = $cur->fetch();
    
    if ($rrow && $rrow['status'] !== 'cancelled'){
      $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ?')->execute(['cancelled', $rid]);
      // Refund user stats (subtract the payment)
      $upd = $pdo->prepare('UPDATE users SET reservation_count = reservation_count - 1, total_spent = total_spent - ? WHERE id = ?');
      $upd->execute([$rrow['payment_amount'], $rrow['user_id']]);
    }
  }
  header('Location: reservations.php'); exit;
}
$res = $pdo->query('SELECT r.*, u.name as user_name, rooms.id as roomid, hotels.name as hotel_name FROM reservations r JOIN users u ON r.user_id=u.id JOIN rooms ON r.room_id=rooms.id JOIN hotels ON rooms.hotel_id=hotels.id ORDER BY r.id DESC')->fetchAll();
include __DIR__ . '/../header.php';
?>
<h2>Rezervacijos</h2>
<table class="table">
  <thead><tr><th>ID</th><th>Vartotojas</th><th>Viešbutis/Kambarys</th><th>Data</th><th>Bendra suma</th><th>Užstatas</th><th>Sumokėta</th><th>Statusas</th><th>Veiksmai</th></tr></thead>
  <tbody>
  <?php foreach($res as $r): ?>
    <tr>
      <td><?php echo $r['id']; ?></td>
      <td><?php echo htmlspecialchars($r['user_name']); ?></td>
      <td><?php echo htmlspecialchars($r['hotel_name']) . ' / #' . $r['room_id']; ?></td>
      <td><?php echo $r['start_date'] . ' → ' . $r['end_date']; ?></td>
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
      <td>
        <?php if($r['status'] === 'pending'): ?>
          <form method="post" style="display:inline">
            <input type="hidden" name="confirm_id" value="<?php echo $r['id']; ?>">
            <button class="btn btn-sm btn-success">Patvirtinti</button>
          </form>
        <?php endif; ?>
        <?php if($r['status'] !== 'cancelled'): ?>
          <form method="post" style="display:inline">
            <input type="hidden" name="cancel_id" value="<?php echo $r['id']; ?>">
            <button class="btn btn-sm btn-warning">Atšaukti</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../footer.php'; ?>
