<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($name === '') $errors[] = 'Vardas privalomas.';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[] = 'Neteisingas el. pašto formatas.';
    if (strlen($pass) < 6) $errors[] = 'Slaptažodis turi būti bent 6 simbolių.';
    if (!$errors){
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?'); $stmt->execute([$email]);
        if ($stmt->fetch()){
            $errors[] = 'El. paštas jau naudojamas.';
        } else {
            $h = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
            $stmt->execute([$name,$email,$h,'client']);
            header('Location: login.php?registered=1'); exit;
        }
    }
}
include 'header.php';
?>
<h2>Registracija</h2>
<?php if($errors): ?>
  <div class="alert alert-danger">
    <?php foreach($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
  </div>
<?php endif; ?>
<form method="post">
  <div class="mb-3">
    <label>Vardas</label>
    <input name="name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label>El. paštas</label>
    <input name="email" type="email" class="form-control" required>
  </div>
  <div class="mb-3">
    <label>Slaptažodis</label>
    <input name="password" type="password" class="form-control" required>
  </div>
  <button class="btn btn-primary">Registruotis</button>
</form>

<?php include 'footer.php'; ?>
