<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

header('Content-Type: application/json');

try {
    $search = $_GET['search'] ?? '';
    $search = $conn->real_escape_string($search);
    
    if (!isset($_SESSION['office_id'])) {
        throw new Exception("Session expired. Please login again.");
    }

    $office_id = $_SESSION['office_id'];
    $where_clause = "WHERE t.office_id = $office_id AND t.deleted_at IS NULL AND u.deleted_at IS NULL";
    
    if (!empty($search)) {
        $where_clause .= " AND (u.firstname LIKE '%$search%' OR u.lastname LIKE '%$search%' OR u.email LIKE '%$search%' OR t.unit LIKE '%$search%')";
    }

    $query = "SELECT t.id, t.unit, u.firstname, u.middlename, u.lastname, u.extension, u.email
              FROM teachers t
              JOIN users u ON t.user_id = u.id
              $where_clause 
              LIMIT 5";
              
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database error: " . $conn->error);
    }

    $output = '';
    while ($row = $result->fetch_assoc()) {
        $output .= "<tr>
                <td>{$row['firstname']} {$row['lastname']} {$row['extension']}</td>
                <td>{$row['email']}</td>
                <td>{$row['unit']}</td>
                <td>
                    <button class='btn btn-success btn-sm mr-2' data-bs-toggle='modal' data-bs-target='#editTeacherModal' 
                        onclick='editTeacher({$row['id']}, \"{$row['firstname']}\", \"{$row['middlename']}\", \"{$row['lastname']}\", \"{$row['extension']}\", \"{$row['email']}\", \"{$row['unit']}\")'>
                        <i class='fas fa-edit'></i> Edit
                    </button>
                    <button class='btn btn-danger btn-sm' data-bs-toggle='modal' data-bs-target='#deleteTeacherModal' 
                        onclick='deleteTeacher({$row['id']})'>
                        <i class='fas fa-trash-alt'></i> Delete
                    </button>
                </td>
            </tr>";
    }

    if (empty($output)) {
        $output = '<tr><td colspan="4" class="text-center">No teachers found.</td></tr>';
    }

    echo $output;

} catch (Exception $e) {
    http_response_code(500);
    echo '<tr><td colspan="4" class="text-center text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
exit();