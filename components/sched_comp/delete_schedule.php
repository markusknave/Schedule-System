<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    if (!isset($_SESSION['office_id'])) {
        throw new Exception('Unauthorized access.');
    }

    $schedule_id = $_POST['id'] ?? 0;
    $office_id = $_SESSION['office_id'];

    if (!is_numeric($schedule_id)) {
        throw new Exception('Invalid schedule ID.');
    }

    $check_stmt = $conn->prepare("SELECT id FROM schedules WHERE id = ? AND office_id = ?");
    $check_stmt->bind_param("ii", $schedule_id, $office_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        throw new Exception("Schedule not found or access denied.");
    }

    $delete_stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
    $delete_stmt->bind_param("i", $schedule_id);

    if ($delete_stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Schedule archived successfully!';
    } else {
        throw new Exception("Error deleting schedule: " . $conn->error);
    }

    $delete_stmt->close();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>