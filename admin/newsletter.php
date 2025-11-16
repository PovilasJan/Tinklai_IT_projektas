<?php
require_once __DIR__ . '/../functions.php';
requireLogin(); 
if (!hasRole('admin') && !hasRole('employee')){ header('Location: ../index.php'); exit; }
$pdo = getPDO();

// Get all newsletter subscribers with their stats
$subscribers = $pdo->query('SELECT ns.email, u.name, u.reservation_count, u.total_spent 
  FROM newsletter_subscribers ns 
  LEFT JOIN users u ON ns.user_id = u.id 
  ORDER BY u.name ASC')->fetchAll();

include __DIR__ . '/../header.php';
?>
<h2>Naujienlaiškio prenumeratoriai</h2>

<?php if(empty($subscribers)): ?>
  <div class="alert alert-info">Nėra prenumeratorių.</div>
<?php else: ?>
<p class="text-muted">Iš viso prenumeratorių: <?php echo count($subscribers); ?></p>
<table class="table table-striped">
  <thead>
    <tr>
      <th>Vardas</th>
      <th>El. paštas</th>
      <th>Rezervacijų sk.</th>
      <th>Išleista suma</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($subscribers as $s): ?>
      <tr>
        <td><?php echo $s['name'] ? htmlspecialchars($s['name']) : '<em>Svečias</em>'; ?></td>
        <td><?php echo htmlspecialchars($s['email']); ?></td>
        <td><?php echo $s['reservation_count'] ?? 0; ?></td>
        <td><?php echo number_format($s['total_spent'] ?? 0, 2); ?> €</td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php include __DIR__ . '/../footer.php'; ?>
