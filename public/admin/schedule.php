<?php
session_start();
@include '../../components/links.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /myschedule/components/login.html");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$office_condition = isAdmin() ? "" : "WHERE s.office_id = $office_id";

$rooms_query = $conn->query("SELECT id, name FROM rooms WHERE office_id = $office_id AND deleted_at IS NULL");
$rooms = $rooms_query->fetch_all(MYSQLI_ASSOC);

$scheduled_rooms_query = $conn->query("
    SELECT DISTINCT r.id, COALESCE(r.name, 'TBA') AS name 
    FROM schedules s
    LEFT JOIN rooms r ON s.room_id = r.id
    WHERE (r.office_id = $office_id OR r.id IS NULL)
    ORDER BY name
");
$all_rooms = $scheduled_rooms_query->fetch_all(MYSQLI_ASSOC);

$rooms = array_merge($rooms, $all_rooms);
$rooms = array_unique($rooms, SORT_REGULAR);

$schedules_query = $conn->query("
     SELECT 
        s.id,
        s.day,
        s.start_time,
        s.end_time,
        CASE 
            WHEN r.id IS NULL OR r.deleted_at IS NOT NULL THEN NULL
            ELSE s.room_id
        END AS room_id,
        CASE 
            WHEN r.id IS NULL OR r.deleted_at IS NOT NULL THEN 'TBA'
            ELSE r.name
        END AS room_name,
        CASE 
            WHEN sub.id IS NULL OR sub.deleted_at IS NOT NULL THEN 'TBA'
            ELSE sub.subject_code
        END AS subject_code,
        CASE 
            WHEN t.id IS NULL OR t.deleted_at IS NOT NULL THEN 'TBA'
            WHEN u.id IS NULL OR u.deleted_at IS NOT NULL THEN 'TBA'
            ELSE CONCAT(LEFT(u.firstname, 1), '. ', u.lastname)
        END AS teacher_name,
        COALESCE(t.unit, '') AS unit
    FROM schedules s
    LEFT JOIN teachers t ON s.teach_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN subjects sub ON s.subject_id = sub.id
    $office_condition
    AND s.deleted_at IS NULL
    ORDER BY 
        CASE WHEN r.name IS NULL THEN 1 ELSE 0 END,
        COALESCE(r.name, 'ZZZ'), 
        s.day, 
        s.start_time
");
$schedules = $schedules_query->fetch_all(MYSQLI_ASSOC);

$conflicts = [];
$schedule_slots = [];

foreach ($schedules as $schedule) {
    if ($schedule['teacher_name'] === 'TBA' || $schedule['room_name'] === 'TBA' || $schedule['subject_code'] === 'TBA') {
        continue;
    }
    
    $key = $schedule['room_id'] . '-' . $schedule['day'] . '-' . $schedule['start_time'] . '-' . $schedule['end_time'];
    
    if (!isset($schedule_slots[$key])) {
        $schedule_slots[$key] = [];
    }
    
    $schedule_slots[$key][] = $schedule;
    
    if (count($schedule_slots[$key]) > 1) {
        $conflicts[$key] = $schedule_slots[$key];
    }
}

$room_schedules = [
    'TBA' => [
        'room_name' => 'TBA',
        'days' => [
            'Monday' => [],
            'Tuesday' => [],
            'Wednesday' => [],
            'Thursday' => [],
            'Friday' => []
        ]
    ]
];

foreach ($rooms as $room) {
    $room_schedules[$room['id']] = [
        'room_name' => $room['name'],
        'days' => [
            'Monday' => [],
            'Tuesday' => [],
            'Wednesday' => [],
            'Thursday' => [],
            'Friday' => []
        ]
    ];
}

foreach ($schedules as $schedule) {
    $room_id = ($schedule['room_name'] === 'TBA') ? 'TBA' : $schedule['room_id'];
    $day = $schedule['day'];
    
    if (isset($room_schedules[$room_id]['days'][$day])) {
        $room_schedules[$room_id]['days'][$day][] = [
            'time' => date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])),
            'subject' => $schedule['subject_code'],
            'teacher' => $schedule['teacher_name'],
            'unit' => $schedule['unit'],
            'has_conflict' => isset($conflicts[$schedule['room_id'] . '-' . $schedule['day'] . '-' . $schedule['start_time'] . '-' . $schedule['end_time']])
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Room Schedules</title>
    <link rel="stylesheet" href="/myschedule/assets/css/schedule.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include '../../components/header.php'; ?>
        <?php include '../../components/sidebar.php'; ?>
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Room Schedules</h1>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="content">
                <div class="container-fluid">
                    <?php if (!empty($conflicts)): ?>
                        <div class="alert alert-danger conflict-alert">
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Schedule Conflicts Detected!</h5>
                            <p>The following time slots have conflicting schedules:</p>
                            <ul>
                                <?php foreach ($conflicts as $key => $conflicting_schedules): ?>
                                    <?php 
                                    $parts = explode('-', $key);
                                    $room_id = $parts[0];
                                    $room_name = '';
                                    foreach ($rooms as $room) {
                                        if ($room['id'] == $room_id) {
                                            $room_name = $room['name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($room_name); ?></strong> on 
                                        <?php echo htmlspecialchars($parts[1]); ?> at 
                                        <?php echo date('h:i A', strtotime($parts[2])) . ' - ' . date('h:i A', strtotime($parts[3])); ?>:
                                        <ul>
                                            <?php foreach ($conflicting_schedules as $schedule): ?>
                                                <li>
                                                    <?php echo htmlspecialchars($schedule['teacher_name']); ?> - 
                                                    <?php echo htmlspecialchars($schedule['subject_code']); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($room_schedules)): ?>
                        <div class="alert alert-info">
                            No rooms or schedules found for your office.
                        </div>
                        <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr class="text-center">
                                    <th>Time</th>
                                    <?php 
                                    $room_columns = [];
                                    foreach ($rooms as $room) {
                                        if ($room['name'] !== 'TBA') {
                                            $room_columns[$room['name']] = $room['id'];
                                        }
                                    }
                                    
                                    $has_tba = false;
                                    foreach ($schedules as $schedule) {
                                        if ($schedule['room_name'] === 'TBA') {
                                            $has_tba = true;
                                            break;
                                        }
                                    }
                                    
                                    if ($has_tba) {
                                        $room_columns['TBA'] = 'TBA';
                                    }
                                    
                                    foreach ($room_columns as $room_name => $room_id): 
                                        $days = [];
                                        foreach ($schedules as $schedule) {
                                            if (($room_id === 'TBA' && $schedule['room_name'] === 'TBA') || 
                                                ($schedule['room_id'] == $room_id)) {
                                                if (!in_array($schedule['day'], $days)) {
                                                    $days[] = $schedule['day'];
                                                }
                                            }
                                        }
                                        
                                        $day_abbreviations = array_map(function($day) {
                                            return substr($day, 0, 3);
                                        }, $days);
                                    ?>
                                        <th>
                                            <?php echo htmlspecialchars($room_name); ?><br>
                                            <small><?php echo implode(' ', $day_abbreviations); ?></small>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $time_slots = [
                                    '07:30:00' => '7:30 - 9:00',
                                    '09:00:00' => '9:00 - 10:30',
                                    '10:30:00' => '10:30 - 12:00',
                                    '13:00:00' => '13:00 - 14:30',
                                    '14:30:00' => '14:30 - 16:00',
                                    '16:00:00' => '16:00 - 17:00',
                                    '17:00:00' => '17:00 - 19:00'
                                ];
                                
                                foreach ($time_slots as $start_time => $time_range): 
                                    list($start, $end) = explode(' - ', $time_range);
                                    $start_time_obj = DateTime::createFromFormat('H:i', $start);
                                    $end_time_obj = DateTime::createFromFormat('H:i', $end);
                                ?>
                                    <tr class="text-center">
                                        <td class="align-middle"><?php echo $time_range; ?></td>
                                        <?php foreach ($room_columns as $room_name => $room_id): ?>
                                            <td>
                                                <?php 
                                                $found_schedules = [];
                                                foreach ($schedules as $schedule) {
                                                    if (($room_id === 'TBA' && $schedule['room_name'] === 'TBA') || 
                                                        ($schedule['room_id'] == $room_id)) {
                                                        $schedule_start = DateTime::createFromFormat('H:i:s', $schedule['start_time']);
                                                        $schedule_end = DateTime::createFromFormat('H:i:s', $schedule['end_time']);
                                                        
                                                        if ($schedule_start >= $start_time_obj && $schedule_end <= $end_time_obj) {
                                                            $conflict_key = $schedule['room_id'] . '-' . $schedule['day'] . '-' . $schedule['start_time'] . '-' . $schedule['end_time'];
                                                            $has_conflict = isset($conflicts[$conflict_key]);
                                                            
                                                            $found_schedules[] = [
                                                                'subject_code' => $schedule['subject_code'],
                                                                'unit' => $schedule['unit'],
                                                                'teacher_name' => $schedule['teacher_name'],
                                                                'has_conflict' => $has_conflict
                                                            ];
                                                        }
                                                    }
                                                }
                                                
                                                if (!empty($found_schedules)): 
                                                    foreach ($found_schedules as $schedule): 
                                                ?>
                                                    <div class="<?php echo $schedule['has_conflict'] ? 'text-danger' : ''; ?>">
                                                        <?php echo htmlspecialchars($schedule['subject_code']); ?><br>
                                                        <?php if (!empty($schedule['unit'])): ?>
                                                            <small><?php echo htmlspecialchars($schedule['unit']); ?></small><br>
                                                        <?php endif; ?>
                                                        <small><?php echo htmlspecialchars($schedule['teacher_name']); ?></small>
                                                    </div>
                                                <?php 
                                                    endforeach; 
                                                endif; 
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>