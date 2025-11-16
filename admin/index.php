<?php
require_once __DIR__ . '/../functions.php';
requireLogin();
if (!hasRole('admin') && !hasRole('employee')){ header('Location: ../index.php'); exit; }
include __DIR__ . '/../header.php';
$user = currentUser();
?>
<h2><?php echo hasRole('admin') ? 'Administratoriaus' : 'Darbuotojo'; ?> skydelis</h2>
<div class="list-group">
  <?php if (hasRole('admin')): ?>
    <a class="list-group-item list-group-item-action" href="hotels.php">Valdyti viešbučius</a>
    <a class="list-group-item list-group-item-action" href="rooms.php">Valdyti kambarius</a>
    <a class="list-group-item list-group-item-action" href="users.php">Valdyti vartotojus</a>
  <?php endif; ?>
  <a class="list-group-item list-group-item-action" href="reservations.php">Visos rezervacijos</a>
  <a class="list-group-item list-group-item-action" href="discounts.php">Nuolaidų kodai</a>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
