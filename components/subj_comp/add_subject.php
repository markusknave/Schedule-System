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

    // Check if subject already exists (including NULL office_id subjects)
    $check = $conn->prepare("SELECT id FROM subjects WHERE (office_id = ? OR office_id IS NULL) AND (subject_code = ? OR name = ?)");
    if (!$check) {
        $response['message'] = "Database error: " . $conn->error;
        echo json_encode($response);
        exit();
    }
    
    $check->bind_param("iss", $office_id, $subject_code, $name);
    if (!$check->execute()) {
        $response['message'] = "Database error: " . $check->error;
        echo json_encode($response);
        exit();
    }
    
    $result = $check->get_result();
    $check->close();

    if ($result->num_rows > 0) {
        $response['message'] = "Subject with this code or name already exists";
        echo json_encode($response);
        exit();
    }

    // Insert new subject with office_id
    $stmt = $conn->prepare("INSERT INTO subjects (office_id, subject_code, name, created_at) VALUES (?, ?, ?, NOW())");
    if (!$stmt) {
        $response['message'] = "Database error: " . $conn->error;
        echo json_encode($response);
        exit();
    }
    
    $stmt->bind_param("iss", $office_id, $subject_code, $name);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Subject added successfully!";
    } else {
        $response['message'] = "Error adding subject: " . $stmt->error;
    }
    
    $stmt->close();
    echo json_encode($response);
    exit();
}
?>