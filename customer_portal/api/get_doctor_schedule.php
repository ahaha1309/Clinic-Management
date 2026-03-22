<?php
// =====================================================
// API GET DOCTOR SCHEDULE
// Lấy lịch làm việc của bác sĩ để hiển thị cho khách
// =====================================================

header('Content-Type: application/json');
require_once '../config/database.php';

$response = ['success' => false, 'schedules' => []];

try {
    if (!isset($_GET['doctor_id']) || empty($_GET['doctor_id'])) {
        throw new Exception('Thiếu thông tin bác sĩ');
    }
    
    $doctor_id = intval($_GET['doctor_id']);
    
    $db = getDB();
    
    // Get doctor schedules
    $stmt = $db->prepare("
        SELECT 
            day_of_week,
            start_time,
            end_time
        FROM doctor_schedules
        WHERE doctor_id = ? AND is_active = 1
        ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                 start_time
    ");
    $stmt->execute([$doctor_id]);
    $schedules = $stmt->fetchAll();
    
    $response['success'] = true;
    $response['schedules'] = $schedules;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
