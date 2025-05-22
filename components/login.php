<?php
session_start();
session_regenerate_id(true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function redirectWithError() {
    header("Location: /myschedule/login.php?error=1");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, firstname, lastname, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $fname, $lname, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = htmlspecialchars("$fname $lname", ENT_QUOTES, 'UTF-8');
            $_SESSION['role'] = $role;
            $_SESSION['is_admin'] = true;  

            if ($role === 'admin') {
                header("Location: /myschedule/public/admin/dashboard.php");
                exit();
            } elseif ($role === 'teacher') {
                $teacher_stmt = $conn->prepare("
                    SELECT t.id, r.name AS status 
                    FROM teachers t
                    JOIN users u ON t.user_id = u.id
                    LEFT JOIN roles r ON u.status_id = r.id
                    WHERE t.user_id = ?
                ");
                $teacher_stmt->bind_param("i", $id);
                $teacher_stmt->execute();
                $teacher_stmt->store_result();

                if ($teacher_stmt->num_rows > 0) {
                    $teacher_stmt->bind_result($teacher_id, $status);
                    $teacher_stmt->fetch();
                    $_SESSION['teacher_id'] = $teacher_id;
                    $_SESSION['status'] = $status ?? 'A';
                    header("Location: /myschedule/public/teachers/teach_dashboard.php");
                    exit();
                } else {
                    redirectWithError();
                }
            }
        } else {
            redirectWithError();
        }
    }

    $stmt = $conn->prepare("SELECT id, name, password FROM offices WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['office_id'] = $id;
            $_SESSION['office_name'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $_SESSION['role'] = 'office';
            header("Location: /myschedule/public/office/dashboard.php");
            exit();
        } else {
            redirectWithError();
        }
    } else {
        redirectWithError();
    }

    $stmt->close();
}

$conn->close();
exit();
?>