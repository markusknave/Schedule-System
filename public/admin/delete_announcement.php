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
    
    // Get announcement data first
    $announcement_data = $conn->prepare("SELECT * FROM announcements WHERE id = ? AND office_id = ?");
    $announcement_data->bind_param("ii", $id, $office_id);
    $announcement_data->execute();
    $announcement = $announcement_data->get_result()->fetch_assoc();
    
    if ($announcement) {
        // Archive the announcement
        $archive_stmt = $conn->prepare("INSERT INTO archived_announcement (office_id, announcement_id, title, img, content, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $archive_stmt->bind_param("iissss", $office_id, $id, $announcement['title'], $announcement['img'], $announcement['content'], $announcement['created_at']);
        
        if ($archive_stmt->execute()) {
            // Now delete from main table
            $delete_stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
            $delete_stmt->bind_param("i", $id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['message'] = "Announcement archived successfully";
            } else {
                $_SESSION['error'] = "Error archiving announcement: " . $delete_stmt->error;
            }
            $delete_stmt->close();
        } else {
            $_SESSION['error'] = "Error archiving announcement: " . $archive_stmt->error;
        }
        $archive_stmt->close();
    } else {
        $_SESSION['error'] = "Announcement not found or you don't have permission";
    }

    $announcement_data->close();
    $conn->close();
    
    header("Location: /myschedule/public/admin/announcements.php");
    exit();
}
?>