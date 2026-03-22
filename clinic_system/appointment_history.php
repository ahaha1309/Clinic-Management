<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requireLogin();

$db = getDB();

$stmt = $db->query("
    SELECT 
        ah.*,
        p.patient_code,
        p.full_name as patient_name,
        u.full_name as doctor_name
    FROM appointment_history ah
    JOIN patients p ON ah.patient_id = p.id
    JOIN doctors d ON ah.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    ORDER BY ah.completed_at DESC
");
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử khám bệnh</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>📚 Lịch sử khám bệnh</h1>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã BN</th>
                            <th>Bệnh nhân</th>
                            <th>Bác sĩ</th>
                            <th>Ngày khám</th>
                            <th>Chẩn đoán</th>
                            <th>Chi phí</th>
                            <th>Ngày hoàn thành</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($h['patient_code']); ?></td>
                            <td><?php echo htmlspecialchars($h['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($h['doctor_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($h['appointment_date'])); ?></td>
                            <td><?php echo htmlspecialchars(substr($h['diagnosis'], 0, 50)) . '...'; ?></td>
                            <td><?php echo $h['total_cost'] ? number_format($h['total_cost']) . 'đ' : '-'; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($h['completed_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
