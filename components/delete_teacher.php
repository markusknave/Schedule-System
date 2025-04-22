<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $teacher_id = $_POST['teacher_id'];
    $office_id = $_SESSION['office_id'];

    // First check if teacher exists and belongs to this office
    $teacher_data = $conn->prepare("SELECT * FROM teachers WHERE id = ? AND office_id = ?");
    $teacher_data->bind_param("ii", $teacher_id, $office_id);
    $teacher_data->execute();
    $teacher = $teacher_data->get_result()->fetch_assoc();
    
    if (!$teacher) {
        $_SESSION['error'] = "Teacher not found or you don't have permission";
        header("Location: /myschedule/public/admin/dashboard.php");
        exit();
    }

    // Soft delete the teacher
    $delete_stmt = $conn->prepare("UPDATE teachers SET deleted_at = NOW() WHERE id = ?");
    $delete_stmt->bind_param("i", $teacher_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Teacher archived successfully";
    } else {
        $_SESSION['error'] = "Error archiving teacher: " . $delete_stmt->error;
    }
    
    $delete_stmt->close();
    $conn->close();
    
    header("Location: /myschedule/public/admin/dashboard.php");
    exit();
}
?>