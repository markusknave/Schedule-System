<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

// Pagination settings
$limit = 5; // Subjects per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Base query conditions
$where_clause = "WHERE s.office_id = {$_SESSION['office_id']} AND s.deleted_at IS NULL";

// Add search conditions if search term exists
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_clause .= " AND (name LIKE '%$search%' OR subject_code LIKE '%$search%')";
}

// Get total number of subjects (with search if applicable)
$total_result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM subjects s
    $where_clause
");
$total_subjects = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_subjects / $limit);


// Fetch subjects with limit for pagination (and search if applicable)
$subjects_query = $conn->query("
    SELECT 
        s.id,
        s.subject_code,
        s.name,
        s.created_at,
        COUNT(sc.id) AS schedule_count,
        MAX(sc.created_at) AS last_schedule_update
    FROM subjects s
    LEFT JOIN schedules sc ON s.id = sc.subject_id
    $where_clause
    GROUP BY s.id, s.subject_code, s.name, s.created_at
    ORDER BY s.subject_code, s.name
    LIMIT $limit OFFSET $offset
");
$subjects = $subjects_query->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Subject Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/myschedule/assets/css/subject.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
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
                            <a href="subjects.php" class="nav-link active">
                                <i class="nav-icon fas fa-book"></i>
                                <p>Subjects</p>
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
                            <h1>Subject Management</h1>
                        </div>
                        <div class="col-sm-6">
                            <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show float-right">
                                    <?php echo $_SESSION['success']; ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <?php unset($_SESSION['success']); ?>
                            <?php elseif (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show float-right">
                                    <?php echo $_SESSION['error']; ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <?php unset($_SESSION['error']); ?>
                            <?php endif; ?>
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
                                    <button class="btn btn-primary" id="addSubjectButton">
                                        <i class="fas fa-plus"></i> Add New Subject
                                    </button>
                                </div>
                                <div>
                                    <form class="d-flex" style="width: 300px">
                                        <input type="text" id="searchInput" class="form-control" placeholder="Search subjects..." 
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
                                <h5 class="mb-0"><i class="fas fa-book mr-2"></i>Subject Management</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush" id="mobileSubjectsList">
                                    <?php if (empty($subjects)): ?>
                                        <div class="list-group-item text-center">No subjects found.</div>
                                    <?php else: ?>
                                        <?php foreach ($subjects as $subject): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0 font-weight-bold">
                                                        <?= htmlspecialchars($subject['subject_code']) ?> - <?= htmlspecialchars($subject['name']) ?>
                                                    </h6>
                                                    <span class="badge bg-info"><?= htmlspecialchars($subject['schedule_count']) ?> schedules</span>
                                                </div>
                                                <div class="d-flex justify-content-end">
                                                    <button class="btn btn-sm btn-info mr-2 view-subject" data-id="<?= $subject['id'] ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-success mr-2 edit-subject" 
                                                        data-id="<?= $subject['id'] ?>" 
                                                        data-code="<?= htmlspecialchars($subject['subject_code']) ?>"
                                                        data-name="<?= htmlspecialchars($subject['name']) ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-subject" data-id="<?= $subject['id'] ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-center mb-3">
                                    <span class="badge bg-primary p-2">
                                        <i class="fas fa-book mr-1"></i> 
                                        Total Subject<?= $total_subjects !== 1 ? 's' : '' ?>: 
                                        <strong><?= $total_subjects ?></strong>
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
                                                <th>Subject Code</th>
                                                <th>Subject Name</th>
                                                <th>Schedule Count</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="subjectsTableBody">
                                            <?php if (empty($subjects)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No subjects found.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($subjects as $subject): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($subject['subject_code']) ?></td>
                                                        <td><?= htmlspecialchars($subject['name']) ?></td>
                                                        <td><?= htmlspecialchars($subject['schedule_count']) ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-info view-subject" data-id="<?= $subject['id'] ?>">
                                                                <i class="fas fa-eye"></i> View
                                                            </button>
                                                            <button class="btn btn-sm btn-success edit-subject" 
                                                                data-id="<?= $subject['id'] ?>" 
                                                                data-code="<?= htmlspecialchars($subject['subject_code']) ?>"
                                                                data-name="<?= htmlspecialchars($subject['name']) ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-subject" data-id="<?= $subject['id'] ?>">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show float-right">
                                <?php echo $_SESSION['success']; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php elseif (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show float-right">
                                <?php echo $_SESSION['error']; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>
                    </div>

                    <!-- Add Subject Modal -->
                    <div class="modal fade" id="addSubjectModal" tabindex="-1" role="dialog" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addSubjectModalLabel">Add New Subject</h5>
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form id="addSubjectForm" action="/myschedule/components/subj_comp/add_subject.php" method="POST">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="subjectCode">Subject Code</label>
                                            <input type="text" class="form-control" id="subjectCode" name="subject_code" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="subjectName">Subject Name</label>
                                            <input type="text" class="form-control" id="subjectName" name="name" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Save Subject</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Subject Modal -->
                    <div class="modal fade" id="editSubjectModal" tabindex="-1" role="dialog" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form id="editSubjectForm" action="/myschedule/components/subj_comp/edit_subject.php" method="POST">
                                    <input type="hidden" id="editSubjectId" name="subject_id">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="editSubjectCode">Subject Code</label>
                                            <input type="text" class="form-control" id="editSubjectCode" name="subject_code" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="editSubjectName">Subject Name</label>
                                            <input type="text" class="form-control" id="editSubjectName" name="name" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Update Subject</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Confirmation Modal -->
                    <div class="modal fade" id="deleteSubjectModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirm Deletion</h5>
                                </div>
                                <form id="deleteSubjectForm" action="/myschedule/components/subj_comp/del_subject.php" method="POST">
                                    <input type="hidden" id="deleteSubjectId" name="subject_id">
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete this subject? This action cannot be undone.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Delete Subject</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <script>
                    $(document).ready(function() {
                        // Show success/error messages
                        $('.alert').delay(3000).fadeOut('slow');

                        // Initialize all modals properly
                        $('.modal').modal({
                            show: false,
                            backdrop: 'static'
                        });

                        // Edit Subject Button Click
                        $(document).on('click', '.edit-subject', function() {
                            const subjectId = $(this).data('id');
                            const subjectCode = $(this).data('code');
                            const subjectName = $(this).data('name');
                            
                            $('#editSubjectId').val(subjectId);
                            $('#editSubjectCode').val(subjectCode);
                            $('#editSubjectName').val(subjectName);
                            $('#editSubjectModal').modal('show');
                        });

                        // Delete Subject Button Click
                        $(document).on('click', '.delete-subject', function() {
                            const subjectId = $(this).data('id');
                            $('#deleteSubjectId').val(subjectId);
                            $('#deleteSubjectModal').modal('show');
                        });

                        // View Subject Button Click
                        $(document).on('click', '.view-subject', function() {
                            const subjectId = $(this).data('id');
                            window.location.href = '/myschedule/components/subj_comp/subject_details.php?id=' + subjectId;
                        });

                        // Add Subject Button Click
                        $('#addSubjectButton').click(function() {
                            $('#addSubjectModal').modal('show');
                        });

                        // Add Subject Form Submission
                        $('#addSubjectForm').submit(function(e) {
                            e.preventDefault();
                            const form = $(this);
                            const formData = form.serialize();
                            
                            $.ajax({
                                url: form.attr('action'),
                                method: form.attr('method'),
                                data: formData,
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        $('#addSubjectModal').modal('hide');
                                        // Show success message
                                        $('<div class="alert alert-success alert-dismissible fade show float-right">' + 
                                        response.message + 
                                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                                        '<span aria-hidden="true">&times;</span></button></div>')
                                        .insertBefore('.content-header .container-fluid .row .col-sm-6')
                                        .delay(3000).fadeOut('slow', function() { $(this).remove(); });
                                        location.reload();
                                    } else {
                                        // Show error message in modal
                                        $('#addSubjectModal .modal-body').prepend(
                                            '<div class="alert alert-danger">' + response.message + '</div>'
                                        );
                                        // Remove error after 5 seconds
                                        setTimeout(function() {
                                            $('#addSubjectModal .alert-danger').fadeOut('slow', function() {
                                                $(this).remove();
                                            });
                                        }, 5000);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    $('#addSubjectModal .modal-body').prepend(
                                        '<div class="alert alert-danger">Error: ' + error + '</div>'
                                    );
                                    setTimeout(function() {
                                        $('#addSubjectModal .alert-danger').fadeOut('slow', function() {
                                            $(this).remove();
                                        });
                                    }, 5000);
                                }
                            });
                        });

                        // Edit Subject Form Submission
                        $('#editSubjectForm').submit(function(e) {
                            e.preventDefault();
                            const form = $(this);
                            const formData = form.serialize();
                            
                            $.ajax({
                                url: form.attr('action'),
                                method: form.attr('method'),
                                data: formData,
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        $('#editSubjectModal').modal('hide');
                                        // Show success message
                                        $('<div class="alert alert-success alert-dismissible fade show float-right">' + 
                                        response.message + 
                                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                                        '<span aria-hidden="true">&times;</span></button></div>')
                                        .insertBefore('.content-header .container-fluid .row .col-sm-6')
                                        .delay(3000).fadeOut('slow', function() { $(this).remove(); });
                                        location.reload();
                                    } else {
                                        // Show error message in modal
                                        $('#editSubjectModal .modal-body').prepend(
                                            '<div class="alert alert-danger">' + response.message + '</div>'
                                        );
                                        // Remove error after 5 seconds
                                        setTimeout(function() {
                                            $('#editSubjectModal .alert-danger').fadeOut('slow', function() {
                                                $(this).remove();
                                            });
                                        }, 5000);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    $('#editSubjectModal .modal-body').prepend(
                                        '<div class="alert alert-danger">Error: ' + error + '</div>'
                                    );
                                    setTimeout(function() {
                                        $('#editSubjectModal .alert-danger').fadeOut('slow', function() {
                                            $(this).remove();
                                        });
                                    }, 5000);
                                }
                            });
                        });

                        // Delete Subject Form Submission
                        $('#deleteSubjectForm').submit(function(e) {
                            e.preventDefault();
                            const form = $(this);
                            const formData = form.serialize();
                            
                            $.ajax({
                                url: form.attr('action'),
                                method: form.attr('method'),
                                data: formData,
                                success: function(response) {
                                    location.reload();
                                },
                                error: function(xhr, status, error) {
                                    alert('Error: ' + error);
                                }
                            });
                        });

                        // Dynamic search functionality
                        function loadSubjects(search = "", page = 1) {
                            const isMobile = $(window).width() < 768;
                            
                            // Show loading state
                            if (isMobile) {
                                $('#mobileSubjectsList').html('<div class="list-group-item text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
                            } else {
                                $('#subjectsTableBody').html('<tr><td colspan="4" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
                            }
                            
                            $.ajax({
                                url: '/myschedule/components/subj_comp/fetch_subjects.php',
                                type: 'GET',
                                data: { 
                                    search: search, 
                                    page: page,
                                    mobile: isMobile
                                },
                                success: function(response) {
                                    if (isMobile) {
                                        $('#mobileSubjectsList').html(response);
                                    } else {
                                        $('#subjectsTableBody').html(response);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error("AJAX Error:", status, error);
                                    // Fallback to regular page reload if AJAX fails
                                    window.location.href = 'subjects.php?page=' + page + 
                                        (search ? '&search=' + encodeURIComponent(search) : '');
                                }
                            });
                        }

                        // Handle window resize to switch between mobile and desktop views
                        let resizeTimer;
                        $(window).on('resize', function() {
                            clearTimeout(resizeTimer);
                            resizeTimer = setTimeout(function() {
                                const searchVal = $('#searchInput').val();
                                const currentPage = $('.page-item.active .page-link-ajax').data('page') || 1;
                                loadSubjects(searchVal, currentPage);
                            }, 200);
                        });

                        // Initial load based on current view
                        loadSubjects('<?= htmlspecialchars($search) ?>', <?= $page ?>);

                        // Live search with debounce
                        let searchTimer;
                        $('#searchInput').on('input', function() {
                            clearTimeout(searchTimer);
                            const searchVal = $(this).val();
                            searchTimer = setTimeout(() => {
                                loadSubjects(searchVal, 1);
                            }, 300);
                        });

                        // Search button click
                        $('#searchButton').click(function() {
                            const searchVal = $('#searchInput').val();
                            loadSubjects(searchVal, 1);
                        });

                        // Handle pagination click
                        $(document).on('click', '.page-link-ajax', function(e) {
                            e.preventDefault();
                            const page = $(this).data('page');
                            const searchVal = $('#searchInput').val();
                            loadSubjects(searchVal, page);
                        });
                    });
                    </script>
                </div>
            </section>
        </div>
    </div>
</body>
</html>