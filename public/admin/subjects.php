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
    <link rel="stylesheet" href="/myschedule/assets/css/subject.css">
</head>
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
<body class="hold-transition sidebar-mini layout-fixed">
    <input type="hidden" value="<?= $page ?>" id="current-page">
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/sidebar.php'; ?>
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Subject Management</h1>
                        </div>
                        <div class="col-sm-6" id="messageContainer"></div>
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
                </div>
            </section>
        </div>
    </div>
    <script src="../../assets/js/subjects.js"></script>
</body>
</html>