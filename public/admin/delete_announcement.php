<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $office_id = $_SESSION['office_id'];
    
    // Verify the announcement belongs to this office before deleting
    $check_query = $conn->query("SELECT id FROM announcements WHERE id = $id AND office_id = $office_id");
    if ($check_query->num_rows > 0) {
        $conn->query("DELETE FROM announcements WHERE id = $id");
        $_SESSION['message'] = "Announcement deleted successfully";
    } else {
        $_SESSION['error'] = "Announcement not found or you don't have permission to delete it";
    }
}

header("Location: /myschedule/public/admin/announcements.php");
exit();
?>