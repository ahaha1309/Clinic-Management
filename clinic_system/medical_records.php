<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('medical_record.view');

$db = getDB();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && hasPermission('medical_record.create')) {
        $record_code = generateCode('BA', 'medical_records', 'record_code');
        
        $stmt = $db->prepare("INSERT INTO medical_records (record_code, appointment_id, patient_id, doctor_id, symptoms, diagnosis, treatment_plan, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $record_code,
            $_POST['appointment_id'],
            $_POST['patient_id'],
            $_POST['doctor_id'],
            $_POST['symptoms'],
            $_POST['diagnosis'],
            $_POST['treatment_plan'],
            $_POST['notes']
        ]);
        
        auditLog('create', 'medical_records', $db->lastInsertId(), null, $_POST);
        $message = 'Tạo bệnh án thành công!';
        $messageType = 'success';
    }
}

$sql="
    SELECT 
        mr.*,
        p.patient_code,
        p.full_name as patient_name,
        u.full_name as doctor_name,
        a.appointment_code
    FROM medical_records mr
    JOIN patients p ON mr.patient_id = p.id
    JOIN doctors d ON mr.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    JOIN appointments a ON mr.appointment_id = a.id
    where 1=1
";  
if($_SESSION['role_id']==2){ // if doctor
$doctor_id = $db->query("SELECT id FROM doctors WHERE user_id = {$_SESSION['user_id']}")->fetch(PDO::FETCH_ASSOC)['id'];
$sql.=" and mr.doctor_id=$doctor_id ";
}
$sql.=" ORDER BY mr.id DESC";
$stmt = $db->query($sql);
$records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý bệnh án</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>📋 Quản lý bệnh án</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if (hasPermission('medical_record.create')): ?>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="location.href='/clinic_system/medical_record_create.php'">➕ Tạo bệnh án</button>
                </div>
                <?php endif; ?>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php if($_SESSION['role_id'] != 2):?> 
                            <th>Mã BA</th>
                            <?php endif; ?>
                            <th>Bệnh nhân</th>
                            <th>Bác sĩ</th>
                            <th>Triệu chứng</th>
                            <th>Chẩn đoán</th>
                            <th>Ngày khám</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                        <tr>
                            <?php if($_SESSION['role_id'] != 2):?> 
                            <td><?php echo htmlspecialchars($record['record_code']); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($record['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($record['symptoms'], 0, 50)) . '...'; ?></td>
                            <td><?php echo htmlspecialchars(substr($record['diagnosis'], 0, 50)) . '...'; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($record['record_date'])); ?></td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="viewRecord(<?php echo $record['id']; ?>)">👁️ Xem</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function viewRecord(id) {
            window.location.href = '/clinic_system/medical_record_view.php?id=' + id;
        }
    </script>
</body>
</html>
