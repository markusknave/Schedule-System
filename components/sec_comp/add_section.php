<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['office_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = strtoupper(trim($_POST['section_name'] ?? ''));
        $office_id = $_SESSION['office_id'];

        if (empty($name)) {
            throw new Exception('Section name is required');
        }

        $stmt = $conn->prepare("INSERT INTO sections (section_name, office_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("si", $name, $office_id);
        
        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Section added successfully!'
            ];
        } else {
            throw new Exception("Error adding section: " . $stmt->error);
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>