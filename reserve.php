<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$pdo = getPDO();
$user = currentUser();

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$start = $_GET['start_date'] ?? null;
$end = $_GET['end_date'] ?? null;

// fetch room
$stmt = $pdo->prepare('SELECT r.*, h.name as hotel_name FROM rooms r JOIN hotels h ON r.hotel_id=h.id WHERE r.id = ?');
$stmt->execute([$room_id]);
$room = $stmt->fetch();
if (!$room){ header('Location: index.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $discount_code = !empty($_POST['discount_code']) ? trim($_POST['discount_code']) : null;
    // simple validation
  if (!$start || !$end) $errors[] = 'Nurodykite datą.';
    $days = (new DateTime($end))->diff(new DateTime($start))->days;
    if ($days <= 0) $errors[] = 'Data iki turi būti vėlesnė už datą nuo.';
    if (!$errors){
    // Check availability: no overlapping pending/confirmed reservations for the same room
    try{
      $pdo->beginTransaction();
      $chk = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE room_id = ? AND status IN ('pending','confirmed') AND NOT (end_date <= ? OR start_date >= ?)");
      $chk->execute([$room_id, $start, $end]);
      $overlap = (int)$chk->fetchColumn();
      if ($overlap > 0){
        // Friendly message when room is not available (Lithuanian)
        $errors[] = 'Kambarys jau rezervuotas nurodytam laikotarpiui. Negalite atlikti rezervacijos.';
        $pdo->rollBack();
      }
    } catch (Exception $e){
      if ($pdo->inTransaction()) $pdo->rollBack();
      $errors[] = 'Tinklo klaida. Bandykite dar kartą.';
    }
  }

  if (!$errors){
        // compute total price and deposit
        $total_price = $room['price'] * $days;
        
        // Check if user has confirmed reservation - if yes, no deposit needed
        $hasConfirmed = hasConfirmedReservation($user['id']);
        $deposit = $hasConfirmed ? 0 : ($total_price * RESERVATION_DEPOSIT_RATE);

        // apply discount based on user's total_spent and discount code
        $disc = applyDiscount($user['id'], $deposit, $discount_code);
        
        // Check if discount code was invalid
        if ($discount_code && !$disc['code_id'] && $disc['discount_type'] !== 'code'){
            $errors[] = 'Nuolaidos kodas neteisingas arba nebegalioja.';
        }
  }
  
  if (!$errors){
  $payment_amount = $disc['final'];
  $discount_percent = $disc['discount'];
  $code_id = $disc['code_id'];

        // determine status: auto-confirm if reservation_count > 10
        $uRow = $pdo->prepare('SELECT reservation_count FROM users WHERE id = ?'); $uRow->execute([$user['id']]);
        $rc = (int)$uRow->fetchColumn();
        $status = ($rc > 10) ? 'confirmed' : 'pending';

    // insert reservation (we're still in transaction)
    $ins = $pdo->prepare('INSERT INTO reservations (user_id, room_id, start_date, end_date, status, total_price, deposit_amount, payment_amount, discount_code_id) VALUES (?,?,?,?,?,?,?,?,?)');
    $ins->execute([$user['id'],$room_id,$start,$end,$status,$total_price,$deposit,$payment_amount,$code_id]);

    // Update user stats immediately since deposit is paid upfront
    $update = $pdo->prepare('UPDATE users SET reservation_count = reservation_count + 1, total_spent = total_spent + ? WHERE id = ?');
    $update->execute([$payment_amount, $user['id']]);
    
    // increment discount code usage if used
    if ($code_id){
      $pdo->prepare('UPDATE discount_codes SET times_used = times_used + 1 WHERE id = ?')->execute([$code_id]);
    }
    
    $pdo->commit();

    // redirect with status so we can show a proper message
    // pass payment and discount so we can show breakdown on success page
    $autoConfirm = ($status === 'confirmed' && $rc > 10) ? '&auto=1' : '';
    header('Location: reserve.php?room_id='.$room_id.'&success=1&status='.$status.'&total='.urlencode($total_price).'&deposit='.urlencode($deposit).'&payment='.urlencode($payment_amount).'&discount='.urlencode($discount_percent).$autoConfirm); exit;
    }
}

include 'header.php';
?>
<h2>Kambario rezervacija</h2>
<div class="card mb-3"><div class="card-body">
  <h5><?php echo htmlspecialchars($room['hotel_name']); ?></h5>
  <p>Vietų: <?php echo $room['places']; ?> • Kaina: <?php echo $room['price']; ?> €/naktį</p>
  <?php if($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div><?php endif; ?>
  <?php if(empty($errors) && !empty($_GET['success']) && !empty($_GET['status'])): ?>
    <?php if($_GET['status'] === 'confirmed'): ?>
      <div class="alert alert-success">Rezervacija sėkmingai patvirtinta <?php if(!empty($_GET['auto'])): ?>(automatiškai - turite >10 rezervacijų)<?php endif; ?>.
        <?php if(isset($_GET['total'])): ?><div>Bendra suma: <?php echo htmlspecialchars($_GET['total']); ?> €</div><?php endif; ?>
        <?php if(isset($_GET['deposit'])): ?>
          <?php if($_GET['deposit'] == 0): ?>
            <div>Užstatas: <strong>0.00 € (atleidžiama)</strong></div>
          <?php else: ?>
            <div>Užstatas (<?php echo (RESERVATION_DEPOSIT_RATE*100); ?>%): <?php echo htmlspecialchars($_GET['deposit']); ?> €</div>
          <?php endif; ?>
        <?php endif; ?>
        <?php if(isset($_GET['discount']) && $_GET['discount'] > 0): ?><div>Taikyta nuolaida: <?php echo htmlspecialchars($_GET['discount']); ?>%</div><?php endif; ?>
        <?php if(isset($_GET['payment'])): ?><div><strong>Mokėtina suma dabar: <?php echo htmlspecialchars($_GET['payment']); ?> €</strong></div><?php endif; ?>
      </div>
    <?php else: ?>
      <div class="alert alert-info">Rezervacija gauta ir lauks patvirtinimo administracijos.<?php if(isset($_GET['total'])): ?> <div>Bendra suma: <?php echo htmlspecialchars($_GET['total']); ?> €</div><?php endif; ?><?php if(isset($_GET['deposit'])): ?> <?php if($_GET['deposit'] == 0): ?><div>Užstatas: <strong>0.00 € (atleidžiama)</strong></div><?php else: ?><div>Užstatas (<?php echo (RESERVATION_DEPOSIT_RATE*100); ?>%): <?php echo htmlspecialchars($_GET['deposit']); ?> €</div><?php endif; ?><?php endif; ?><?php if(isset($_GET['discount']) && $_GET['discount'] > 0): ?> <div>Taikyta nuolaida: <?php echo htmlspecialchars($_GET['discount']); ?>%</div><?php endif; ?><?php if(isset($_GET['payment'])): ?> <div><strong>Mokėtina suma dabar: <?php echo htmlspecialchars($_GET['payment']); ?> €</strong></div><?php endif; ?></div>
    <?php endif; ?>
  <?php endif; ?>
  <form method="post">
    <div class="row">
      <div class="col-md-3 mb-2"><label>Data nuo</label><input type="date" name="start_date" value="<?php echo htmlspecialchars($start); ?>" class="form-control"></div>
      <div class="col-md-3 mb-2"><label>Data iki</label><input type="date" name="end_date" value="<?php echo htmlspecialchars($end); ?>" class="form-control"></div>
      <div class="col-md-3 mb-2"><label>Nuolaidos kodas (neprivaloma)</label><input type="text" name="discount_code" placeholder="pvz. VASARA2025" class="form-control"></div>
    </div>
    <?php if(hasConfirmedReservation($user['id'])): ?>
      <p class="alert alert-success">✓ Turite patvirtintą rezervaciją - užstato mokėti nereikia!</p>
    <?php else: ?>
      <p>Rezervacijos avansas: <?php echo (RESERVATION_DEPOSIT_RATE*100); ?>% mokėtina už visą laikotarpį. Nuolaidos taikomos automatiškai pagal jūsų išleistą sumą.</p>
    <?php endif; ?>
  <?php
  // Show preview breakdown if dates provided
  if ($start && $end){
    try{
      $discount_code_preview = $_POST['discount_code'] ?? null;
      $days = (new DateTime($end))->diff(new DateTime($start))->days;
      if ($days > 0){
        $total_price = $room['price'] * $days;
        $hasConfirmed = hasConfirmedReservation($user['id']);
        $deposit = $hasConfirmed ? 0 : ($total_price * RESERVATION_DEPOSIT_RATE);
        $disc = applyDiscount($user['id'], $deposit, $discount_code_preview);
        echo '<div class="alert alert-info">Naktų sk.: '.htmlspecialchars($days).'<br>'; 
        echo 'Viso: '.htmlspecialchars(number_format($total_price,2))." €<br>";
        if ($hasConfirmed){
          echo 'Avansas: <strong>0.00 € (atleidžiama)</strong><br>';
        } else {
          echo 'Avansas ('.(RESERVATION_DEPOSIT_RATE*100)."%):".htmlspecialchars(number_format($deposit,2))." €<br>";
        }
        if ($disc['discount']>0) echo 'Nuolaida: '.htmlspecialchars($disc['discount'])."%<br>";
        echo '<strong>Mokėtina suma dabar: '.htmlspecialchars(number_format($disc['final'],2))." €</strong></div>";
      }
    } catch (Exception $e){ /* ignore preview errors */ }
  }
  ?>
    <button class="btn btn-primary">Patvirtinti rezervaciją</button>
  </form>
</div></div>

<?php include 'footer.php'; ?>
