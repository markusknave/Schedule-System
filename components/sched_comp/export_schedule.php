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

// CSV headers
fputcsv($output, ['Day', 'Start Time', 'End Time', 'Room', 'Subject', 'Teacher', 'Section']);

$query = $conn->query("
    SELECT 
        s.day,
        DATE_FORMAT(s.start_time, '%h:%i %p') AS start_time,
        DATE_FORMAT(s.end_time, '%h:%i %p') AS end_time,
        COALESCE(r.name, 'TBA') AS room_name,
        COALESCE(sub.subject_code, 'TBA') AS subject_code,
        COALESCE(CONCAT(LEFT(u.firstname, 1), '. ', u.lastname), 'TBA') AS teacher_name,
        COALESCE(sec.section_name, 'TBA') AS section_name
    FROM schedules s
    LEFT JOIN teachers t ON s.teach_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN subjects sub ON s.subject_id = sub.id
    LEFT JOIN sections sec ON s.section_id = sec.id
    WHERE s.office_id = $office_id
    AND s.deleted_at IS NULL
    ORDER BY s.day, s.start_time
");

while ($row = $query->fetch_assoc()) {
    fputcsv($output, [
        $row['day'],
        $row['start_time'],
        $row['end_time'],
        $row['room_name'],
        $row['subject_code'],
        $row['teacher_name'],
        $row['section_name']
    ]);
}

fclose($output);
exit();
?>