<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

$office_id = $_SESSION['office_id'];
$schedule_id = $_GET['id'] ?? 0;

// Check if the schedule exists and belongs to the office
$check_query = $conn->query("SELECT id FROM schedules WHERE id = $schedule_id AND office_id = $office_id");
if ($check_query->num_rows === 0) {
    $_SESSION['error_message'] = "Schedule not found or you don't have permission to delete it.";
    header("Location: schedule.php");
    exit();
}

// Perform the hard delete
$delete_query = $conn->query("DELETE FROM schedules WHERE id = $schedule_id");

if ($delete_query) {
    $_SESSION['success_message'] = "Schedule deleted successfully!";
} else {
    $_SESSION['error_message'] = "Error deleting schedule: " . $conn->error;
}

header("Location: /myschedule/public/office/schedule.php");
exit();
?>