<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $office_id = $_SESSION['office_id'];

    $table = '';
    switch ($type) {
        case 'teachers':
            $table = 'teachers';
            break;
        case 'rooms':
            $table = 'rooms';
            break;
        case 'announcements':
            $table = 'announcements';
            break;
        case 'schedules':
            $table = 'schedules';
            break;
        case 'subjects':
            $table = 'subjects';
            break;
    }

    if (!empty($table)) {
        $stmt = $conn->prepare("UPDATE $table SET deleted_at = NULL WHERE id = ? AND office_id = ?");
        $stmt->bind_param("ii", $id, $office_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => ucfirst($type) . ' restored successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error restoring ' . $type]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
    }
    $conn->close();
}
?>