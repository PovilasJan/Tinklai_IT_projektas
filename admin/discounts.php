<?php
require_once __DIR__ . '/../functions.php';
requireLogin(); 
if (!hasRole('admin') && !hasRole('employee')){ header('Location: ../index.php'); exit; }
$pdo = getPDO();
$user = currentUser();
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
  // Create new discount code
  if (!empty($_POST['code']) && isset($_POST['action']) && $_POST['action']==='add'){
    $code = strtoupper(trim($_POST['code']));
    $discount_percent = isset($_POST['discount_percent']) ? (int)$_POST['discount_percent'] : 0;
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    
    if ($code === '') $errors[] = 'Kodas privalomas.';
    if ($discount_percent <= 0 || $discount_percent > 100) $errors[] = 'Nuolaida turi būti tarp 1-100%.';
    
    if (!$errors){
      try{
        $stmt = $pdo->prepare('INSERT INTO discount_codes (code, discount_percent, created_by, expires_at, usage_limit) VALUES (?,?,?,?,?)');
        $stmt->execute([$code, $discount_percent, $user['id'], $expires_at, $usage_limit]);
        $success = 'Nuolaidos kodas sukurtas sėkmingai.';
      } catch (Exception $e){
        $errors[] = 'Klaida kuriant kodą (galbūt toks kodas jau egzistuoja).';
      }
    }
  }

  // Deactivate discount code
  if (!empty($_POST['deactivate_id']) && isset($_POST['action']) && $_POST['action']==='deactivate'){
    $id = (int)$_POST['deactivate_id'];
    $pdo->prepare('UPDATE discount_codes SET is_active = 0 WHERE id = ?')->execute([$id]);
    $success = 'Kodas deaktyvuotas.';
  }

  // Activate discount code
  if (!empty($_POST['activate_id']) && isset($_POST['action']) && $_POST['action']==='activate'){
    $id = (int)$_POST['activate_id'];
    $pdo->prepare('UPDATE discount_codes SET is_active = 1 WHERE id = ?')->execute([$id]);
    $success = 'Kodas aktyvuotas.';
  }
}

$codes = $pdo->query('SELECT dc.*, u.name as creator_name FROM discount_codes dc JOIN users u ON dc.created_by = u.id ORDER BY dc.created_at DESC')->fetchAll();
include __DIR__ . '/../header.php';
?>
<h2>Nuolaidų kodai</h2>
<?php if($errors): ?>
  <div class="alert alert-danger">
    <?php foreach($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
  </div>
<?php endif; ?>
<?php if($success): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<h4>Sukurti naują nuolaidos kodą</h4>
<form method="post" class="row g-2 mb-4">
  <input type="hidden" name="action" value="add">
  <div class="col-md-3">
    <label class="form-label">Kodas</label>
    <input name="code" placeholder="pvz. VASARA2025" class="form-control" required>
  </div>
  <div class="col-md-2">
    <label class="form-label">Nuolaida (%)</label>
    <input name="discount_percent" type="number" min="1" max="100" class="form-control" required>
  </div>
  <div class="col-md-2">
    <label class="form-label">Galioja iki</label>
    <input name="expires_at" type="date" class="form-control">
  </div>
  <div class="col-md-2">
    <label class="form-label">Naudojimų limitas</label>
    <input name="usage_limit" type="number" min="1" class="form-control" placeholder="Neribota">
  </div>
  <div class="col-md-3 align-self-end">
    <button class="btn btn-success">Sukurti kodą</button>
  </div>
</form>

<h4>Esami nuolaidos kodai</h4>
<table class="table">
  <thead>
    <tr>
      <th>Kodas</th>
      <th>Nuolaida</th>
      <th>Sukūrė</th>
      <th>Galioja iki</th>
      <th>Panaudota</th>
      <th>Limitas</th>
      <th>Statusas</th>
      <th>Veiksmai</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($codes as $c): ?>
    <tr class="<?php echo $c['is_active'] ? '' : 'table-secondary'; ?>">
      <td><strong><?php echo htmlspecialchars($c['code']); ?></strong></td>
      <td><?php echo $c['discount_percent']; ?>%</td>
      <td><?php echo htmlspecialchars($c['creator_name']); ?></td>
      <td><?php echo $c['expires_at'] ?? 'Neterminuota'; ?></td>
      <td><?php echo $c['times_used']; ?></td>
      <td><?php echo $c['usage_limit'] ?? 'Neribota'; ?></td>
      <td>
        <?php if ($c['is_active']): ?>
          <span class="badge bg-success">Aktyvi</span>
        <?php else: ?>
          <span class="badge bg-secondary">Neaktyvi</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($c['is_active']): ?>
          <form method="post" style="display:inline">
            <input type="hidden" name="action" value="deactivate">
            <input type="hidden" name="deactivate_id" value="<?php echo $c['id']; ?>">
            <button class="btn btn-sm btn-warning">Deaktyvuoti</button>
          </form>
        <?php else: ?>
          <form method="post" style="display:inline">
            <input type="hidden" name="action" value="activate">
            <input type="hidden" name="activate_id" value="<?php echo $c['id']; ?>">
            <button class="btn btn-sm btn-success">Aktyvuoti</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../footer.php'; ?>
