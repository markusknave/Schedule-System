<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['office_id'])) {
    header("Location: login.html");
    exit();
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $extension = $_POST['extension'];
    $email = $_POST['email'];
    $unit = $_POST['unit'];
    $office_id = $_SESSION['office_id'];

    // Insert new teacher
    $stmt = $conn->prepare("INSERT INTO teachers (office_id, firstname, middlename, lastname, extension, email, unit) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $office_id, $firstname, $middlename, $lastname, $extension, $email, $unit);
    if ($stmt->execute()) {
        header("Location: /myschedule/public/admin/dashboard.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>