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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $subject_code = strtoupper(trim($_POST['subject_code']));
    $name = strtoupper(trim($_POST['name']));
    $office_id = $_SESSION['office_id'];
    $response = ['success' => false, 'message' => ''];
    
    // Validate inputs
    if (empty($subject_code) || empty($name)) {
        $response['message'] = "Subject code and name are required";
        echo json_encode($response);
        exit();
    }

    // Check if another subject already exists with the same code or name (excluding current subject)
    $check = $conn->prepare("SELECT id FROM subjects 
                            WHERE (office_id = ? OR office_id IS NULL) 
                            AND (subject_code = ? OR name = ?) 
                            AND id != ?");
    if (!$check) {
        $response['message'] = "Database error: " . $conn->error;
        echo json_encode($response);
        exit();
    }
    
    $check->bind_param("issi", $office_id, $subject_code, $name, $subject_id);
    if (!$check->execute()) {
        $response['message'] = "Database error: " . $check->error;
        echo json_encode($response);
        exit();
    }
    
    $result = $check->get_result();
    $check->close();

    if ($result->num_rows > 0) {
        $response['message'] = "Another subject with this code or name already exists";
        echo json_encode($response);
        exit();
    }

    // Update query
    $stmt = $conn->prepare("UPDATE subjects SET subject_code=?, name=? WHERE id=?");
    if (!$stmt) {
        $response['message'] = "Database error: " . $conn->error;
        echo json_encode($response);
        exit();
    }
    
    $stmt->bind_param("ssi", $subject_code, $name, $subject_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Subject updated successfully!";
    } else {
        $response['message'] = "Error updating subject: " . $stmt->error;
    }

    $stmt->close();
    echo json_encode($response);
    exit();
}
?>