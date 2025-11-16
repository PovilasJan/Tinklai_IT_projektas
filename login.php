<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[] = 'Neteisingas el. paštas.';
    if (!$errors){
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if (!$u || !password_verify($pass, $u['password'])){
            $errors[] = 'Neteisingi prisijungimo duomenys.';
        } else {
            // store minimal user info in session
            $_SESSION['user'] = [ 'id'=>$u['id'], 'name'=>$u['name'], 'email'=>$u['email'], 'role'=>$u['role'] ];
            header('Location: index.php'); exit;
        }
    }
}
include 'header.php';
?>
<h2>Prisijungimas</h2>
<?php if(!empty($_GET['registered'])): ?><div class="alert alert-success">Registracija sėkminga. Prisijunkite.</div><?php endif; ?>
<?php if($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div><?php endif; ?>
<form method="post">
  <div class="mb-3"><label>El. paštas</label><input name="email" type="email" class="form-control" required></div>
  <div class="mb-3"><label>Slaptažodis</label><input name="password" type="password" class="form-control" required></div>
  <button class="btn btn-primary">Prisijungti</button>
</form>

<?php include 'footer.php'; ?>
