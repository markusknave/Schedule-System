<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'total_teachers' => 0,
    'mobile_html' => '<div class="list-group-item text-center">No teachers found.</div>',
    'desktop_html' => '<tr><td colspan="4" class="text-center">No teachers found.</td></tr>',
    'mobile_pagination' => '',
    'desktop_pagination' => ''
];

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (isset($_GET['mobile']) && $_GET['mobile'] == 'true' ? 5 : 10); // Changed to 10
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? trim($_GET['search']) : "";
    $is_mobile = isset($_GET['mobile']) && $_GET['mobile'] == 'true';

    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Session expired. Please login again.");
    }

    // Modified query to get all teachers (users with role 'teacher')
    $where_clause = "WHERE u.role = 'teacher' AND u.deleted_at IS NULL";
    $join_clause = "LEFT JOIN teachers t ON u.id = t.user_id";
    
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $where_clause .= " AND (u.firstname LIKE '%$search%' OR u.lastname LIKE '%$search%' OR u.email LIKE '%$search%' OR t.unit LIKE '%$search%')";
    }

    $total_query = "SELECT COUNT(*) AS total 
                FROM users u
                $join_clause
                $where_clause";
    $total_result = $conn->query($total_query);
    
    if (!$total_result) {
        throw new Exception("Database error: " . $conn->error);
    }

    $total_row = $total_result->fetch_assoc();
    $total_teachers = isset($total_row['total']) ? (int)$total_row['total'] : 0;
    $total_pages = ceil($total_teachers / $limit);

    $query = "SELECT u.id as user_id, u.firstname, u.middlename, u.lastname, u.extension, u.email,
                     t.id as teacher_id, t.unit, t.created_at
              FROM users u
              $join_clause
              $where_clause
              ORDER BY u.lastname, u.firstname
              LIMIT $limit OFFSET $offset";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database error: " . $conn->error);
    }

    $teachers = $result->fetch_all(MYSQLI_ASSOC);

    if (!empty($teachers)) {
        $mobile_html = '';
        foreach ($teachers as $teacher) {
            $teacher_id = isset($teacher['teacher_id']) ? $teacher['teacher_id'] : $teacher['user_id'];
            $mobile_html .= '<div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 font-weight-bold">'
                        .htmlspecialchars("{$teacher['firstname']} {$teacher['lastname']} {$teacher['extension']}").
                    '</h6>
                    <span class="badge bg-info">'.htmlspecialchars($teacher['unit'] ?? 'N/A').'</span>
                </div>
                <p class="mb-2 text-muted small">
                    <i class="fas fa-envelope mr-1"></i> '.htmlspecialchars($teacher['email']).'
                </p>
                <div class="d-flex justify-content-end">
                    <button class="btn btn-sm btn-success mr-2 edit-teacher" 
                        data-id="'.$teacher_id.'" 
                        data-firstname="'.htmlspecialchars($teacher['firstname']).'"
                        data-middlename="'.htmlspecialchars($teacher['middlename']).'"
                        data-lastname="'.htmlspecialchars($teacher['lastname']).'"
                        data-extension="'.htmlspecialchars($teacher['extension']).'"
                        data-email="'.htmlspecialchars($teacher['email']).'"
                        data-unit="'.htmlspecialchars($teacher['unit'] ?? '').'">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-teacher" data-id="'.$teacher_id.'">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>';
        }
        $response['mobile_html'] = $mobile_html;

        $desktop_html = '';
        foreach ($teachers as $teacher) {
            $teacher_id = isset($teacher['teacher_id']) ? $teacher['teacher_id'] : $teacher['user_id'];
            $desktop_html .= '<tr>
                <td>'.htmlspecialchars("{$teacher['firstname']} {$teacher['lastname']} {$teacher['extension']}").'</td>
                <td>'.htmlspecialchars($teacher['email']).'</td>
                <td>'.htmlspecialchars($teacher['unit'] ?? 'N/A').'</td>
                <td>
                    <button class="btn btn-sm btn-success edit-teacher" 
                        data-id="'.$teacher_id.'" 
                        data-firstname="'.htmlspecialchars($teacher['firstname']).'"
                        data-middlename="'.htmlspecialchars($teacher['middlename']).'"
                        data-lastname="'.htmlspecialchars($teacher['lastname']).'"
                        data-extension="'.htmlspecialchars($teacher['extension']).'"
                        data-email="'.htmlspecialchars($teacher['email']).'"
                        data-unit="'.htmlspecialchars($teacher['unit'] ?? '').'">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-danger delete-teacher" data-id="'.$teacher_id.'">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </td>
            </tr>';
        }
        $response['desktop_html'] = $desktop_html;
    }

    if ($total_pages > 1) {
        $mobile_pagination = '<div class="list-group-item">
            <nav><ul class="pagination justify-content-center mt-3">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $mobile_pagination .= '<li class="page-item '.($page == $i ? 'active' : '').'">
                    <a class="page-link page-link-ajax" href="#" data-page="'.$i.'">'.$i.'</a>
                </li>';
        }
        $mobile_pagination .= '</ul></nav></div>';
        $response['mobile_pagination'] = $mobile_pagination;

        $desktop_pagination = '<nav><ul class="pagination justify-content-center mt-3">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $desktop_pagination .= '<li class="page-item '.($page == $i ? 'active' : '').'">
                    <a class="page-link page-link-ajax" href="#" data-page="'.$i.'">'.$i.'</a>
                </li>';
        }
        $desktop_pagination .= '</ul></nav>';
        $response['desktop_pagination'] = $desktop_pagination;
    }

    $response['success'] = true;
    $response['total_teachers'] = $total_teachers;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
exit();