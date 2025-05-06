<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

$announcement_id = $_REQUEST['id'] ?? ($_POST['announcement_id'] ?? null);
$office_id = $_SESSION['office_id'];
$permanent = $_REQUEST['permanent'] ?? false;

if (!$announcement_id) {
    $_SESSION['error'] = "No announcement ID provided";
    header("Location: /myschedule/public/admin/announcements.php");
    exit();
}

$query = $conn->prepare("SELECT img FROM announcements WHERE id = ? AND office_id = ?");
$query->bind_param("ii", $announcement_id, $office_id);
$query->execute();
$result = $query->get_result();
$announcement = $result->fetch_assoc();
$query->close();

if ($permanent) {
    $delete_stmt = $conn->prepare("DELETE FROM announcements WHERE id = ? AND office_id = ?");
    $delete_stmt->bind_param("ii", $announcement_id, $office_id);
    if ($delete_stmt->execute()) {
        if (!empty($announcement['img'])) {
            $image_path = $_SERVER['DOCUMENT_ROOT'] . parse_url($announcement['img'], PHP_URL_PATH);
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        $_SESSION['success'] = "Announcement permanently deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting announcement: " . $delete_stmt->error;
    }
} else {
    $delete_stmt = $conn->prepare("UPDATE announcements SET deleted_at = NOW() WHERE id = ? AND office_id = ?");
    $delete_stmt->bind_param("ii", $announcement_id, $office_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Announcement archived successfully";
    } else {
        $_SESSION['error'] = "Error archiving announcement: " . $delete_stmt->error;
    }
}

$delete_stmt->close();
$conn->close();

// Redirect back to approriate page
header("Location: /myschedule/public/admin/announcements.php");
exit();
?>