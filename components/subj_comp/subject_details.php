<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

// Get subject ID from URL
$subject_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get subject details
$subject_query = $conn->prepare("SELECT subject_code, name FROM subjects WHERE id = ?");
$subject_query->bind_param("i", $subject_id);
$subject_query->execute();
$subject_result = $subject_query->get_result();
$subject = $subject_result->fetch_assoc();

if (!$subject) {
    header("Location: /myschedule/public/admin/subjects.php");
    exit();
}

// Get all teachers teaching this subject
$teachers_query = $conn->prepare("
    SELECT 
        t.id,
        CONCAT(t.firstname, ' ', t.lastname) AS teacher_name,
        o.name AS office_name,
        COUNT(sc.id) AS schedule_count
    FROM teachers t
    JOIN schedules sc ON t.id = sc.teach_id
    JOIN offices o ON t.office_id = o.id
    WHERE sc.subject_id = ?
    GROUP BY t.id, teacher_name, office_name
    ORDER BY teacher_name
");
$teachers_query->bind_param("i", $subject_id);
$teachers_query->execute();
$teachers = $teachers_query->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Details - <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/myschedule/assets/css/subject.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .teacher-card {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
        }
        .teacher-card:hover {
            background-color: #e9ecef;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
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
                            <a href="/myschedule/public/admin/dashboard.php" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Teachers Management</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/myschedule/public/admin/schedule.php" class="nav-link">
                                <i class="nav-icon fa fa-calendar"></i>
                                <p>Schedules</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/myschedule/public/admin/rooms.php" class="nav-link">
                                <i class="nav-icon fas fa-grip-horizontal"></i>
                                <p>Rooms</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/myschedule/public/admin/subjects.php" class="nav-link active">
                                <i class="nav-icon fas fa-book"></i>
                                <p>Subjects</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/myschedule/public/admin/announcements.php" class="nav-link">
                                <i class="nav-icon fa fa-exclamation-circle"></i>
                                <p>Announcements</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/myschedule/public/admin/archived.php" class="nav-link">
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
                            <h1>Subject: <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['name']); ?></h1>
                        </div>
                        <div class="col-sm-6">
                            <a href="/myschedule/public/admin/subjects.php" class="btn btn-default float-right">
                                <i class="fas fa-arrow-left"></i> Back to Subjects
                            </a>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="content">
                <div class="container-fluid">
                    <?php if (empty($teachers)): ?>
                        <div class="alert alert-info">
                            No teachers found for this subject.
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Teachers Teaching This Subject</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($teachers as $teacher): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="teacher-card">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h5 class="mb-0"><?php echo htmlspecialchars($teacher['teacher_name']); ?></h5>
                                                    <span class="badge badge-primary"><?php echo htmlspecialchars($teacher['schedule_count']); ?> class<?php echo $teacher['schedule_count'] > 1 ? 'es' : ''; ?></span>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Office:</strong> <?php echo htmlspecialchars($teacher['office_name']); ?>
                                                </div>
                                                <a href="/myschedule/public/admin/teacher_details.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-user"></i> View Teacher
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</body>
</html>