<?php
session_start();
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['office_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $firstname = $_POST['firstname'] ?? '';
        $middlename = $_POST['middlename'] ?? '';
        $lastname = $_POST['lastname'] ?? '';
        $extension = $_POST['extension'] ?? '';
        $email = $_POST['email'] ?? '';
        $unit = $_POST['unit'] ?? '';
        $office_id = $_SESSION['office_id'];
        
        // Validate required fields
        if (empty($firstname) || empty($lastname) || empty($email) || empty($unit)) {
            throw new Exception('All required fields must be filled');
        }

        // Generate a random password (you might want to send this to the teacher)
        $password = bin2hex(random_bytes(8));
        print $password;
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Start transaction
        $conn->begin_transaction();

        try {
            // First insert into users table
            $user_stmt = $conn->prepare("INSERT INTO users 
                (email, password, firstname, lastname, middlename, extension, role, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'teacher', NOW())");
            $user_stmt->bind_param("ssssss", $email, $hashed_password, $firstname, $lastname, $middlename, $extension);
            
            if (!$user_stmt->execute()) {
                throw new Exception("Failed to create user account: " . $user_stmt->error);
            }
            
            $user_id = $user_stmt->insert_id;
            $user_stmt->close();

            // Then insert into teachers table
            $teacher_stmt = $conn->prepare("INSERT INTO teachers 
                (office_id, user_id, unit, created_at) 
                VALUES (?, ?, ?, NOW())");
            $teacher_stmt->bind_param("iis", $office_id, $user_id, $unit);
            
            if (!$teacher_stmt->execute()) {
                throw new Exception("Failed to create teacher record: " . $teacher_stmt->error);
            }
            
            $teacher_id = $teacher_stmt->insert_id;
            $teacher_stmt->close();

            // Commit transaction
            $conn->commit();

            $response = [
                'success' => true,
                'message' => 'Teacher added successfully!',
                'teacher_id' => $teacher_id
            ];
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>