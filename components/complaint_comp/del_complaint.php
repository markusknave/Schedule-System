<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['office_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$complaint_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$office_id = $_SESSION['office_id'];

if ($complaint_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid complaint ID']);
    exit;
}

$check_query = "SELECT c.id 
                FROM complaints c
                JOIN teachers t ON c.teacher_id = t.id
                WHERE c.id = ? AND t.office_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $complaint_id, $office_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Complaint not found or access denied']);
    exit;
}

$delete_query = "DELETE FROM complaints WHERE id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param("i", $complaint_id);

if ($delete_stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$delete_stmt->close();
$stmt->close();
?>