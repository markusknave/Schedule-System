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

    // First check if teacher has any schedules
    $check_stmt = $conn->prepare("SELECT id FROM schedules WHERE teach_id = ?");
    $check_stmt->bind_param("i", $teacher_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Cannot archive teacher with assigned schedules";
        header("Location: /myschedule/public/admin/dashboard.php");
        exit();
    }

    // Get teacher data first
    $teacher_data = $conn->prepare("SELECT * FROM teachers WHERE id = ? AND office_id = ?");
    $teacher_data->bind_param("ii", $teacher_id, $office_id);
    $teacher_data->execute();
    $teacher = $teacher_data->get_result()->fetch_assoc();
    
    if ($teacher) {
        // Archive the teacher
        $archive_stmt = $conn->prepare("INSERT INTO archived_teachers (original_id) VALUES (?)");
        $archive_stmt->bind_param("i", $teacher_id);
        
        if ($archive_stmt->execute()) {
            // Now delete from main table
            $delete_stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
            $delete_stmt->bind_param("i", $teacher_id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['success'] = "Teacher archived successfully";
            } else {
                $_SESSION['error'] = "Error archiving teacher: " . $delete_stmt->error;
            }
            $delete_stmt->close();
        } else {
            $_SESSION['error'] = "Error archiving teacher: " . $archive_stmt->error;
        }
        $archive_stmt->close();
    } else {
        $_SESSION['error'] = "Teacher not found or you don't have permission";
    }

    $teacher_data->close();
    $conn->close();
    
    header("Location: /myschedule/public/admin/dashboard.php");
    exit();
}
?>