<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/public/admin/logger.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$response = ['success' => false, 'message' => ''];

try {
    $office_id = $_POST['office_id'] ?? null;

    if (empty($office_id)) {
        throw new Exception("Office ID is required");
    }

    // Soft delete the office
    $stmt = $conn->prepare("UPDATE offices SET deleted_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $office_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Office deleted successfully!";
        log_action('DELETE', "Deleted office with ID $office_id");
    } else {
        throw new Exception("Error deleting office: " . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
exit();
?>