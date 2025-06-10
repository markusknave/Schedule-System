<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/public/admin/logger.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    header("Location: /myschedule/login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $teacher_id = $_POST['teacher_id'] ?? null;
        $firstname = strtoupper($_POST['firstname'] ?? '');
        $middlename = strtoupper($_POST['middlename'] ?? '');
        $lastname = strtoupper($_POST['lastname'] ?? '');
        $extension = strtoupper($_POST['extension'] ?? '');
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $unit = strtoupper($_POST['unit'] ?? '');   

        $check = $conn->prepare("SELECT t.id, t.user_id FROM teachers t WHERE t.id = ?");
        $check->bind_param("i", $teacher_id);
        $check->execute();
        $result = $check->get_result();
        
        if (!$result->num_rows) {
            throw new Exception("Teacher not found or access denied");
        }
        
        $teacher = $result->fetch_assoc();
        $user_id = $teacher['user_id'];

        $conn->begin_transaction();

        try {
            $user_fields = "firstname=?, middlename=?, lastname=?, extension=?, email=?";
            $params = [$firstname, $middlename, $lastname, $extension, $email];
            $types = "sssss";

            if (!empty($password)) {
                if (strlen($password) < 8) {
                    throw new Exception("Password must be at least 8 characters long");
                }
                $user_fields .= ", password=?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
                $types .= "s";
            }

            $params[] = $user_id;
            $types .= "i";

            $user_stmt = $conn->prepare("UPDATE users SET $user_fields WHERE id=?");
            $user_stmt->bind_param($types, ...$params);

            if (!$user_stmt->execute()) {
                throw new Exception("Failed to update User: " . $user_stmt->error);
            }
            $user_stmt->close();

            $teacher_stmt = $conn->prepare("UPDATE teachers SET 
                unit=? 
                WHERE id=?");
            $teacher_stmt->bind_param("si", $unit, $teacher_id);

            if (!$teacher_stmt->execute()) {
                throw new Exception("Failed to update teacher unit: " . $teacher_stmt->error);
            }
            $teacher_stmt->close();

            $conn->commit();

            $response = [
                'success' => true,
                'message' => 'Teacher updated successfully!'
            ];
            log_action('UPDATE', "Updated teacher with ID $teacher_id (user ID $user_id)");
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>