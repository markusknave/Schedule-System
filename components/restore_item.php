<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';

if (!isset($_SESSION['office_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = $_POST['id'];
$type = $_POST['type'];
$office_id = $_SESSION['office_id'];

try {
    switch ($type) {
        case 'announcements':
            // Get the archived announcement
            $stmt = $conn->prepare("SELECT * FROM archived_announcement WHERE id = ? AND office_id = ?");
            $stmt->bind_param("ii", $id, $office_id);
            $stmt->execute();
            $announcement = $stmt->get_result()->fetch_assoc();
            
            if ($announcement) {
                // Insert back into announcements
                $stmt = $conn->prepare("INSERT INTO announcements (office_id, title, img, content, created_at) 
                                    VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $announcement['office_id'], $announcement['title'], 
                                $announcement['img'], $announcement['content'], $announcement['created_at']);
                $stmt->execute();
                
                // Delete from archive
                $stmt = $conn->prepare("DELETE FROM archived_announcement WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
            break;
            
            case 'teachers':
                // Get the archived teacher
                $stmt = $conn->prepare("SELECT * FROM archived_teachers WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $teacher = $stmt->get_result()->fetch_assoc();
                
                if ($teacher) {
                    // Get the original teacher data (assuming it's stored in the same row)
                    $stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ? AND office_id = ?");
                    $stmt->bind_param("ii", $teacher['original_id'], $office_id);
                    $stmt->execute();
                    $originalTeacher = $stmt->get_result()->fetch_assoc();
                    
                    if ($originalTeacher) {
                        // Insert back into teachers (or update if needed)
                        $stmt = $conn->prepare("UPDATE teachers SET 
                                            firstname = ?, middlename = ?, lastname = ?, 
                                            extension = ?, email = ?, unit = ?, office_id = ?
                                            WHERE id = ?");
                        $stmt->bind_param("ssssssii", 
                                        $originalTeacher['firstname'], $originalTeacher['middlename'],
                                        $originalTeacher['lastname'], $originalTeacher['extension'],
                                        $originalTeacher['email'], $originalTeacher['unit'],
                                        $originalTeacher['office_id'], $teacher['original_id']);
                        $stmt->execute();
                        
                        // Delete from archive
                        $stmt = $conn->prepare("DELETE FROM archived_teachers WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                    }
                }
                break;
                
            case 'rooms':
                // Get the archived room
                $stmt = $conn->prepare("SELECT * FROM archived_rooms WHERE id = ? AND office_id = ?");
                $stmt->bind_param("ii", $id, $office_id);
                $stmt->execute();
                $room = $stmt->get_result()->fetch_assoc();
                
                if ($room) {
                    // Insert back into rooms
                    $stmt = $conn->prepare("INSERT INTO rooms (name, office_id, created_at) 
                                        VALUES (?, ?, ?)");
                    $stmt->bind_param("sis", $room['name'], $room['office_id'], $room['created_at']);
                    $stmt->execute();
                    
                    // Delete from archive
                    $stmt = $conn->prepare("DELETE FROM archived_rooms WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                }
                break;
                
            case 'schedules':
                // Get the archived schedule
                $stmt = $conn->prepare("SELECT * FROM archived_schedules WHERE id = ? AND office_id = ?");
                $stmt->bind_param("ii", $id, $office_id);
                $stmt->execute();
                $schedule = $stmt->get_result()->fetch_assoc();
                
                if ($schedule) {
                    // Insert back into schedules
                    $stmt = $conn->prepare("INSERT INTO schedules (teach_id, subject_id, room_id, day, start_time, end_time, created_at) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisssss", 
                                    $schedule['teach_id'], $schedule['subject_id'], $schedule['room_id'],
                                    $schedule['day'], $schedule['start_time'], $schedule['end_time'],
                                    $schedule['created_at']);
                    $stmt->execute();
                    
                    // Delete from archive
                    $stmt = $conn->prepare("DELETE FROM archived_schedules WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                }
                break;
                
            default:
                throw new Exception("Invalid type specified");
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>