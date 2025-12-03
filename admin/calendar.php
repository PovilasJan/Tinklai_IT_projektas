<?php
require_once __DIR__ . '/../functions.php';
requireLogin();
if (!hasRole('admin') && !hasRole('employee')) {
    header('Location: ../index.php');
    exit;
}

$pdo = getPDO();

// Get selected room or all rooms
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : null;

// Get all rooms for filter dropdown
$rooms = $pdo->query('SELECT r.id, r.places, h.name as hotel_name 
    FROM rooms r 
    JOIN hotels h ON r.hotel_id = h.id 
    ORDER BY h.name, r.id')->fetchAll();

// Get reservations
if ($room_id) {
    $stmt = $pdo->prepare('SELECT r.*, u.name as user_name, rm.places, h.name as hotel_name 
        FROM reservations r 
        JOIN users u ON r.user_id = u.id 
        JOIN rooms rm ON r.room_id = rm.id 
        JOIN hotels h ON rm.hotel_id = h.id 
        WHERE r.room_id = ?');
    $stmt->execute([$room_id]);
} else {
    $stmt = $pdo->query('SELECT r.*, u.name as user_name, rm.places, h.name as hotel_name 
        FROM reservations r 
        JOIN users u ON r.user_id = u.id 
        JOIN rooms rm ON r.room_id = rm.id 
        JOIN hotels h ON rm.hotel_id = h.id');
}
$reservations = $stmt->fetchAll();

// Convert to FullCalendar format
$events = [];
foreach ($reservations as $r) {
    $color = '#6c757d'; // cancelled - gray
    if ($r['status'] === 'confirmed') {
        $color = '#28a745'; // green
    } elseif ($r['status'] === 'pending') {
        $color = '#ffc107'; // yellow
    }
    
    $events[] = [
        'id' => $r['id'],
        'title' => $r['hotel_name'] . ' - Kamb. #' . $r['room_id'] . ' (' . $r['user_name'] . ')',
        'start' => $r['start_date'],
        'end' => $r['end_date'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'reservation_id' => $r['id'],
            'status' => $r['status'],
            'user_name' => $r['user_name'],
            'room_id' => $r['room_id'],
            'total_price' => $r['total_price'],
            'payment_amount' => $r['payment_amount']
        ]
    ];
}

include __DIR__ . '/../header.php';
?>

<h2>Rezervacijų kalendorius</h2>

<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label">Filtruoti pagal kambarį:</label>
        <select class="form-select" id="roomFilter" onchange="filterRoom(this.value)">
            <option value="">Visi kambariai</option>
            <?php foreach ($rooms as $room): ?>
                <option value="<?php echo $room['id']; ?>" <?php echo $room_id == $room['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($room['hotel_name']); ?> - Kambarys #<?php echo $room['id']; ?> (<?php echo $room['places']; ?> vietų)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-8 text-end">
        <div class="d-inline-block me-3">
            <span class="badge bg-success">■</span> Patvirtinta
        </div>
        <div class="d-inline-block me-3">
            <span class="badge bg-warning text-dark">■</span> Laukia patvirtinimo
        </div>
        <div class="d-inline-block">
            <span class="badge bg-secondary">■</span> Atšaukta
        </div>
    </div>
</div>

<div id='calendar'></div>

<!-- Event Detail Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rezervacijos detalės</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventDetails">
                <!-- Details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Uždaryti</button>
            </div>
        </div>
    </div>
</div>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/lt.js'></script>
<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'lt',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listMonth'
        },
        buttonText: {
            today: 'Šiandien',
            month: 'Mėnuo',
            week: 'Savaitė',
            list: 'Sąrašas'
        },
        events: <?php echo json_encode($events); ?>,
        eventClick: function(info) {
            var props = info.event.extendedProps;
            var statusBadge = '';
            if (props.status === 'confirmed') {
                statusBadge = '<span class="badge bg-success">Patvirtinta</span>';
            } else if (props.status === 'pending') {
                statusBadge = '<span class="badge bg-warning text-dark">Laukia patvirtinimo</span>';
            } else {
                statusBadge = '<span class="badge bg-secondary">Atšaukta</span>';
            }
            
            var details = `
                <p><strong>Rezervacijos ID:</strong> #${props.reservation_id}</p>
                <p><strong>Statusas:</strong> ${statusBadge}</p>
                <p><strong>Vartotojas:</strong> ${props.user_name}</p>
                <p><strong>Kambarys:</strong> #${props.room_id}</p>
                <p><strong>Data nuo:</strong> ${info.event.start.toLocaleDateString('lt-LT')}</p>
                <p><strong>Data iki:</strong> ${info.event.end ? info.event.end.toLocaleDateString('lt-LT') : 'N/A'}</p>
                <p><strong>Bendra suma:</strong> ${parseFloat(props.total_price).toFixed(2)} €</p>
                <p><strong>Sumokėta:</strong> ${parseFloat(props.payment_amount).toFixed(2)} €</p>
                <hr>
                <a href="reservations.php" class="btn btn-primary btn-sm">Peržiūrėti rezervacijas</a>
            `;
            
            document.getElementById('eventDetails').innerHTML = details;
            var modal = new bootstrap.Modal(document.getElementById('eventModal'));
            modal.show();
        },
        eventDidMount: function(info) {
            info.el.style.cursor = 'pointer';
        }
    });
    calendar.render();
});

function filterRoom(roomId) {
    if (roomId) {
        window.location.href = 'calendar.php?room_id=' + roomId;
    } else {
        window.location.href = 'calendar.php';
    }
}
</script>

<style>
#calendar {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.fc-event {
    cursor: pointer;
}

.fc-event:hover {
    opacity: 0.8;
}
</style>

<?php include __DIR__ . '/../footer.php'; ?>
