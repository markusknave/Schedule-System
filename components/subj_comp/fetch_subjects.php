<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$where_clause = "WHERE s.deleted_at IS NULL";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_clause .= " AND (s.name LIKE '%$search%' OR s.subject_code LIKE '%$search%')";
}

$total_result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM subjects s
    $where_clause
");
$total_subjects = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_subjects / $limit);

$query = $conn->query("
    SELECT 
        s.id,
        s.subject_code,
        s.name,
        s.created_at,
        COUNT(sc.id) AS schedule_count,
        MAX(sc.created_at) AS last_schedule_update
    FROM subjects s
    LEFT JOIN schedules sc ON s.id = sc.subject_id AND sc.deleted_at IS NULL
    $where_clause
    GROUP BY s.id, s.subject_code, s.name, s.created_at
    ORDER BY s.subject_code, s.name
    LIMIT $limit OFFSET $offset
");
$subjects = $query->fetch_all(MYSQLI_ASSOC);

// Check if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($is_ajax) {
    $is_mobile = isset($_GET['mobile']) && $_GET['mobile'] == 'true';
    
    if (empty($subjects)) {
        if ($is_mobile) {
            echo '<div class="list-group-item text-center">No subjects found.</div>';
        } else {
            echo '<tr><td colspan="4" class="text-center">No subjects found.</td></tr>';
        }
    } else {
        if ($is_mobile) {
            // Mobile view response
            foreach ($subjects as $subject) {
                echo '<div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 font-weight-bold">'
                            .htmlspecialchars($subject['subject_code']).' - '.htmlspecialchars($subject['name']).
                        '</h6>
                        <span class="badge bg-info">'.htmlspecialchars($subject['schedule_count']).' schedules</span>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-sm btn-info mr-2 view-subject" data-id="'.$subject['id'].'">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-success mr-2 edit-subject" 
                            data-id="'.$subject['id'].'" 
                            data-code="'.htmlspecialchars($subject['subject_code']).'"
                            data-name="'.htmlspecialchars($subject['name']).'">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-subject" data-id="'.$subject['id'].'">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>';
            }
        } else {
            // Desktop view response
            foreach ($subjects as $subject) {
                echo '<tr>
                    <td>'.htmlspecialchars($subject['subject_code']).'</td>
                    <td>'.htmlspecialchars($subject['name']).'</td>
                    <td>'.htmlspecialchars($subject['schedule_count']).'</td>
                    <td>
                        <button class="btn btn-sm btn-info view-subject" data-id="'.$subject['id'].'">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-success edit-subject" 
                            data-id="'.$subject['id'].'" 
                            data-code="'.htmlspecialchars($subject['subject_code']).'"
                            data-name="'.htmlspecialchars($subject['name']).'">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-subject" data-id="'.$subject['id'].'">
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