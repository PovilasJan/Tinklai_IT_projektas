<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$pdo = getPDO();
$user = currentUser();

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$start = $_GET['start_date'] ?? null;
$end = $_GET['end_date'] ?? null;

// fetch room
$stmt = $pdo->prepare('SELECT r.*, h.id as hotel_id, h.name as hotel_name, h.city FROM rooms r JOIN hotels h ON r.hotel_id=h.id WHERE r.id = ?');
$stmt->execute([$room_id]);
$room = $stmt->fetch();
if (!$room){ header('Location: index.php'); exit; }

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = trim($_POST['comment'] ?? '');
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Įvertinimas turi būti nuo 1 iki 5 žvaigždučių.';
    } else {
        // Check if user has stayed at this hotel
        $checkStayed = $pdo->prepare("SELECT COUNT(*) FROM reservations r 
            JOIN rooms ro ON r.room_id = ro.id 
            WHERE r.user_id = ? AND ro.hotel_id = ? 
            AND r.status = 'confirmed' AND r.end_date < NOW()");
        $checkStayed->execute([$user['id'], $room['hotel_id']]);
        $hasStayed = (int)$checkStayed->fetchColumn() > 0;
        
        if (!$hasStayed) {
            $errors[] = 'Galite vertinti tik viešbučius, kuriuose jau buvote.';
        } else {
            // Insert or update review
            $reviewStmt = $pdo->prepare("INSERT INTO hotel_reviews (user_id, hotel_id, rating, comment, created_at) 
                VALUES (?, ?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = NOW()");
            $reviewStmt->execute([$user['id'], $room['hotel_id'], $rating, $comment]);
            header('Location: reserve.php?room_id='.$room_id.'&review_success=1');
            exit;
        }
    }
}

// Get hotel review statistics
$reviewStats = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM hotel_reviews WHERE hotel_id = ?");
$reviewStats->execute([$room['hotel_id']]);
$stats = $reviewStats->fetch();
$avgRating = $stats['avg_rating'] ? round($stats['avg_rating'], 1) : 0;
$reviewCount = (int)$stats['review_count'];

// Get recent reviews
$reviewsStmt = $pdo->prepare("SELECT hr.*, u.name as user_name FROM hotel_reviews hr 
    JOIN users u ON hr.user_id = u.id 
    WHERE hr.hotel_id = ? 
    ORDER BY hr.created_at DESC LIMIT 10");
$reviewsStmt->execute([$room['hotel_id']]);
$reviews = $reviewsStmt->fetchAll();

// Check if current user can review this hotel
$canReview = false;
$alreadyReviewed = false;
if ($user) {
    $checkStayed = $pdo->prepare("SELECT COUNT(*) FROM reservations r 
        JOIN rooms ro ON r.room_id = ro.id 
        WHERE r.user_id = ? AND ro.hotel_id = ? 
        AND r.status = 'confirmed' AND r.end_date < NOW()");
    $checkStayed->execute([$user['id'], $room['hotel_id']]);
    $canReview = (int)$checkStayed->fetchColumn() > 0;
    
    $checkReviewed = $pdo->prepare("SELECT COUNT(*) FROM hotel_reviews WHERE user_id = ? AND hotel_id = ?");
    $checkReviewed->execute([$user['id'], $room['hotel_id']]);
    $alreadyReviewed = (int)$checkReviewed->fetchColumn() > 0;
}

// Get existing reservations for this room (for calendar)
$reservStmt = $pdo->prepare("SELECT start_date, end_date, status FROM reservations WHERE room_id = ? AND status IN ('pending','confirmed')");
$reservStmt->execute([$room_id]);
$existingReservations = $reservStmt->fetchAll();

// Get all photos for this room (for collage)
$photosStmt = $pdo->prepare("SELECT file_path, is_primary, sort_order FROM room_photos WHERE room_id = ? ORDER BY is_primary DESC, sort_order ASC LIMIT 5");
$photosStmt->execute([$room_id]);
$room_photos = $photosStmt->fetchAll();

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

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<style>
  .fc .fc-highlight {
    background-color: #fff3cd !important;
    opacity: 0.7;
  }
</style>

<h2>Kambario rezervacija</h2>
<div class="row">
  <!-- Photo Collage -->
  <div class="col-md-12 mb-3">
    <?php if (!empty($room_photos)): ?>
      <div class="row g-2">
        <?php 
        $primary = $room_photos[0];
        $others = array_slice($room_photos, 1);
        ?>
        <!-- Primary photo (larger) -->
        <div class="col-md-6">
          <img src="<?php echo htmlspecialchars($primary['file_path']); ?>" 
               class="img-fluid w-100 rounded" 
               style="height: 400px; object-fit: cover;" 
               alt="Kambarys">
        </div>
        <!-- Other photos (grid) -->
        <div class="col-md-6">
          <div class="row g-2">
            <?php foreach ($others as $photo): ?>
              <div class="col-6">
                <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" 
                     class="img-fluid w-100 rounded" 
                     style="height: 195px; object-fit: cover;" 
                     alt="Kambarys">
              </div>
            <?php endforeach; ?>
            <?php 
            // Fill empty slots with placeholder if less than 5 photos
            $remaining = 4 - count($others);
            for ($i = 0; $i < $remaining; $i++): 
            ?>
              <div class="col-6">
                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 195px;">
                  <span class="text-muted">Nuotrauka nepridėta</span>
                </div>
              </div>
            <?php endfor; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
  
  <!-- Calendar Column -->
  <div class="col-md-7">
    <div class="card mb-3">
      <div class="card-body">
        <h5><?php echo htmlspecialchars($room['hotel_name']); ?> - <?php echo htmlspecialchars($room['city'] ?? ''); ?></h5>
        <p><strong>Kambarys #<?php echo $room['id']; ?></strong> • Vietų: <?php echo $room['places']; ?> • Kaina: <?php echo $room['price']; ?> €/naktį</p>
        
        <!-- Hotel Rating -->
        <?php if ($reviewCount > 0): ?>
        <div class="mb-3">
          <strong>Viešbučio įvertinimas:</strong>
          <span class="text-warning">
            <?php 
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= floor($avgRating)) {
                    echo '★';
                } elseif ($i - 0.5 <= $avgRating) {
                    echo '⯨';
                } else {
                    echo '☆';
                }
            }
            ?>
          </span>
          <span><?php echo $avgRating; ?> / 5</span>
          <small class="text-muted">(<?php echo $reviewCount; ?> <?php echo $reviewCount == 1 ? 'įvertinimas' : 'įvertinimai'; ?>)</small>
        </div>
        <?php endif; ?>
        
        <div id='calendar' style="max-width: 100%; height: 500px;"></div>
        
        <div class="mt-3">
          <small class="text-muted">
            <span class="ms-2 badge bg-success">■</span> Laisva (galima rezervuoti)
          </small>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Reservation Form Column -->
  <div class="col-md-5">
<div class="card mb-3"><div class="card-body">
  <h5 class="card-title">Rezervacijos forma</h5>
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
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var startInput = document.querySelector('input[name="start_date"]');
    var endInput = document.querySelector('input[name="end_date"]');
  var roomId = <?php echo (int)$room_id; ?>;
  var calendarStorageKey = 'roomCalendar_'+roomId;
  var storedDate = sessionStorage.getItem(calendarStorageKey);
  var initialDate = storedDate ? new Date(storedDate) : new Date();
    
    var selectedStart = null;
    var selectedEnd = null;
    
    // Existing reservations
    var bookedDates = <?php echo json_encode($existingReservations); ?>;
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        initialDate: initialDate,
        locale: 'lt',
        height: 'auto',
        selectable: true,
        selectMirror: true,
        selectOverlap: false,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        buttonText: {
            today: 'Šiandien'
        },
        validRange: {
            start: new Date().toISOString().split('T')[0]
        },
        selectAllow: function(selectInfo) {
            var start = selectInfo.startStr;
            var end = new Date(selectInfo.end);
            end.setDate(end.getDate());
            var endStr = end.toISOString().split('T')[0];
            
            return checkAvailability(start, endStr);
        },
        select: function(info) {
            var start = info.startStr;
            var end = new Date(info.end);
            end.setDate(end.getDate());
            var endStr = end.toISOString().split('T')[0];
            
            if (!checkAvailability(start, endStr)) {
                alert('Pasirinktas laikotarpis yra užimtas. Pasirinkite kitas datas.');
                calendar.unselect();
                return;
            }
            
            selectedStart = start;
            selectedEnd = endStr;
            
            startInput.value = start;
            endInput.value = endStr;
            
            // Trigger change to update price preview
            startInput.dispatchEvent(new Event('change'));
        },
        unselect: function() {
            selectedStart = null;
            selectedEnd = null;
        },
        events: bookedDates.map(function(res) {
            return {
                start: res.start_date,
                end: res.end_date,
                display: 'background',
                backgroundColor: '#ffcccc',
            };
        }),
        dayCellDidMount: function(info) {
            var cellDate = info.date.toISOString().split('T')[0];
            var isBooked = false;
            
            bookedDates.forEach(function(res) {
                if (cellDate >= res.start_date && cellDate < res.end_date) {
                    isBooked = true;
                }
            });
            
            if (isBooked) {
                info.el.style.backgroundColor = '#ffcccc';
                info.el.style.cursor = 'not-allowed';
                info.el.title = 'Užimta';
            } else if (cellDate >= new Date().toISOString().split('T')[0]) {
                info.el.style.backgroundColor = '#d4edda';
                info.el.title = 'Laisva';
            }
        }
    });
    
    calendar.render();
    calendar.on('datesSet', function(arg) {
      try {
        sessionStorage.setItem(calendarStorageKey, arg.startStr || arg.start.toISOString());
      } catch (e) {
        /* ignore storage errors */
      }
    });
    
    function checkAvailability(start, end) {
        for (var i = 0; i < bookedDates.length; i++) {
            var booked = bookedDates[i];
            if (!(end < booked.start_date || start > booked.end_date)) {
                return false;
            }
        }
        return true;
    }
});
</script>

<!-- Hotel Reviews Section -->
<div class="row mt-5">
  <div class="col-md-12">
    <h3>Viešbučio atsiliepimai</h3>
    
    <?php if (!empty($_GET['review_success'])): ?>
      <div class="alert alert-success">Jūsų atsiliepimas sėkmingai pateiktas!</div>
    <?php endif; ?>
    
    <!-- Review Form -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">Vertinti viešbutį</h5>
        <?php if ($canReview && !$alreadyReviewed): ?>
          <form method="post">
            <div class="mb-3">
              <label class="form-label"><strong>Įvertinimas:</strong></label>
              <div>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <label class="me-3">
                    <input type="radio" name="rating" value="<?php echo $i; ?>" required>
                    <?php echo str_repeat('★', $i); ?>
                  </label>
                <?php endfor; ?>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Komentaras (neprivaloma):</label>
              <textarea name="comment" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" name="submit_review" class="btn btn-primary">Pateikti atsiliepimą</button>
          </form>
        <?php elseif ($alreadyReviewed): ?>
          <div class="alert alert-info">Jūs jau įvertinote šį viešbutį.</div>
        <?php else: ?>
          <div class="alert alert-warning">Galite vertinti tik viešbučius, kuriuose jau buvote (pasibaigusi patvirtinta rezervacija).</div>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Reviews List -->
    <?php if (!empty($reviews)): ?>
      <h5>Visi atsiliepimai (<?php echo $reviewCount; ?>)</h5>
      <?php foreach ($reviews as $review): ?>
        <div class="card mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
                <span class="text-warning ms-2">
                  <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                </span>
              </div>
              <small class="text-muted"><?php echo date('Y-m-d', strtotime($review['created_at'])); ?></small>
            </div>
            <?php if (!empty($review['comment'])): ?>
              <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="alert alert-info">Šis viešbutis dar neturi atsiliepimų. Būkite pirmas!</div>
    <?php endif; ?>
  </div>
</div>

<?php include 'footer.php'; ?>
