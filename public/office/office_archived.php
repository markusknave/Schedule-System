<?php
session_start();
@include '../../components/links.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'announcements';

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$office_id = $_SESSION['office_id'];
$total_items = 0;
$archived_data = [];

switch ($type) {
    case 'announcements':
        $query = "SELECT COUNT(*) AS total FROM announcements WHERE office_id = $office_id AND deleted_at IS NOT NULL";
        $total_result = $conn->query($query);
        $total_items = $total_result->fetch_assoc()['total'];
        
        $query = "SELECT a.*, o.name AS deleted_by 
                FROM announcements a 
                JOIN offices o ON a.office_id = o.id 
                WHERE a.office_id = $office_id 
                AND a.deleted_at IS NOT NULL
                ORDER BY a.deleted_at DESC 
                LIMIT $limit OFFSET $offset";
        $archived_data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        break;
        
    case 'teachers':
        $query = "SELECT COUNT(*) AS total FROM teachers WHERE office_id = $office_id AND deleted_at IS NOT NULL";
        $total_result = $conn->query($query);
        $total_items = $total_result->fetch_assoc()['total'];
        
        $query = "SELECT t.*, u.firstname, u.lastname, u.middlename, o.name AS deleted_by 
                FROM teachers t 
                JOIN users u ON t.user_id = u.id
                JOIN offices o ON t.office_id = o.id 
                WHERE t.office_id = $office_id 
                AND t.deleted_at IS NOT NULL
                ORDER BY t.deleted_at DESC 
                LIMIT $limit OFFSET $offset";
        $archived_data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        break;
        
    case 'rooms':
        $query = "SELECT COUNT(*) AS total FROM rooms WHERE office_id = $office_id AND deleted_at IS NOT NULL";
        $total_result = $conn->query($query);
        $total_items = $total_result->fetch_assoc()['total'];
        
        $query = "SELECT r.*, o.name AS deleted_by 
                FROM rooms r 
                JOIN offices o ON r.office_id = o.id 
                WHERE r.office_id = $office_id 
                AND r.deleted_at IS NOT NULL
                ORDER BY r.deleted_at DESC 
                LIMIT $limit OFFSET $offset";
        $archived_data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        break;
        
    case 'schedules':
        $query = "SELECT COUNT(*) AS total FROM schedules WHERE office_id = $office_id AND deleted_at IS NOT NULL";
        $total_result = $conn->query($query);
        $total_items = $total_result->fetch_assoc()['total'];
        
        $query = "SELECT s.*, o.name AS deleted_by 
                FROM schedules s 
                JOIN offices o ON s.office_id = o.id 
                WHERE s.office_id = $office_id 
                AND s.deleted_at IS NOT NULL
                ORDER BY s.deleted_at DESC 
                LIMIT $limit OFFSET $offset";
        $archived_data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        break;

        case 'subjects':
            $query = "SELECT COUNT(*) AS total FROM subjects WHERE office_id = $office_id AND deleted_at IS NOT NULL";
            $total_result = $conn->query($query);
            $total_items = $total_result->fetch_assoc()['total'];
            
            $query = "SELECT s.*, o.name AS deleted_by 
                    FROM subjects s 
                    JOIN offices o ON s.office_id = o.id 
                    WHERE s.office_id = $office_id 
                    AND s.deleted_at IS NOT NULL
                    ORDER BY s.deleted_at DESC 
                    LIMIT $limit OFFSET $offset";
            $archived_data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
            break;
}

$total_pages = ceil($total_items / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Items</title>

    <style>
        .archive-nav {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
        }
        .archive-nav .nav-link {
            color: #495057;
            font-weight: 500;
        }
        .archive-nav .nav-link.active {
            color: #007bff;
            background-color: #e9ecef;
            border-radius: 5px;
        }
        .archive-item {
            border-left: 4px solid #6c757d;
            margin-bottom: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .archive-item-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .archive-item-title {
            font-weight: bold;
            font-size: 1.1rem;
        }
        .archive-item-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .archive-actions {
            margin-top: 10px;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<?php ?>
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/office_sidebar.php'; ?>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Archived Items</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <div class="archive-nav">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link <?= $type === 'announcements' ? 'active' : '' ?>" 
                                   href="?type=announcements">Announcements</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $type === 'teachers' ? 'active' : '' ?>" 
                                   href="?type=teachers">Teachers</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $type === 'rooms' ? 'active' : '' ?>" 
                                   href="?type=rooms">Rooms</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $type === 'schedules' ? 'active' : '' ?>" 
                                   href="?type=schedules">Schedules</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $type === 'subjects' ? 'active' : '' ?>" 
                                href="?type=subjects">Subjects</a>
                            </li>
                        </ul>
                    </div>

                    <?php if (empty($archived_data)): ?>
                        <div class="alert alert-info">
                            <h5><i class="icon fas fa-info"></i> No archived items found!</h5>
                            There are currently no archived <?= $type ?> to display.
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body">
                                <?php foreach ($archived_data as $item): ?>
                                    <div class="archive-item">
                                        <div class="archive-item-header">
                                            <div class="archive-item-title">
                                                <?php 
                                                switch ($type) {
                                                    case 'announcements':
                                                        echo htmlspecialchars($item['title']);
                                                        break;
                                                    case 'teachers':
                                                        echo htmlspecialchars($item['firstname'] . ' ' . $item['lastname']);
                                                        break;
                                                    case 'rooms':
                                                        echo htmlspecialchars($item['name']);
                                                        break;
                                                    case 'schedules':
                                                        echo "Schedule ID: " . htmlspecialchars($item['id']);
                                                        break;
                                                    case 'subjects':
                                                        echo htmlspecialchars($item['subject_code'] . ' - ' . $item['name']);
                                                        break;
                                                }
                                                ?>
                                            </div>
                                            <div class="archive-item-meta">
                                                Deleted by: <?= htmlspecialchars($item['deleted_by']) ?> | 
                                                <?= date('M j, Y g:i A', strtotime($item['deleted_at'])) ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($type === 'announcements'): ?>
                                            <div class="archive-item-content">
                                                <?= nl2br(htmlspecialchars(substr($item['content'], 0, 200))) ?>
                                                <?= strlen($item['content']) > 200 ? '...' : '' ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="archive-actions">
                                            <button class="btn btn-sm btn-success restore-btn" 
                                                    data-type="<?= $type ?>" 
                                                    data-id="<?= $item['id'] ?>">
                                                <i class="fas fa-undo"></i> Restore
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn" 
                                                    data-type="<?= $type ?>" 
                                                    data-id="<?= $item['id'] ?>">
                                                <i class="fas fa-trash"></i> Permanently Delete
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if ($total_pages > 1): ?>
                                <div class="card-footer">
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?type=<?= $type ?>&page=<?= $page - 1 ?>">
                                                    &laquo; Previous
                                                </a>
                                            </li>
                                            
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                    <a class="page-link" href="?type=<?= $type ?>&page=<?= $i ?>">
                                                        <?= $i ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?type=<?= $type ?>&page=<?= $page + 1 ?>">
                                                    Next &raquo;
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Confirm Action</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalBody">
                    Are you sure you want to perform this action?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="confirmAction">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/archive.js"></script>
</body>
</html>