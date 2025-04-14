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

// Fetch all announcements for this office
$office_id = $_SESSION['office_id'];
$announcements_query = $conn->query("
    SELECT * FROM announcements 
    WHERE office_id = $office_id 
    ORDER BY created_at DESC
");
$announcements = $announcements_query->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/myschedule/assets/css/announcements.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .carousel-item {
            height: 40vh;
            min-height: 300px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .carousel-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            padding: 20px;
            color: white;
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
            font-size: 1rem;
            opacity: 0.8;
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
        
        
        /* Adjust carousel controls to not overlap */
        .carousel-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
        }
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
                            <a href="schedule.php" class="nav-link">
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
                            <a href="announcements.php" class="nav-link active">
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
                            <!-- Indicators -->
                            <div class="carousel-indicators">
                                <?php foreach ($announcements as $index => $announcement): ?>
                                    <button type="button" data-bs-target="#announcementsCarousel" 
                                        data-bs-slide-to="<?= $index ?>" 
                                        <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?> 
                                        aria-label="Slide <?= $index + 1 ?>"></button>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Slides -->
                            <div class="carousel-inner">
                                <?php foreach ($announcements as $index => $announcement): ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>" 
                                        style="background-image: url('<?= htmlspecialchars($announcement['img']) ?>')">
                                        <div class="edit-btn-container">
                                        <form action="edit_announcement.php" method="GET" style="display: inline;">
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
                            
                            <!-- Controls -->
                            <button class="carousel-control-prev" type="button" data-bs-target="#announcementsCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#announcementsCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden"></span>
                            </button>
                        </div>
                        
                        <!-- Content for each announcement (hidden until active) -->
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
        
        <!-- Add Announcement Button -->
        <a href="create_announcement.php" class="btn btn-primary btn-lg add-announcement-btn">
            <i class="fas fa-plus"></i>
        </a>
    </div>

    <script src="/myschedule/assets/js/loading_screen.js"></script>
    <script>
$(document).ready(function() {
    // Variables for auto-scroll control
    let autoScrollEnabled = true;
    let scrollInterval;
    const scrollSpeed = 30; // pixels per second
    const pauseBetweenSlides = 3000; // 3 seconds
    
    // Initialize carousel without autoplay
    const carousel = new bootstrap.Carousel('#announcementsCarousel', {
        interval: false,
        ride: false
    });
    
    // Function to start auto-scrolling for current content
    function startAutoScroll() {
        const activeIndex = $('.carousel-item.active').index();
        const contentElement = $(`#scroll-content-${activeIndex}`);
        const containerHeight = contentElement.parent().height();
        const contentHeight = contentElement[0].scrollHeight;
        
        // Reset scroll position
        contentElement.scrollTop(0);
        
        // Clear any existing interval
        clearInterval(scrollInterval);
        
        // Calculate total scroll time (in ms)
        const scrollTime = ((contentHeight - containerHeight) / scrollSpeed) * 1000;
        
        // Start scrolling
        if (contentHeight > containerHeight) {
            const startTime = Date.now();
            
            scrollInterval = setInterval(() => {
                if (!autoScrollEnabled) return;
                
                const elapsed = Date.now() - startTime;
                const progress = Math.min(elapsed / scrollTime, 1);
                const scrollPosition = progress * (contentHeight - containerHeight);
                
                contentElement.scrollTop(scrollPosition);
                
                // When we reach the bottom, pause then go to next slide
                if (progress >= 1) {
                    clearInterval(scrollInterval);
                    setTimeout(() => {
                        if (autoScrollEnabled) {
                            carousel.next();
                        }
                    }, pauseBetweenSlides);
                }
            }, 16); // ~60fps
        } else {
            // Content fits without scrolling, just pause then move to next
            setTimeout(() => {
                if (autoScrollEnabled) {
                    carousel.next();
                }
            }, pauseBetweenSlides + 2000); // Extra time for reading
        }
    }
    
    // Toggle auto-scroll
    window.toggleAutoScroll = function() {
        autoScrollEnabled = !autoScrollEnabled;
        $('.pause-btn i').toggleClass('fa-pause fa-play');
        
        if (autoScrollEnabled) {
            startAutoScroll();
        } else {
            clearInterval(scrollInterval);
        }
    };
    
    // Start auto-scroll when slide changes
    $('#announcementsCarousel').on('slid.bs.carousel', function() {
        // Show the corresponding content
        const activeIndex = $('.carousel-item.active').index();
        $('.announcement-content-container').hide();
        $(`#content-${activeIndex}`).show();
        
        if (autoScrollEnabled) {
            startAutoScroll();
        }
    });
    
    // Start auto-scroll for initial slide
    startAutoScroll();
});

function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this announcement?')) {
        window.location.href = '/myschedule/public/admin/delete_announcement.php?id=' + id;
    }
}
    </script>
</body>
</html>