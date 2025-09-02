<?php
session_start();
date_default_timezone_set('Asia/Manila');
@include '../../components/links.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: ../../login.php");
    exit();
}

require_once '../../config.php';
require_once '../../constants.php';

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

$office_announcements = [];
$office_ann_query = $conn->query("
    SELECT a.id, a.title, a.content, a.created_at,
           COALESCE(o.name, CONCAT(u.firstname, ' ', u.lastname)) AS source_name
    FROM announcements a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN teachers t_post ON a.user_id = t_post.user_id
    LEFT JOIN offices o ON a.office_id = o.id
    WHERE a.office_id = $office_id
      AND a.deleted_at IS NULL
      -- exclude announcements posted by teachers (professors)
      AND (a.user_id IS NULL OR t_post.user_id IS NULL)
    ORDER BY a.created_at DESC
");
while ($row = $office_ann_query->fetch_assoc()) {
    $office_announcements[] = $row;
}

foreach ($professors as &$professor) {
    $user_id = (int)$professor['user_id'];
    $prof_ann_query = $conn->query("
        SELECT a.id, a.title, a.content, a.created_at
        FROM announcements a
        WHERE a.user_id = $user_id
        AND a.deleted_at IS NULL
        ORDER BY a.created_at DESC
    ");
    $professor['announcements'] = $prof_ann_query->fetch_all(MYSQLI_ASSOC);
}

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

function safeHtml($content) {
    return strip_tags(
        $content,
        '<p><br><strong><em><u><span><blockquote><ul><ol><li><h1><h2><h3><h4><h5><h6>'
    );
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="refresh" content="60">
<title>Teachers Schedule Bulletin</title>
<link rel="stylesheet" href="../../assets/css/announ.css">
<style>
.custom-carousel-item { display: none; }
.custom-carousel-item.active { display: block; animation: fadeIn 400ms; }
@keyframes fadeIn { from{opacity:0} to{opacity:1} }

.announcement-section { margin-top: 15px; padding: 10px; border-top: 1px solid #ccc; }
.announcement-container { max-height: 150px; overflow: hidden; position: relative; }
.announcement-display { padding: 10px;
.announcement-inner { will-change: transform; } 

.announcement-source { display: none; }
.office-announcement { padding: 20px; text-align: center; }
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
                            <img src="../../assets/img/favicon.png" alt="Favicon" class="header-favicon-left">
                            <h1>Teachers Schedule Bulletin</h1>
                            <img src="../../assets/img/favicon2.webp" alt="Favicon" class="header-favicon-right">
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
                                <?php foreach ($professors as $index => $professor):
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

                                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                                    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                                    socket_connect($socket, "8.8.8.8", 80);
                                    socket_getsockname($socket, $hostIp);
                                    socket_close($socket);
                                    $rootFolder = trim(strtok($_SERVER['SCRIPT_NAME'], '/'), '/');
                                    $base_url = $protocol . "://" . $hostIp . "/" . $rootFolder;
                                    $complaint_url = "{$base_url}/public/complaints.php?teacher_id={$professor['teacher_id']}";
                                    $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . rawurlencode($complaint_url);
                                ?>
                                <div class="custom-carousel-item <?= $index === 0 ? 'active' : '' ?>"
                                     data-index="<?= $index ?>" data-teacher-id="<?= $professor['teacher_id'] ?>">
                                    <div class="profile-header">
                                        <div class="profile-info">
                                            <div class="profile-pic">
                                                <?php if ($imgFound): ?>
                                                    <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($professor['full_name']) ?>"
                                                         onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\'fas fa-user\'></i>'; ">
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
                                                        <th>Time</th><th>Course Code</th><th>Subject</th><th>Section</th><th>Room</th>
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

                                    <div class="announcement-section">
                                        <h3>Professor Announcements</h3>
                                        <div class="announcement-container">
                                            <div class="announcement-display">
                                                <div class="announcement-inner">
                                                    <p>Loading professor announcements...</p>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if (!empty($professor['announcements'])): ?>
                                            <?php foreach ($professor['announcements'] as $ann): ?>
                                                <div class="announcement-source">
                                                    <?= safeHtml($ann['title']) ?>
                                                    <?= safeHtml($ann['content']) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>

                                <div class="custom-carousel-item" data-index="<?= count($professors) ?>" data-office="1">
                                    <div class="office-announcement">
                                        <h2>Office Announcements</h2>
                                        <div class="announcement-container">
                                            <div class="announcement-display">
                                                <div class="announcement-inner">
                                                    <p>Loading office announcements...</p>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if (!empty($office_announcements)): ?>
                                            <?php foreach ($office_announcements as $oAnn): ?>
                                                <div class="announcement-source">
                                                    <?= safeHtml($oAnn['title']) ?>
                                                    <?= safeHtml($oAnn['content']) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

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
    function hideHeader() { header.addClass('header-hidden'); }
    function showHeader() { header.removeClass('header-hidden'); }
    headerTimeout = setTimeout(hideHeader, 5000);
    $(document).on('mousemove', function(e) {
        if (e.clientY < 50) {
            showHeader();
            clearTimeout(headerTimeout);
            headerTimeout = setTimeout(hideHeader, 3000);
        }
    });
    header.on('mouseenter', function() { clearTimeout(headerTimeout); showHeader(); });
    header.on('mouseleave', function() { headerTimeout = setTimeout(hideHeader, 2000); });
});

(function () {
    const DEFAULT_TIME = 8000; 
    const STAY_BEFORE = 900;   
    const STAY_AFTER  = 8000;   
    const PIXEL_MULT  = 60;    

    function sleep(ms) { return new Promise(res => setTimeout(res, ms)); }

    async function showWithScroll(displayInner, container) {
        displayInner.style.transform = 'translateY(0)';
        const scrollH = displayInner.scrollHeight;
        const clientH = container.clientHeight;

        if (scrollH <= clientH) {
            await sleep(DEFAULT_TIME);
            return;
        }

        const distance = scrollH - clientH;
        const scrollDuration = Math.max(300, Math.round(distance * PIXEL_MULT));

        await sleep(STAY_BEFORE);

        try {
            if (displayInner._runningAnimation) {
                displayInner._runningAnimation.cancel();
                displayInner._runningAnimation = null;
            }
            const anim = displayInner.animate(
                [
                    { transform: 'translateY(0)' },
                    { transform: `translateY(-${distance}px)` }
                ],
                { duration: scrollDuration, easing: 'linear', fill: 'forwards' }
            );
            displayInner._runningAnimation = anim;
            await anim.finished;
            displayInner._runningAnimation = null;
        } catch (e) {
            displayInner.style.transform = `translateY(-${distance}px)`;
            await sleep(scrollDuration);
        }

        await sleep(STAY_AFTER);
        displayInner.style.transform = 'translateY(0)';
    }

    async function playAnnouncementsForSlide(slide) {
        const display = slide.querySelector('.announcement-display');
        const container = slide.querySelector('.announcement-container');
        const inner = display.querySelector('.announcement-inner');
        const sources = Array.from(slide.querySelectorAll('.announcement-source'));

        if (!inner) return;

        if (!sources || sources.length === 0) {
            inner.innerHTML = '<p>No announcements available</p>';
            await sleep(DEFAULT_TIME);
            return;
        }

        for (let src of sources) {
            inner.innerHTML = src.innerHTML;
            await new Promise(r => requestAnimationFrame(r));
            await showWithScroll(inner, container);
        }
    }

    async function startCarousel() {
        const slides = Array.from(document.querySelectorAll('.custom-carousel-item'));
        if (!slides.length) return;
        let idx = slides.findIndex(s => s.classList.contains('active'));
        if (idx === -1) idx = 0;

        while (true) {
            slides.forEach(s => s.classList.remove('active'));
            const current = slides[idx];
            if (!current) break;
            current.classList.add('active');

            try {
                await playAnnouncementsForSlide(current);
            } catch (err) {
                console.error('Carousel playback error:', err);
                await sleep(DEFAULT_TIME);
            }

            idx = (idx + 1) % slides.length;
        }
    }

    window.addEventListener('load', function () {
        startCarousel();
    });
})();
</script>
</body>
</html>
