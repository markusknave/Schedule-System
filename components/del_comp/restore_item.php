<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

try {
    // Authentication check
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['office_id'])) {
        throw new Exception("Authentication required", 401);
    }

    // Role-based access control
    $isAdmin = ($_SESSION['role'] === 'admin');
    $officeId = $_SESSION['office_id'] ?? null;

    if (!$isAdmin && !$officeId) {
        throw new Exception("Unauthorized access", 403);
    }

    // Method validation
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Method not allowed", 405);
    }

    // Input validation
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
    
    if (!$id || $id < 1) {
        $response['errors']['id'] = "Invalid ID";
        throw new Exception("Invalid parameters", 400);
    }

    $validTypes = ['teachers', 'rooms', 'announcements', 'schedules', 'subjects', 'offices'];
    if (empty($type) || !in_array($type, $validTypes)) {
        $response['errors']['type'] = "Invalid type";
        throw new Exception("Invalid parameters", 400);
    }

    // Ownership verification for non-admins
    if (!$isAdmin) {
        $checkSql = "SELECT id FROM $type WHERE id = ? AND office_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $id, $officeId);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows === 0) {
            throw new Exception("You don't have permission to restore this item", 403);
        }
        $checkStmt->close();
    }

    // Perform restoration
    $sql = "UPDATE $type SET deleted_at = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = ucfirst($type) . ' restored successfully';
        http_response_code(200);
    } else {
        throw new Exception("Database operation failed", 500);
    }

    $stmt->close();
} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    $response['message'] = $e->getMessage();
}

// Ensure no output before this
while (ob_get_level()) {
    ob_end_clean();
}

echo json_encode($response);
exit();
?>