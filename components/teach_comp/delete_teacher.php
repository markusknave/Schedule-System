<?php
session_start();
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['office_id'])) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    header("Location: /myschedule/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $teacher_id = $_POST['teacher_id'];
    $office_id = $_SESSION['office_id'];

    try {
        $teacher_data = $conn->prepare("SELECT * FROM teachers WHERE id = ? AND office_id = ?");
        $teacher_data->bind_param("ii", $teacher_id, $office_id);
        $teacher_data->execute();
        $teacher = $teacher_data->get_result()->fetch_assoc();
        
        if (!$teacher) {
            throw new Exception("Teacher not found or you don't have permission");
        }

        $delete_stmt = $conn->prepare("UPDATE teachers SET deleted_at = NOW() WHERE id = ?");
        $delete_stmt->bind_param("i", $teacher_id);
        
        if ($delete_stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Teacher archived successfully!";
        } else {
            throw new Exception("Error archiving teacher: " . $delete_stmt->error);
        }
        
        $delete_stmt->close();
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        
    }
    
    $conn->close();
    echo json_encode($response);
    exit();
}
?>