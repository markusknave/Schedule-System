<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/public/admin/logger.php';

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
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['office_id'])) {
        throw new Exception("Authentication required", 401);
    }

    $isAdmin = ($_SESSION['role'] === 'admin');
    $officeId = $_SESSION['office_id'] ?? null;

    if (!$isAdmin && !$officeId) {
        throw new Exception("Unauthorized access", 403);
    }

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Method not allowed", 405);
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
    
    if (!$id || $id < 1) {
        $response['errors']['id'] = "Invalid ID";
        throw new Exception("Invalid parameters", 400);
    }

    $validTypes = ['teachers', 'rooms', 'announcements', 'schedules', 'subjects', 'offices', 'sections'];
    if (empty($type) || !in_array($type, $validTypes)) {
        $response['errors']['type'] = "Invalid type";
        throw new Exception("Invalid parameters", 400);
    }

    if (!$isAdmin) {
        $checkSql = "SELECT id FROM $type WHERE id = ? AND office_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $id, $officeId);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows === 0) {
            throw new Exception("You don't have permission to delete this item", 403);
        }
        $checkStmt->close();
    }

    $extraData = null;
    if ($type === 'announcements') {
        $query = $conn->prepare("SELECT img FROM announcements WHERE id = ?");
        $query->bind_param("i", $id);
        $query->execute();
        $result = $query->get_result();
        $extraData = $result->fetch_assoc();
        $query->close();
    }

    $sql = "DELETE FROM $type WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        if ($type === 'announcements' && !empty($extraData['img'])) {
            $image_path = $_SERVER['DOCUMENT_ROOT'] . parse_url($extraData['img'], PHP_URL_PATH);
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $response['success'] = true;
        $response['message'] = ucfirst($type) . ' permanently deleted successfully';
        http_response_code(200);
        log_action('DELETE', "Permanently deleted $type with ID $id");
    } else {
        throw new Exception("Database operation failed", 500);
    }

    $stmt->close();
} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    $response['message'] = $e->getMessage();
}

while (ob_get_level()) {
    ob_end_clean();
}

echo json_encode($response);
exit();
?>