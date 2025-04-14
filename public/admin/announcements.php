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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --carousel-height: 70vh;
            --title-font-size: 3.5rem;
            --title-font-size-mobile: 2rem;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .carousel-item {
            height: var(--carousel-height);
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }
        
        .carousel-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.7) 100%);
        }
        
        .carousel-caption {
            position: absolute;
            right: 15%;
            bottom: 20%;
            left: 15%;
            padding: 20px;
            text-align: center;
            background-color: rgba(0,0,0,0.6);
            border-radius: 15px;
            backdrop-filter: blur(5px);
            transform: translateY(20px);
            transition: all 0.5s ease;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .carousel-item.active .carousel-caption {
            transform: translateY(0);
        }
        
        .carousel-title {
            font-size: var(--title-font-size);
            font-weight: 700;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.8);
            margin-bottom: 15px;
            line-height: 1.2;
            color: #fff;
        }
        
        .carousel-date {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.8);
            margin-bottom: 10px;
        }
        
        .carousel-indicators {
            bottom: 30px;
        }
        
        .carousel-indicators button {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin: 0 5px;
            background-color: rgba(255,255,255,0.5);
            border: none;
        }
        
        .carousel-indicators button.active {
            background-color: #fff;
            transform: scale(1.3);
        }
        
        .carousel-control-prev, .carousel-control-next {
            width: 50px;
            height: 50px;
            background-color: rgba(0,0,0,0.3);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        #announcementsCarousel:hover .carousel-control-prev,
        #announcementsCarousel:hover .carousel-control-next {
            opacity: 1;
        }
        
        .carousel-control-prev-icon, 
        .carousel-control-next-icon {
            width: 1.5rem;
            height: 1.5rem;
        }
        
        .add-announcement-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .add-announcement-btn:hover {
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            :root {
                --carousel-height: 60vh;
                --title-font-size: var(--title-font-size-mobile);
            }
            
            .carousel-caption {
                right: 10%;
                left: 10%;
                bottom: 15%;
                padding: 15px;
            }
            
            .carousel-title {
                font-size: 1.8rem;
            }
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
                </li>
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
                        <div id="announcementsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="8000">
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
                                        <div class="carousel-caption">
                                            <h2 class="carousel-title"><?= htmlspecialchars($announcement['title']) ?></h2>
                                            <div class="carousel-date">
                                                <?= date('F j, Y \a\t g:i A', strtotime($announcement['created_at'])) ?>
                                            </div>
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
        // Initialize carousel with 8 second interval
        $('#announcementsCarousel').carousel({
            interval: 8000,
            pause: "hover"
        });
        
        $('#announcementsCarousel').on('slid.bs.carousel', function () {
            $('.carousel-item.active .carousel-caption').css('transform', 'translateY(0)');
        });
    });
    </script>
</body>
</html>