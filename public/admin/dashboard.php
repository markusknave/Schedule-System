<?php
include_once __DIR__ . '/logger.php';
session_start();
@include '../../components/links.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /myschedule/login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$admin_user_id = $_SESSION['user_id'];

$stmt_admin = $conn->prepare("
    SELECT u.st_leave, u.end_leave, r.name AS status_code 
    FROM users u 
    LEFT JOIN roles r ON u.status_id = r.id 
    WHERE u.id = ?
");
$stmt_admin->bind_param("i", $admin_user_id);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
$admin_data = $result_admin->fetch_assoc();

$st_leave = $admin_data['st_leave'] ?? null;
$end_leave = $admin_data['end_leave'] ?? null;

if (!isset($_SESSION['status'])) {
    $_SESSION['status'] = $admin_data['status_code'] ?? 'UN';
}

$limit = isset($_GET['mobile']) ? 5 : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

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
$total_teachers = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_teachers / $limit);

$query = "SELECT u.id, u.firstname, u.middlename, u.lastname, u.extension, u.email,
                 t.id as teacher_id, t.unit, t.created_at,
                 r.name AS status_code, u.st_leave, u.end_leave
          FROM users u
          $join_clause
          LEFT JOIN roles r ON u.status_id = r.id
          $where_clause
          ORDER BY u.lastname, u.firstname
          LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

$shown_count = $result->num_rows;

// Handle log clearing (truncate, not delete)
if (isset($_POST['clear_log']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    $log_file = __DIR__ . '/log.txt';
    file_put_contents($log_file, "");
    $log_cleared = true;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers Management</title>
    <link rel="stylesheet" href="/myschedule/assets/css/teacher.css">
    <style>
        th {
        min-width: 300px !important;
        text-align: center !important;
        }

        tr{
            min-width: 300px !important;
            text-align: center !important;
        }

        .status-container {
            background-color: #f8f9fa;
            padding: 8px 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }

        .status-label {
            font-weight: 500;
            margin-right: 10px;
            white-space: nowrap;
        }

        .status-date {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>

</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/admin_sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2 align-items-center">
                        <div class="col-sm-6">
                            <h1>Teachers Management</h1>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex flex-column align-items-end">
                                <div class="d-flex align-items-center status-container">
                                    <span class="status-label">Update Your Status:</span>
                                    <select id="adminStatusSelect" class="form-control w-auto"> 
                                        <option value="A" <?= $_SESSION['status'] === 'A' ? 'selected' : '' ?>>Available</option>
                                        <option value="OL" <?= $_SESSION['status'] === 'OL' ? 'selected' : '' ?>>On-Leave</option>
                                        <option value="OT" <?= $_SESSION['status'] === 'OT' ? 'selected' : '' ?>>On-Travel</option>
                                        <option value="B" <?= $_SESSION['status'] === 'B' ? 'selected' : '' ?>>Busy</option>
                                        <option value="UN" <?= $_SESSION['status'] === 'UN' ? 'selected' : '' ?>>Unavailable</option>
                                    </select>
                                </div>
                                <?php if (($_SESSION['status'] === 'OL' || $_SESSION['status'] === 'OT') && $st_leave && $end_leave): ?>
                                    <div class="status-date" style="color: red;">
                                        <?= $_SESSION['status'] === 'OL' ? 'On-Leave' : 'On-Travel' ?>: 
                                        <?= date('M d, Y', strtotime($st_leave)) ?> - <?= date('M d, Y', strtotime($end_leave)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="content">
                <div class="container-fluid">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between">
                                <div>
                                </div>
                                <div>
                                    <form class="d-flex" style="width: 300px">
                                        <input type="text" id="searchInput" class="form-control" placeholder="Search teachers..." 
                                            value="<?= htmlspecialchars($search) ?>">
                                        <button type="button" class="btn btn-primary ml-2" id="searchButton">Search</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-block d-md-none"> <!-- Mobile view -->
                        <div class="card mb-4 border-primary">
                            <div class="card-header bg-dark text-white">
                                <h5 class="mb-0"><i class="fas fa-users mr-2"></i>Teachers Management</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush" id="mobileTeachersList">
                                    <?php if ($result->num_rows == 0): ?>
                                        <div class="list-group-item text-center">No teachers found.</div>
                                    <?php else: ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0 font-weight-bold">
                                                        <?= htmlspecialchars("{$row['firstname']} {$row['lastname']} {$row['extension']}") ?>
                                                    </h6>
                                                    <span class="badge bg-info"><?= htmlspecialchars($row['unit']) ?></span>
                                                </div>
                                                <p class="mb-2 text-muted small">
                                                    <i class="fas fa-envelope mr-1"></i> <?= htmlspecialchars($row['email']) ?>
                                                </p>
                                                <div class="d-flex justify-content-end">
                                                    <button class="btn btn-sm btn-success mr-2 edit-teacher" 
                                                        data-id="<?= $row['teacher_id'] ?>" 
                                                        data-firstname="<?= htmlspecialchars($row['firstname']) ?>"
                                                        data-middlename="<?= htmlspecialchars($row['middlename']) ?>"
                                                        data-lastname="<?= htmlspecialchars($row['lastname']) ?>"
                                                        data-extension="<?= htmlspecialchars($row['extension']) ?>"
                                                        data-email="<?= htmlspecialchars($row['email']) ?>"
                                                        data-unit="<?= htmlspecialchars($row['unit']) ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-teacher" data-id="<?= $row['teacher_id'] ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-center mb-3">
                                    <span class="badge bg-primary p-2">
                                        <i class="fas fa-users mr-1"></i> 
                                        Total Teacher<?= $total_teachers !== 1 ? 's' : '' ?>: 
                                        <strong><?= $total_teachers ?></strong>
                                    </span>
                                </div>
                                
                                <nav aria-label="Page navigation" class="mt-2">
                                    <ul class="pagination pagination-sm justify-content-center">
                                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= max(1, $page - 1) ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <?php 
                                        $start = max(1, $page - 2);
                                        $end = min($total_pages, $page + 2);
                                        
                                        for ($i = $start; $i <= $end; $i++): ?>
                                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= min($total_pages, $page + 1) ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>

                    <div class="d-none d-md-block overflow-hidden"> <!-- Desktop view -->
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="table-responsive ">
                                    <table class="table table-striped table-hover">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Unit</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="teachersTableBody">
                                            <?php 
                                            $result->data_seek(0);
                                            if ($result->num_rows == 0): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No teachers found.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php while ($row = $result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars("{$row['firstname']} {$row['lastname']} {$row['extension']}") ?></td>
                                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                                        <td><?= htmlspecialchars($row['unit']) ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-success edit-teacher" 
                                                                data-id="<?= $row['teacher_id'] ?>" 
                                                                data-firstname="<?= htmlspecialchars($row['firstname']) ?>"
                                                                data-middlename="<?= htmlspecialchars($row['middlename']) ?>"
                                                                data-lastname="<?= htmlspecialchars($row['lastname']) ?>"
                                                                data-extension="<?= htmlspecialchars($row['extension']) ?>"
                                                                data-email="<?= htmlspecialchars($row['email']) ?>"
                                                                data-unit="<?= htmlspecialchars($row['unit']) ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-teacher" data-id="<?= $row['teacher_id'] ?>">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="editTeacherModal" tabindex="-1" role="dialog" 
                            aria-labelledby="editTeacherModalLabel" aria-hidden="true"
                            data-bs-backdrop="true" data-bs-keyboard="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editTeacherModalLabel">Edit Teacher</h5>
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form id="editTeacherForm" action="/myschedule/components/admin_comp/user_comp/edit_user.php" method="POST">
                                    <input type="hidden" id="editTeacherId" name="teacher_id">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="editFirstname">First Name</label>
                                            <input type="text" class="form-control" id="editFirstname" name="firstname" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="editMiddlename">Middle Name</label>
                                            <input type="text" class="form-control" id="editMiddlename" name="middlename">
                                        </div>
                                        <div class="form-group">
                                            <label for="editLastname">Last Name</label>
                                            <input type="text" class="form-control" id="editLastname" name="lastname" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="editExtension">Extension (Jr., Sr., I, etc.)</label>
                                            <input type="text" class="form-control" id="editExtension" name="extension">
                                        </div>
                                        <div class="form-group">
                                            <label for="editEmail">Email</label>
                                            <input type="email" class="form-control" id="editEmail" name="email" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="editPassword">New Password (leave blank to keep current)</label>
                                            <input type="password" class="form-control" id="editPassword" name="password">
                                        </div>
                                        <div class="form-group">
                                            <label for="editUnit">Unit</label>
                                            <input type="text" class="form-control" id="editUnit" name="unit" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Update Teacher</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="deleteTeacherModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirm Deletion</h5>
                                </div>
                                <form id="deleteTeacherForm" action="/myschedule/components/admin_comp/user_comp/delete_user.php" method="POST">
                                    <input type="hidden" id="deleteTeacherId" name="teacher_id">
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete this teacher? This action cannot be undone.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Delete Teacher</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="adminDateModal" tabindex="-1" role="dialog" aria-labelledby="adminDateModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="adminDateModalLabel">Set Leave/Travel Dates</h5>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="adminDateForm">
                                    <div class="form-group">
                                        <label for="adminStartDate">Start Date</label>
                                        <input type="date" class="form-control" id="adminStartDate" name="start_date" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="adminEndDate">End Date</label>
                                        <input type="date" class="form-control" id="adminEndDate" name="end_date" required>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="adminSaveDates">Save</button>
                            </div>
                        </div>
                    </div>
                </div>

                    <script src="../../assets/js/user.js"></script>
                </div>
            </section>
        </div>
    </div>

    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
    <div class="card mt-5">
        <div class="card-header"><strong>System Log</strong></div>
        <div class="card-body">
            <form method="POST" onsubmit="return confirm('Are you sure you want to clear the log?');">
                <button type="submit" name="clear_log" class="btn btn-danger mb-3">Clear Log</button>
            </form>
            <pre style="max-height:300px;overflow:auto;background:#222;color:#eee;padding:1em;"><?php
                $log_file = __DIR__ . '/log.txt';
                if (file_exists($log_file)) {
                    echo htmlspecialchars(file_get_contents($log_file));
                } else {
                    echo 'No log entries yet.';
                }
            ?></pre>
            <?php if (!empty($log_cleared)) echo '<div class="alert alert-success mt-2">Log cleared.</div>'; ?>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>