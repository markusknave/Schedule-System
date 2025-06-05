<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';

header('Content-Type: application/json');

try {
    if (!isset($_FILES['profileImage']) || $_FILES['profileImage']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded.');
    }

    $file = $_FILES['profileImage'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file format. Allowed: JPG, PNG, GIF.');
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('File size exceeds 5MB.');
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $targetPath = IMAGE_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to save image.');
    }

    global $conn;
    
    if ($_SESSION['role'] === 'office') {
        $stmt = $conn->prepare("UPDATE offices SET img = ? WHERE id = ?");
        $stmt->bind_param("si", $filename, $_SESSION['office_id']);
    } else {
        $stmt = $conn->prepare("UPDATE users SET img = ? WHERE id = ?");
        $stmt->bind_param("si", $filename, $_SESSION['user_id']);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Database update failed: ' . $stmt->error);
    }

    $_SESSION['img_filename'] = $filename;
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>