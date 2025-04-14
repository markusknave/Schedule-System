<?php
session_start();

// Secure session settings
session_regenerate_id(true); // Prevent session fixation attacks

// Database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Get user from database
    $stmt = $conn->prepare("SELECT id, name, password FROM offices WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $hashed_password);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashed_password)) {
            // Secure session variables
            $_SESSION['office_id'] = $id;
            $_SESSION['office_name'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $_SESSION['login_time'] = time(); // Track login time
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT']; // Prevent session hijacking

            // Redirect to dashboard
            header("Location: /myschedule/public/admin/dashboard.php");
            exit();
        } else {
            echo "<script>alert('Invalid email or password!'); window.location='/myschedule/login.html';</script>";
        }
    } else {
        echo "<script>alert('User not found!'); window.location='/myschedule/login.html';</script>";
    }

    $stmt->close();
}

$conn->close();
?>


