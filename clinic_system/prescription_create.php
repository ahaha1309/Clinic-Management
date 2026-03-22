<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('prescription.create');

$db = getDB();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        
        header('Location: prescriptions.php?success=1');
        exit();
    }
}

$stmt = $db->query("
    SELECT 
        mr.id,
        mr.record_code,
        p.id as patient_id,
        p.patient_code,
        p.full_name as patient_name,
        d.id as doctor_id,
        u.full_name as doctor_name,
        mr.diagnosis,
        mr.record_date
    FROM medical_records mr
    JOIN patients p ON mr.patient_id = p.id
    JOIN doctors d ON mr.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    LEFT JOIN prescriptions pr ON mr.id = pr.medical_record_id
    WHERE pr.id IS NULL
    ORDER BY mr.record_date DESC
");
$medical_records = $stmt->fetchAll();

$medicines = $db->query("SELECT * FROM medicines WHERE is_active = 1 AND stock_quantity > 0 ORDER BY medicine_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kê đơn thuốc</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>💊 Kê đơn thuốc</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if (empty($medical_records)): ?>
                    <div class="alert alert-warning">
                        ⚠️ Không có bệnh án nào cần kê đơn thuốc.
                        <br>Vui lòng tạo bệnh án trước khi kê đơn.
                    </div>
                    <a href="medical_records.php" class="btn btn-primary">← Quay lại bệnh án</a>
                <?php else: ?>
                
                <form method="POST">
                    <div class="form-section">
                        <h3>1. Chọn bệnh án</h3>
                        <div class="form-group">
                            <label>Bệnh án *</label>
                            <select name="medical_record_id" id="medical_record_id" required onchange="fillInfo()">
                                <option value="">-- Chọn bệnh án --</option>
                                <?php foreach ($medical_records as $mr): ?>
                                <option value="<?php echo $mr['id']; ?>"
                                        data-patient-id="<?php echo $mr['patient_id']; ?>"
                                        data-doctor-id="<?php echo $mr['doctor_id']; ?>"
                                        data-patient-name="<?php echo htmlspecialchars($mr['patient_name']); ?>"
                                        data-doctor-name="<?php echo htmlspecialchars($mr['doctor_name']); ?>"
                                        data-diagnosis="<?php echo htmlspecialchars($mr['diagnosis']); ?>">
                                    <?php echo htmlspecialchars($mr['record_code']); ?> - 
                                    <?php echo htmlspecialchars($mr['patient_name']); ?> - 
                                    <?php echo date('d/m/Y', strtotime($mr['record_date'])); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <input type="hidden" name="patient_id" id="patient_id">
                        <input type="hidden" name="doctor_id" id="doctor_id">
                        
                        <div class="info-display" id="info-display" style="display: none;">
                            <p><strong>👤 Bệnh nhân:</strong> <span id="patient_name_display"></span></p>
                            <p><strong>👨‍⚕️ Bác sĩ:</strong> <span id="doctor_name_display"></span></p>
                            <p><strong>🔍 Chẩn đoán:</strong> <span id="diagnosis_display"></span></p>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>2. Danh sách thuốc</h3>
                        <div class="medicine-list">
                            <h4>Thuốc kê đơn</h4>
                            <div id="medicine-container">
                                <div class="medicine-row">
                                    <div>
                                        <label>Thuốc *</label>
                                        <select name="medicine_id[]" required>
                                            <option value="">-- Chọn thuốc --</option>
                                            <?php foreach ($medicines as $med): ?>
                                            <option value="<?php echo $med['id']; ?>">
                                                <?php echo htmlspecialchars($med['medicine_name']); ?> 
                                                (Tồn: <?php echo $med['stock_quantity']; ?> <?php echo $med['unit']; ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label>Số lượng *</label>
                                        <input type="number" name="quantity[]" required min="1" value="1">
                                    </div>
                                    <div>
                                        <label>Liều dùng *</label>
                                        <input type="text" name="dosage[]" required placeholder="VD: 2 viên/lần">
                                    </div>
                                    <div>
                                        <label>Hướng dẫn sử dụng *</label>
                                        <input type="text" name="instructions[]" required placeholder="VD: Uống sau ăn, ngày 3 lần">
                                    </div>
                                    <div class="medicine-row-actions">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">🗑️</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-success btn-add-medicine" onclick="addMedicineRow()">➕ Thêm thuốc</button>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>3. Ghi chú</h3>
                        <div class="form-group">
                            <label>Ghi chú thêm cho đơn thuốc</label>
                            <textarea name="notes" rows="3" placeholder="Lưu ý đặc biệt cho bệnh nhân (nếu có)"></textarea>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">💾 Lưu đơn thuốc</button>
                        <a href="prescriptions.php" class="btn btn-secondary">❌ Hủy</a>
                    </div>
                </form>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function fillInfo() {
            const select = document.getElementById('medical_record_id');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                document.getElementById('patient_id').value = option.dataset.patientId;
                document.getElementById('doctor_id').value = option.dataset.doctorId;
                document.getElementById('patient_name_display').textContent = option.dataset.patientName;
                document.getElementById('doctor_name_display').textContent = option.dataset.doctorName;
                document.getElementById('diagnosis_display').textContent = option.dataset.diagnosis;
                document.getElementById('info-display').style.display = 'block';
            } else {
                document.getElementById('info-display').style.display = 'none';
            }
        }
        
        function addMedicineRow() {
            const container = document.getElementById('medicine-container');
            const firstRow = container.querySelector('.medicine-row');
            const newRow = firstRow.cloneNode(true);
            
            newRow.querySelectorAll('select, input').forEach(input => {
                if (input.type === 'number') {
                    input.value = 1;
                } else {
                    input.value = '';
                }
            });
            
            container.appendChild(newRow);
        }
        
        function removeRow(btn) {
            const container = document.getElementById('medicine-container');
            if (container.querySelectorAll('.medicine-row').length > 1) {
                btn.closest('.medicine-row').remove();
            } else {
                alert('Phải có ít nhất 1 thuốc trong đơn!');
            }
        }
    </script>
</body>
</html>