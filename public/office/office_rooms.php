<?php
session_start();
@include '../../components/links.php';

// Check if the user is logged in
if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/components/login.html");
    exit();
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

// Pagination settings
$limit = isset($_GET['mobile']) ? 5 : 7;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Base query conditions
$office_id = $_SESSION['office_id'];
$where_clause = "WHERE r.office_id = $office_id AND r.deleted_at IS NULL";

// Add search conditions if search term exists
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_clause .= " AND r.name LIKE '%$search%'";
}

// Get total number of rooms (with search if applicable)
$total_result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM rooms r
    $where_clause
");
$total_rooms = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rooms / $limit);

// Fetch rooms with limit for pagination (and search if applicable)
$rooms_query = $conn->query("
    SELECT 
        r.id,
        r.name,
        r.created_at,
        COUNT(s.id) AS schedule_count,
        MAX(s.created_at) AS last_schedule_update
    FROM rooms r
    LEFT JOIN schedules s ON r.id = s.room_id
    $where_clause
    GROUP BY r.id, r.name, r.created_at
    ORDER BY r.name
    LIMIT $limit OFFSET $offset
");
$rooms = $rooms_query->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Room Management</title>
    <link rel="stylesheet" href="/myschedule/assets/css/room.css">
</head>
<style >
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
<?php ?>
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/sidebar.php'; ?>
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Rooms Management</h1>
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
                                    <button class="btn btn-primary" id="addRoomButton">
                                        <i class="fas fa-plus"></i> Add New Room
                                    </button>
                                    <button class="btn btn-secondary ml-2" id="exportToCsv">
                                        <i class="fas fa-file-export"></i> Export to CSV
                                    </button>
                                </div>
                                <div>
                                    <form class="d-flex" style="width: 300px">
                                        <input type="text" id="searchInput" class="form-control" placeholder="Search rooms..." 
                                            value="<?= htmlspecialchars($search) ?>">
                                        <button type="button" class="btn btn-primary ml-2" id="searchButton">Search</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
<!-- Mobile view -->
<div class="d-block d-md-none"> 
    <div class="card mb-4 border-primary">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-door-open mr-2"></i>Rooms Management</h5>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush" id="mobileRoomsList">
                <?php if (empty($rooms)): ?>
                    <div class="list-group-item text-center">No rooms found for your office.</div>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 font-weight-bold">
                                    <?= htmlspecialchars($room['name']) ?>
                                </h6>
                                <span class="badge bg-info"><?= htmlspecialchars($room['schedule_count']) ?> schedules</span>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-sm btn-info mr-2 view-room" data-id="<?= $room['id'] ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-success mr-2 edit-room" 
                                    data-id="<?= $room['id'] ?>" 
                                    data-name="<?= htmlspecialchars($room['name']) ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-room" data-id="<?= $room['id'] ?>">
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
            <table class="table table-striped table-hover table-fixed-layout overflow-hidden">
                    <thead class="thead-dark">
                        <tr>
                            <th>Room Name</th>
                            <th>Schedule Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="roomsTableBody">
                        <!-- AJAX will fill this -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1" role="dialog" aria-labelledby="addRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRoomModalLabel">Add New Room</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addRoomForm" action="/myschedule/components/room_comp/add_room.php" method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="roomName">Room Name</label>
                            <input type="text" class="form-control" id="roomName" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div class="modal fade" id="editRoomModal" tabindex="-1" role="dialog" aria-labelledby="editRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoomModalLabel">Edit Room</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editRoomForm" action="/myschedule/components/room_comp/edit_room.php" method="POST">
                    <input type="hidden" id="editRoomId" name="room_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="editRoomName">Room Name</label>
                            <input type="text" class="form-control" id="editRoomName" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteRoomModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                </div>
                <form id="deleteRoomForm" action="/myschedule/components/room_comp/delete_room.php" method="POST">
                    <input type="hidden" id="deleteRoomId" name="room_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this room? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="../../assets/js/rooms.js"></script>
    <script>
        const phpVars = {
            searchTerm: '<?= htmlspecialchars($search, ENT_QUOTES) ?>',
            currentPage: <?= $page ?>,
            baseUrl: '/myschedule/components/room_comp/'
        };
    </script>
</body>
</html>