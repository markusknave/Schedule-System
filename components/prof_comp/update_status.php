<?php
session_start();
require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $newStatusCode = $_POST['status'];
    $user_id = $_SESSION['user_id'];

    $validStatuses = ['A', 'OL', 'B', 'UN'];
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
        echo json_encode(['success' => false, 'error' => 'Status not found']);
        exit();
    }

    $status_id = $row['id'];

    $updateStmt = $conn->prepare("UPDATE users SET status_id = ? WHERE id = ?");
    $updateStmt->bind_param("ii", $status_id, $user_id);

    if ($updateStmt->execute()) {
        $_SESSION['status'] = $newStatusCode;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}
