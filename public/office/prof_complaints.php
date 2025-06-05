<?php
session_start();
@include '../../components/links.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$office_id = $_SESSION['office_id'];

$where_clause = "WHERE c.teacher_id = t.id AND t.office_id = $office_id";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_clause .= " AND (c.name LIKE '%$search%' OR c.email LIKE '%$search%' OR c.subject LIKE '%$search%' OR CONCAT(u.firstname, ' ', u.lastname) LIKE '%$search%')";
}

$total_query = "SELECT COUNT(*) AS total 
                FROM complaints c
                JOIN teachers t ON c.teacher_id = t.id
                JOIN users u ON t.user_id = u.id
                $where_clause";
$total_result = $conn->query($total_query);
$total_complaints = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_complaints / $limit);

$query = "SELECT c.id, c.name AS complainant_name, c.email AS complainant_email, 
                 c.subject, c.message, c.created_at, c.status,
                 CONCAT(u.firstname, ' ', u.lastname) AS professor_name,
                 o.name AS office_name
          FROM complaints c
          JOIN teachers t ON c.teacher_id = t.id
          JOIN users u ON t.user_id = u.id
          JOIN offices o ON t.office_id = o.id
          $where_clause
          ORDER BY c.created_at DESC
          LIMIT $limit OFFSET $offset";
$result = $conn->query($query);
$shown_count = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Complaints | Leyte Normal University</title>
    <link rel="stylesheet" href="/myschedule/assets/css/off_complaint.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed lnu-bg">
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                </div>
            </section>
            
            <section class="content">
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h1>Professor Complaints</h1>
                                </div>
                                <div class="search-container" style="width: 300px">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Search complaints..." 
                                        value="<?= htmlspecialchars($search) ?>">
                                    <i class="fas fa-search search-icon"></i>
                                </div>
                            </div>
                            <div class="lnu-divider"></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="lnu-card">
                                <div class="lnu-card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-exclamation-triangle mr-2"></i>Complaints List
                                    </div>
                                    <div>
                                        <span class="badge lnu-badge p-2">
                                            Total Complaints: <strong><?= $total_complaints ?></strong>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <?php if ($result->num_rows == 0): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                            <h4>No Complaints Found</h4>
                                            <p class="text-muted">There are currently no complaints to display.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Complainant</th>
                                                        <th>Professor</th>
                                                        <th>Subject</th>
                                                        <th>Date</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($row = $result->fetch_assoc()): ?>
                                                        <tr class="complaint-row" data-id="<?= $row['id'] ?>">
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="mr-2">
                                                                        <i class="fas fa-user-circle fa-lg text-primary"></i>
                                                                    </div>
                                                                    <div>
                                                                        <strong><?= htmlspecialchars($row['complainant_name']) ?></strong>
                                                                        <div class="small text-muted"><?= htmlspecialchars($row['complainant_email']) ?></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="mr-2">
                                                                        <i class="fas fa-chalkboard-teacher fa-lg text-info"></i>
                                                                    </div>
                                                                    <div>
                                                                        <strong><?= htmlspecialchars($row['professor_name']) ?></strong>
                                                                        <div class="small text-muted"><?= htmlspecialchars($row['office_name']) ?></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td><?= htmlspecialchars($row['subject']) ?></td>
                                                            <td>
                                                                <?= date('M d, Y', strtotime($row['created_at'])) ?><br>
                                                                <span class="small text-muted"><?= date('h:i A', strtotime($row['created_at'])) ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge status-<?= $row['status'] ?? 'pending' ?>">
                                                                    <?= $row['status'] ? ucfirst($row['status']) : 'Pending' ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-lnu view-complaint" data-id="<?= $row['id'] ?>">
                                                                    <i class="fas fa-eye mr-1"></i> View
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr class="complaint-detail" id="detail-<?= $row['id'] ?>" style="display: none;">
                                                            <td colspan="6">
                                                                <div class="d-flex justify-content-between mb-2">
                                                                    <h5>Complaint Details</h5>
                                                                    <button class="btn btn-sm btn-outline-secondary close-detail" data-id="<?= $row['id'] ?>">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <strong>Subject:</strong> <?= htmlspecialchars($row['subject']) ?>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <strong>Message:</strong>
                                                                    <div class="border rounded p-3 bg-white">
                                                                        <?= nl2br(htmlspecialchars($row['message'])) ?>
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <strong>Submitted:</strong> 
                                                                        <?= date('M d, Y h:i A', strtotime($row['created_at'])) ?>
                                                                    </div>
                                                                    <div>
                                                                        <button class="btn btn-sm btn-success mr-2 btn-mark-resolved" data-id="<?= $row['id'] ?>">
                                                                            <i class="fas fa-check-circle mr-1"></i> Mark Resolved
                                                                        </button>
                                                                        <button class="btn btn-sm btn-danger btn-delete-complaint" data-id="<?= $row['id'] ?>">
                                                                            <i class="fas fa-trash-alt mr-1"></i> Delete
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <nav aria-label="Page navigation" class="mt-4">
                                            <ul class="pagination justify-content-center">
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
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="../../assets/js/off_complaint.js"></script>
</body>
</html>