<?php
session_start();
require '../../components/links.php';
require '../../config.php';

if (!isset($_SESSION['status'])) {
    $_SESSION['status'] = 'UN';
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /myschedule/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if ($teacher) {
    $teacher_id = $teacher['id'];
    $stmt = $conn->prepare("
        SELECT s.day, s.start_time, s.end_time, subj.name AS subject, r.name AS room, sec.section_name 
        FROM schedules s
        JOIN subjects subj ON s.subject_id = subj.id
        JOIN rooms r ON s.room_id = r.id
        LEFT JOIN sections sec ON s.section_id = sec.id
        WHERE s.teach_id = ?
        AND s.deleted_at IS NULL
        ORDER BY FIELD(s.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), s.start_time
    ");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedules = $result->fetch_all(MYSQLI_ASSOC);
}else{
    $schedules = [];
    die("Error: Teacher not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
        <style>

        html, body {
            height: 100%;
            overflow: hidden;
        }
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
        .content {
            min-height: calc(100vh - 200px);
        }

        .table-responsive {
            overflow-x: auto;
        }

    </style>
</head>
<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/teach_sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Dashboard</h1>
                        </div>
                        <div class="col-sm-6">
                            <div class="float-right">
                                <span class="mr-2">Update Current Status:</span>
                                <select id="statusSelect" class="form-control">
                                    <option value="A" <?= $_SESSION['status'] === 'A' ? 'selected' : '' ?>>Available</option>
                                    <option value="OL" <?= $_SESSION['status'] === 'OL' ? 'selected' : '' ?>>On-Leave</option>
                                    <option value="B" <?= $_SESSION['status'] === 'B' ? 'selected' : '' ?>>Busy</option>
                                    <option value="UN" <?= $_SESSION['status'] === 'UN' ? 'selected' : '' ?>>Unavailable</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Schedule Section -->
            <section class="content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Your Schedule</h3>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Time</th>
                                        <th>Subject</th>
                                        <th>Room</th>
                                        <th>Section</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($schedule['day']) ?></td>
                                        <td>
                                            <?= date('h:i A', strtotime($schedule['start_time'])) ?> - 
                                            <?= date('h:i A', strtotime($schedule['end_time'])) ?>
                                        </td>
                                        <td><?= htmlspecialchars($schedule['subject']) ?></td>
                                        <td><?= htmlspecialchars($schedule['room']) ?></td>
                                        <td><?= htmlspecialchars($schedule['section_name'] ?? 'N/A') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#statusSelect').change(function() {
            const newStatus = $(this).val();
            $.ajax({
                url: '../../components/prof_comp/update_status.php',
                method: 'POST',
                data: { status: newStatus },
                success: function(response) {
                    location.reload();
                }
            });
        });
    });
    </script>
</body>
</html>