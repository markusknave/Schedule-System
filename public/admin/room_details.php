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

// Get room ID from URL
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get room details
$room_query = $conn->prepare("SELECT name FROM rooms WHERE id = ?");
$room_query->bind_param("i", $room_id);
$room_query->execute();
$room_result = $room_query->get_result();
$room = $room_result->fetch_assoc();

if (!$room) {
    header("Location: /myschedule/public/admin/rooms.php");
    exit();
}

// Get all schedules for this room
$schedules_query = $conn->prepare("
    SELECT 
        s.id,
        s.day,
        s.start_time,
        s.end_time,
        o.name AS office_name,
        sub.name AS subject_name,
        CONCAT(t.firstname, ' ', t.lastname) AS teacher_name
    FROM schedules s
    JOIN teachers t ON s.teach_id = t.id
    JOIN offices o ON t.office_id = o.id
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE s.room_id = ?
    ORDER BY s.day, s.start_time
");
$schedules_query->bind_param("i", $room_id);
$schedules_query->execute();
$schedules = $schedules_query->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Details - <?php echo htmlspecialchars($room['name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/myschedule/assets/css/room.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>

    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<?php include COMPONENTS_PATH . '/loading_screen.php'; ?>
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
                </li>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <span class="nav-link">Logged in as, <?php echo htmlspecialchars($_SESSION['office_name'] ?? 'User'); ?></span>
                    </li>
                </ul>
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
                            <a href="schedule.php" class="nav-link">
                                <i class="nav-icon fa fa-calendar"></i>
                                <p>Schedules</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rooms.php" class="nav-link active">
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
                            <h1>Room: <?php echo htmlspecialchars($room['name']); ?></h1>
                        </div>
                        <div class="col-sm-6">
                            <a href="/myschedule/public/admin/rooms.php" class="btn btn-default float-right">
                                <i class="fas fa-arrow-left"></i> Back to Rooms
                            </a>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="content">
                <div class="container-fluid">
                    <?php if (empty($schedules)): ?>
                        <div class="alert alert-info">
                            No schedules found for this room.
                        </div>
                    <?php else: ?>
                        <!-- Group schedules by office -->
                        <?php 
                        $grouped_schedules = [];
                        foreach ($schedules as $schedule) {
                            $grouped_schedules[$schedule['office_name']][] = $schedule;
                        }
                        ?>
                        
                        <?php foreach ($grouped_schedules as $office_name => $office_schedules): ?>
                            <div class="card mb-4">
                                <div class="card-header office-header">
                                    <h3 class="card-title"><?php echo htmlspecialchars($office_name); ?></h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($office_schedules as $schedule): ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="schedule-card">
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span class="time-badge">
                                                            <?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])); ?>
                                                        </span>
                                                        <span class="badge badge-secondary">
                                                            <?php echo htmlspecialchars($schedule['day']); ?>
                                                        </span>
                                                    </div>
                                                    <div class="mb-2">
                                                        <strong>Subject:</strong> 
                                                        <?php echo htmlspecialchars($schedule['subject_name']); ?>
                                                    </div>
                                                    <div>
                                                        <strong>Teacher:</strong> 
                                                        <?php echo htmlspecialchars($schedule['teacher_name']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
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