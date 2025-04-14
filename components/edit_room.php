<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'];
    $name = $_POST['name'];

    // Update query
    $stmt = $conn->prepare("UPDATE rooms SET name=? WHERE id=?");
    $stmt->bind_param("si", $name, $room_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Room updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating room: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: /myschedule/public/admin/rooms.php");
    exit();
}
?>