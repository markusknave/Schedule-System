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
    $office_id = $_SESSION['office_id'];

    $room_data = $conn->prepare("SELECT * FROM rooms WHERE id = ? AND office_id = ?");
    $room_data->bind_param("ii", $room_id, $office_id);
    $room_data->execute();
    $room = $room_data->get_result()->fetch_assoc();
    
    if (!$room) {
        $_SESSION['error'] = "Room not found or you don't have permission";
        header("Location: /myschedule/public/admin/rooms.php");
        exit();
    }
    
    $delete_stmt = $conn->prepare("UPDATE rooms SET deleted_at = NOW() WHERE id = ?");
    $delete_stmt->bind_param("i", $room_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Room archived successfully";
    } else {
        $_SESSION['error'] = "Error archiving room: " . $delete_stmt->error;
    }
    
    $delete_stmt->close();
    $conn->close();
    
    header("Location: /myschedule/public/admin/rooms.php");
    exit();
}
?>