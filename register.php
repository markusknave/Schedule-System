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
    
    // Check existing office name
    $stmt = $conn->prepare("SELECT id FROM offices WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $name_error = "Office name already exists!";
    }
    $stmt->close();
    
    // Check existing email
    $stmt = $conn->prepare("SELECT id FROM offices WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $email_error = "Email already registered!";
    }
    $stmt->close();
    
    // Validate password
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{5,}$/', $password)) {
        $password_error = "Password must be at least 5 characters long, contain 1 capital letter and 1 number.";
    } elseif ($password !== $confirm_password) {
        $password_error = "Passwords do not match!";
    }
    
    // If no errors, insert into database
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
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="./assets/img/favicon.png">
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="./assets/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/uf-style.css">
    <title>Register</title>
    <style>
        body {
            background-color: #1a1a2e;
            overflow: hidden;
        }

        .fade-out {
            animation: slideOut 0.5s forwards;
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .error-message {
            color: #ff0000;
            font-size: 14px;
            font-weight: bold;
            display: inline;
            margin-bottom: 10px; 
        }
    </style>
</head>
<body>
<div class="uf-form-signin" id="registerForm">
    <div class="text-center">
        <img src="./assets/img/favicon.png" alt="" width="200" height="200">
        <h1 class="h2" style="color: #ffffff;">Office Registration</h1>
    </div>
    <form class="mt-4" action="register.php" method="POST">
        <?php if (!empty($name_error)) : ?>
            <small class="error-message"><?php echo $name_error ?></small>
        <?php endif; ?>
        <div class="input-group uf-input-group input-group-lg mb-3">
            <span class="input-group-text fa fa-user" style="color: #000ed3;"></span>
            <input type="text" class="form-control" name="name" placeholder="Office name" required 
                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
        </div>

        <?php if (!empty($email_error)) : ?>
            <small class="error-message"><?php echo $email_error ?></small>
        <?php endif; ?>
        <div class="input-group uf-input-group input-group-lg mb-3">
            <span class="input-group-text fa fa-envelope" style="color: #000ed3;"></span>
            <input type="email" class="form-control" name="email" placeholder="Email address" required
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        </div>

            <div class="input-group uf-input-group input-group-lg mb-3">
                <span class="input-group-text fa fa-lock" style="color: #000ed3;"></span>
                <input type="password" id="password" class="form-control" name="password" placeholder="Password" required oninput="clearError('passwordError')">
            </div>
            <small id="passwordError" class="error-message"></small> 
            
            <div class="input-group uf-input-group input-group-lg mb-3">
                <span class="input-group-text fa fa-lock" style="color: #000ed3;"></span>
                <input type="password" id="confirm_password" class="form-control" name="confirm_password" placeholder="Confirm Password" required oninput="clearError('confirmPasswordError')">
            </div>
            <small id="confirmPasswordError" class="error-message"></small>

            <div class="d-grid mb-4">
                <button type="submit" class="btn uf-btn-primary btn-lg">Sign Up</button>
            </div>
            <div class="mt-4 text-center">
                <span class="text-white">Already have an account?</span>
                <a href="login.php" class="text-white text-decoration-underline" id="loginLink">Login</a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById("loginLink").addEventListener("click", function(event) {
            event.preventDefault(); 
            document.getElementById("registerForm").classList.add("fade-out");
            setTimeout(() => {
                window.location.href = "login.php"; 
            }, 500);
        });

        function validatePassword() {
            let password = document.getElementById("password").value;
            let confirmPassword = document.getElementById("confirm_password").value;
            let passwordError = document.getElementById("passwordError");
            let confirmPasswordError = document.getElementById("confirmPasswordError");

            let passwordPattern = /^(?=.*[A-Z])(?=.*\d).{5,}$/;

            if (!passwordPattern.test(password)) {
                passwordError.innerHTML = "Password must be at least 5 characters long, contain at least 1 capital letter, and 1 number.";
                return false; 
            }

            if (password !== confirmPassword) {
                confirmPasswordError.innerHTML = "Passwords do not match!";
                return false; 
            }

            return true;
        }

        function clearError(id) {
            document.getElementById(id).innerHTML = "";
        }
    </script>
</body>
</html>
