<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('prescription.view');

$db = getDB();
$message = '';
$messageType = '';

if (isset($_GET['success'])) {
    $message = 'Kê đơn thuốc thành công!';
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && hasPermission('prescription.create')) {
    if ($_POST['action'] === 'create') {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE medical_record_id = ?");
        $stmt->execute([$_POST['medical_record_id']]);
        
        if ($stmt->fetch()['count'] > 0) {
            $message = 'Bệnh án này đã có đơn thuốc!';
            $messageType = 'danger';
        } else {
            $prescription_code = generateCode('DT', 'prescriptions', 'prescription_code');
            
            $stmt = $db->prepare("INSERT INTO prescriptions (prescription_code, medical_record_id, patient_id, doctor_id, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $prescription_code,
                $_POST['medical_record_id'],
                $_POST['patient_id'],
                $_POST['doctor_id'],
                $_POST['notes'] ?? ''
            ]);
            
            $prescription_id = $db->lastInsertId();
            
            if (isset($_POST['medicine_id']) && is_array($_POST['medicine_id'])) {
                for ($i = 0; $i < count($_POST['medicine_id']); $i++) {
                    if (!empty($_POST['medicine_id'][$i])) {
                        $stmt = $db->prepare("INSERT INTO prescription_details (prescription_id, medicine_id, quantity, dosage, instructions) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $prescription_id,
                            $_POST['medicine_id'][$i],
                            $_POST['quantity'][$i],
                            $_POST['dosage'][$i],
                            $_POST['instructions'][$i]
                        ]);
                    }
                }
            }
            
            auditLog('create', 'prescriptions', $prescription_id, null, $_POST);
            $message = 'Kê đơn thuốc thành công!';
            $messageType = 'success';
        }
    }
}

$sql="
    SELECT 
        pr.*,
        p.patient_code,
        p.full_name as patient_name,
        u.full_name as doctor_name,
        mr.record_code
    FROM prescriptions pr
    JOIN patients p ON pr.patient_id = p.id
    JOIN doctors d ON pr.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    JOIN medical_records mr ON pr.medical_record_id = mr.id
    where 1=1
";
if($_SESSION['role_id']==2){ // if doctor
$doctor_id = $db->query("SELECT id FROM doctors WHERE user_id = {$_SESSION['user_id']}")->fetch(PDO::FETCH_ASSOC)['id'];
$sql.=" and pr.doctor_id=$doctor_id ";
}
$sql.=" ORDER BY pr.id DESC";
$stmt = $db->query($sql);
$prescriptions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn thuốc</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>💊 Quản lý đơn thuốc</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <div class="stats-grid">
                    <div class="stats-card">
                        <h3>📋 Tổng đơn thuốc</h3>
                        <p class="number"><?php echo count($prescriptions); ?></p>
                    </div>
                    <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3>📅 Hôm nay</h3>
                        <p class="number">
                            <?php 
                            $today = array_filter($prescriptions, function($p) {
                                return date('Y-m-d', strtotime($p['prescription_date'])) == date('Y-m-d');
                            });
                            echo count($today);
                            ?>
                        </p>
                    </div>
                </div>
                
                <?php if (hasPermission('prescription.create')): ?>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="location.href='prescription_create.php'">➕ Kê đơn thuốc</button>
                </div>
                <?php endif; ?>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php if($_SESSION['role_id'] != 2):?> 
                            <th>Mã ĐT</th>
                            <th>Mã BA</th>
                            <?php endif; ?>
                            <th>Bệnh nhân</th>
                            <th>Bác sĩ</th>
                            <th>Ngày kê đơn</th>
                            <th>Ghi chú</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prescriptions)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                📋 Chưa có đơn thuốc nào
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($prescriptions as $pres): ?>
                        <tr>
                            <?php if($_SESSION['role_id'] != 2):?>  
                            <td><?php echo htmlspecialchars($pres['prescription_code']); ?></td>
                            <td><?php echo htmlspecialchars($pres['record_code']); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($pres['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($pres['doctor_name']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pres['prescription_date'])); ?></td>
                            <td><?php echo htmlspecialchars($pres['notes'] ?? ''); ?></td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="viewPrescription(<?php echo $pres['id']; ?>)" title="Xem chi tiết">👁️ Xem</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function viewPrescription(id) {
            window.location.href = 'prescription_view.php?id=' + id;
        }
    </script>
</body>
</html>