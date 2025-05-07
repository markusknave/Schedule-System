<?php
session_start();
@include '../../components/links.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/components/login.html");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$limit = isset($_GET['mobile']) ? 5 : 7;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$office_id = $_SESSION['office_id'];
$where_clause = "WHERE s.office_id = $office_id AND s.deleted_at IS NULL";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_clause .= " AND s.subject_name LIKE '%$search%'";
}

$total_result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM sections s
    $where_clause
");
$total_sections = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_sections / $limit);

$sections_query = $conn->query("
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
$sections = $sections_query->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Section Management</title>

</head>
<style>
    th {
        min-width: 300px !important;
        text-align: center !important;
    }

    tr {
        min-width: 300px !important;
        text-align: center !important;
    }
</style>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/sidebar.php'; ?>
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Sections Management</h1>
                        </div>
                        <div class="col-sm-6" id="messageContainer">
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
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button class="btn btn-primary" id="addSectionButton">
                                        <i class="fas fa-plus"></i> Add New Section
                                    </button>
                                </div>
                                <div>
                                    <form class="d-flex" style="width: 300px">
                                        <input type="text" id="searchInput" class="form-control" placeholder="Search sections..." 
                                            value="<?= htmlspecialchars($search) ?>">
                                        <button type="button" class="btn btn-primary ml-2" id="searchButton">Search</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-block d-md-none"> 
                        <div class="card mb-4 border-primary">
                            <div class="card-header bg-dark text-white">
                                <h5 class="mb-0"><i class="fas fa-users mr-2"></i>Sections Management</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush" id="mobileSectionsList">
                                    <?php if (empty($sections)): ?>
                                        <div class="list-group-item text-center">No sections found for your office.</div>
                                    <?php else: ?>
                                        <?php foreach ($sections as $section): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0 font-weight-bold">
                                                        <?= htmlspecialchars($section['section_name']) ?>
                                                    </h6>
                                                    <span class="badge bg-info"><?= htmlspecialchars($section['schedule_count']) ?> schedules</span>
                                                </div>
                                                <div class="d-flex justify-content-end">
                                                    <button class="btn btn-sm btn-info mr-2 view-section" data-id="<?= $section['id'] ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-success mr-2 edit-section" 
                                                        data-id="<?= $section['id'] ?>" 
                                                        data-name="<?= htmlspecialchars($section['section_name']) ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-section" data-id="<?= $section['id'] ?>">
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
                                        <i class="fas fa-users mr-1"></i> 
                                        Total Section<?= $total_sections !== 1 ? 's' : '' ?>: 
                                        <strong><?= $total_sections ?></strong>
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
                                    <table class="table table-striped table-hover table-fixed-layout overflow-hidden">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Section Name</th>
                                                <th>Schedule Count</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sectionsTableBody">
                                            <?php if (empty($sections)): ?>
                                                <tr><td colspan="3" class="text-center">No sections found for your office.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($sections as $section): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($section['section_name']) ?></td>
                                                        <td><?= htmlspecialchars($section['schedule_count']) ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-info view-section" data-id="<?= $section['id'] ?>">
                                                                <i class="fas fa-eye"></i> View
                                                            </button>
                                                            <button class="btn btn-sm btn-success edit-section" 
                                                                data-id="<?= $section['id'] ?>" 
                                                                data-name="<?= htmlspecialchars($section['section_name']) ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-section" data-id="<?= $section['id'] ?>">
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

                    <div class="modal fade" id="addSectionModal" tabindex="-1" role="dialog" aria-labelledby="addSectionModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addSectionModalLabel">Add New Section</h5>
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form id="addSectionForm" action="/myschedule/components/sec_comp/add_section.php" method="POST">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="sectionName">Section Name</label>
                                            <input type="text" class="form-control" id="sectionName" name="section_name" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Save Section</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="editSectionModal" tabindex="-1" role="dialog" aria-labelledby="editSectionModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editSectionModalLabel">Edit Section</h5>
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form id="editSectionForm" action="/myschedule/components/sec_comp/edit_section.php" method="POST">
                                    <input type="hidden" id="editSectionId" name="section_id">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="editSectionName">Section Name</label>
                                            <input type="text" class="form-control" id="editSectionName" name="section_name" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Update Section</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="deleteSectionModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirm Deletion</h5>
                                </div>
                                <form id="deleteSectionForm" action="/myschedule/components/sec_comp/delete_section.php" method="POST">
                                    <input type="hidden" id="deleteSectionId" name="section_id">
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete this section? This action cannot be undone.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Delete Section</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="../../assets/js/sections.js"></script>
    <script>
        const phpVars = {
            searchTerm: '<?= htmlspecialchars($search, ENT_QUOTES) ?>',
            currentPage: <?= $page ?>,
            baseUrl: '/myschedule/components/sec_comp/'
        };
    </script>
</body>
</html>