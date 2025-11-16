<?php
require_once __DIR__ . '/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email'] ?? '');
    $user_id = currentUser()['id'] ?? null;
    if (filter_var($email, FILTER_VALIDATE_EMAIL)){
        $pdo = getPDO();
        $ins = $pdo->prepare('INSERT INTO newsletter_subscribers (user_id,email) VALUES (?,?)');
        $ins->execute([$user_id,$email]);
        $msg = 'Prenumeravote naujienas.';
    } else { $msg = 'Neteisingas el. paštas.'; }
}
include 'header.php';
?>
<h2>Naujienlaiškio prenumerata</h2>
<?php if(!empty($msg)): ?><div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<form method="post" class="mb-3">
  <div class="mb-3"><label>El. paštas</label><input name="email" type="email" class="form-control" required></div>
  <button class="btn btn-primary">Prenumeruoti</button>
</form>
<?php include 'footer.php'; ?>
