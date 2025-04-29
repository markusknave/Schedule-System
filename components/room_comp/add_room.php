<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = strtoupper($_POST['name']);
    $office_id = $_SESSION['office_id'];

    // Insert new room
    $stmt = $conn->prepare("INSERT INTO rooms (name, office_id, created_at) VALUES (?, ?, Now())");
    $stmt->bind_param("si", $name, $office_id);
    
    if ($stmt->execute()) {
        header("Location: /myschedule/public/admin/rooms.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>