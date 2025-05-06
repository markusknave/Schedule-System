<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (isset($_GET['mobile']) && $_GET['mobile'] == 'true' ? 5 : 7);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$office_id = $_SESSION['office_id'];
$where_clause = "WHERE s.office_id = $office_id AND s.deleted_at IS NULL";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_clause .= " AND s.section_name LIKE '%$search%'";
}

$total_result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM sections s
    $where_clause
");
$total_sections = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_sections / $limit);

$query = $conn->query("
    SELECT 
        s.id,
        s.section_name,
        s.created_at,
        COUNT(sc.id) AS schedule_count,
        MAX(sc.created_at) AS last_schedule_update
    FROM sections s
    LEFT JOIN schedules sc ON s.id = sc.section_id
    $where_clause
    GROUP BY s.id, s.section_name, s.created_at
    ORDER BY s.section_name
    LIMIT $limit OFFSET $offset
");
$sections = $query->fetch_all(MYSQLI_ASSOC);

$response = [
    'mobile_html' => '',
    'desktop_html' => '',
    'total_sections' => $total_sections
];

if (empty($sections)) {
    $response['mobile_html'] = '<div class="list-group-item text-center">No sections found.</div>';
    $response['desktop_html'] = '<tr><td colspan="3" class="text-center">No sections found.</td></tr>';
} else {
    // Mobile view HTML
    foreach ($sections as $section) {
        $response['mobile_html'] .= '<div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 font-weight-bold">'
                    .htmlspecialchars($section['section_name']).
                '</h6>
                <span class="badge bg-info">'.htmlspecialchars($section['schedule_count']).' schedules</span>
            </div>
            <div class="d-flex justify-content-end">
                <button class="btn btn-sm btn-info mr-2 view-section" data-id="'.$section['id'].'">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-success mr-2 edit-section" 
                    data-id="'.$section['id'].'" 
                    data-name="'.htmlspecialchars($section['section_name']).'">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-section" data-id="'.$section['id'].'">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>';
    }
    
    // Desktop view HTML
    foreach ($sections as $section) {
        $response['desktop_html'] .= '<tr>
            <td>'.htmlspecialchars($section['section_name']).'</td>
            <td>'.htmlspecialchars($section['schedule_count']).'</td>
            <td>
                <button class="btn btn-sm btn-info view-section" data-id="'.$section['id'].'">
                    <i class="fas fa-eye"></i> View
                </button>
                <button class="btn btn-sm btn-success edit-section" 
                    data-id="'.$section['id'].'" 
                    data-name="'.htmlspecialchars($section['section_name']).'">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-danger delete-section" data-id="'.$section['id'].'">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </td>
        </tr>';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>