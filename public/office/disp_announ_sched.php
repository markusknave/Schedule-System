<?php
session_start();
@include '../../components/links.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Announcements & Schedules</title>
</head>
<body>
    
</body>
</html>