<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/components/login.html");
    exit();
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

// Get current office ID
$office_id = $_SESSION['office_id'];

// Get all rooms for this office
$rooms_query = $conn->query("SELECT id, name FROM rooms WHERE office_id = $office_id");
$rooms = $rooms_query->fetch_all(MYSQLI_ASSOC);

// Get all schedules for this office, grouped by room
$schedules_query = $conn->query("
    SELECT 
        s.id,
        s.day,
        s.start_time,
        s.end_time,
        s.room_id,
        r.name AS room_name,
        sub.name AS subject_name,
        CONCAT(t.firstname, ' ', t.lastname) AS teacher_name,
        t.unit
    FROM schedules s
    JOIN teachers t ON s.teach_id = t.id
    JOIN rooms r ON s.room_id = r.id
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE t.office_id = $office_id
    ORDER BY r.name, s.day, s.start_time
");
$schedules = $schedules_query->fetch_all(MYSQLI_ASSOC);

// Check for schedule conflicts
$conflicts = [];
$schedule_slots = [];

foreach ($schedules as $schedule) {
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
$room_schedules = [];
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
    $room_id = $schedule['room_id'];
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/myschedule/assets/css/schedule.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<?php include COMPONENTS_PATH . '/loading_screen.php'; ?>
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="nav-link">Logged in as, <?php echo htmlspecialchars($_SESSION['office_name'] ?? 'User'); ?></span>
                </li>
            </ul>
        </nav>
        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color:rgb(5, 29, 160);">
            <div class="container overflow-hidden">

                <a href="#" class="brand-link">
                    <img src="/myschedule/assets/img/favicon.png" width="35" height="35" alt="" class="ml-2">
                    <span class="brand-text font-weight-light">LNU Teacher's Board</span>
                </a>
            </div>
                <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Teachers Management</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="schedule.php" class="nav-link active">
                                <i class="nav-icon fa fa-calendar"></i>
                                <p>Schedules</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rooms.php" class="nav-link">
                                <i class="nav-icon fas fa-grip-horizontal"></i>
                                <p>Rooms</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="announcements.php" class="nav-link">
                                <i class="nav-icon fa fa-exclamation-circle"></i>
                                <p>Announcements</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="archived.php" class="nav-link">
                                <i class="nav-icon fa fa-archive"></i>
                                <p>Archived</p>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div style="position: absolute; bottom: 0;" class="nav-item overflow-hidden">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="/myschedule/components/logout.php" class="nav-link">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Logout</p>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>
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

    <script>
    $(document).ready(function() {
        // Add loading screen functionality
        $(window).on('beforeunload', function() {
            $('.loading-screen').show();
        });
    });
    </script>
    <script src="/myschedule/assets/js/loading_screen.js"></script>
</body>
</html>