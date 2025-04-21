<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$office_id = $_SESSION['office_id'];
$where_clause = "WHERE office_id = $office_id AND deleted_at IS NULL";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_clause .= " AND (firstname LIKE '%$search%' OR lastname LIKE '%$search%' OR email LIKE '%$search%' OR unit LIKE '%$search%')";
}

$total_result = $conn->query("SELECT COUNT(*) AS total FROM teachers $where_clause");
$total_teachers = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_teachers / $limit);

$query = "SELECT * FROM teachers $where_clause LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// Check if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';


if ($is_ajax) {
    // Output table rows for AJAX
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
    
    // Output pagination for AJAX
    echo "<tr><td colspan='4'>";
    echo "<nav><ul class='pagination justify-content-center mt-3'>";
    for ($i = 1; $i <= $total_pages; $i++) {
        echo "<li class='page-item ".($page == $i ? 'active' : '')."'>
                <a class='page-link page-link-ajax' href='#' data-page='{$i}'>{$i}</a>
            </li>";
    }
    echo "</ul></nav></td></tr>";
} else {
    // For non-AJAX requests, just return empty since we're using full page reloads
    echo "";
}
?>
