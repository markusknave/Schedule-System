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
    $office_id = $_POST['office_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email)) {
        throw new Exception("Name and email are required");
    }

    $query = "UPDATE offices SET name = ?, email = ?";
    $params = [$name, $email];
    $types = "ss";

    if (!empty($password)) {
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query .= ", password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    $query .= " WHERE id = ?";
    $params[] = $office_id;
    $types .= "i";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Office updated successfully!";
        log_action('UPDATE', "Updated office with ID $office_id, name: $name");
    } else {
        throw new Exception("Error updating office: " . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
exit();
?>