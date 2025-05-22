<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'total_offices' => 0,
    'mobile_html' => '<div class="list-group-item text-center">No offices found.</div>',
    'desktop_html' => '<tr><td colspan="3" class="text-center">No offices found.</td></tr>',
    'mobile_pagination' => '',
    'desktop_pagination' => ''
];

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (isset($_GET['mobile']) && $_GET['mobile'] == 'true' ? 5 : 10);
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? trim($_GET['search']) : "";
    $is_mobile = isset($_GET['mobile']) && $_GET['mobile'] == 'true';

    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Session expired. Please login again.");
    }

    $where_clause = "WHERE deleted_at IS NULL";
    
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $where_clause .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')";
    }

    $total_query = "SELECT COUNT(*) AS total FROM offices $where_clause";
    $total_result = $conn->query($total_query);
    
    if (!$total_result) {
        throw new Exception("Database error: " . $conn->error);
    }

    $total_row = $total_result->fetch_assoc();
    $total_offices = isset($total_row['total']) ? (int)$total_row['total'] : 0;
    $total_pages = ceil($total_offices / $limit);

    $query = "SELECT * FROM offices $where_clause ORDER BY name LIMIT $limit OFFSET $offset";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database error: " . $conn->error);
    }

    $offices = $result->fetch_all(MYSQLI_ASSOC);

    if (!empty($offices)) {
        $mobile_html = '';
        foreach ($offices as $office) {
            $mobile_html .= '<div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 font-weight-bold">'
                        .htmlspecialchars($office['name']).
                    '</h6>
                </div>
                <p class="mb-2 text-muted small">
                    <i class="fas fa-envelope mr-1"></i> '.htmlspecialchars($office['email']).'
                </p>
                <div class="d-flex justify-content-end">
                    <button class="btn btn-sm btn-success mr-2 edit-office" 
                        data-id="'.$office['id'].'" 
                        data-name="'.htmlspecialchars($office['name']).'"
                        data-email="'.htmlspecialchars($office['email']).'">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-office" data-id="'.$office['id'].'">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>';
        }
        $response['mobile_html'] = $mobile_html;

        $desktop_html = '';
        foreach ($offices as $office) {
            $desktop_html .= '<tr>
                <td>'.htmlspecialchars($office['name']).'</td>
                <td>'.htmlspecialchars($office['email']).'</td>
                <td>
                    <button class="btn btn-sm btn-success edit-office" 
                        data-id="'.$office['id'].'" 
                        data-name="'.htmlspecialchars($office['name']).'"
                        data-email="'.htmlspecialchars($office['email']).'">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-danger delete-office" data-id="'.$office['id'].'">
                        <i class="fas fa-trash"></i> Archive
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
    $response['total_offices'] = $total_offices;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
exit();