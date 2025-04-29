<?php
session_start();
@include '../../components/links.php';

// Check if the user is logged in
if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

// Get current office ID
$office_id = $_SESSION['office_id'];

// Get all active rooms for this office
$rooms_query = $conn->query("SELECT id, name FROM rooms WHERE office_id = $office_id AND deleted_at IS NULL");
$rooms = $rooms_query->fetch_all(MYSQLI_ASSOC);

// Also include rooms that have schedules but might be archived
$scheduled_rooms_query = $conn->query("
    SELECT DISTINCT r.id, COALESCE(r.name, 'TBA') AS name 
    FROM schedules s
    LEFT JOIN rooms r ON s.room_id = r.id
    WHERE (r.office_id = $office_id OR r.id IS NULL)
    ORDER BY name
");
$all_rooms = $scheduled_rooms_query->fetch_all(MYSQLI_ASSOC);

// Combine both results
$rooms = array_merge($rooms, $all_rooms);
$rooms = array_unique($rooms, SORT_REGULAR);

// Get all schedules for this office, grouped by room
$schedules_query = $conn->query("
    SELECT 
        s.id,
        s.day,
        s.start_time,
        s.end_time,
        CASE 
            WHEN r.id IS NULL OR r.deleted_at IS NOT NULL THEN NULL
            ELSE s.room_id
        END AS room_id,
        CASE 
            WHEN r.id IS NULL OR r.deleted_at IS NOT NULL THEN 'TBA'
            ELSE r.name
        END AS room_name,
        CASE 
            WHEN sub.id IS NULL OR sub.deleted_at IS NOT NULL THEN 'TBA'
            ELSE sub.name
        END AS subject_name,
        CASE 
            WHEN t.id IS NULL OR t.deleted_at IS NOT NULL THEN 'TBA'
            WHEN u.id IS NULL OR u.deleted_at IS NOT NULL THEN 'TBA'
            ELSE CONCAT(u.firstname, ' ', u.lastname)
        END AS teacher_name,
        COALESCE(t.unit, '') AS unit
    FROM schedules s
    LEFT JOIN teachers t ON s.teach_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN subjects sub ON s.subject_id = sub.id
    WHERE s.office_id = $office_id
    AND s.deleted_at IS NULL
    ORDER BY 
        CASE WHEN r.name IS NULL THEN 1 ELSE 0 END,
        COALESCE(r.name, 'ZZZ'), 
        s.day, 
        s.start_time
");
$schedules = $schedules_query->fetch_all(MYSQLI_ASSOC);

// Check for schedule conflicts
$conflicts = [];
$schedule_slots = [];

foreach ($schedules as $schedule) {
    if ($schedule['teacher_name'] === 'TBA' || $schedule['room_name'] === 'TBA' || $schedule['subject_name'] === 'TBA') {
        continue;
    }
    
    $key = $schedule['room_id'] . '-' . $schedule['day'] . '-' . $schedule['start_time'] . '-' . $schedule['end_time'];
    
    if (!isset($schedule_slots[$key])) {
        $schedule_slots[$key] = [];
    }
    
    $schedule_slots[$key][] = $schedule;
    
    if (count($schedule_slots[$key]) > 1) {
        $conflicts[$key] = $schedule_slots[$key];
    }
}

// Organize schedules by room
$room_schedules = [
    'TBA' => [
        'room_name' => 'TBA',
        'days' => [
            'Monday' => [],
            'Tuesday' => [],
            'Wednesday' => [],
            'Thursday' => [],
            'Friday' => []
        ]
    ]
];

// Add all active rooms
foreach ($rooms as $room) {
    $room_schedules[$room['id']] = [
        'room_name' => $room['name'],
        'days' => [
            'Monday' => [],
            'Tuesday' => [],
            'Wednesday' => [],
            'Thursday' => [],
            'Friday' => []
        ]
    ];
}

foreach ($schedules as $schedule) {
    // Use 'TBA' as room_id if the room is deleted
    $room_id = ($schedule['room_name'] === 'TBA') ? 'TBA' : $schedule['room_id'];
    $day = $schedule['day'];
    
    if (isset($room_schedules[$room_id]['days'][$day])) {
        $room_schedules[$room_id]['days'][$day][] = [
            'time' => date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])),
            'subject' => $schedule['subject_name'],
            'teacher' => $schedule['teacher_name'],
            'unit' => $schedule['unit'],
            'has_conflict' => isset($conflicts[$schedule['room_id'] . '-' . $schedule['day'] . '-' . $schedule['start_time'] . '-' . $schedule['end_time']])
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Room Schedules</title>
    <link rel="stylesheet" href="/myschedule/assets/css/schedule.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<?php ?>
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/sidebar.php'; ?>
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Room Schedules</h1>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="content">
                <div class="container-fluid">
                    
                    <?php if (!empty($conflicts)): ?>
                        <div class="alert alert-danger conflict-alert">
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Schedule Conflicts Detected!</h5>
                            <p>The following time slots have conflicting schedules:</p>
                            <ul>
                                <?php foreach ($conflicts as $key => $conflicting_schedules): ?>
                                    <?php 
                                    $parts = explode('-', $key);
                                    $room_id = $parts[0];
                                    $room_name = '';
                                    foreach ($rooms as $room) {
                                        if ($room['id'] == $room_id) {
                                            $room_name = $room['name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($room_name); ?></strong> on 
                                        <?php echo htmlspecialchars($parts[1]); ?> at 
                                        <?php echo date('h:i A', strtotime($parts[2])) . ' - ' . date('h:i A', strtotime($parts[3])); ?>:
                                        <ul>
                                            <?php foreach ($conflicting_schedules as $schedule): ?>
                                                <li>
                                                    <?php echo htmlspecialchars($schedule['teacher_name']); ?> - 
                                                    <?php echo htmlspecialchars($schedule['subject_name']); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($room_schedules)): ?>
                        <div class="alert alert-info">
                            No rooms or schedules found for your office.
                        </div>
                    <?php else: ?>
                        <?php foreach ($room_schedules as $room_id => $room_data): ?>
                            <div class="card room-card">
                                <div class="room-header">
                                    <h4><?php echo htmlspecialchars($room_data['room_name']); ?></h4>
                                </div>
                                
                                <div class="card-body">
                                    <?php 
                                    $has_schedules = false;
                                    foreach ($room_data['days'] as $day => $schedules) {
                                        if (!empty($schedules)) $has_schedules = true;
                                    }
                                    ?>
                                    
                                    <?php if (!$has_schedules): ?>
                                        <div class="no-schedules">No schedules assigned to this room</div>
                                    <?php else: ?>
                                        <?php foreach ($room_data['days'] as $day => $schedules): ?>
                                            <?php if (!empty($schedules)): ?>
                                                <div class="day-header">
                                                    <h5><?php echo htmlspecialchars($day); ?></h5>
                                                </div>
                                                
                                                <div class="row">
                                                    <?php foreach ($schedules as $schedule): ?>
                                                        <div class="col-md-6 col-lg-4">
                                                            <div class="schedule-card <?php echo $schedule['has_conflict'] ? 'conflict' : ''; ?>">
                                                                <div class="d-flex justify-content-between mb-2">
                                                                    <span class="time-badge">
                                                                        <?php echo htmlspecialchars($schedule['time']); ?>
                                                                    </span>
                                                                    <?php if ($schedule['has_conflict']): ?>
                                                                        <span class="conflict-badge">Conflict</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <strong>Subject:</strong> 
                                                                    <?php echo htmlspecialchars($schedule['subject']); ?>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <strong>Teacher:</strong> 
                                                                    <?php echo htmlspecialchars($schedule['teacher']); ?>
                                                                </div>
                                                                <div>
                                                                    <strong>Unit:</strong> 
                                                                    <?php echo htmlspecialchars($schedule['unit']); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

</body>
</html>