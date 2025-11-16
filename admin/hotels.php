<?php
require_once __DIR__ . '/../functions.php';
requireLogin(); if (!hasRole('admin')){ header('Location: ../index.php'); exit; }
$pdo = getPDO();
$errors = [];
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
  // Add hotel
  if (!empty($_POST['name']) && isset($_POST['action']) && $_POST['action']==='add'){
    $name = trim($_POST['name']);
    $city = trim($_POST['city'] ?? '');
    $rating = isset($_POST['rating']) ? (float)$_POST['rating'] : 0.0;
    if ($name === '') $errors[] = 'Pavadinimas privalomas.';
    if ($city === '') $errors[] = 'Miestas privalomas.';
    if (!is_numeric($rating) || $rating < 0 || $rating > 5) $errors[] = 'Neteisingas įvertinimas (0-5).';
    if (!$errors){
      try{
        $stmt = $pdo->prepare('INSERT INTO hotels (name,city,rating) VALUES (?,?,?)');
        $stmt->execute([$name, $city, $rating]);
        $success = 'Viešbutis pridėtas sėkmingai.';
      } catch (Exception $e){
        $errors[] = 'Klaida pridedant viešbutį: ' . $e->getMessage();
      }
    }
  }

  // Delete hotel
  if (!empty($_POST['delete_id']) && isset($_POST['action']) && $_POST['action']==='delete'){
    $deleteId = (int)$_POST['delete_id'];
    try{
      $stmt = $pdo->prepare('DELETE FROM hotels WHERE id = ?');
      $stmt->execute([$deleteId]);
      $success = 'Viešbutis ištrintas.';
    } catch (Exception $e){
      $errors[] = 'Klaida trinant viešbutį: ' . $e->getMessage();
    }
  }
}

$hotels = $pdo->query('SELECT * FROM hotels')->fetchAll();
include __DIR__ . '/../header.php';
?>
<h2>Viešbučiai</h2>
<?php if($errors): ?>
  <div class="alert alert-danger">
    <?php foreach($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
  </div>
<?php endif; ?>
<?php if($success): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<form method="post" class="row g-2 mb-3">
  <input type="hidden" name="action" value="add">
  <div class="col-md-4"><input name="name" placeholder="Pavadinimas" class="form-control"></div>
  <div class="col-md-3"><input name="city" placeholder="Miestas" class="form-control"></div>
  <div class="col-md-2"><input name="rating" placeholder="Įvertinimas" class="form-control" step="0.1" type="number"></div>
  <div class="col-md-3"><button class="btn btn-success">Pridėti</button></div>
</form>
<table class="table">
  <thead><tr><th>Pavadinimas</th><th>Miestas</th><th>Įvertinimas</th><th>Veiksmai</th></tr></thead>
  <tbody>
  <?php foreach($hotels as $h): ?>
    <tr>
      <td><?php echo htmlspecialchars($h['name']); ?></td>
      <td><?php echo htmlspecialchars($h['city']); ?></td>
      <td><?php echo $h['rating']; ?></td>
      <td>
        <form method="post" style="display:inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="delete_id" value="<?php echo $h['id']; ?>">
          <button class="btn btn-sm btn-danger">Ištrinti</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../footer.php'; ?>
