<?php
require_once __DIR__ . '/../functions.php';
requireLogin(); if (!hasRole('admin')){ header('Location: ../index.php'); exit; }
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!empty($_POST['hotel_id'])){
        // Insert new room
        $stmt = $pdo->prepare('INSERT INTO rooms (hotel_id, places, price, status) VALUES (?,?,?,?)');
        $stmt->execute([$_POST['hotel_id'], (int)$_POST['places'], (float)$_POST['price'], $_POST['status']]);
        $room_id = $pdo->lastInsertId();
        
        // Create upload directory if needed
        $upload_dir = __DIR__ . '/../uploads/rooms/' . $room_id;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Handle up to 5 photo uploads
        $primary_index = isset($_POST['primary_photo']) ? (int)$_POST['primary_photo'] : 0;
        $primary_photo_id = null;
        
        if (!empty($_FILES['photos']['name'][0])) {
            for ($i = 0; $i < min(5, count($_FILES['photos']['name'])); $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION);
                    $filename = 'photo_' . ($i + 1) . '_' . time() . '.' . $ext;
                    $filepath = $upload_dir . '/' . $filename;
                    $relative_path = 'uploads/rooms/' . $room_id . '/' . $filename;
                    
                    if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $filepath)) {
                        $is_primary = ($i === $primary_index) ? 1 : 0;
                        $ins = $pdo->prepare('INSERT INTO room_photos (room_id, file_path, is_primary, sort_order) VALUES (?,?,?,?)');
                        $ins->execute([$room_id, $relative_path, $is_primary, $i + 1]);
                        
                        if ($is_primary) {
                            $primary_photo_id = $pdo->lastInsertId();
                        }
                    }
                }
            }
            
            // Update room with primary photo ID
            if ($primary_photo_id) {
                $pdo->prepare('UPDATE rooms SET primary_photo_id = ? WHERE id = ?')->execute([$primary_photo_id, $room_id]);
            }
        }
    }
    if (!empty($_POST['delete_id'])){ 
        $pdo->prepare('DELETE FROM rooms WHERE id = ?')->execute([$_POST['delete_id']]); 
    }
    header('Location: rooms.php'); exit;
}
$hotels = $pdo->query('SELECT id,name FROM hotels')->fetchAll();
$rooms = $pdo->query('SELECT r.*, h.name as hotel_name FROM rooms r JOIN hotels h ON r.hotel_id=h.id')->fetchAll();
include __DIR__ . '/../header.php';
?>
<h2>Kambariai</h2>
<form method="post" enctype="multipart/form-data" class="row g-2 mb-3">
  <div class="col-md-3">
    <label>Viešbutis</label>
    <select name="hotel_id" class="form-control" required>
      <?php foreach($hotels as $h) echo '<option value="'.$h['id'].'">'.htmlspecialchars($h['name']).'</option>'; ?>
    </select>
  </div>
  <div class="col-md-2">
    <label>Vietos</label>
    <input name="places" class="form-control" placeholder="Vietos" required>
  </div>
  <div class="col-md-2">
    <label>Kaina</label>
    <input name="price" class="form-control" placeholder="Kaina" required>
  </div>
  <div class="col-md-3">
    <label>Statusas</label>
    <select name="status" class="form-control">
      <option value="available">available</option>
      <option value="maintenance">maintenance</option>
    </select>
  </div>
  
  <div class="col-md-12 mt-3">
    <label class="form-label"><strong>Nuotraukos (iki 5)</strong></label>
    <input type="file" name="photos[]" class="form-control mb-2" accept="image/*" multiple>
    <small class="text-muted">Pasirinkite iki 5 nuotraukų. Pažymėkite pagrindinę:</small>
  </div>
  
  <div class="col-md-12">
    <div id="photoPreview" class="d-flex flex-wrap gap-2 mt-2"></div>
  </div>
  
  <div class="col-md-12"><button class="btn btn-success mt-2">Pridėti kambarį</button></div>
</form>

<script>
document.querySelector('input[name="photos[]"]').addEventListener('change', function(e) {
    const preview = document.getElementById('photoPreview');
    preview.innerHTML = '';
    const files = Array.from(e.target.files).slice(0, 5);
    
    files.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const div = document.createElement('div');
            div.className = 'position-relative';
            div.innerHTML = `
                <img src="${ev.target.result}" style="width: 120px; height: 120px; object-fit: cover;" class="border">
                <div class="form-check mt-1">
                    <input class="form-check-input" type="radio" name="primary_photo" value="${index}" id="primary${index}" ${index === 0 ? 'checked' : ''}>
                    <label class="form-check-label" for="primary${index}">Pagrindinė</label>
                </div>
            `;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
});
</script>
<table class="table">
  <thead><tr><th>Nuotrauka</th><th>Viešbutis</th><th>Vietos</th><th>Kaina</th><th>Statusas</th><th>Veiksmai</th></tr></thead>
  <tbody>
  <?php foreach($rooms as $r): 
    $primary_photo = null;
    if ($r['primary_photo_id']) {
        $photoStmt = $pdo->prepare('SELECT file_path FROM room_photos WHERE id = ?');
        $photoStmt->execute([$r['primary_photo_id']]);
        $primary_photo = $photoStmt->fetchColumn();
    }
  ?>
    <tr>
      <td>
        <?php if ($primary_photo): ?>
          <img src="../<?php echo htmlspecialchars($primary_photo); ?>" style="width: 60px; height: 60px; object-fit: cover;" class="rounded">
        <?php else: ?>
          <span class="text-muted">-</span>
        <?php endif; ?>
      </td>
      <td><?php echo htmlspecialchars($r['hotel_name']); ?></td>
      <td><?php echo $r['places']; ?></td>
      <td><?php echo $r['price']; ?> €</td>
      <td><?php echo $r['status']; ?></td>
      <td><form method="post" style="display:inline"><input type="hidden" name="delete_id" value="<?php echo $r['id']; ?>"><button class="btn btn-sm btn-danger">Ištrinti</button></form></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../footer.php'; ?>
