<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/components/login.html");
    exit();
}
// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

// Pagination settings
$limit = 5; // Teachers per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : ""; // Get search term from URL

// Base query conditions
$office_id = $_SESSION['office_id'];
$where_clause = "WHERE office_id = $office_id";

// Add search conditions if search term exists
if (!empty($search)) {
    $search = $conn->real_escape_string($search); // Prevent SQL injection
    $where_clause .= " AND (firstname LIKE '%$search%' OR lastname LIKE '%$search%' OR email LIKE '%$search%' OR unit LIKE '%$search%')";
}

// Get total number of teachers (with search if applicable)
$total_result = $conn->query("SELECT COUNT(*) AS total FROM teachers $where_clause");
$total_teachers = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_teachers / $limit);

// Fetch teachers with limit for pagination (and search if applicable)
$query = "SELECT * FROM teachers $where_clause LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

$shown_count = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Teachers Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<?php include COMPONENTS_PATH . '/loading_screen.php'; ?>
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="nav-link">Logged in as, <?php echo htmlspecialchars($_SESSION['office_name'] ?? 'User'); ?></span>
                </li>
            </ul>
        </nav>
        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color:rgb(5, 29, 160);">
            <div class="container overflow-hidden">

                <a href="#" class="brand-link">
                    <img src="/myschedule/assets/img/favicon.png" width="35" height="35" alt="" class="ml-2">
                    <span class="brand-text font-weight-light">LNU Teacher's Board</span>
                </a>
            </div>
                <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link active">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Teachers Management</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="schedule.php" class="nav-link">
                                <i class="nav-icon fa fa-calendar"></i>
                                <p>Schedules</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rooms.php" class="nav-link">
                                <i class="nav-icon fas fa-grip-horizontal"></i>
                                <p>Rooms</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="announcements.php" class="nav-link">
                                <i class="nav-icon fa fa-exclamation-circle"></i>
                                <p>Announcements</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="archived.php" class="nav-link">
                                <i class="nav-icon fa fa-archive"></i>
                                <p>Archived</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>
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
                    <div class="card">
                    <div class="card-header d-flex justify-content-between">
                            <form method="GET" class="d-flex" style="width: 50%">
                                <input type="text" id="searchInput" class="form-control" placeholder="Search teachers...">
                                <button type="submit" class="btn btn-primary ml-2">Search</button>
                            </form>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">Add Teacher</button>
                        </div>

<div class="d-block d-md-none"> <!-- Mobile view (hidden on md screens and up) -->
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-chalkboard-teacher mr-2"></i>Teachers Management</h5>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php 
                // Reset result pointer if needed
                if ($result->num_rows > 0) {
                    $result->data_seek(0);
                }
                while ($row = $result->fetch_assoc()): ?>
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
                        <button class="btn btn-sm btn-success mr-2" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editTeacherModal"
                            onclick='editTeacher(<?= $row['id'] ?>, "<?= addslashes($row['firstname']) ?>", "<?= addslashes($row['middlename']) ?>", "<?= addslashes($row['lastname']) ?>", "<?= addslashes($row['extension']) ?>", "<?= addslashes($row['email']) ?>", "<?= addslashes($row['unit']) ?>")'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" 
                            data-bs-toggle="modal" 
                            data-bs-target="#deleteTeacherModal"
                            onclick='deleteTeacher(<?= $row['id'] ?>)'>
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <div class="card-footer bg-light">
        <div class="d-flex justify-content-center mb-3">
        <span class="badge bg-primary p-2">
            <i class="fas fa-users mr-1"></i> 
            Total Teacher<?php echo $total_teachers !== 1 ? 's' : ''; ?>: 
            <strong><?= $total_teachers ?></strong>
        </span>
    </div>
            
            <!-- Mobile Pagination -->
            <nav aria-label="Page navigation" class="mt-2">
    <ul class="pagination pagination-sm justify-content-center">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= max(1, $page - 1) ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
        <?php 
        // Show limited pagination links on mobile
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
<div class="d-none d-md-block"> <!-- Desktop view (hidden on mobile) -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Unit</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="teachersTableBody">
            <?php 
            // Reset pointer to reuse result set
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= "{$row['firstname']} {$row['lastname']} {$row['extension']}" ?></td>
                    <td><?= $row['email'] ?></td>
                    <td><?= $row['unit'] ?></td>
                    <td>
                        <button class="btn btn-success btn-sm mr-2" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editTeacherModal"
                            onclick='editTeacher(<?= $row['id'] ?>, "<?= $row['firstname'] ?>", "<?= $row['middlename'] ?>", "<?= $row['lastname'] ?>", "<?= $row['extension'] ?>", "<?= $row['email'] ?>", "<?= $row['unit'] ?>")'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-danger btn-sm mr-2" 
                            data-bs-toggle="modal" 
                            data-bs-target="#deleteTeacherModal"
                            onclick='deleteTeacher(<?= $row['id'] ?>)'>
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <div class="d-flex justify-content-center mb-3">
        <span class="badge bg-primary p-2">
            <i class="fas fa-users mr-1"></i> 
            Total Teacher<?php echo $total_teachers !== 1 ? 's' : ''; ?>: 
            <strong><?= $total_teachers ?></strong>
        </span>
    </div>
</div>

    <!-- Edit Teacher Modal -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Teacher</h5>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form action="/myschedule/components/edit_teacher.php" method="POST">
                        <input type="hidden" id="edit_teacher_id" name="teacher_id">
                        <div class="mb-3">
                            <label>First Name</label>
                            <input type="text" class="form-control" id="edit_firstname" name="firstname" required>
                        </div>
                        <div class="mb-3">
                            <label>Middle Name</label>
                            <input type="text" class="form-control" id="edit_middlename" name="middlename">
                        </div>
                        <div class="mb-3">
                            <label>Last Name</label>
                            <input type="text" class="form-control" id="edit_lastname" name="lastname" required>
                        </div>
                        <div class="mb-3">
                            <label>Extension (Jr., Sr., I, etc.)</label>
                            <input type="text" class="form-control" id="edit_extension" name="extension">
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label>Unit</label>
                            <input type="text" class="form-control" id="edit_unit" name="unit" required>
                        </div>
                        <button type="submit" class="btn btn-success">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Teacher</h5>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form action="/myschedule/components/add_teacher.php" method="POST">
                        <div class="mb-3">
                            <label>First Name</label>
                            <input type="text" class="form-control" name="firstname" required>
                        </div>
                        <div class="mb-3">
                            <label>Middle Name</label>
                            <input type="text" class="form-control" name="middlename">
                        </div>
                        <div class="mb-3">
                            <label>Last Name</label>
                            <input type="text" class="form-control" name="lastname" required>
                        </div>
                        <div class="mb-3">
                            <label>Extension (Jr., Sr., I, etc.)</label>
                            <input type="text" class="form-control" name="extension">
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label>Unit</label>
                            <input type="text" class="form-control" name="unit" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Teacher</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<!-- Delete Teacher Confirmation Modal -->
<div class="modal fade" id="deleteTeacherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this teacher?</p>
            </div>
            <div class="modal-footer">
                <form action="/myschedule/components/delete_teacher.php" method="POST">
                    <input type="hidden" id="delete_teacher_id" name="teacher_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
    <script>
    function editTeacher(id, firstname, middlename, lastname, extension, email, unit) {
        document.getElementById('edit_teacher_id').value = id;
        document.getElementById('edit_firstname').value = firstname;
        document.getElementById('edit_middlename').value = middlename;
        document.getElementById('edit_lastname').value = lastname;
        document.getElementById('edit_extension').value = extension;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_unit').value = unit;
    }

    function deleteTeacher(id) {
    document.getElementById('delete_teacher_id').value = id;
}

// Modify your AJAX call to show loading state
function loadTeachers($search = "", $page = 1) {
    $('#teachersTableBody').html('<tr><td colspan="4" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
    
    $.ajax({
        url: '/myschedule/components/fetch_teachers.php',
        type: 'GET',
        data: { search: search, page: page },
        success: function(response) {
            $('#teachersTableBody').html(response);
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            $('#teachersTableBody').html('<tr><td colspan="4" class="text-center text-danger">Error loading data</td></tr>');
        }
    });
}

$(document).ready(function () {
    $('#search-input').on('keyup', function () {
        var query = $(this).val();
        $.ajax({
            url: '/myschedule/components/search_teachers.php',
            type: 'GET',
            data: { search: query },
            success: function (data) {
                $('#teachers-table-body').html(data);
            }
        });
    });
});

let timer;
$('#search-input').on('keyup', function () {
    clearTimeout(timer);
    const query = $(this).val();
    timer = setTimeout(() => {
        $.ajax({
            url: '/myschedule/components/search_teachers.php',
            type: 'GET',
            data: { search: query },
            success: function (data) {
                $('#teachers-table-body').html(data);
            }
        });
    }, 300); // Wait 300ms after user stops typing
});

$(document).ready(function() {
    // Only initialize AJAX pagination for desktop view
    if ($(window).width() >= 768) { // md breakpoint
        function loadTeachers(search = "", page = 1) {
            $.ajax({
                url: '/myschedule/components/fetch_teachers.php',
                type: 'GET',
                data: { search: search, page: page },
                success: function(response) {
                    $('#teachersTableBody').html(response);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    // Fallback to regular page reload if AJAX fails
                    window.location.href = 'dashboard.php?page=' + page + 
                    (search ? '&search=' + encodeURIComponent(search) : '');
                }
            });
        }

        // Initial load
        loadTeachers();

        // Live search
        $('#searchInput').on('input', function() {
            const searchVal = $(this).val();
            loadTeachers(searchVal, 1);
        });

        // Handle pagination click for desktop
        $(document).on('click', '.page-link-ajax', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            const searchVal = $('#searchInput').val();
            loadTeachers(searchVal, page);
        });
    }
    
    // Mobile view will use regular links, no AJAX needed
});
    </script>
    <script src="/myschedule/assets/js/loading_screen.js"></script>
</body>
</html>
