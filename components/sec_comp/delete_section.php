<?php
session_start();
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_SESSION['office_id'])) {
            throw new Exception('Unauthorized');
        }

        $section_id = $_POST['section_id'] ?? null;
        $office_id = $_SESSION['office_id'];

        $check = $conn->prepare("SELECT id FROM sections WHERE id = ? AND office_id = ?");
        $check->bind_param("ii", $section_id, $office_id);
        $check->execute();
        
        if (!$check->get_result()->num_rows) {
            throw new Exception('Section not found or access denied');
        }

        $stmt = $conn->prepare("UPDATE sections SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $section_id);

        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Section deleted successfully!'
            ];
        } else {
            throw new Exception("Error deleting section: " . $stmt->error);
        }

        $stmt->close();
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>