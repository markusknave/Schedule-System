<?php
session_start();
@include '../../components/links.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$section_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$section_query = $conn->prepare("SELECT section_name FROM sections WHERE id = ?");
$section_query->bind_param("i", $section_id);
$section_query->execute();
$section_result = $section_query->get_result();
$section = $section_result->fetch_assoc();

if (!$section) {
    header("Location: /myschedule/public/office/sections.php");
    exit();
}

$schedules_query = $conn->prepare("
    SELECT 
        s.id,
        s.day,
        s.start_time,
        s.end_time,
        o.name AS office_name,
        sub.name AS subject_name,
        CONCAT(u.firstname, ' ', u.lastname) AS teacher_name,
        r.name AS room_name
    FROM schedules s
    JOIN teachers t ON s.teach_id = t.id
    JOIN users u ON t.user_id = u.id
    JOIN offices o ON t.office_id = o.id
    JOIN subjects sub ON s.subject_id = sub.id
    JOIN rooms r ON s.room_id = r.id
    WHERE s.section_id = ?
    ORDER BY s.day, s.start_time
");
$schedules_query->bind_param("i", $section_id);
$schedules_query->execute();
$schedules = $schedules_query->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Section Details - <?php echo htmlspecialchars($section['name']); ?></title>
    <link rel="stylesheet" href="/myschedule/assets/css/section.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/sidebar.php'; ?>
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Section: <?php echo htmlspecialchars($section['section_name']); ?></h1>
                        </div>
                        <div class="col-sm-6">
                            <a href="/myschedule/public/office/sections.php" class="btn btn-default float-right">
                                <i class="fas fa-arrow-left"></i> Back to Sections
                            </a>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="content">
                <div class="container-fluid">
                    <?php if (empty($schedules)): ?>
                        <div class="alert alert-info">
                            No schedules found for this section.
                        </div>
                    <?php else: ?>
                        <?php 
                        $grouped_schedules = [];
                        foreach ($schedules as $schedule) {
                            $grouped_schedules[$schedule['day']][] = $schedule;
                        }
                        ?>
                        
                        <?php foreach ($grouped_schedules as $day => $day_schedules): ?>
                            <div class="card mb-4">
                                <div class="card-header day-header">
                                    <h3 class="card-title"><?php echo htmlspecialchars($day); ?></h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($day_schedules as $schedule): ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="schedule-card">
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span class="time-badge">
                                                            <?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])); ?>
                                                        </span>
                                                        <span class="badge badge-secondary">
                                                            <?php echo htmlspecialchars($schedule['room_name']); ?>
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
</body>
</html>