<?php
// Include database configuration
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];


    // Validate password strength
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{5,}$/', $password)) {
        die("Password must be at least 5 characters long, contain at least 1 capital letter, and 1 number.");
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        die("Passwords do not match!");
    }

    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert data
    $stmt = $conn->prepare("INSERT INTO offices (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashed_password);

    if ($stmt->execute()) {
        header("Location: /myschedule/login.html");
        echo "Registration successful!";
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
