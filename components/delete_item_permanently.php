<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';

if (!isset($_SESSION['office_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = $_POST['id'];
$type = $_POST['type'];
$office_id = $_SESSION['office_id'];

try {
    switch ($type) {
        case 'announcements':
            $stmt = $conn->prepare("DELETE FROM archived_announcement WHERE id = ? AND office_id = ?");
            $stmt->bind_param("ii", $id, $office_id);
            $stmt->execute();
            break;
            
        case 'teachers':
            $stmt = $conn->prepare("DELETE FROM archived_teachers WHERE id = ? AND office_id = ?");
            $stmt->bind_param("ii", $id, $office_id);
            $stmt->execute();
            break;
            
        case 'rooms':
            $stmt = $conn->prepare("DELETE FROM archived_rooms WHERE id = ? AND office_id = ?");
            $stmt->bind_param("ii", $id, $office_id);
            $stmt->execute();
            break;
            
        case 'schedules':
            $stmt = $conn->prepare("DELETE FROM archived_schedules WHERE id = ? AND office_id = ?");
            $stmt->bind_param("ii", $id, $office_id);
            $stmt->execute();
            break;
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>