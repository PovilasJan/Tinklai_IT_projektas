<?php
require_once __DIR__ . '/functions.php';
$pdo = getPDO();

$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : null;
$city = $_GET['city'] ?? '';
$places = isset($_GET['places']) ? (int)$_GET['places'] : null;
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : null;
$start = $_GET['start_date'] ?? null;
$end = $_GET['end_date'] ?? null;
$rating_min = isset($_GET['rating_min']) ? (float)$_GET['rating_min'] : null;

$params = [];
if ($hotel_id){
  $sql = 'SELECT r.*, h.id as hotel_id, h.name as hotel_name, h.city, h.rating FROM rooms r JOIN hotels h ON r.hotel_id = h.id WHERE r.hotel_id = ?';
  $params[] = $hotel_id;
} else {
  $sql = 'SELECT r.*, h.id as hotel_id, h.name as hotel_name, h.city, h.rating FROM rooms r JOIN hotels h ON r.hotel_id = h.id WHERE 1=1';
  if ($city){ $sql .= ' AND h.city LIKE ?'; $params[] = "%$city%"; }
}
if ($rating_min){ $sql .= ' AND h.rating >= ?'; $params[] = $rating_min; }
if ($places){ $sql .= ' AND r.places >= ?'; $params[] = $places; }
if ($price_max){ $sql .= ' AND r.price <= ?'; $params[] = $price_max; }

// If dates provided, exclude rooms that have overlapping reservations (not cancelled)
if ($start && $end){
  // overlapping if NOT(end < start_req OR start > end_req)
  $sql .= " AND r.id NOT IN (SELECT room_id FROM reservations WHERE NOT (end_date < ? OR start_date > ?) AND status <> 'cancelled')";
  // add params for start/end in same order used in SQL (for placeholders)
  $params[] = $start; // for end_date < ? compares to start
  $params[] = $end;   // for start_date > ? compares to end
}

$query = $pdo->prepare($sql);
$query->execute($params);
$rooms = $query->fetchAll();

include 'header.php';
?>
<h2>Rezultatų sąrašas</h2>
<?php if($hotel_id):
  $hname = $pdo->prepare('SELECT name FROM hotels WHERE id = ?'); $hname->execute([$hotel_id]); $hn = $hname->fetchColumn();
  if ($hn) echo '<h4>Kambariai viešbutyje: '.htmlspecialchars($hn).'</h4>';
endif; ?>
<?php if(empty($rooms)): ?><div class="alert alert-info">Nerasta kambarių pagal jūsų kriterijus.</div><?php endif; ?>
<div class="row">
<?php foreach($rooms as $r): 
  // Fetch primary photo
  $primary_photo = null;
  if (!empty($r['primary_photo_id'])) {
      $photoStmt = $pdo->prepare('SELECT file_path FROM room_photos WHERE id = ?');
      $photoStmt->execute([$r['primary_photo_id']]);
      $primary_photo = $photoStmt->fetchColumn();
  }
  // Fallback to old photo field
  if (!$primary_photo && !empty($r['photo'])) {
      $primary_photo = $r['photo'];
  }
  if (!$primary_photo) {
      $primary_photo = 'https://via.placeholder.com/300x200?text=No+Image';
  }
  
  // Fetch hotel review statistics
  $reviewStatsStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM hotel_reviews WHERE hotel_id = ?");
  $reviewStatsStmt->execute([$r['hotel_id']]);
  $reviewStats = $reviewStatsStmt->fetch();
  $avgRating = $reviewStats['avg_rating'] ? round($reviewStats['avg_rating'], 1) : 0;
  $reviewCount = (int)$reviewStats['review_count'];
?>
  <div class="col-md-6 mb-3">
    <div class="card">
      <div class="row g-0">
        <div class="col-4"><img src="<?php echo htmlspecialchars($primary_photo); ?>" class="img-fluid thumb" alt="" style="height: 200px; object-fit: cover;"></div>
        <div class="col-8">
          <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($r['hotel_name']); ?> (<?php echo htmlspecialchars($r['city']); ?>)</h5>
            <p>Kaina: <?php echo $r['price']; ?> € • Vietų: <?php echo $r['places']; ?> • Įvertinimas: <?php echo $r['rating']; ?></p>
            <?php if ($reviewCount > 0): ?>
            <div class="mb-2">
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
              <small><?php echo $avgRating; ?> (<?php echo $reviewCount; ?> <?php echo $reviewCount == 1 ? 'įvertinimas' : 'įvertinimai'; ?>)</small>
            </div>
            <?php endif; ?>
            <a href="reserve.php?room_id=<?php echo $r['id']; ?>&start_date=<?php echo urlencode($start); ?>&end_date=<?php echo urlencode($end); ?>" class="btn btn-primary">Rezervuoti</a>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>
