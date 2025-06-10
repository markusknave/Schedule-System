<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/public/admin/logger.php';
session_start();
@include '../../components/links.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.php");
    exit();
}

$office_id = $_SESSION['office_id'];
$schedule_id = $_GET['id'] ?? 0;

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


$rooms_query = $conn->query("SELECT id, name FROM rooms WHERE office_id = $office_id AND deleted_at IS NULL");
$rooms = $rooms_query->fetch_all(MYSQLI_ASSOC);

$teachers_query = $conn->query("
    SELECT t.id, u.firstname, u.lastname 
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE t.office_id = $office_id AND t.deleted_at IS NULL AND u.deleted_at IS NULL
");
$teachers = $teachers_query->fetch_all(MYSQLI_ASSOC);

$subjects_query = $conn->query("SELECT id, subject_code FROM subjects WHERE office_id = $office_id AND deleted_at IS NULL");
$subjects = $subjects_query->fetch_all(MYSQLI_ASSOC);

$sections_query = $conn->query("SELECT id, section_name FROM sections WHERE office_id = $office_id AND deleted_at IS NULL");
$sections = $sections_query->fetch_all(MYSQLI_ASSOC);
$form_data = $_SESSION['form_data'] ?? [];
$error = $_SESSION['error'] ?? null;
$conflict_fields = $_SESSION['conflict_fields'] ?? [];

unset($_SESSION['form_data'], $_SESSION['error'], $_SESSION['conflict_fields']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $day = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room_id = !empty($_POST['room_id']) ? $_POST['room_id'] : NULL;
    $teach_id = $_POST['teach_id'];
    $subject_id = $_POST['subject_id'];
    $section_id = $_POST['section_id'];
    
    $conflictQuery = "SELECT s.*, r.name AS room_name, sec.section_name, sub.subject_code, u.firstname, u.lastname 
                    FROM schedules s
                    LEFT JOIN rooms r ON s.room_id = r.id
                    LEFT JOIN sections sec ON s.section_id = sec.id
                    LEFT JOIN subjects sub ON s.subject_id = sub.id
                    LEFT JOIN teachers t ON s.teach_id = t.id
                    LEFT JOIN users u ON t.user_id = u.id
                    WHERE s.office_id = ? AND s.id != ? AND s.deleted_at IS NULL AND (";

    $params = [$office_id, $schedule_id];
    $types = 'ii';
    $conditions = [];

    if ($room_id !== NULL) {
        $conditions[] = "(s.room_id = ? AND s.day = ? AND s.start_time < ? AND s.end_time > ?)";
        $types .= 'isss';
        array_push($params, $room_id, $day, $end_time, $start_time);
    }

    $conditions[] = "(s.section_id = ? AND s.day = ? AND s.start_time < ? AND s.end_time > ?)";
    $types .= 'isss';
    array_push($params, $section_id, $day, $end_time, $start_time);

    $conditions[] = "(s.teach_id = ? AND s.day = ? AND s.start_time < ? AND s.end_time > ?)";
    $types .= 'isss';
    array_push($params, $teach_id, $day, $end_time, $start_time);

    $conflictQuery .= implode(' OR ', $conditions) . ")";

    $stmt_check = $conn->prepare($conflictQuery);
    $stmt_check->bind_param($types, ...$params);
    $stmt_check->execute();
    $conflicting_schedules = $stmt_check->get_result()->fetch_all(MYSQLI_ASSOC);

    if (!empty($conflicting_schedules)) {
        $error_messages = [];
        $conflict_fields = ['day' => false, 'start_time' => false, 'end_time' => false, 'room' => false, 'section' => false, 'teacher' => false];

        foreach ($conflicting_schedules as $conflict) {
            if ($room_id !== NULL && $conflict['room_id'] == $room_id) {
                $conflict_fields['room'] = true;
            }
            if ($conflict['section_id'] == $section_id) {
                $conflict_fields['section'] = true;
            }
            if ($conflict['teach_id'] == $teach_id) {
                $conflict_fields['teacher'] = true;
            }
            $conflict_fields['day'] = $conflict_fields['start_time'] = $conflict_fields['end_time'] = true;

            $room_name = $conflict['room_name'] ?? 'TBA';
            $error_messages[] = "Conflict with: {$room_name}, {$conflict['day']} ({$conflict['start_time']}-{$conflict['end_time']}), Section {$conflict['section_name']}, {$conflict['subject_code']} by {$conflict['firstname']} {$conflict['lastname']}";
        }

        $_SESSION['error'] = implode("<br>", $error_messages);
        $_SESSION['conflict_fields'] = $conflict_fields;
        $_SESSION['form_data'] = $_POST;
        header("Location: edit_schedule.php?id=$schedule_id");
        exit();
    } else {
        $stmt = $conn->prepare("
            UPDATE schedules 
            SET day = ?, start_time = ?, end_time = ?, room_id = ?, teach_id = ?, subject_id = ?, section_id = ?
            WHERE id = ? AND office_id = ?
        ");
        $stmt->bind_param("ssssiiiii", $day, $start_time, $end_time, $room_id, $teach_id, $subject_id, $section_id, $schedule_id, $office_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Schedule updated successfully!";
            log_action('UPDATE', "Updated schedule with ID $schedule_id for office $office_id");
            header("Location: /myschedule/public/office/schedule.php");
            exit();
        } else {
            $error = "Error updating schedule: " . $conn->error;
        }
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
                            <a href="../../public/office/schedule.php" class="btn btn-secondary float-right">
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
                                        <option value="Saturday" <?php echo $schedule['day'] === 'Saturday' ? 'selected' : ''; ?>>Saturday</option>
                                        <option value="Sunday" <?php echo $schedule['day'] === 'Sunday' ? 'selected' : ''; ?>>Sunday</option>
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
