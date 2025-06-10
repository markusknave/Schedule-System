<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (isset($_GET['mobile']) && $_GET['mobile'] == 'true' ? 5 : 7);
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

$response = [
    'mobile_html' => '',
    'desktop_html' => '',
    'total_rooms' => $total_rooms
];

if (empty($rooms)) {
    $response['mobile_html'] = '<div class="list-group-item text-center">No rooms found.</div>';
    $response['desktop_html'] = '<tr><td colspan="3" class="text-center">No rooms found.</td></tr>';
} else {
    // Mobile view HTML
    foreach ($rooms as $room) {
        $response['mobile_html'] .= '<div class="list-group-item">
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
    
    // Desktop view HTML
    foreach ($rooms as $room) {
        $response['desktop_html'] .= '<tr>
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
                    <i class="fas fa-trash"></i> Archive
                </button>
            </td>
        </tr>';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>