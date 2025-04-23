<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

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
            // First get the image path
            $query = $conn->prepare("SELECT img FROM announcements WHERE id = ? AND office_id = ?");
            $query->bind_param("ii", $id, $office_id);
            $query->execute();
            $result = $query->get_result();
            $announcement = $result->fetch_assoc();
            $query->close();
            break;
        case 'schedules':
            $table = 'schedules';
            break;
        case 'subjects':
            $table = 'subjects';
            break;
    }

    if (!empty($table)) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE id = ? AND office_id = ?");
        $stmt->bind_param("ii", $id, $office_id);
        
        if ($stmt->execute()) {
            // If this was an announcement, delete the image
            if ($type === 'announcements' && !empty($announcement['img'])) {
                $image_path = $_SERVER['DOCUMENT_ROOT'] . parse_url($announcement['img'], PHP_URL_PATH);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            echo json_encode(['success' => true, 'message' => ucfirst($type) . ' permanently deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting ' . $type]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
    }
    $conn->close();
}
?>