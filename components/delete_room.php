<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $room_id = $_POST['room_id'];

    // First check if room has any schedules
    $check_stmt = $conn->prepare("SELECT id FROM schedules WHERE room_id = ?");
    $check_stmt->bind_param("i", $room_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Cannot delete room with assigned schedules";
        header("Location: /public/admin/rooms.php");
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Room deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting room: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    
    header("Location: /myschedule/public/admin/rooms.php");
    exit();
}
?>