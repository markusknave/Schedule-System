<?php
session_start();
session_regenerate_id(true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$name_error = '';
$email_error = '';
$password_error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $stmt = $conn->prepare("SELECT id FROM offices WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $name_error = "Office name already exists!";
    }
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT id FROM offices WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $email_error = "Email already registered!";
    }
    $stmt->close();
    
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{5,}$/', $password)) {
        $password_error = "Password must be at least 5 characters long, contain 1 capital letter and 1 number.";
    } elseif ($password !== $confirm_password) {
        $password_error = "Passwords do not match!";
    }
    
    if (empty($name_error) && empty($email_error) && empty($password_error)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO offices (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        
        if ($stmt->execute()) {
            header("Location: /myschedule/login.php");
            exit();
        } else {
            die("Registration failed: " . $stmt->error);
        }
        $stmt->close();
    }
}


require_once '../../myschedule/register.php';
?>