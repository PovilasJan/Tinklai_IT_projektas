<?php include 'header.php'; ?>
<h2>Ieškokite viešbučio ir kambario</h2>
<form method="get" action="rooms.php" class="row g-3 mb-4">
  <div class="col-md-3">
    <label class="form-label">Miestas</label>
    <input name="city" class="form-control" placeholder="pvz. Vilnius">
  </div>
  <div class="col-md-2">
    <label class="form-label">Data nuo</label>
    <input type="date" name="start_date" class="form-control">
  </div>
  <div class="col-md-2">
    <label class="form-label">Data iki</label>
    <input type="date" name="end_date" class="form-control">
  </div>
  <div class="col-md-1">
    <label class="form-label">Vietos</label>
    <input type="number" name="places" class="form-control" min="1">
  </div>
  <div class="col-md-2">
    <label class="form-label">Kaina iki (€)</label>
    <input type="number" name="price_max" class="form-control" step="0.01">
  </div>
  <div class="col-md-1">
    <label class="form-label">Įvertinimas</label>
    <input type="number" name="rating_min" class="form-control" step="0.1" min="0" max="5">
  </div>
  <div class="col-md-2 align-self-end">
    <button class="btn btn-primary">Ieškoti</button>
  </div>
</form>

<h3>Populiariausi viešbučiai</h3>
<?php
  $pdo = getPDO();
  $hotels = $pdo->query('SELECT * FROM hotels ORDER BY rating DESC LIMIT 6')->fetchAll();
?>
<div class="row">
<?php foreach($hotels as $h): 
  // Fetch hotel review statistics
  $reviewStatsStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM hotel_reviews WHERE hotel_id = ?");
  $reviewStatsStmt->execute([$h['id']]);
  $reviewStats = $reviewStatsStmt->fetch();
  $avgRating = $reviewStats['avg_rating'] ? round($reviewStats['avg_rating'], 1) : 0;
  $reviewCount = (int)$reviewStats['review_count'];
?>
  <div class="col-md-4 mb-3">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title"><?php echo htmlspecialchars($h['name']); ?></h5>
        <p class="card-text"><?php echo htmlspecialchars($h['city']); ?></p>
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
        <?php else: ?>
        <p class="text-muted"><small>Įvertinimų nėra</small></p>
        <?php endif; ?>
        <a href="rooms.php?hotel_id=<?php echo $h['id']; ?>" class="btn btn-sm btn-outline-primary">Peržiūrėti kambarius</a>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>
