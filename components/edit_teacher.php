<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = $_POST['teacher_id'];
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $extension = $_POST['extension'];
    $email = $_POST['email'];
    $unit = $_POST['unit'];

    // Update query
    $stmt = $conn->prepare("UPDATE teachers SET firstname=?, middlename=?, lastname=?, extension=?,email=?, unit=? WHERE id=?");
    $stmt->bind_param("ssssssi", $firstname, $middlename, $lastname, $extension, $email, $unit, $teacher_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Teacher updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating teacher: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: /myschedule/public/admin/dashboard.php");
    exit();
}
?>
