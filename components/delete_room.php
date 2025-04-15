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

    // First check if room has any schedules
    $check_stmt = $conn->prepare("SELECT id FROM schedules WHERE room_id = ?");
    $check_stmt->bind_param("i", $room_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Cannot archive room with assigned schedules";
        header("Location: /public/admin/rooms.php");
        exit();
    }

    // Get room data first
    $room_data = $conn->prepare("SELECT * FROM rooms WHERE id = ? AND office_id = ?");
    $room_data->bind_param("ii", $room_id, $office_id);
    $room_data->execute();
    $room = $room_data->get_result()->fetch_assoc();
    
    if ($room) {
        // Archive the room
        $archive_stmt = $conn->prepare("INSERT INTO archived_rooms (office_id, rooms_id, name, created_at) VALUES (?, ?, ?, ?)");
        $archive_stmt->bind_param("iiss", $office_id, $room_id, $room['name'], $room['created_at']);
        
        if ($archive_stmt->execute()) {
            // Now delete from main table
            $delete_stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
            $delete_stmt->bind_param("i", $room_id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['success'] = "Room archived successfully";
            } else {
                $_SESSION['error'] = "Error archiving room: " . $delete_stmt->error;
            }
            $delete_stmt->close();
        } else {
            $_SESSION['error'] = "Error archiving room: " . $archive_stmt->error;
        }
        $archive_stmt->close();
    } else {
        $_SESSION['error'] = "Room not found or you don't have permission";
    }

    $room_data->close();
    $conn->close();
    
    header("Location: /myschedule/public/admin/rooms.php");
    exit();
}
?>