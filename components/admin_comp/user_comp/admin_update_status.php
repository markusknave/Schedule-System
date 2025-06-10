<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $status = $_POST['status'];
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;

    $validStatuses = ['A', 'OL', 'OT', 'B', 'UN'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status code']);
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();
    $role = $result->fetch_assoc();
    
    if (!$role) {
        echo json_encode(['success' => false, 'error' => "Role not found for status: $status"]);
        exit();
    }
    
    $role_id = $role['id'];

    if ($status === 'OL' || $status === 'OT') {
        if (empty($start_date) || empty($end_date)) {
            echo json_encode(['success' => false, 'error' => 'Dates are required for leave/travel']);
            exit();
        }
    } else {
        $start_date = null;
        $end_date = null;
    }

    $updateStmt = $conn->prepare("UPDATE users 
        SET status_id = ?, 
            st_leave = ?, 
            end_leave = ? 
        WHERE id = ?");
    $updateStmt->bind_param("issi", $role_id, $start_date, $end_date, $user_id);

    if ($updateStmt->execute()) {
        echo json_encode(['success' => true]);
        log_action('UPDATE', "Updated status for user ID $user_id to $status");
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Invalid request method']);