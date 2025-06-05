<?php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $newStatusCode = $_POST['status'];
    $user_id = $_SESSION['user_id'];
    
    $validStatuses = ['A', 'OL', 'B', 'UN', 'OT'];
    if (!in_array($newStatusCode, $validStatuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status code']);
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->bind_param("s", $newStatusCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        error_log("Role not found for status: $newStatusCode");
        echo json_encode(['success' => false, 'error' => "Role not found for status: $newStatusCode"]);
        exit();
    }

    $status_id = $row['id'];

    $startDate = null;
    $endDate = null;
    
    if ($newStatusCode === 'OL' || $newStatusCode === 'OT') {
        if (empty($_POST['start_date']) || empty($_POST['end_date'])) {
            echo json_encode(['success' => false, 'error' => 'Dates are required for leave/travel']);
            exit();
        }
        
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        
        if (!validateDate($startDate) || !validateDate($endDate)) {
            echo json_encode(['success' => false, 'error' => 'Invalid date format']);
            exit();
        }
        
        if (strtotime($startDate) > strtotime($endDate)) {
            echo json_encode(['success' => false, 'error' => 'End date must be after start date']);
            exit();
        }
    }

    $updateStmt = $conn->prepare("UPDATE users 
        SET status_id = ?, 
            st_leave = ?, 
            end_leave = ? 
        WHERE id = ?");
    
    if (!in_array($newStatusCode, ['OL', 'OT'])) {
        $startDate = null;
        $endDate = null;
    }
    
    $updateStmt->bind_param("issi", $status_id, $startDate, $endDate, $user_id);

    if ($updateStmt->execute()) {
        $_SESSION['status'] = $newStatusCode;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}