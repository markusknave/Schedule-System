<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$search = $_GET['search'] ?? '';
$search = $conn->real_escape_string($search);
$office_id = $_SESSION['office_id'];

$where_clause = "WHERE office_id = $office_id";
if (!empty($search)) {
    $where_clause .= " AND (firstname LIKE '%$search%' OR lastname LIKE '%$search%' OR email LIKE '%$search%' OR unit LIKE '%$search%')";
}

$query = "SELECT * FROM teachers $where_clause LIMIT 5"; // You can paginate if needed
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    echo "<tr>
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
?>
