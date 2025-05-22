<?php
session_start();
require '../../components/links.php';
require '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /myschedule/login.php");
    exit();
}
$announcements = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("
        SELECT a.* 
        FROM announcements a
        JOIN teachers t ON a.office_id = t.office_id
        WHERE t.user_id = ? AND a.deleted_at IS NULL
        ORDER BY a.created_at DESC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $announcements = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
    <style>

        .wrapper {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .content-wrapper {
            flex: 1;
            overflow-y: auto;
            padding-bottom: 20px;
        }
        .carousel-item {
            height: 30vh;
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
            background: rgba(0, 0, 0, 0.48);
            padding: 1rem;
            color: white;
            z-index: 0;
        }
        .announcement-content-container {
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-top: 1rem;
            max-height: 75vh;
            overflow: hidden;
            position: relative;
        }
        .announcement-content {
            white-space: normal;
            overflow-y: auto;
            max-height: 125vh;
        }
        .carousel-controls {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .carousel-indicators button {

            border: 2px solid gray;
            background: transparent;
            margin: 0 5px;
        }

        .carousel-indicators .active {
            background: gray;
        }
        .carousel-title {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .carousel-date {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
        }
        .carousel-control-prev .carousel-control-next {
            z-index: 20;
        }
        .carousel-control-prev-icon, .carousel-control-next-icon {
            
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/teach_sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <h1>Announcements</h1>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <?php if (empty($announcements)): ?>
                        <div class="alert alert-info">
                            <h5><i class="icon fas fa-info"></i> No announcements yet!</h5>
                            There are currently no announcements to display.
                        </div>
                    <?php else: ?>
                        <div id="announcementsCarousel" class="carousel slide" data-bs-ride="carousel">
                            
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
                            
                            <button class="carousel-control-prev bg-transparent" style="border: none;" type="button" data-bs-target="#announcementsCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            </button>
                            <button class="carousel-control-next bg-transparent" style="border: none;" type="button" data-bs-target="#announcementsCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            </button>
                        </div>
                        
                        <?php foreach ($announcements as $index => $announcement): ?>
                            <div class="announcement-content-container" id="content-<?= $index ?>" style="display: <?= $index === 0 ? 'block' : 'none' ?>;">
                                <div class="announcement-content">
                                    <?=$announcement['content'] ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <script src="../../assets/js/announcements.js"></script>
</body>
</html>