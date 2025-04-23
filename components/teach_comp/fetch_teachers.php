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
$teachers = $result->fetch_all(MYSQLI_ASSOC);

// Check if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($is_ajax) {
    $is_mobile = isset($_GET['mobile']) && $_GET['mobile'] == 'true';
    
    if (empty($teachers)) {
        if ($is_mobile) {
            echo '<div class="list-group-item text-center">No teachers found.</div>';
        } else {
            echo '<tr><td colspan="4" class="text-center">No teachers found.</td></tr>';
        }
    } else {
        if ($is_mobile) {
            // Mobile view response
            foreach ($teachers as $teacher) {
                echo '<div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 font-weight-bold">'
                            .htmlspecialchars("{$teacher['firstname']} {$teacher['lastname']} {$teacher['extension']}").
                        '</h6>
                        <span class="badge bg-info">'.htmlspecialchars($teacher['unit']).'</span>
                    </div>
                    <p class="mb-2 text-muted small">
                        <i class="fas fa-envelope mr-1"></i> '.htmlspecialchars($teacher['email']).'
                    </p>
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-sm btn-info mr-2 view-teacher" data-id="'.$teacher['id'].'">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-success mr-2 edit-teacher" 
                            data-id="'.$teacher['id'].'" 
                            data-firstname="'.htmlspecialchars($teacher['firstname']).'"
                            data-middlename="'.htmlspecialchars($teacher['middlename']).'"
                            data-lastname="'.htmlspecialchars($teacher['lastname']).'"
                            data-extension="'.htmlspecialchars($teacher['extension']).'"
                            data-email="'.htmlspecialchars($teacher['email']).'"
                            data-unit="'.htmlspecialchars($teacher['unit']).'">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-teacher" data-id="'.$teacher['id'].'">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>';
            }
        } else {
            // Desktop view response
            foreach ($teachers as $teacher) {
                echo '<tr>
                    <td>'.htmlspecialchars("{$teacher['firstname']} {$teacher['lastname']} {$teacher['extension']}").'</td>
                    <td>'.htmlspecialchars($teacher['email']).'</td>
                    <td>'.htmlspecialchars($teacher['unit']).'</td>
                    <td>
                        <button class="btn btn-sm btn-info view-teacher" data-id="'.$teacher['id'].'">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-success edit-teacher" 
                            data-id="'.$teacher['id'].'" 
                            data-firstname="'.htmlspecialchars($teacher['firstname']).'"
                            data-middlename="'.htmlspecialchars($teacher['middlename']).'"
                            data-lastname="'.htmlspecialchars($teacher['lastname']).'"
                            data-extension="'.htmlspecialchars($teacher['extension']).'"
                            data-email="'.htmlspecialchars($teacher['email']).'"
                            data-unit="'.htmlspecialchars($teacher['unit']).'">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-teacher" data-id="'.$teacher['id'].'">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>';
            }
        }
    }
    
    // Output pagination for AJAX
    if ($is_mobile) {
        echo '<div class="list-group-item">
            <nav><ul class="pagination justify-content-center mt-3">';
        for ($i = 1; $i <= $total_pages; $i++) {
            echo '<li class="page-item '.($page == $i ? 'active' : '').'">
                    <a class="page-link page-link-ajax" href="#" data-page="'.$i.'">'.$i.'</a>
                </li>';
        }
        echo '</ul></nav>
        </div>';
    } else {
        echo '<tr><td colspan="4">
            <nav><ul class="pagination justify-content-center mt-3">';
        for ($i = 1; $i <= $total_pages; $i++) {
            echo '<li class="page-item '.($page == $i ? 'active' : '').'">
                    <a class="page-link page-link-ajax" href="#" data-page="'.$i.'">'.$i.'</a>
                </li>';
        }
        echo '</ul></nav>
        </td></tr>';
    }
} else {
    echo "";
}
?>