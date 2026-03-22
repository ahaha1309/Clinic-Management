<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('medical_record.create');

$db = getDB();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record_code = generateCode('BA', 'medical_records', 'record_code');
    
    $stmt = $db->prepare("
        INSERT INTO medical_records 
        (record_code, appointment_id, patient_id, doctor_id, symptoms, diagnosis, treatment_plan, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $record_code,
        $_POST['appointment_id'],
        $_POST['patient_id'],
        $_POST['doctor_id'],
        $_POST['symptoms'],
        $_POST['diagnosis'],
        $_POST['treatment_plan'],
        $_POST['notes'] ?? ''
    ]);
    
    auditLog('create', 'medical_records', $db->lastInsertId(), null, $_POST);
    
    header("Location: medical_records.php?success=1");
    exit;
}

// Get appointments without medical records
$stmt = $db->query("
    SELECT 
        a.id,
        a.appointment_code,
        p.id as patient_id,
        p.patient_code,
        p.full_name as patient_name,
        d.id as doctor_id,
        u.full_name as doctor_name,
        a.appointment_date
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    LEFT JOIN medical_records mr ON a.id = mr.appointment_id
    WHERE a.status IN ('confirmed', 'completed') AND mr.id IS NULL
    ORDER BY a.appointment_date DESC
");
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo bệnh án</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>📋 Tạo bệnh án mới</h1>
                
                <?php if (empty($appointments)): ?>
                    <div class="alert alert-warning">
                        ⚠️ Không có lịch hẹn nào cần tạo bệnh án. 
                        <br>Vui lòng đảm bảo có lịch hẹn đã xác nhận và chưa có bệnh án.
                    </div>
                    <a href="appointments.php" class="btn btn-primary">← Quay lại lịch hẹn</a>
                <?php else: ?>
                
                <form method="POST">
                    <div class="form-section">
                        <h3>1. Chọn lịch hẹn</h3>
                        <div class="form-group">
                            <label>Lịch hẹn *</label>
                            <select name="appointment_id" id="appointment_id" required onchange="fillPatientDoctor()">
                                <option value="">-- Chọn lịch hẹn --</option>
                                <?php foreach ($appointments as $apt): ?>
                                <option value="<?php echo $apt['id']; ?>" 
                                        data-patient-id="<?php echo $apt['patient_id']; ?>"
                                        data-doctor-id="<?php echo $apt['doctor_id']; ?>"
                                        data-patient-name="<?php echo htmlspecialchars($apt['patient_name']); ?>"
                                        data-doctor-name="<?php echo htmlspecialchars($apt['doctor_name']); ?>">
                                    <?php echo htmlspecialchars($apt['appointment_code']); ?> - 
                                    <?php echo htmlspecialchars($apt['patient_name']); ?> - 
                                    <?php echo date('d/m/Y', strtotime($apt['appointment_date'])); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <input type="hidden" name="patient_id" id="patient_id">
                        <input type="hidden" name="doctor_id" id="doctor_id">
                        
                        <div class="info-display" id="info-display" style="display: none;">
                            <p><strong>👤 Bệnh nhân:</strong> <span id="patient_name_display"></span></p>
                            <p><strong>👨‍⚕️ Bác sĩ:</strong> <span id="doctor_name_display"></span></p>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>2. Thông tin khám bệnh</h3>
                        <div class="form-group">
                            <label>Triệu chứng *</label>
                            <textarea name="symptoms" required rows="3" placeholder="Mô tả các triệu chứng của bệnh nhân (sốt, ho, đau đầu...)"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Chẩn đoán *</label>
                            <textarea name="diagnosis" required rows="3" placeholder="Chẩn đoán bệnh của bệnh nhân (cảm cúm, viêm họng...)"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Kế hoạch điều trị *</label>
                            <textarea name="treatment_plan" required rows="4" placeholder="Kế hoạch điều trị cho bệnh nhân (nghỉ ngơi, uống thuốc...)"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Ghi chú thêm</label>
                            <textarea name="notes" rows="2" placeholder="Ghi chú bổ sung (nếu có)"></textarea>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">💾 Lưu bệnh án</button>
                        <a href="medical_records.php" class="btn btn-secondary">❌ Hủy</a>
                    </div>
                </form>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function fillPatientDoctor() {
            const select = document.getElementById('appointment_id');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                document.getElementById('patient_id').value = option.dataset.patientId;
                document.getElementById('doctor_id').value = option.dataset.doctorId;
                document.getElementById('patient_name_display').textContent = option.dataset.patientName;
                document.getElementById('doctor_name_display').textContent = option.dataset.doctorName;
                document.getElementById('info-display').style.display = 'block';
            } else {
                document.getElementById('patient_id').value = '';
                document.getElementById('doctor_id').value = '';
                document.getElementById('info-display').style.display = 'none';
            }
        }
    </script>
</body>
</html>