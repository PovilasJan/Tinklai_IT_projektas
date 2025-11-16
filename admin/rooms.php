<?php
require_once __DIR__ . '/../functions.php';
requireLogin(); if (!hasRole('admin')){ header('Location: ../index.php'); exit; }
$pdo = getPDO();
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!empty($_POST['hotel_id'])){
        $stmt = $pdo->prepare('INSERT INTO rooms (hotel_id, places, price, photo, status) VALUES (?,?,?,?,?)');
        $stmt->execute([$_POST['hotel_id'], (int)$_POST['places'], (float)$_POST['price'], $_POST['photo'], $_POST['status']]);
    }
    if (!empty($_POST['delete_id'])){ $pdo->prepare('DELETE FROM rooms WHERE id = ?')->execute([$_POST['delete_id']]); }
    header('Location: rooms.php'); exit;
}
$hotels = $pdo->query('SELECT id,name FROM hotels')->fetchAll();
$rooms = $pdo->query('SELECT r.*, h.name as hotel_name FROM rooms r JOIN hotels h ON r.hotel_id=h.id')->fetchAll();
include __DIR__ . '/../header.php';
?>
<h2>Kambariai</h2>
<form method="post" class="row g-2 mb-3">
  <div class="col-md-3"><select name="hotel_id" class="form-control">
    <?php foreach($hotels as $h) echo '<option value="'.$h['id'].'">'.htmlspecialchars($h['name']).'</option>'; ?>
  </select></div>
  <div class="col-md-2"><input name="places" class="form-control" placeholder="Vietos"></div>
  <div class="col-md-2"><input name="price" class="form-control" placeholder="Kaina"></div>
  <div class="col-md-3"><input name="photo" class="form-control" placeholder="Foto URL"></div>
  <div class="col-md-2"><select name="status" class="form-control"><option value="available">available</option><option value="maintenance">maintenance</option></select></div>
  <div class="col-md-12"><button class="btn btn-success mt-2">Pridėti kambarį</button></div>
</form>
<table class="table">
  <thead><tr><th>Viešbutis</th><th>Vietos</th><th>Kaina</th><th>Statusas</th><th>Veiksmai</th></tr></thead>
  <tbody>
  <?php foreach($rooms as $r): ?>
    <tr>
      <td><?php echo htmlspecialchars($r['hotel_name']); ?></td>
      <td><?php echo $r['places']; ?></td>
      <td><?php echo $r['price']; ?> €</td>
      <td><?php echo $r['status']; ?></td>
      <td><form method="post" style="display:inline"><input type="hidden" name="delete_id" value="<?php echo $r['id']; ?>"><button class="btn btn-sm btn-danger">Ištrinti</button></form></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../footer.php'; ?>
