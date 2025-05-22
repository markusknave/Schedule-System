<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';

session_start();

if (!isset($_SESSION['office_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    header("Location: /myschedule/login.php");
    exit();
}

$office_id = $_SESSION['office_id'];

$query = $conn->query("
    SELECT 
        r.name,
        COUNT(s.id) AS schedule_count,
        MAX(COALESCE(s.created_at, r.created_at)) AS last_updated
    FROM rooms r
    LEFT JOIN schedules s ON r.id = s.room_id
    WHERE r.office_id = $office_id
    GROUP BY r.id, r.name
    ORDER BY r.name
");

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="rooms_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, ['Room Name', 'Schedule Count', 'Last Updated']);

while ($row = $query->fetch_assoc()) {
    fputcsv($output, [
        $row['name'],
        $row['schedule_count'],
        date('Y-m-d H:i', strtotime($row['last_updated']))
    ]);
}

fclose($output);
exit();
?>