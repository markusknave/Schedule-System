<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['office_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $firstname = strtoupper($_POST['firstname'] ?? '');
        $middlename = strtoupper($_POST['middlename'] ?? '');
        $lastname = strtoupper($_POST['lastname'] ?? '');
        $extension = strtoupper($_POST['extension'] ?? '');
        $email = $_POST['email'] ?? '';
        $unit = strtoupper($_POST['unit'] ?? '');        
        $office_id = $_SESSION['office_id'];
        
        if (empty($firstname) || empty($lastname) || empty($email) || empty($unit)) {
            throw new Exception('All required fields must be filled');
        }

        $currentDate = date('Ymd');
        $password = $lastname . $currentDate;
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $conn->begin_transaction();

        try {
            $user_stmt = $conn->prepare("INSERT INTO users 
                (email, password, firstname, lastname, middlename, extension, role, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'teacher', NOW())");
            $user_stmt->bind_param("ssssss", $email, $hashed_password, $firstname, $lastname, $middlename, $extension);
            
            if (!$user_stmt->execute()) {
                throw new Exception("Failed to create user account: " . $user_stmt->error);
            }
            
            $user_id = $user_stmt->insert_id;
            $user_stmt->close();

            $teacher_stmt = $conn->prepare("INSERT INTO teachers 
                (office_id, user_id, unit, created_at) 
                VALUES (?, ?, ?, NOW())");
            $teacher_stmt->bind_param("iis", $office_id, $user_id, $unit);
            
            if (!$teacher_stmt->execute()) {
                throw new Exception("Failed to create teacher record: " . $teacher_stmt->error);
            }
            
            $teacher_id = $teacher_stmt->insert_id;
            $teacher_stmt->close();

            $conn->commit();

            $response = [
                'success' => true,
                'teacher_id' => $teacher_id,
                'message' => 'Teacher added successfully!'
            ];
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
} catch (Exception $e) {
    $response['message'] = 'Error when adding teacher';
}

echo json_encode($response);
exit();
?>