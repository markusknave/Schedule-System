<?php
include_once '../../public/admin/logger.php';
session_start();
require_once '../../config.php';
require_once '../../constants.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$announcement_id = $_REQUEST['id'] ?? ($_POST['announcement_id'] ?? null);
$user_id = $_SESSION['user_id'];
$permanent = $_REQUEST['permanent'] ?? false;

if (!$announcement_id) {
    $_SESSION['error'] = "No announcement ID provided";
    header("Location: ../../public/teachers/teach_announcements.php");
    exit();
}

$query = $conn->prepare("SELECT img FROM announcements WHERE id = ? AND user_id = ?");
$query->bind_param("ii", $announcement_id, $user_id);
$query->execute();
$result = $query->get_result();
$announcement = $result->fetch_assoc();
$query->close();

if ($permanent) {
    $delete_stmt = $conn->prepare("DELETE FROM announcements WHERE id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $announcement_id, $user_id);
    if ($delete_stmt->execute()) {
        if (!empty($announcement['img'])) {
            $image_path = $_SERVER['DOCUMENT_ROOT'] . parse_url($announcement['img'], PHP_URL_PATH);
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        $_SESSION['success'] = "Announcement permanently deleted successfully";
        log_action('DELETE', "Permanently deleted user announcement with ID $announcement_id");
    } else {
        $_SESSION['error'] = "Error deleting announcement: " . $delete_stmt->error;
    }
} else {
    $delete_stmt = $conn->prepare("UPDATE announcements SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $announcement_id, $user_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Announcement archived successfully";
        log_action('ARCHIVE', "Archived user announcement with ID $announcement_id");
    } else {
        $_SESSION['error'] = "Error archiving announcement: " . $delete_stmt->error;
    }
}

$delete_stmt->close();
$conn->close();

header("Location: ../../public/teachers/teach_announcements.php");
exit();
?> 