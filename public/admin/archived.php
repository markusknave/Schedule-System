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
                </li>
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
                            <a href="dashboard.php" class="nav-link">
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
                            <a href="archived.php" class="nav-link active">
                                <i class="nav-icon fa fa-archive"></i>
                                <p>Archived</p>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div style="position: absolute; bottom: 0;" class="nav-item overflow-hidden">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="/myschedule/components/logout.php" class="nav-link">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Logout</p>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Teachers Schedules</h1>
                        </div>
                    </div>
                </div>
            </section>
            <section class="content">
                <div class="container-fluid">
                    <div class="card">
                    <div class="card-header d-flex justify-content-between">
                            <form method="GET" class="d-flex" style="width: 50%">
                                <input type="text" id="searchInput" class="form-control" placeholder="Search Schedules...">
                                <button type="submit" class="btn btn-primary ml-2">Search</button>
                            </form>
                        </div>
                        
<script>
let timer;
$('#search-input').on('keyup', function () {
    clearTimeout(timer);
    const query = $(this).val();
    timer = setTimeout(() => {
        $.ajax({
            url: 'search_teachers.php',
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
                url: 'fetch_teachers.php',
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

        // Live search
        $('#searchInput').on('input', function() {
            const searchVal = $(this).val();
        });

        // Handle pagination click for desktop
        $(document).on('click', '.page-link-ajax', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            const searchVal = $('#searchInput').val();
        });
    }
});
    </script>
    <script src="/myschedule/assets/js/loading_screen.js"></script>
</body>
</html>
