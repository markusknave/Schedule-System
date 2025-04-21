<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$office_id = $_SESSION['office_id'];
$where_clause = "WHERE r.office_id = $office_id AND r.deleted_at IS NULL";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_clause .= " AND r.name LIKE '%$search%'";
}

$total_result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM rooms r
    $where_clause
");
$total_rooms = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rooms / $limit);

$query = $conn->query("
    SELECT 
        r.id,
        r.name,
        r.created_at,
        COUNT(s.id) AS schedule_count,
        MAX(s.created_at) AS last_schedule_update
    FROM rooms r
    LEFT JOIN schedules s ON r.id = s.room_id
    $where_clause
    GROUP BY r.id, r.name, r.created_at
    ORDER BY r.name
    LIMIT $limit OFFSET $offset
");
$rooms = $query->fetch_all(MYSQLI_ASSOC);

// Check if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($is_ajax) {
    $is_mobile = isset($_GET['mobile']) && $_GET['mobile'] == 'true';
    
    if (empty($rooms)) {
        if ($is_mobile) {
            echo '<div class="list-group-item text-center">No rooms found.</div>';
        } else {
            echo '<tr><td colspan="3" class="text-center">No rooms found.</td></tr>';
        }
    } else {
        if ($is_mobile) {
            // Mobile view response
            foreach ($rooms as $room) {
                echo '<div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 font-weight-bold">'
                            .htmlspecialchars($room['name']).
                        '</h6>
                        <span class="badge bg-info">'.htmlspecialchars($room['schedule_count']).' schedules</span>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-sm btn-info mr-2 view-room" data-id="'.$room['id'].'">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-success mr-2 edit-room" 
                            data-id="'.$room['id'].'" 
                            data-name="'.htmlspecialchars($room['name']).'">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-room" data-id="'.$room['id'].'">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>';
            }
        } else {
            // Desktop view response
            foreach ($rooms as $room) {
                echo '<tr>
                    <td>'.htmlspecialchars($room['name']).'</td>
                    <td>'.htmlspecialchars($room['schedule_count']).'</td>
                    <td>
                        <button class="btn btn-sm btn-info view-room" data-id="'.$room['id'].'">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-success edit-room" 
                            data-id="'.$room['id'].'" 
                            data-name="'.htmlspecialchars($room['name']).'">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-room" data-id="'.$room['id'].'">
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
        echo '<tr><td colspan="3">
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