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

$stmt_user = $conn->prepare("SELECT st_leave, end_leave FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();

$st_leave = $user_data['st_leave'] ?? null;
$end_leave = $user_data['end_leave'] ?? null;

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
} else {
    $schedules = [];
    $error = "Teacher not found. Please contact administrator.";
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
        .status-date {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
            text-align: right;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .status-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-label {
            white-space: nowrap;
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
                    <div class="row mb-2 align-items-center">
                        <div class="col-sm-6">
                            <h1 class="m-0">Dashboard</h1>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex flex-column align-items-end">
                                <div class="d-flex align-items-center status-container">
                                    <span class="status-label">Update Current Status:</span>
                                    <select id="statusSelect" class="form-control w-auto"> 
                                        <option value="A" <?= $_SESSION['status'] === 'A' ? 'selected' : '' ?>>Available</option>
                                        <option value="OL" <?= $_SESSION['status'] === 'OL' ? 'selected' : '' ?>>On-Leave</option>
                                        <option value="OT" <?= $_SESSION['status'] === 'OT' ? 'selected' : '' ?>>On-Travel</option>
                                        <option value="B" <?= $_SESSION['status'] === 'B' ? 'selected' : '' ?>>Busy</option>
                                        <option value="UN" <?= $_SESSION['status'] === 'UN' ? 'selected' : '' ?>>Unavailable</option>
                                    </select>
                                </div>
                                <?php if (($_SESSION['status'] === 'OL' || $_SESSION['status'] === 'OT') && $st_leave && $end_leave): ?>
                                    <div class="status-date" style="color: red;">
                                        <?= $_SESSION['status'] === 'OL' ? 'On-Leave' : 'On-Travel' ?>: 
                                        <?= date('M d, Y', strtotime($st_leave)) ?> - <?= date('M d, Y', strtotime($end_leave)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Schedule Section -->
            <section class="content">
                <div class="container-fluid">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                    <?php else: ?>
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
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="dateModal" tabindex="-1" role="dialog" aria-labelledby="dateModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dateModalLabel">Set Leave/Travel Dates</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="dateForm">
                        <div class="form-group">
                            <label for="startDate">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="endDate">End Date</label>
                            <input type="date" class="form-control" id="endDate" name="end_date" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveDates">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        let selectedStatus = null;
        
        $('#statusSelect').change(function() {
            selectedStatus = $(this).val();
            
            if (selectedStatus === 'OL' || selectedStatus === 'OT') {
                $('#dateModal').modal('show');
            } else {
                updateStatus(selectedStatus);
            }
        });

        $('#saveDates').click(function() {
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            
            if (!startDate || !endDate) {
                alert('Please fill both dates');
                return;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('End date must be after start date');
                return;
            }
            
            updateStatus(selectedStatus, startDate, endDate);
            $('#dateModal').modal('hide');
        });

        function updateStatus(status, startDate = null, endDate = null) {
            $.ajax({
                url: '../../components/prof_comp/update_status.php',
                method: 'POST',
                dataType: 'json',
                data: { 
                    status: status,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Request failed: ' + error);
                }
            });
        }
    });
    </script>
</body>
</html>