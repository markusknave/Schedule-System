<?php
session_start();
@include '../../components/links.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$office_id = $_SESSION['office_id'];

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
        CASE
            WHEN sec.id IS NULL OR sec.deleted_at IS NOT NULL THEN 'TBA'
            ELSE sec.section_name
        END AS section_name
    FROM schedules s
    LEFT JOIN teachers t ON s.teach_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN subjects sub ON s.subject_id = sub.id
    LEFT JOIN sections sec ON s.section_id = sec.id
    WHERE s.office_id = $office_id
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
            'section' => $schedule['section_name'],
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
    <title>Room Schedules</title>
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
                        <div class="col-sm-6">
                            <a href="../../public/office/disp_announ_sched.php" class="btn btn-info float-right">
                                <i class="fas fa-eye"></i> View Schedule
                            </a>
                            <a href="../../components/sched_comp/add_schedule.php" class="btn btn-primary float-right mr-2">
                                <i class="fas fa-plus"></i> Add Schedule
                            </a>
                            <a href="../../components/sched_comp/export_schedule.php" class="btn btn-secondary float-right mr-2">
                                <i class="fas fa-file-export"></i> Export to CSV
                            </a>
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
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
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
                                    list($slot_start, $slot_end) = explode(' - ', $time_range);
                                    $slot_start_time = DateTime::createFromFormat('H:i', $slot_start);
                                    $slot_end_time = DateTime::createFromFormat('H:i', $slot_end);
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
                                                        
                                                        if (($schedule_start < $slot_end_time) && ($schedule_end > $slot_start_time)) {
                                                            $conflict_key = $schedule['room_id'] . '-' . $schedule['day'] . '-' . $schedule['start_time'] . '-' . $schedule['end_time'];
                                                            $has_conflict = isset($conflicts[$conflict_key]);
                                                            
                                                            $overlap_start = max($schedule_start, $slot_start_time);
                                                            $overlap_end = min($schedule_end, $slot_end_time);
                                                            $overlap_duration = $overlap_start->diff($overlap_end);
                                                            
                                                            $found_schedules[] = [
                                                                'id' => $schedule['id'],
                                                                'subject_code' => $schedule['subject_code'],
                                                                'section_name' => $schedule['section_name'],
                                                                'teacher_name' => $schedule['teacher_name'],
                                                                'has_conflict' => $has_conflict,
                                                                'overlap_percent' => ($overlap_duration->h * 60 + $overlap_duration->i) / 
                                                                                    ($slot_start_time->diff($slot_end_time)->h * 60 + $slot_start_time->diff($slot_end_time)->i) * 100
                                                            ];
                                                        }
                                                    }
                                                }
                                                
                                                usort($found_schedules, function($a, $b) {
                                                    return $b['overlap_percent'] <=> $a['overlap_percent'];
                                                });
                                                
                                                if (!empty($found_schedules)): 
                                                    foreach ($found_schedules as $schedule): 
                                                ?>
                                                    <div class="<?php echo $schedule['has_conflict'] ? 'text-danger' : ''; ?>" 
                                                        style="margin-bottom: 5px; border-left: 3px solid <?php echo $schedule['has_conflict'] ? '#dc3545' : '#28a745'; ?>; padding-left: 5px;">
                                                        <?php echo htmlspecialchars($schedule['subject_code']); ?><br>
                                                        <small><?php echo htmlspecialchars($schedule['section_name']); ?></small><br>
                                                        <small><?php echo htmlspecialchars($schedule['teacher_name']); ?></small>
                                                        <div class="btn-group btn-group-sm d-block mt-2">
                                                            <a href="../../components/sched_comp/edit_schedule.php?id=<?php echo $schedule['id']; ?>" class="btn btn-info btn-xs">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                            <button class="btn btn-danger btn-xs delete-schedule" data-id="<?php echo $schedule['id']; ?>">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </div>
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
                <div class="modal fade" id="deleteScheduleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="deleteScheduleForm" action="/myschedule/components/sched_comp/delete_schedule.php" method="POST">
                    <input type="hidden" id="deleteScheduleId" name="id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this schedule? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>

        <script>
$(document).ready(function() {
    $(document).on('click', '.delete-schedule', function() {
        const scheduleId = $(this).data('id');
        $('#deleteScheduleId').val(scheduleId);
        $('#deleteScheduleModal').modal('show');
    });

    $('#deleteScheduleForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
                $('#deleteScheduleModal').modal('hide');
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error);
            }
        });
    });
});
</script>
    </body>
    </html>