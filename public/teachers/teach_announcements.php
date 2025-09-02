<?php
session_start();
require '../../components/links.php';
require '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$announcements = [];

if (isset($_SESSION['user_id'])) {
    // Get office announcements (from the user's office)
    $stmt = $conn->prepare("
        SELECT a.*, 'office' as source_type
        FROM announcements a
        JOIN teachers t ON a.office_id = t.office_id
        WHERE t.user_id = ? AND a.deleted_at IS NULL AND a.office_id IS NOT NULL
        ORDER BY a.created_at DESC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $office_announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get user announcements (created by the user themselves)
    $stmt = $conn->prepare("
        SELECT a.*, 'user' as source_type
        FROM announcements a
        WHERE a.user_id = ? AND a.deleted_at IS NULL AND a.user_id IS NOT NULL
        ORDER BY a.created_at DESC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user_announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Combine and sort by creation date
    $announcements = array_merge($office_announcements, $user_announcements);
    usort($announcements, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
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
        .announcement-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .badge-office {
            background-color: #007bff;
        }
        .badge-user {
            background-color: #28a745;
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
                                        
                                        <!-- Announcement type badge -->
                                        <div class="announcement-badge <?= $announcement['source_type'] === 'office' ? 'badge-office' : 'badge-user' ?>">
                                            <?= $announcement['source_type'] === 'office' ? 'Office' : 'Personal' ?>
                                        </div>
                                        
                                        <!-- Edit/Delete buttons for user announcements -->
                                        <?php if ($announcement['source_type'] === 'user'): ?>
                                            <div class="edit-btn-container" style="position: absolute; top: 20px; right: 20px; z-index: 10;">
                                                <a href="../../components/announ_comp/edit_user_announcement.php?id=<?= $announcement['id'] ?>" 
                                                   class="btn btn-sm btn-success" style="margin-right: 5px;">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="confirmDelete(<?= $announcement['id'] ?>)">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        
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
        
        <!-- Create Announcement Button -->
        <a href="../../components/announ_comp/create_user_announcement.php" 
            class="btn btn-primary btn-lg"
            style="position: fixed; bottom: 2.5rem; right: 2.5rem; z-index: 1000; height: 2.5rem; border-radius: 50%; font-size: 24px;">
                <i class="fas fa-plus"></i>
        </a>
    </div>

    <script src="../../assets/js/announcements.js"></script>
    
    <script>
        function confirmDelete(announcementId) {
            if (confirm('Are you sure you want to delete this announcement? This action cannot be undone.')) {
                window.location.href = '../../components/announ_comp/delete_user_announcement.php?id=' + announcementId;
            }
        }
    </script>
</body>
</html>