<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('invoice.create');

$db = getDB();
$message = '';
$messageType = '';
$_SESSION['back_url'] = $_SERVER['REQUEST_URI'];

// Kiểm tra xem có dữ liệu restore không
$restoreData = null;
if (isset($_SESSION['invoice_restore_data'])) {
    $restoreData = $_SESSION['invoice_restore_data'];
    unset($_SESSION['invoice_restore_data']); // Xóa sau khi lấy
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_invoice') {
        $stmt = $db->prepare("
            SELECT 
                d.consultation_fee, 
                a.patient_id, 
                mr.id as medical_record_id
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN medical_records mr ON a.id = mr.appointment_id
            WHERE a.id = ?
        ");
        $stmt->execute([$_POST['appointment_id']]);
        $data = $stmt->fetch();
        
        if (!$data) {
            $message = 'Không tìm thấy lịch hẹn!';
            $messageType = 'danger';
        } else {
            $consultation_fee = $data['consultation_fee'];
            $patient_id = $data['patient_id'];
            $medical_record_id = $data['medical_record_id'];
            $discount = $_POST['discount'] ?? 0;
            $invoice_code = generateCode('HD', 'invoices', 'invoice_code');
            
            $stmt = $db->prepare("
                INSERT INTO invoices 
                (invoice_code, patient_id, appointment_id, medical_record_id, consultation_fee, discount,total_amount, created_by) 
                VALUES (?, ?, ?, ?,?, ?, ?, ?)
            ");
            $stmt->execute([
                $invoice_code,
                $patient_id,
                $_POST['appointment_id'],
                $medical_record_id,
                $consultation_fee,
                $discount,
                $consultation_fee,
                $_SESSION['user_id']
            ]);
            
            $invoice_id = $db->lastInsertId();
            auditLog('create', 'invoices', $invoice_id, null, $_POST);
            
            header("Location: invoice_detail.php?id=$invoice_id");
            exit();
        }
    }
}

$stmt = $db->query("
    SELECT 
        a.id,
        a.appointment_code,
        p.patient_code,
        p.full_name as patient_name,
        d.consultation_fee,
        u.full_name as doctor_name,
        a.appointment_date,
        mr.diagnosis
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    JOIN medical_records mr ON a.id = mr.appointment_id
    LEFT JOIN invoices i ON a.id = i.appointment_id
    WHERE a.status = 'completed' AND i.id IS NULL
    ORDER BY a.appointment_date DESC
");
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo hóa đơn</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>📄 Tạo hóa đơn mới</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if (empty($appointments)): ?>
                    <div class="alert alert-warning">
                        ⚠️ Không có lịch hẹn nào cần tạo hóa đơn. 
                        <br><br>
                        <strong>Điều kiện tạo hóa đơn:</strong>
                        <ul style="margin-top: 10px;">
                            <li>✓ Lịch hẹn đã hoàn thành (status = completed)</li>
                            <li>✓ Đã có bệnh án</li>
                            <li>✓ Chưa có hóa đơn</li>
                        </ul>
                    </div>
                    <div class="action-buttons">
                        <a href="appointments.php" class="btn btn-primary">← Quay lại lịch hẹn</a>
                        <a href="medical_records.php" class="btn btn-secondary">📋 Xem bệnh án</a>
                    </div>
                <?php else: ?>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create_invoice">
                    
                    <div class="form-section">
                        <h3>1. Chọn lịch hẹn</h3>
                        <div class="form-group">
                            <label>Lịch hẹn đã hoàn thành *</label>
                            <select name="appointment_id" id="appointment_id" required onchange="updateFee()">
                                <option value="">-- Chọn lịch hẹn --</option>
                                <?php foreach ($appointments as $apt): ?>
                                <option value="<?php echo $apt['id']; ?>"
                                        data-fee="<?php echo $apt['consultation_fee']; ?>"
                                        data-patient="<?php echo htmlspecialchars($apt['patient_name']); ?>"
                                        data-doctor="<?php echo htmlspecialchars($apt['doctor_name']); ?>"
                                        data-diagnosis="<?php echo htmlspecialchars($apt['diagnosis']); ?>"
                                        <?php echo ($restoreData && $restoreData['appointment_id'] == $apt['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($apt['appointment_code']); ?> - 
                                    <?php echo htmlspecialchars($apt['patient_name']); ?> - 
                                    <?php echo date('d/m/Y', strtotime($apt['appointment_date'])); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="info-display" id="info-display" style="display: none;">
                            <p><strong>👤 Bệnh nhân:</strong> <span id="patient_display"></span></p>
                            <p><strong>👨‍⚕️ Bác sĩ:</strong> <span id="doctor_display"></span></p>
                            <p><strong>🔍 Chẩn đoán:</strong> <span id="diagnosis_display"></span></p>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>2. Thông tin thanh toán</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Phí khám (tự động)</label>
                                <input type="text" id="fee_display" value="<?php echo $restoreData ? htmlspecialchars(number_format($restoreData['consultation_fee'])) : ' '; ?>" readonly style="background: #e9ecef; font-weight: bold;">
                            </div>
                            <div class="form-group">
                                <label>Giảm giá(%)</label>
                                <input type="number" name="discount" id="discount" value="<?php echo $restoreData ? htmlspecialchars(number_format($restoreData['discount'])) : '0'; ?>" min="0" onchange="calculateTotal()" placeholder="Nhập số tiền giảm giá">
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>📌 Lưu ý:</strong> 
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Sau khi tạo hóa đơn, bạn có thể thêm <strong>dịch vụ</strong> và <strong>thuốc</strong> vào hóa đơn.</li>
                            <li>Tổng tiền sẽ tự động cập nhật khi thêm/xóa dịch vụ hoặc thuốc (trigger).</li>
                            <li>Bệnh nhân có thể thanh toán nhiều lần (partial payment).</li>
                        </ul>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">💾 Tạo hóa đơn</button>
                        <a href="invoices.php" class="btn btn-secondary">❌ Hủy</a>
                    </div>
                </form>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Tự động load dữ liệu khi có restore data
        window.addEventListener('DOMContentLoaded', function() {
            <?php if ($restoreData): ?>
            updateFee();
            <?php endif; ?>
        });
        
        function updateFee() {
            const select = document.getElementById('appointment_id');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                const fee = parseFloat(option.dataset.fee);
                document.getElementById('patient_display').textContent = option.dataset.patient;
                document.getElementById('doctor_display').textContent = option.dataset.doctor;
                document.getElementById('diagnosis_display').textContent = option.dataset.diagnosis;
                document.getElementById('fee_display').value = fee.toLocaleString('vi-VN') + 'đ';
                document.getElementById('fee_display2').textContent = fee.toLocaleString('vi-VN') + 'đ';
                document.getElementById('info-display').style.display = 'block';
                document.getElementById('price-breakdown').style.display = 'block';
                calculateTotal();
            } else {
                document.getElementById('info-display').style.display = 'none';
                document.getElementById('price-breakdown').style.display = 'none';
            }
        }
        function calculateTotal() {
            const select = document.getElementById('appointment_id');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                const fee = parseFloat(option.dataset.fee);
                const discount = parseFloat(document.getElementById('discount').value) || 0;
                const total = Math.max(0, fee - discount);
                
                document.getElementById('discount_display').textContent = '-' + discount.toLocaleString('vi-VN') + 'đ';
                document.getElementById('total_display').textContent = total.toLocaleString('vi-VN') + 'đ';
            }
        }
    </script>
</body>
</html>