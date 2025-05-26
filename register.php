<?php
session_start();
session_regenerate_id(true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = []; // Array to hold all errors

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate Office Name
    $stmt = $conn->prepare("SELECT id FROM offices WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Office name already exists!";
    }
    $stmt->close();
    
    // Validate Email Format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format!";
    }
    
    // Check Email Existence
    $stmt = $conn->prepare("SELECT id FROM offices WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Email already registered!";
    }
    $stmt->close();
    
    // Validate Password
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{5,}$/', $password)) {
        $errors[] = "Password must be at least 5 characters long, contain 1 capital letter and 1 number.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match!";
    }
    
    // Proceed if no errors
    if (empty($errors)) {
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
            color: #ff6b6b;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="uf-form-signin" id="registerForm">
    <div class="text-center">
        <img src="./assets/img/favicon.png" alt="" width="200" height="200">
        <h1 class="h2" style="color: #ffffff;">Office Registration</h1>
    </div>
    <form class="mt-4" action="register.php" method="POST" onsubmit="return validatePassword()">
        <div id="errorMessage" class="error-message">
            <?php 
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    echo htmlspecialchars($error) . '<br>';
                }
            }
            ?>
        </div>
  <div class="input-group uf-input-group mb-3">
            <span class="input-group-text fa fa-user" style="color: #000ed3;"></span>
            <input type="text" class="form-control" name="name" placeholder="Office name" required 
                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
        </div>

        <div class="input-group uf-input-group mb-3">
            <span class="input-group-text fa fa-envelope" style="color: #000ed3;"></span>
            <input type="email" class="form-control" name="email" placeholder="Email address" required
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        </div>

        <div class="input-group uf-input-group mb-3">
            <span class="input-group-text fa fa-lock" style="color: #000ed3;"></span>
            <input type="password" id="password" class="form-control" name="password" placeholder="Password" required>
        </div>
        
        <div class="input-group uf-input-group mb-3">
            <span class="input-group-text fa fa-lock" style="color: #000ed3;"></span>
            <input type="password" id="confirm_password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
        </div>


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

        document.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', () => {
            document.getElementById('errorMessage').innerHTML = '';
        });
    });
    </script>
</body>
</html>
