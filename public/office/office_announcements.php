<?php
session_start();
@include '../../components/links.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/components/login.html");
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$office_id = $_SESSION['office_id'];
$announcements_query = $conn->query("
    SELECT * FROM announcements 
    WHERE office_id = $office_id
    AND deleted_at IS NULL
    ORDER BY created_at DESC
");
$announcements = $announcements_query->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>

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
        .edit-btn-container {
        position: absolute;
        top: 20px;
        left: 20px;
        z-index: 10;
        }
        
        .edit-btn, .del-btn {
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .edit-btn:hover, .del-btn:hover {
            background-color: rgba(56, 56, 56, 0.9);
            color: white;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<?php ?>
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/office_sidebar.php'; ?>
        
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Announcements</h1>
                        </div>
                    </div>
                </div>
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
                                        <div class="edit-btn-container">
                                        <form action="/myschedule/components/office_announ/edit_announcement.php" method="GET" style="display: inline;">
                                                <input type="hidden" name="id" value="<?= $announcement['id'] ?>">
                                                <button type="submit" class="edit-btn">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            </form>
                                            <button class="del-btn ml-1" onclick="confirmDelete(<?= $announcement['id'] ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <div class="carousel-caption">
                                            <h2 class="carousel-title"><?= htmlspecialchars($announcement['title']) ?></h2>
                                            <div class="carousel-date">
                                                <?= date('F j, Y \a\t g:i A', strtotime($announcement['created_at'])) ?>
                                            </div>
                                        </div>
                                        <div class="carousel-controls">
                                            <button class="pause-btn" onclick="toggleAutoScroll()">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button class="carousel-control-prev bg-transparent" style="border: none; background-color: transparent;"" type="button" data-bs-target="#announcementsCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden"></span>
                            </button>
                            <button class="carousel-control-next bg-transparent" style="border: none; background-color: transparent;"" type="button" data-bs-target="#announcementsCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden"></span>
                            </button>
                        </div>
                        
                        <?php foreach ($announcements as $index => $announcement): ?>
                            <div class="announcement-content-container" id="content-<?= $index ?>" style="display: <?= $index === 0 ? 'block' : 'none' ?>;">
                                <div class="announcement-content" id="scroll-content-<?= $index ?>">
                                    <?= $announcement['content'] ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        
        <a href="/myschedule/components/office_announ/create_announcement.php" 
            class="btn btn-primary btn-lg "
            style="position: fixed; bottom: 2.5rem; right: 2.5rem; z-index: 1000; height: 2.5rem; border-radius: 50%; font-size: 24px;">
                <i class="fas fa-plus"></i>
        </a>
    </div>
    <script src="../../assets/js/announcements.js"></script>
</body>
</html>