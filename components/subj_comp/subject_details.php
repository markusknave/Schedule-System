<?php
session_start();
@include '../../components/links.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$subject_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$subject_query = $conn->prepare("SELECT subject_code, name FROM subjects WHERE id = ?");
$subject_query->bind_param("i", $subject_id);
$subject_query->execute();
$subject_result = $subject_query->get_result();
$subject = $subject_result->fetch_assoc();

if (!$subject) {
    header("Location: /myschedule/public/office/subjects.php");
    exit();
}

$teachers_query = $conn->prepare("
    SELECT 
        t.id,
        CONCAT(u.firstname, ' ', u.lastname) AS teacher_name,
        o.name AS office_name,
        COUNT(sc.id) AS schedule_count
    FROM teachers t
    JOIN users u ON t.user_id = u.id
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
    <link rel="stylesheet" href="/myschedule/assets/css/subject.css">
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
    <?php include '../../components/header.php'; ?>
    <?php include '../../components/sidebar.php'; ?>
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Subject: <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['name']); ?></h1>
                        </div>
                        <div class="col-sm-6">
                            <a href="/myschedule/public/office/subjects.php" class="btn btn-default float-right">
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