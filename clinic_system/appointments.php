<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('appointment.view');

$db = getDB();
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && hasPermission('appointment.create')) {
        $appointment_code = generateCode('LH', 'appointments', 'appointment_code');
        
        // Check doctor schedule
        $dayOfWeek = date('l', strtotime($_POST['appointment_date']));
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ? AND start_time <= ? AND end_time >= ? AND is_active = 1");
        $stmt->execute([$_POST['doctor_id'], $dayOfWeek, $_POST['appointment_time'], $_POST['appointment_time']]);
        
        if ($stmt->fetch()['count'] == 0) {
            $message = 'Bác sĩ không làm việc trong khung giờ này!';
            $messageType = 'danger';
        } else {
            $stmt = $db->prepare("INSERT INTO appointments (appointment_code, patient_id, doctor_id, appointment_date, appointment_time, reason, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $appointment_code,
                $_POST['patient_id'],
                $_POST['doctor_id'],
                $_POST['appointment_date'],
                $_POST['appointment_time'],
                $_POST['reason'],
                $_POST['notes'],
                $_SESSION['user_id']
            ]);
            
            auditLog('create', 'appointments', $db->lastInsertId(), null, $_POST);
            $message = 'Đặt lịch hẹn thành công!';
            $messageType = 'success';
        }
    } elseif ($_POST['action'] === 'update_status' && hasPermission('appointment.edit')) {
        $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['id']]);
        
        auditLog('update', 'appointments', $_POST['id'], null, $_POST);
        $message = 'Cập nhật trạng thái thành công!';
        $messageType = 'success';
    }
}

// Get appointments
    $sql="
        SELECT 
            a.*,
            p.patient_code,
            p.full_name as patient_name,
            p.phone as patient_phone,
            d.doctor_code,
            u.full_name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        where 1=1
    ";
if($_SESSION['role_id']==2){ // if doctor
$doctor_id = $db->query("SELECT id FROM doctors WHERE user_id = {$_SESSION['user_id']}")->fetch(PDO::FETCH_ASSOC)['id'];
$sql.=" and a.doctor_id=$doctor_id ";
}
$sql.=" ORDER BY a.appointment_date DESC, a.appointment_time DESC ";
$stmt = $db->query($sql);
$appointments = $stmt->fetchAll();

// Get patients for form
$patients = $db->query("SELECT id, patient_code, full_name FROM patients ORDER BY full_name")->fetchAll();

// FIX: Lấy danh sách bác sĩ - bao gồm cả bác sĩ mới tạo
// Chỉ lấy những bác sĩ có trong bảng doctors (đã có đầy đủ thông tin)
$doctors = $db->query("
    SELECT 
        d.id, 
        d.doctor_code, 
        u.full_name,
        d.specialization
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    WHERE u.is_active = 1
    ORDER BY u.full_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý lịch hẹn</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>📅 Quản lý lịch hẹn</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if (hasPermission('appointment.create')): ?>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="showCreateModal()">➕ Đặt lịch hẹn</button>
                </div>
                <?php endif; ?>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php if ($_SESSION['role_id'] != 2): // Nếu không phải bác sĩ, hiển thị cột bác sĩ ?>
                            <th>Mã lịch hẹn</th>
                            <?php endif; ?>
                            <th>Bệnh nhân</th>
                            <th>Bác sĩ</th>
                            <th>Ngày hẹn</th>
                            <th>Giờ hẹn</th>
                            <th>Lý do</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt): ?>
                        <tr>
                            <?php if ($_SESSION['role_id'] != 2): // Nếu không phải bác sĩ, hiển thị cột mã lịch hẹn?>
                            <td><?php echo htmlspecialchars($apt['appointment_code']); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($apt['doctor_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($apt['appointment_date'])); ?></td>
                            <td><?php echo date('H:i', strtotime($apt['appointment_time'])); ?></td>
                            <td><?php echo htmlspecialchars($apt['reason']); ?></td>
                            <td>
                                <?php
                                $badges = [
                                    'scheduled' => 'badge-warning',
                                    'confirmed' => 'badge-info',
                                    'completed' => 'badge-success',
                                    'cancelled' => 'badge-danger'
                                ];
                                $texts = [
                                    'scheduled' => 'Đã đặt',
                                    'confirmed' => 'Đã xác nhận',
                                    'completed' => 'Hoàn thành',
                                    'cancelled' => 'Đã hủy'
                                ];
                                ?>
                                <span class="badge <?php echo $badges[$apt['status']]; ?>"><?php echo $texts[$apt['status']]; ?></span>
                            </td>
                            <td class="table-actions">
                                <?php if (hasPermission('appointment.edit') && ($apt['status'] != 'completed' && $apt['status'] != 'cancelled')): ?>
                                <?php if (hasPermission('appointment.edit') && $apt['status'] != 'completed'): ?>
                                <button class="btn btn-success btn-sm" onclick="updateStatus(<?php echo $apt['id']; ?>, 'completed')">✓ Hoàn thành</button>
                                <?php endif; ?>
                                <?php if (hasPermission('appointment.edit') && $apt['status'] != 'cancelled'): ?>
                                <button class="btn btn-danger btn-sm" onclick="updateStatus(<?php echo $apt['id']; ?>, 'cancelled')">✗ Hủy</button>
                                <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Đặt lịch hẹn</h2>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label>Bệnh nhân *</label>
                    <select name="patient_id" required>
                        <option value="">-- Chọn bệnh nhân --</option>
                        <?php foreach ($patients as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['patient_code'] . ' - ' . $p['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Bác sĩ *</label>
                    <select name="doctor_id" id="doctorSelect" required onchange="loadDoctorSchedule()">
                        <option value="">-- Chọn bác sĩ --</option>
                        <?php foreach ($doctors as $d): ?>
                        <option value="<?php echo $d['id']; ?>" data-specialization="<?php echo htmlspecialchars($d['specialization']); ?>">
                            <?php echo htmlspecialchars($d['doctor_code'] . ' - ' . $d['full_name']); ?>
                            <?php if ($d['specialization']): ?>
                                (<?php echo htmlspecialchars($d['specialization']); ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Hiển thị lịch làm việc của bác sĩ - sử dụng class info-display từ CSS có sẵn -->
                <div id="doctorScheduleInfo" class="info-display" style="display: none;">
                    <p><strong>📅 Lịch làm việc:</strong></p>
                    <div id="scheduleContent"></div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Ngày hẹn *</label>
                        <input type="date" name="appointment_date" id="appointmentDate" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Giờ hẹn *</label>
                        <input type="time" name="appointment_time" id="appointmentTime" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Lý do khám</label>
                    <textarea name="reason"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="notes"></textarea>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">💾 Lưu</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">❌ Hủy</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const doctorSchedules = {};
        
        // Load all doctor schedules on page load
        <?php
        $stmt = $db->query("
            SELECT 
                ds.doctor_id,
                ds.day_of_week,
                ds.start_time,
                ds.end_time
            FROM doctor_schedules ds
            WHERE ds.is_active = 1
            ORDER BY FIELD(ds.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), ds.start_time
        ");
        $allSchedules = $stmt->fetchAll();
        
        $schedulesByDoctor = [];
        foreach ($allSchedules as $schedule) {
            if (!isset($schedulesByDoctor[$schedule['doctor_id']])) {
                $schedulesByDoctor[$schedule['doctor_id']] = [];
            }
            $schedulesByDoctor[$schedule['doctor_id']][] = $schedule;
        }
        
        echo "const doctorSchedulesData = " . json_encode($schedulesByDoctor) . ";";
        ?>
        
        const dayNames = {
            'Monday': 'Thứ 2',
            'Tuesday': 'Thứ 3',
            'Wednesday': 'Thứ 4',
            'Thursday': 'Thứ 5',
            'Friday': 'Thứ 6',
            'Saturday': 'Thứ 7',
            'Sunday': 'Chủ nhật'
        };
        
        function showCreateModal() {
            document.getElementById('appointmentModal').classList.add('active');
            document.getElementById('doctorScheduleInfo').style.display = 'none';
        }
        
        function closeModal() {
            document.getElementById('appointmentModal').classList.remove('active');
        }
        
        function loadDoctorSchedule() {
            const doctorId = document.getElementById('doctorSelect').value;
            const scheduleInfo = document.getElementById('doctorScheduleInfo');
            const scheduleContent = document.getElementById('scheduleContent');
            
            if (!doctorId) {
                scheduleInfo.style.display = 'none';
                return;
            }
            
            const schedules = doctorSchedulesData[doctorId] || [];
            
            if (schedules.length === 0) {
                scheduleContent.innerHTML = '<p style="color: red; margin: 5px 0;">⚠️ Bác sĩ chưa có lịch làm việc!</p>';
            } else {
                let html = '';
                schedules.forEach(schedule => {
                    html += `<span class="badge badge-info" style="margin: 3px;">${dayNames[schedule.day_of_week]}: ${schedule.start_time.substring(0,5)} - ${schedule.end_time.substring(0,5)}</span> `;
                });
                scheduleContent.innerHTML = html;
            }
            
            scheduleInfo.style.display = 'block';
        }
        
        function updateStatus(id, status) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="update_status"><input type="hidden" name="id" value="' + id + '"><input type="hidden" name="status" value="' + status + '">';
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>