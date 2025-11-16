<?php
require_once __DIR__ . '/../functions.php';
requireLogin(); if (!hasRole('admin')){ header('Location: ../index.php'); exit; }
$pdo = getPDO();
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    // Add new user
    if (!empty($_POST['name']) && isset($_POST['action']) && $_POST['action']==='add'){
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'] ?? 'client';
        
        if ($name === '') $errors[] = 'Vardas privalomas.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Neteisingas el. paštas.';
        if (strlen($password) < 6) $errors[] = 'Slaptažodis turi būti bent 6 simbolių.';
        if (!in_array($role, ['admin','employee','client'])) $errors[] = 'Neteisinga rolė.';
        
        if (!$errors){
            try{
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)');
                $stmt->execute([$name, $email, $hashedPassword, $role]);
                $success = 'Vartotojas sukurtas sėkmingai.';
            } catch (Exception $e){
                $errors[] = 'Klaida kuriant vartotoją (galbūt toks el. paštas jau egzistuoja).';
            }
        }
    }
    
    // Delete user
    if (!empty($_POST['delete_id']) && isset($_POST['action']) && $_POST['action']==='delete'){ 
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$_POST['delete_id']]); 
        $success = 'Vartotojas ištrintas.';
    }
    
    if ($success || $errors){
        // Don't redirect, show message
    } else {
        header('Location: users.php'); exit;
    }
}
$users = $pdo->query('SELECT id,name,email,role,reservation_count,total_spent FROM users')->fetchAll();
include __DIR__ . '/../header.php';
?>
<h2>Vartotojai</h2>

<?php if($errors): ?>
  <div class="alert alert-danger">
    <?php foreach($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
  </div>
<?php endif; ?>
<?php if($success): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<h4>Pridėti naują vartotoją</h4>
<form method="post" class="row g-2 mb-4">
  <input type="hidden" name="action" value="add">
  <div class="col-md-3">
    <label class="form-label">Vardas</label>
    <input name="name" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">El. paštas</label>
    <input name="email" type="email" class="form-control" required>
  </div>
  <div class="col-md-2">
    <label class="form-label">Slaptažodis</label>
    <input name="password" type="password" class="form-control" required>
  </div>
  <div class="col-md-2">
    <label class="form-label">Rolė</label>
    <select name="role" class="form-control">
      <option value="client">Client</option>
      <option value="employee">Employee</option>
      <option value="admin">Admin</option>
    </select>
  </div>
  <div class="col-md-2 align-self-end">
    <button class="btn btn-success">Pridėti</button>
  </div>
</form>

<h4>Esami vartotojai</h4>
<table class="table">
  <thead><tr><th>Vardas</th><th>El. paštas</th><th>Role</th><th>Rezervacijų sk.</th><th>Išleista (€)</th><th>Veiksmai</th></tr></thead>
  <tbody>
  <?php foreach($users as $u): ?>
    <tr>
      <td><?php echo htmlspecialchars($u['name']); ?></td>
      <td><?php echo htmlspecialchars($u['email']); ?></td>
      <td><?php echo $u['role']; ?></td>
      <td><?php echo $u['reservation_count']; ?></td>
      <td><?php echo $u['total_spent']; ?></td>
      <td>
        <form method="post" style="display:inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="delete_id" value="<?php echo $u['id']; ?>">
          <button class="btn btn-sm btn-danger">Ištrinti</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../footer.php'; ?>
