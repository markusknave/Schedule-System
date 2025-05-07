<?php
session_start();
@include '../../components/links.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

// Get current day
$current_day = date('l'); // e.g. "Monday", "Tuesday", etc.

// Get announcements
$office_id = $_SESSION['office_id'];
$announcements_query = $conn->query("
    SELECT * FROM announcements 
    WHERE office_id = $office_id
    AND deleted_at IS NULL
    ORDER BY created_at DESC
");
$announcements = $announcements_query->fetch_all(MYSQLI_ASSOC);

// Get schedules for current day
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
            ELSE sub.subject_code
        END AS subject_code,
        CASE 
            WHEN t.id IS NULL OR t.deleted_at IS NOT NULL THEN 'TBA'
            WHEN u.id IS NULL OR u.deleted_at IS NOT NULL THEN 'TBA'
            ELSE CONCAT(LEFT(u.firstname, 1), '. ', u.lastname)
        END AS teacher_name,
        CASE
            WHEN sec.id IS NULL OR sec.deleted_at IS NOT NULL THEN 'TBA'
            ELSE sec.section_name
        END AS section_name
    FROM schedules s
    LEFT JOIN teachers t ON s.teach_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN subjects sub ON s.subject_id = sub.id
    LEFT JOIN sections sec ON s.section_id = sec.id
    WHERE s.office_id = $office_id
    AND s.deleted_at IS NULL
    AND s.day = '$current_day'
    ORDER BY 
        CASE WHEN r.name IS NULL THEN 1 ELSE 0 END,
        COALESCE(r.name, 'ZZZ'), 
        s.start_time
");
$schedules = $schedules_query->fetch_all(MYSQLI_ASSOC);

// Group schedules by room
$room_schedules = [];
foreach ($schedules as $schedule) {
    $room_id = ($schedule['room_name'] === 'TBA') ? 'TBA' : $schedule['room_id'];
    if (!isset($room_schedules[$room_id])) {
        $room_schedules[$room_id] = [
            'room_name' => $schedule['room_name'],
            'schedules' => []
        ];
    }
    $room_schedules[$room_id]['schedules'][] = [
        'time' => date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])),
        'subject' => $schedule['subject_code'],
        'teacher' => $schedule['teacher_name'],
        'section' => $schedule['section_name']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements & Schedules</title>
    <style>
        .carousel-item {
            height: 50vh;
            min-height: 300px;
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }
        .carousel-caption {
            position: absolute;
            bottom: 3rem;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            padding: 20px;
            color: white;
            position: absolute;
        }
        
        .announcement-content-container {
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-top: 20px;
            max-height: 300px;
            overflow: hidden;
            position: relative;
        }
        
        .announcement-content {
            white-space: pre-line;
            overflow-y: auto;
            max-height: 300px;
        }
        
        .carousel-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
        }
        
        .pause-btn {
            background-color: rgba(0,0,0,0.5);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
        }
        
        .carousel-title {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .carousel-date {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.8);
        }
        
        .schedule-container {
            margin-top: 0.5rem;
            padding: 20px;
        }
        
        .schedule-header {
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }
        
        .room-schedule {
            margin-bottom: 30px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .room-header {
            background-color: #343a40;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
        }
        
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .schedule-table th {
            background-color: #f8f9fa;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }
        
        .schedule-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .schedule-table tr:last-child td {
            border-bottom: none;
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        
        <div class="content-wrapper" style="margin-left: 0;">
            <section class="content">
                <div class="container-fluid">
                    <!-- Announcements Carousel -->
                    <?php if (!empty($announcements)): ?>
                        <div id="announcementsCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-indicators">
                                <?php foreach ($announcements as $index => $announcement): ?>
                                    <button type="button" data-bs-target="#announcementsCarousel" 
                                        data-bs-slide-to="<?= $index ?>" 
                                        <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?> 
                                        aria-label="Slide <?= $index + 1 ?>"></button>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="carousel-inner">
                                <?php foreach ($announcements as $index => $announcement): ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>" 
                                        style="background-image: url('<?= htmlspecialchars($announcement['img']) ?>')">
                                        <div class="carousel-caption">
                                            <h2 class="carousel-title"><?= htmlspecialchars($announcement['title']) ?></h2>
                                            <div class="carousel-date">
                                                <?= date('F j, Y \a\t g:i A', strtotime($announcement['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Room Schedules -->
                    <div class="schedule-container">
                        <h2 class="schedule-header">Today's Schedules (<?= $current_day ?>)</h2>
                        
                        <?php if (empty($room_schedules)): ?>
                            <div class="alert alert-info">
                                No schedules found for today.
                            </div>
                        <?php else: ?>
                            <?php foreach ($room_schedules as $room): ?>
                                <div class="room-schedule">
                                    <div class="room-header">
                                        <?= htmlspecialchars($room['room_name']) ?>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="schedule-table">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Subject</th>
                                                    <th>Teacher</th>
                                                    <th>Section</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($room['schedules'] as $schedule): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($schedule['time']) ?></td>
                                                        <td><?= htmlspecialchars($schedule['subject']) ?></td>
                                                        <td><?= htmlspecialchars($schedule['teacher']) ?></td>
                                                        <td><?= htmlspecialchars($schedule['section']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <script src="../../assets/js/announcements.js"></script>
    <script>
        // Override the default settings for the carousel
        $(document).ready(function() {
            // Change the pause between slides to 10 seconds (10000ms)
            const pauseBetweenSlides = 10000;
            
            // Initialize carousel with auto-rotate disabled (we'll handle it manually)
            const carousel = new bootstrap.Carousel('#announcementsCarousel', {
                interval: false,
                ride: false
            });
            
            // Function to automatically move to next slide
            function startAutoRotation() {
                setInterval(() => {
                    carousel.next();
                }, pauseBetweenSlides);
            }
            
            // Start the auto rotation
            startAutoRotation();
            
            // Update content container when slide changes
            $('#announcementsCarousel').on('slid.bs.carousel', function() {
                const activeIndex = $('.carousel-item.active').index();
                $('.announcement-content-container').hide();
                $(`#content-${activeIndex}`).show();
            });
        });
    </script>
</body>
</html>