<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
session_start();

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.php");
    exit();
}

$office_id = $_SESSION['office_id'];

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="schedule_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

$roomsQuery = $conn->query("SELECT id, name FROM rooms WHERE office_id = $office_id AND deleted_at IS NULL ORDER BY name");
$rooms = [];
while ($room = $roomsQuery->fetch_assoc()) {
    $rooms[$room['id']] = $room['name'];
}
$roomNames = array_values($rooms);
$roomIds = array_keys($rooms);

$scheduleQuery = $conn->query("
    SELECT 
        s.day,
        s.start_time,
        s.end_time,
        s.room_id,
        COALESCE(sub.subject_code, 'TBA') AS subject_code,
        COALESCE(CONCAT(LEFT(u.firstname, 1), '. ', u.lastname), 'TBA') AS teacher,
        COALESCE(sec.section_name, 'TBA') AS section
    FROM schedules s
    LEFT JOIN teachers t ON s.teach_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN subjects sub ON s.subject_id = sub.id
    LEFT JOIN sections sec ON s.section_id = sec.id
    WHERE s.office_id = $office_id AND s.deleted_at IS NULL
");

$timePoints = [];
$schedules = []; 

while ($row = $scheduleQuery->fetch_assoc()) {
    $day = $row['day'];
    $start = $row['start_time'];
    $end = $row['end_time'];
    $roomId = $row['room_id'];

    $timePoints[$start] = true;
    $timePoints[$end] = true;

    $text = "{$row['subject_code']} ({$row['teacher']}) - {$row['section']}";
    $schedules[$day][$roomId][] = [
        'start' => $start,
        'end' => $end,
        'text' => $text
    ];
}

$sortedTimes = array_keys($timePoints);
usort($sortedTimes, function ($a, $b) {
    return strtotime($a) - strtotime($b);
});

$timeSlots = [];
for ($i = 0; $i < count($sortedTimes) - 1; $i++) {
    $start = $sortedTimes[$i];
    $end = $sortedTimes[$i + 1];
    $slotKey = "$start-$end";
    $slotLabel = date('G:i', strtotime($start)) . ' - ' . date('G:i', strtotime($end));
    $timeSlots[$slotKey] = $slotLabel;
}

$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

foreach ($daysOfWeek as $day) {
    fputcsv($output, [$day]);
    fputcsv($output, array_merge(['Time'], $roomNames));

    foreach ($timeSlots as $slotKey => $slotLabel) {
        [$slotStart, $slotEnd] = explode('-', $slotKey);
        $row = [$slotLabel];

        foreach ($roomIds as $roomId) {
            $value = '';

            if (!empty($schedules[$day][$roomId])) {
                foreach ($schedules[$day][$roomId] as $entry) {
                    if ($slotStart >= $entry['start'] && $slotEnd <= $entry['end']) {
                        $value = $entry['text'];
                        break;
                    }
                }
            }

            $row[] = $value;
        }

        fputcsv($output, $row);
    }

    fputcsv($output, []);
}

fclose($output);
exit();
?>
