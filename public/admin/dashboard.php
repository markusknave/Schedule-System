<?php
session_start();
@include '../../components/links.php';

// Check if the user is logged in
if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

// Pagination settings
$limit = 5; // Teachers per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Base query conditions
$office_id = $_SESSION['office_id'];
$where_clause = "WHERE t.office_id = $office_id AND t.deleted_at IS NULL AND u.deleted_at IS NULL";

// Add search conditions if search term exists
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_clause .= " AND (u.firstname LIKE '%$search%' OR u.lastname LIKE '%$search%' OR u.email LIKE '%$search%' OR t.unit LIKE '%$search%')";
}

// Get total number of teachers (with search if applicable)
$total_query = "SELECT COUNT(*) AS total 
                FROM teachers t
                JOIN users u ON t.user_id = u.id
                $where_clause";
$total_result = $conn->query($total_query);
$total_teachers = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_teachers / $limit);

// Fetch teachers with limit for pagination (and search if applicable)
$query = "SELECT t.id, t.unit, t.created_at, 
        u.firstname, u.middlename, u.lastname, u.extension, u.email
        FROM teachers t
        JOIN users u ON t.user_id = u.id
         $where_clause
         ORDER BY u.lastname, u.firstname
        LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

$shown_count = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Teachers Management</title>
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
    </style>

</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Teachers Management</h1>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="content">
                <div class="container-fluid">
                    <!-- Action Buttons -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button class="btn btn-primary" id="addTeacherButton">
                                        <i class="fas fa-plus"></i> Add New Teacher
                                    </button>
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
                                                        data-id="<?= $row['id'] ?>" 
                                                        data-firstname="<?= htmlspecialchars($row['firstname']) ?>"
                                                        data-middlename="<?= htmlspecialchars($row['middlename']) ?>"
                                                        data-lastname="<?= htmlspecialchars($row['lastname']) ?>"
                                                        data-extension="<?= htmlspecialchars($row['extension']) ?>"
                                                        data-email="<?= htmlspecialchars($row['email']) ?>"
                                                        data-unit="<?= htmlspecialchars($row['unit']) ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-teacher" data-id="<?= $row['id'] ?>">
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

                    <div class="d-none d-md-block"> <!-- Desktop view -->
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="table-responsive">
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
                                            // Reset result pointer
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
                                                                data-id="<?= $row['id'] ?>" 
                                                                data-firstname="<?= htmlspecialchars($row['firstname']) ?>"
                                                                data-middlename="<?= htmlspecialchars($row['middlename']) ?>"
                                                                data-lastname="<?= htmlspecialchars($row['lastname']) ?>"
                                                                data-extension="<?= htmlspecialchars($row['extension']) ?>"
                                                                data-email="<?= htmlspecialchars($row['email']) ?>"
                                                                data-unit="<?= htmlspecialchars($row['unit']) ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-teacher" data-id="<?= $row['id'] ?>">
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

                    <!-- Add Teacher Modal -->
                    <div class="modal fade" id="addTeacherModal" tabindex="-1" role="dialog" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addTeacherModalLabel">Add New Teacher</h5>
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form id="addTeacherForm" action="/myschedule/components/teach_comp/add_teacher.php" method="POST">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="firstname">First Name</label>
                                            <input type="text" class="form-control" id="firstname" name="firstname" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="middlename">Middle Name</label>
                                            <input type="text" class="form-control" id="middlename" name="middlename">
                                        </div>
                                        <div class="form-group">
                                            <label for="lastname">Last Name</label>
                                            <input type="text" class="form-control" id="lastname" name="lastname" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="extension">Extension (Jr., Sr., I, etc.)</label>
                                            <input type="text" class="form-control" id="extension" name="extension">
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="unit">Unit</label>
                                            <input type="text" class="form-control" id="unit" name="unit" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Save Teacher</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Teacher Modal -->
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
                                <form id="editTeacherForm" action="/myschedule/components/teach_comp/edit_teacher.php" method="POST">
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

                    <!-- Delete Confirmation Modal -->
                    <div class="modal fade" id="deleteTeacherModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirm Deletion</h5>
                                </div>
                                <form id="deleteTeacherForm" action="/myschedule/components/teach_comp/delete_teacher.php" method="POST">
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

                    <script src="../../assets/js/teacher.js"></script>
                </div>
            </section>
        </div>
    </div>
</body>
</html>