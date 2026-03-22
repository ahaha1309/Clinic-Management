<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('user.view');

$db = getDB();
$user_id = $_GET['user_id'] ?? 0;

$response = [
    'doctor' => null,
    'schedules' => []
];

if ($user_id) {
    // Get doctor info
    $stmt = $db->prepare("SELECT * FROM doctors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $response['doctor'] = $stmt->fetch();
    
    if ($response['doctor']) {
        // Get schedules
        $stmt = $db->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ? AND is_active = 1 ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
        $stmt->execute([$response['doctor']['id']]);
        $response['schedules'] = $stmt->fetchAll();
    }
}

header('Content-Type: application/json');
echo json_encode($response);