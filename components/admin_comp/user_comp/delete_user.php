<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/public/admin/logger.php';
session_start();
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $teacher_id = $_POST['teacher_id'];

    try {
        // First check if the teacher exists
        $teacher_data = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
        $teacher_data->bind_param("i", $teacher_id);
        $teacher_data->execute();
        $teacher = $teacher_data->get_result()->fetch_assoc();
        
        if (!$teacher) {
            throw new Exception("Teacher not found");
        }

        // Check if user is admin or from the same office
        if ($_SESSION['role'] !== 'admin' && isset($_SESSION['office_id']) && $teacher['office_id'] != $_SESSION['office_id']) {
            throw new Exception("You don't have permission to delete this teacher");
        }

        $delete_stmt = $conn->prepare("UPDATE teachers SET deleted_at = NOW() WHERE id = ?");
        $delete_stmt->bind_param("i", $teacher_id);
        
        if ($delete_stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Teacher archived successfully!";
            log_action('DELETE', "Archived teacher with ID $teacher_id");
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