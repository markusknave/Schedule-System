<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (isset($_GET['mobile']) && $_GET['mobile'] == 'true' ? 5 : 7);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$is_mobile = isset($_GET['mobile']) && $_GET['mobile'] == 'true';

$where_clause = "WHERE s.office_id = {$_SESSION['office_id']} AND s.deleted_at IS NULL";

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

// Prepare response array
$response = [
    'mobile_html' => '',
    'desktop_html' => '',
    'mobile_pagination' => '',
    'desktop_pagination' => '',
    'total_subjects' => $total_subjects
];

if (empty($subjects)) {
    $response['mobile_html'] = '<div class="list-group-item text-center">No subjects found.</div>';
    $response['desktop_html'] = '<tr><td colspan="4" class="text-center">No subjects found.</td></tr>';
} else {
    // Mobile view HTML
    foreach ($subjects as $subject) {
        $response['mobile_html'] .= '<div class="list-group-item">
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
    
    // Desktop view HTML
    foreach ($subjects as $subject) {
        $response['desktop_html'] .= '<tr>
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

// Pagination HTML
$start = max(1, $page - 2);
$end = min($total_pages, $page + 2);

// Mobile pagination
$response['mobile_pagination'] = '<div class="list-group-item">
    <nav><ul class="pagination justify-content-center mt-3">';
for ($i = $start; $i <= $end; $i++) {
    $response['mobile_pagination'] .= '<li class="page-item '.($page == $i ? 'active' : '').'">
            <a class="page-link page-link-ajax" href="#" data-page="'.$i.'">'.$i.'</a>
        </li>';
}
$response['mobile_pagination'] .= '</ul></nav></div>';

// Desktop pagination
$response['desktop_pagination'] = '<tr><td colspan="4">
    <nav><ul class="pagination justify-content-center mt-3">';
for ($i = $start; $i <= $end; $i++) {
    $response['desktop_pagination'] .= '<li class="page-item '.($page == $i ? 'active' : '').'">
            <a class="page-link page-link-ajax" href="#" data-page="'.$i.'">'.$i.'</a>
        </li>';
}
$response['desktop_pagination'] .= '</ul></nav></td></tr>';

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit();