<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $subject_id = $_POST['subject_id'];

    $subject_data = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
    $subject_data->bind_param("i", $subject_id);
    $subject_data->execute();
    $subject = $subject_data->get_result()->fetch_assoc();
    
    if (!$subject) {
        $_SESSION['error'] = "Subject not found";
        header("Location: /myschedule/public/admin/subjects.php");
        exit();
    }
    
    $delete_stmt = $conn->prepare("UPDATE subjects SET deleted_at = NOW() WHERE id = ?");
    $delete_stmt->bind_param("i", $subject_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Subject archived successfully";
    } else {
        $_SESSION['error'] = "Error archiving subject: " . $delete_stmt->error;
    }
    
    $delete_stmt->close();
    $conn->close();
    
    header("Location: /myschedule/public/admin/subjects.php");
    exit();
}
?>