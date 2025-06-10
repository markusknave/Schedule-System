<?php
session_start();
date_default_timezone_set('Asia/Manila');
@include '../../components/links.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$current_day = date('l');
$office_id = $_SESSION['office_id'];

$professors_query = $conn->query("
    SELECT
        t.id AS teacher_id,
        u.id AS user_id,
        u.firstname,
        u.lastname,
        u.extension,
        CONCAT(u.firstname, ' ', u.lastname, IFNULL(CONCAT(' ', u.extension), '')) AS full_name,
        t.unit AS designation,
        u.img AS profile_img,
        u.st_leave,
        u.end_leave,
        r.name AS status_name,
        u.status_id
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN roles r ON u.status_id = r.id
    WHERE t.office_id = $office_id
    AND t.deleted_at IS NULL
    AND u.deleted_at IS NULL
    ORDER BY u.lastname, u.firstname
");
$professors = $professors_query->fetch_all(MYSQLI_ASSOC);

foreach ($professors as &$professor) {
    $teacher_id = $professor['teacher_id'];
    $schedule_query = $conn->query("
        SELECT 
            s.start_time,
            s.end_time,
            sub.subject_code,
            sub.name AS subject_name,
            sec.section_name,
            r.name AS room_name
        FROM schedules s
        LEFT JOIN subjects sub ON s.subject_id = sub.id
        LEFT JOIN sections sec ON s.section_id = sec.id
        LEFT JOIN rooms r ON s.room_id = r.id
        WHERE s.teach_id = $teacher_id
        AND s.day = '$current_day'
        AND s.deleted_at IS NULL
        ORDER BY s.start_time
    ");
    $professor['schedule'] = $schedule_query->fetch_all(MYSQLI_ASSOC);
}

$statusLabels = [
    'A' => ['label' => 'Is In', 'color' => 'green'],
    'OT' => ['label' => 'On Travel', 'color' => 'orange'],
    'B' => ['label' => 'Is Out', 'color' => 'red'],
    'OL' => ['label' => 'On Leave', 'color' => 'blue'],
    'UN' => ['label' => 'Absent', 'color' => 'gray'],
];

$unique_professors = [];
$seen_ids = [];
foreach ($professors as $prof) {
    if (!in_array($prof['teacher_id'], $seen_ids)) {
        $unique_professors[] = $prof;
        $seen_ids[] = $prof['teacher_id'];
    }
}
$professors = $unique_professors;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="60">
    <title>Teachers Schedule Bulletin</title>
    <link rel="stylesheet" href="/myschedule/assets/css/announ.css">
    <style>
        .custom-carousel-item {
            display: none;
        }
        .custom-carousel-item.active {
            display: block;
            animation: fadeIn 1s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        
        <div class="content-wrapper" style="margin-left: 0;">
            <section class="content">
                <div class="container-fluid">
                    <div class="teacher-bulletin">
                        <div class="header">
                            <div class="header-title">
                                <img src="/myschedule/assets/img/favicon.png" alt="Favicon" class="header-favicon-left">
                                <h1>Teachers Schedule Bulletin</h1>
                                <img src="/myschedule/assets/img/favicon2.webp" alt="Favicon" class="header-favicon-right">
                            </div>
                            <h2><?= date('l, F j, Y') ?></h2>
                        </div>
                        
                        <?php if (empty($professors)): ?>
                            <div class="no-teachers">
                                <i class="fas fa-user-graduate fa-3x mb-3"></i>
                                <p>No professors found for this office</p>
                            </div>
                        <?php else: ?>
                            <div class="carousel-container">
                                <div id="professorCarousel">
                                    <div class="carousel-inner">
                                        <?php 
                                        foreach ($professors as $index => $professor): 
                                            $status_name = $professor['status_name'] ?? 'UN';
                                            $status_config = $statusLabels[$status_name] ?? $statusLabels['UN'];
                                            
                                            $imgFound = false;
                                            $imgUrl = '';
                                            if (!empty($professor['profile_img'])) {
                                                $filename = basename($professor['profile_img']);
                                                $filePath = IMAGE_DIR . $filename;
                                                if (file_exists($filePath)) {
                                                    $imgUrl = IMAGE_BASE . UPLOAD_REL_PATH . $filename;
                                                    $imgFound = true;
                                                }
                                            }
                                            
                                            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
                                            $complaint_url = "{$base_url}/myschedule/public/complaints.php?teacher_id={$professor['teacher_id']}";
                                            $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . rawurlencode($complaint_url);
                                        ?>
                                        <div class="custom-carousel-item <?= $index === 0 ? 'active' : '' ?>" 
                                             data-index="<?= $index ?>"
                                             data-teacher-id="<?= $professor['teacher_id'] ?>">
                                            <div class="profile-header">
                                                <div class="profile-info">
                                                    <div class="profile-pic">
                                                        <?php if ($imgFound): ?>
                                                            <img src="<?= htmlspecialchars($imgUrl) ?>" 
                                                                 alt="<?= htmlspecialchars($professor['full_name']) ?>"
                                                                 onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\'fas fa-user\'></i>';">
                                                        <?php else: ?>
                                                            <i class="fas fa-user"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="teacher-details">
                                                        <div class="teacher-name"><?= htmlspecialchars($professor['full_name']) ?></div>
                                                        <div class="teacher-designation"><?= htmlspecialchars($professor['designation']) ?></div>
                                                        <div class="teacher-status">
                                                            <span class="status-label">Status:</span>
                                                            <span class="status-value" style="background-color: <?= $status_config['color'] ?>;">
                                                                <?= $status_config['label'] ?>
                                                            </span>
                                                        </div>
                                                        <?php if (!empty($professor['st_leave']) && !empty($professor['end_leave'])): ?>
                                                            <div class="leave-dates ">
                                                                <?= date('M j, Y', strtotime($professor['st_leave'])) ?> - 
                                                                <?= date('M j, Y', strtotime($professor['end_leave'])) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <img src="<?= $qr_code_url ?>" alt="Complaint QR Code">
                                                    <div class="text-center mt-2 small">Scan to file complaint</div>
                                                </div>
                                            </div>
                                            
                                            <div class="schedule-section">
                                                <h3 class="schedule-header"><?= $current_day ?> Schedule</h3>
                                                
                                                <?php if (!empty($professor['schedule'])): ?>
                                                    <table class="schedule-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Time</th>
                                                                <th>Course Code</th>
                                                                <th>Subject</th>
                                                                <th>Section</th>
                                                                <th>Room</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($professor['schedule'] as $schedule): ?>
                                                                <tr>
                                                                    <td><?= date('g:i A', strtotime($schedule['start_time'])) ?> - <?= date('g:i A', strtotime($schedule['end_time'])) ?></td>
                                                                    <td><?= htmlspecialchars($schedule['subject_code'] ?? 'N/A') ?></td>
                                                                    <td><?= htmlspecialchars($schedule['subject_name'] ?? 'N/A') ?></td>
                                                                    <td><?= htmlspecialchars($schedule['section_name'] ?? 'N/A') ?></td>
                                                                    <td><?= htmlspecialchars($schedule['room_name'] ?? 'N/A') ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                <?php else: ?>
                                                    <div class="no-schedule">
                                                        <i class="far fa-calendar-times fa-2x"></i>
                                                        <p>No schedule for <?= $current_day ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <script>
    $(document).ready(function() {
        let headerTimeout;
        const header = $('.main-header');
        
        function hideHeader() {
            header.addClass('header-hidden');
        }
        
        function showHeader() {
            header.removeClass('header-hidden');
        }
        
        headerTimeout = setTimeout(hideHeader, 5000);
        
        $(document).on('mousemove', function(e) {
            if (e.clientY < 50) {
                showHeader();
                clearTimeout(headerTimeout);
                headerTimeout = setTimeout(hideHeader, 3000);
            }
        });
        
        header.on('mouseenter', function() {
            clearTimeout(headerTimeout);
            showHeader();
        });
        
        header.on('mouseleave', function() {
            headerTimeout = setTimeout(hideHeader, 2000);
        });
    });

    $(window).on('load', function () {
        const items = document.querySelectorAll('.custom-carousel-item');
        const totalItems = items.length;
        
        // console.log("=== Carousel Debug Information ===");
        // console.log("Total DOM elements found:", totalItems);
        
        // items.forEach((item, index) => {
        //     console.log(`Item ${index}:`, {
        //         teacherId: item.getAttribute('data-teacher-id'),
        //         professorName: item.querySelector('.teacher-name').innerText,
        //         isActive: item.classList.contains('active')
        //     });
        // });
        
        // const teacherIds = new Set();
        // items.forEach(item => {
        //     const teacherId = item.getAttribute('data-teacher-id');
        //     teacherIds.add(teacherId);
        //     console.log(`Adding teacher ID to Set: ${teacherId}`);
        // });
        
        // console.log("Unique teacher IDs:", Array.from(teacherIds));
        // console.log("Total unique teachers:", teacherIds.size);
        // console.log("Total carousel items:", totalItems);
        
        let currentIndex = 0;
        
        function cycleItems() {
            items.forEach(item => item.classList.remove('active'));
            
            currentIndex = (currentIndex + 1) % totalItems;
            
            const nextItem = items[currentIndex];
            nextItem.classList.add('active');
            
            // console.log("=== Carousel Cycle ===");
            // console.log("Current Index:", currentIndex);
            // console.log("Teacher ID:", nextItem.getAttribute('data-teacher-id'));
            // console.log("Professor Name:", nextItem.querySelector('.teacher-name').innerText);
        }
        
        if (totalItems > 1) {
            const carouselInterval = setInterval(cycleItems, 10000);
            window.addEventListener('beforeunload', () => clearInterval(carouselInterval));
        }
    });
    </script>
</body>
</html>