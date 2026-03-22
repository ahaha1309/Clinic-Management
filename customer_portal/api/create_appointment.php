<?php
// ==========================================
// API CREATE APPOINTMENT REQUEST
// Lưu vào bảng appointment_requests (chờ duyệt)
// ==========================================

header('Content-Type: application/json');
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Phương thức không hợp lệ');
    }

    // ===== Validate bắt buộc =====
    $required_fields = ['full_name', 'phone', 'gender', 'service_id', 'appointment_date', 'consent'];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
        }
    }

    // ===== Làm sạch dữ liệu =====
    $full_name = trim($_POST['full_name']);
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'];
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
    $email = trim($_POST['email'] ?? '');
    $service_id = intval($_POST['service_id']);
    $doctor_id = !empty($_POST['doctor_id']) ? intval($_POST['doctor_id']) : null;
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'] ?? '09:00:00';
    $reason = trim($_POST['reason'] ?? '');

    // ===== Validate dữ liệu =====

    if (strlen($phone) < 10 || strlen($phone) > 11) {
        throw new Exception('Số điện thoại không hợp lệ');
    }

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email không hợp lệ');
    }

    if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
        throw new Exception('Ngày khám không được ở quá khứ');
    }

    if (!$appointment_time) {
        $appointment_time = '09:00:00';
    } elseif (strlen($appointment_time) == 5) {
        $appointment_time .= ':00';
    }

    $db = getDB();

    // ===== Tạo mã yêu cầu =====
    $request_code = generateCode('REQ', 'appointment_requests', 'request_code');

    // ===== Insert vào bảng request =====
    $stmt = $db->prepare("
        INSERT INTO appointment_requests (
            request_code,
            full_name,
            date_of_birth,
            gender,
            phone,
            email,
            service_id,
            doctor_id,
            appointment_date,
            appointment_time,
            reason,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $stmt->execute([
        $request_code,
        $full_name,
        $date_of_birth,
        $gender,
        $phone,
        $email,
        $service_id,
        $doctor_id,
        $appointment_date,
        $appointment_time,
        $reason
    ]);

    $response['success'] = true;
    $response['message'] = 'Yêu cầu đặt lịch đã được gửi. Vui lòng chờ phê duyệt.';
    $response['request_code'] = $request_code;

} catch (Exception $e) {

    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Appointment Request Error: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
