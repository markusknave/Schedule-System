<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

if (!isset($_SESSION['office_id'])) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit();
}

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $subject_id = $_POST['subject_id'];

        try{
        $subject_data = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
        $subject_data->bind_param("i", $subject_id);
        $subject_data->execute();
        $subject = $subject_data->get_result()->fetch_assoc();
        
        if (!$subject) {
            throw new Exception("Subject not found or you don't have permission");
        }
        
        $delete_stmt = $conn->prepare("UPDATE subjects SET deleted_at = NOW() WHERE id = ?");
        $delete_stmt->bind_param("i", $subject_id);
        
        if ($delete_stmt->execute()) {
            $response['success'] = true;
        } else {
            throw new Exception("Error archiving subject: " . $delete_stmt->error);
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