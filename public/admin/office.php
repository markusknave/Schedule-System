<?php
session_start();
@include '../../components/links.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /myschedule/login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$limit = isset($_GET['mobile']) ? 5 : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$where_clause = "WHERE deleted_at IS NULL";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_clause .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')";
}

$total_query = "SELECT COUNT(*) AS total FROM offices $where_clause";
$total_result = $conn->query($total_query);
$total_offices = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_offices / $limit);

$query = "SELECT * FROM offices $where_clause ORDER BY name LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

$shown_count = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offices Management</title>
    <link rel="stylesheet" href="/myschedule/assets/css/teacher.css">
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
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/admin_sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Offices Management</h1>
                        </div>
                        <div class="col-sm-6" id="messageContainer"></div>
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
                                        <input type="text" id="searchInput" class="form-control" placeholder="Search offices..." 
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
                                <h5 class="mb-0"><i class="fas fa-building mr-2"></i>Offices Management</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush" id="mobileOfficesList">
                                    <?php if ($result->num_rows == 0): ?>
                                        <div class="list-group-item text-center">No offices found.</div>
                                    <?php else: ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0 font-weight-bold">
                                                        <?= htmlspecialchars($row['name']) ?>
                                                    </h6>
                                                </div>
                                                <p class="mb-2 text-muted small">
                                                    <i class="fas fa-envelope mr-1"></i> <?= htmlspecialchars($row['email']) ?>
                                                </p>
                                                <div class="d-flex justify-content-end">
                                                    <button class="btn btn-sm btn-success mr-2 edit-office" 
                                                        data-id="<?= $row['id'] ?>" 
                                                        data-name="<?= htmlspecialchars($row['name']) ?>"
                                                        data-email="<?= htmlspecialchars($row['email']) ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-office" data-id="<?= $row['id'] ?>">
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
                                        <i class="fas fa-building mr-1"></i> 
                                        Total Office<?= $total_offices !== 1 ? 's' : '' ?>: 
                                        <strong><?= $total_offices ?></strong>
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
                                <div class="table-responsive overflow-container">
                                    <table class="table table-striped table-hover">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="officesTableBody">
                                            <?php 
                                            $result->data_seek(0);
                                            if ($result->num_rows == 0): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No offices found.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php while ($row = $result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-success edit-office" 
                                                                data-id="<?= $row['id'] ?>" 
                                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                                data-email="<?= htmlspecialchars($row['email']) ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-office" data-id="<?= $row['id'] ?>">
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

                    <div class="modal fade" id="editOfficeModal" tabindex="-1" role="dialog" 
                            aria-labelledby="editOfficeModalLabel" aria-hidden="true"
                            data-bs-backdrop="true" data-bs-keyboard="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editOfficeModalLabel">Edit Office</h5>
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form id="editOfficeForm" action="/myschedule/components/admin_comp/office_comp/edit_office.php" method="POST">
                                    <input type="hidden" id="editOfficeId" name="office_id">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="editName">Office Name</label>
                                            <input type="text" class="form-control" id="editName" name="name" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="editEmail">Email</label>
                                            <input type="email" class="form-control" id="editEmail" name="email" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="editPassword">New Password (leave blank to keep current)</label>
                                            <input type="password" class="form-control" id="editPassword" name="password">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Update Office</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="deleteOfficeModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirm Deletion</h5>
                                </div>
                                <form id="deleteOfficeForm" action="/myschedule/components/admin_comp/office_comp/delete_office.php" method="POST">
                                    <input type="hidden" id="deleteOfficeId" name="office_id">
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete this office? This action cannot be undone.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Delete Office</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <script src="../../assets/js/office.js"></script>
                </div>
            </section>
        </div>
    </div>
</body>
</html>