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

        $room_id = $_POST['room_id'] ?? null;
        $name = strtoupper(trim($_POST['name'] ?? ''));

        if (empty($name)) {
            throw new Exception('Room name is required');
        }

        $check = $conn->prepare("SELECT id FROM rooms WHERE id = ? AND office_id = ?");
        $check->bind_param("ii", $room_id, $_SESSION['office_id']);
        $check->execute();
        
        if (!$check->get_result()->num_rows) {
            throw new Exception('Room not found or access denied');
        }

        $stmt = $conn->prepare("UPDATE rooms SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $room_id);

        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Room updated successfully!'
            ];
        } else {
            throw new Exception("Error updating room: " . $stmt->error);
        }

        $stmt->close();
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>