<?php
session_start();
@include '../../components/links.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

$office_id = $_SESSION['office_id'];
$schedule_id = $_GET['id'] ?? 0;

// Fetch the schedule to edit
$schedule_query = $conn->query("
    SELECT * FROM schedules 
    WHERE id = $schedule_id AND office_id = $office_id AND deleted_at IS NULL
");
$schedule = $schedule_query->fetch_assoc();

if (!$schedule) {
    $_SESSION['error_message'] = "Schedule not found or you don't have permission to edit it.";
    header("Location: schedule.php");
    exit();
}

// Fetch available rooms
$rooms_query = $conn->query("SELECT id, name FROM rooms WHERE office_id = $office_id AND deleted_at IS NULL");
$rooms = $rooms_query->fetch_all(MYSQLI_ASSOC);

// Fetch available teachers
$teachers_query = $conn->query("
    SELECT t.id, u.firstname, u.lastname 
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE t.office_id = $office_id AND t.deleted_at IS NULL AND u.deleted_at IS NULL
");
$teachers = $teachers_query->fetch_all(MYSQLI_ASSOC);

// Fetch available subjects
$subjects_query = $conn->query("SELECT id, subject_code FROM subjects WHERE office_id = $office_id AND deleted_at IS NULL");
$subjects = $subjects_query->fetch_all(MYSQLI_ASSOC);

// Fetch available sections
$sections_query = $conn->query("SELECT id, section_name FROM sections WHERE office_id = $office_id AND deleted_at IS NULL");
$sections = $sections_query->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $day = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room_id = !empty($_POST['room_id']) ? $_POST['room_id'] : NULL;
    $teach_id = $_POST['teach_id'];
    $subject_id = $_POST['subject_id'];
    $section_id = $_POST['section_id'];
    
    $stmt = $conn->prepare("
        UPDATE schedules 
        SET day = ?, start_time = ?, end_time = ?, room_id = ?, teach_id = ?, subject_id = ?, section_id = ?
        WHERE id = ? AND office_id = ?
    ");
    $stmt->bind_param("ssssiiiii", $day, $start_time, $end_time, $room_id, $teach_id, $subject_id, $section_id, $schedule_id, $office_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Schedule updated successfully!";
        header("Location: /myschedule/public/office/schedule.php");
        exit();
    } else {
        $error = "Error updating schedule: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule</title>
    <link rel="stylesheet" href="/myschedule/assets/css/schedule.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/sidebar.php'; ?>
        
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Edit Schedule</h1>
                        </div>
                        <div class="col-sm-6">
                            <a href="schedule.php" class="btn btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back to Schedules
                            </a>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="content">
                <div class="container-fluid">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Schedule Information</h3>
                        </div>
                        <form method="POST">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="day">Day</label>
                                    <select class="form-control" id="day" name="day" required>
                                        <option value="">Select Day</option>
                                        <option value="Monday" <?php echo $schedule['day'] === 'Monday' ? 'selected' : ''; ?>>Monday</option>
                                        <option value="Tuesday" <?php echo $schedule['day'] === 'Tuesday' ? 'selected' : ''; ?>>Tuesday</option>
                                        <option value="Wednesday" <?php echo $schedule['day'] === 'Wednesday' ? 'selected' : ''; ?>>Wednesday</option>
                                        <option value="Thursday" <?php echo $schedule['day'] === 'Thursday' ? 'selected' : ''; ?>>Thursday</option>
                                        <option value="Friday" <?php echo $schedule['day'] === 'Friday' ? 'selected' : ''; ?>>Friday</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                <label for="section_id">Section</label>
                                <select class="form-control" id="section_id" name="section_id" required>
                                    <option value="">Select Section</option>
                                    <?php foreach ($sections as $section): ?>
                                        <option value="<?php echo $section['id']; ?>"
                                            <?php echo $schedule['section_id'] == $section['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($section['section_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                                
                                <div class="form-group">
                                    <label for="start_time">Start Time</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" 
                                           value="<?php echo htmlspecialchars(substr($schedule['start_time'], 0, 5)); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="end_time">End Time</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" 
                                           value="<?php echo htmlspecialchars(substr($schedule['end_time'], 0, 5)); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="room_id">Room (Optional)</label>
                                    <select class="form-control" id="room_id" name="room_id">
                                        <option value="">Select Room (Optional)</option>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?php echo $room['id']; ?>" 
                                                <?php echo $schedule['room_id'] == $room['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($room['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="teach_id">Teacher</label>
                                    <select class="form-control" id="teach_id" name="teach_id" required>
                                        <option value="">Select Teacher</option>
                                        <?php foreach ($teachers as $teacher): ?>
                                            <option value="<?php echo $teacher['id']; ?>"
                                                <?php echo $schedule['teach_id'] == $teacher['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($teacher['firstname'] . ' ' . $teacher['lastname']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="subject_id">Subject</label>
                                    <select class="form-control" id="subject_id" name="subject_id" required>
                                        <option value="">Select Subject</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?php echo $subject['id']; ?>"
                                                <?php echo $schedule['subject_id'] == $subject['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($subject['subject_code']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Update Schedule</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
