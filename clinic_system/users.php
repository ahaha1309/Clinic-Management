<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('user.view');

$db = getDB();
$message = '';
$messageType = '';

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && hasPermission('user.create')) {
        $username = $_POST['username'];
        $password = md5($_POST['password']);
        $role_id = $_POST['role_id'];
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        
        try {
            // Bắt đầu transaction
            $db->beginTransaction();
            
            // Tạo user
            $stmt = $db->prepare("INSERT INTO users (username, password, role_id, full_name, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $role_id, $full_name, $email, $phone]);
            $user_id = $db->lastInsertId();
            
            auditLog('create', 'users', $user_id, null, $_POST);
            
            // Nếu là bác sĩ (role_id = 2), tạo bản ghi trong bảng doctors và lịch làm việc
            if ($role_id == 2) {
                // Tạo mã bác sĩ
                $stmt = $db->query("SELECT MAX(CAST(SUBSTRING(doctor_code, 3) AS UNSIGNED)) as max_code FROM doctors");
                $max_code = $stmt->fetch()['max_code'];
                $new_code = 'BS' . str_pad(($max_code + 1), 3, '0', STR_PAD_LEFT);
                
                // Lấy thông tin bác sĩ
                $specialization = $_POST['specialization'] ?? '';
                $qualification = $_POST['qualification'] ?? '';
                $experience_years = $_POST['experience_years'] ?? 0;
                $consultation_fee = $_POST['consultation_fee'] ?? 0;
                
                // Insert vào bảng doctors
                $stmt = $db->prepare("INSERT INTO doctors (user_id, doctor_code, specialization, qualification, experience_years, consultation_fee) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $new_code, $specialization, $qualification, $experience_years, $consultation_fee]);
                $doctor_id = $db->lastInsertId();
                
                // Thêm lịch làm việc nếu có
                if (isset($_POST['schedules']) && is_array($_POST['schedules'])) {
                    foreach ($_POST['schedules'] as $schedule) {
                        if (!empty($schedule['day_of_week']) && !empty($schedule['start_time']) && !empty($schedule['end_time'])) {
                            $stmt = $db->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, is_active) VALUES (?, ?, ?, ?, 1)");
                            $stmt->execute([
                                $doctor_id,
                                $schedule['day_of_week'],
                                $schedule['start_time'],
                                $schedule['end_time']
                            ]);
                        }
                    }
                }
            }
            
            $db->commit();
            
            $message = 'Tạo người dùng thành công!' . ($role_id == 2 ? ' (Đã tạo thông tin bác sĩ và lịch làm việc)' : '');
            $messageType = 'success';
        } catch (PDOException $e) {
            $db->rollBack();
            $message = 'Lỗi: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($_POST['action'] === 'update' && hasPermission('user.edit')) {
        $id = $_POST['id'];
        $role_id = $_POST['role_id'];
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $oldData = $stmt->fetch();
            
            $stmt = $db->prepare("UPDATE users SET role_id = ?, full_name = ?, email = ?, phone = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$role_id, $full_name, $email, $phone, $is_active, $id]);
            
            auditLog('update', 'users', $id, $oldData, $_POST);
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $password = md5($_POST['password']);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$password, $id]);
            }
            
            // Nếu là bác sĩ, cập nhật thông tin bác sĩ
            if ($role_id == 2) {
                // Kiểm tra xem đã có bản ghi doctor chưa
                $stmt = $db->prepare("SELECT id FROM doctors WHERE user_id = ?");
                $stmt->execute([$id]);
                $doctor = $stmt->fetch();
                
                if ($doctor) {
                    // Cập nhật thông tin bác sĩ
                    $stmt = $db->prepare("UPDATE doctors SET specialization = ?, qualification = ?, experience_years = ?, consultation_fee = ? WHERE user_id = ?");
                    $stmt->execute([
                        $_POST['specialization'] ?? '',
                        $_POST['qualification'] ?? '',
                        $_POST['experience_years'] ?? 0,
                        $_POST['consultation_fee'] ?? 0,
                        $id
                    ]);
                    $doctor_id = $doctor['id'];
                } else {
                    // Tạo mới nếu chưa có
                    $stmt = $db->query("SELECT MAX(CAST(SUBSTRING(doctor_code, 3) AS UNSIGNED)) as max_code FROM doctors");
                    $max_code = $stmt->fetch()['max_code'];
                    $new_code = 'BS' . str_pad(($max_code + 1), 3, '0', STR_PAD_LEFT);
                    
                    $stmt = $db->prepare("INSERT INTO doctors (user_id, doctor_code, specialization, qualification, experience_years, consultation_fee) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $id,
                        $new_code,
                        $_POST['specialization'] ?? '',
                        $_POST['qualification'] ?? '',
                        $_POST['experience_years'] ?? 0,
                        $_POST['consultation_fee'] ?? 0
                    ]);
                    $doctor_id = $db->lastInsertId();
                }
                
                // Xóa lịch cũ và thêm lịch mới
                if (isset($_POST['schedules']) && is_array($_POST['schedules'])) {
                    $stmt = $db->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?");
                    $stmt->execute([$doctor_id]);
                    
                    foreach ($_POST['schedules'] as $schedule) {
                        if (!empty($schedule['day_of_week']) && !empty($schedule['start_time']) && !empty($schedule['end_time'])) {
                            $stmt = $db->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, is_active) VALUES (?, ?, ?, ?, 1)");
                            $stmt->execute([
                                $doctor_id,
                                $schedule['day_of_week'],
                                $schedule['start_time'],
                                $schedule['end_time']
                            ]);
                        }
                    }
                }
            }
            
            $db->commit();
            
            $message = 'Cập nhật người dùng thành công!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $db->rollBack();
            $message = 'Lỗi: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($_POST['action'] === 'delete' && hasPermission('user.delete')) {
        $id = $_POST['id'];
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $oldData = $stmt->fetch();
        
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        auditLog('delete', 'users', $id, $oldData, null);
        
        $message = 'Xóa người dùng thành công!';
        $messageType = 'success';
    }
}

// Get all users
$stmt = $db->query("
    SELECT u.*, r.role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    ORDER BY u.id DESC
");
$users = $stmt->fetchAll();

// Get all roles
$roles = $db->query("SELECT * FROM roles")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>👤 Quản lý người dùng & Phân quyền</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (hasPermission('user.create')): ?>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="showCreateModal()">➕ Thêm người dùng</button>
                </div>
                <?php endif; ?>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Điện thoại</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($user['role_name']); ?></span></td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge badge-success">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Khóa</span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <?php if (hasPermission('user.edit')): ?>
                                <button class="btn btn-warning btn-sm" onclick='editUser(<?php echo json_encode($user); ?>)'>✏️ Sửa</button>
                                <?php endif; ?>
                                <?php if (hasPermission('user.delete') && $user['id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">🗑️ Xóa</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Create/Edit Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm người dùng</h2>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="userId">
                
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" id="username" required>
                </div>
                
                <div class="form-group">
                    <label>Mật khẩu <span id="passwordNote">(để trống nếu không đổi)</span></label>
                    <input type="password" name="password" id="password">
                </div>
                
                <div class="form-group">
                    <label>Họ tên *</label>
                    <input type="text" name="full_name" id="full_name" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="email">
                </div>
                
                <div class="form-group">
                    <label>Điện thoại</label>
                    <input type="text" name="phone" id="phone">
                </div>
                
                <div class="form-group">
                    <label>Vai trò *</label>
                    <select name="role_id" id="role_id" required onchange="toggleDoctorFields()">
                        <option value="">-- Chọn vai trò --</option>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Các trường dành riêng cho Bác sĩ - sử dụng class form-section từ CSS có sẵn -->
                <div id="doctorFields" class="form-section" style="display: none;">
                    <h3>🩺 Thông tin Bác sĩ</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Chuyên khoa</label>
                            <input type="text" name="specialization" id="specialization" placeholder="VD: Nội khoa, Nhi khoa">
                        </div>
                        
                        <div class="form-group">
                            <label>Trình độ</label>
                            <input type="text" name="qualification" id="qualification" placeholder="VD: Bác sĩ Chuyên khoa I">
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Số năm kinh nghiệm</label>
                            <input type="number" name="experience_years" id="experience_years" min="0" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label>Phí khám (VNĐ)</label>
                            <input type="number" name="consultation_fee" id="consultation_fee" min="0" value="200000">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><strong>📅 Lịch làm việc</strong></label>
                        <div id="scheduleList" class="medicine-list">
                            <!-- Schedule rows will be added here -->
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm btn-add-schedule" onclick="addScheduleRow()">➕ Thêm ca làm việc</button>
                    </div>
                </div>
                
                <div class="form-group" id="activeGroup" style="display: none;">
                    <label>
                        <input type="checkbox" name="is_active" id="is_active" value="1">
                        Kích hoạt tài khoản
                    </label>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">💾 Lưu</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">❌ Hủy</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let scheduleCounter = 0;
        
        function showCreateModal() {
            document.getElementById('modalTitle').textContent = 'Thêm người dùng';
            document.getElementById('formAction').value = 'create';
            document.getElementById('userId').value = '';
            document.getElementById('username').readOnly = false;
            document.getElementById('password').required = true;
            document.getElementById('passwordNote').textContent = '*';
            document.getElementById('activeGroup').style.display = 'none';
            
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('full_name').value = '';
            document.getElementById('email').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('role_id').value = '';
            
            // Reset doctor fields
            document.getElementById('specialization').value = '';
            document.getElementById('qualification').value = '';
            document.getElementById('experience_years').value = '0';
            document.getElementById('consultation_fee').value = '200000';
            document.getElementById('scheduleList').innerHTML = '';
            document.getElementById('doctorFields').style.display = 'none';
            scheduleCounter = 0;
            
            document.getElementById('userModal').classList.add('active');
        }
        
        function editUser(user) {
            document.getElementById('modalTitle').textContent = 'Sửa người dùng';
            document.getElementById('formAction').value = 'update';
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('username').readOnly = true;
            document.getElementById('password').required = false;
            document.getElementById('passwordNote').textContent = '(để trống nếu không đổi)';
            document.getElementById('full_name').value = user.full_name;
            document.getElementById('email').value = user.email || '';
            document.getElementById('phone').value = user.phone || '';
            document.getElementById('role_id').value = user.role_id;
            document.getElementById('is_active').checked = user.is_active == 1;
            document.getElementById('activeGroup').style.display = 'block';
            
            // Load doctor info if role is doctor
            if (user.role_id == 2) {
                loadDoctorInfo(user.id);
            } else {
                document.getElementById('doctorFields').style.display = 'none';
            }
            
            document.getElementById('userModal').classList.add('active');
        }
        
        function loadDoctorInfo(userId) {
            // Make AJAX call to get doctor info
            fetch('get_doctor_info.php?user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.doctor) {
                        document.getElementById('specialization').value = data.doctor.specialization || '';
                        document.getElementById('qualification').value = data.doctor.qualification || '';
                        document.getElementById('experience_years').value = data.doctor.experience_years || 0;
                        document.getElementById('consultation_fee').value = data.doctor.consultation_fee || 0;
                        
                        // Load schedules
                        document.getElementById('scheduleList').innerHTML = '';
                        scheduleCounter = 0;
                        if (data.schedules && data.schedules.length > 0) {
                            data.schedules.forEach(schedule => {
                                addScheduleRow(schedule);
                            });
                        }
                        
                        document.getElementById('doctorFields').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error loading doctor info:', error);
                    document.getElementById('doctorFields').style.display = 'block';
                });
        }
        
        function toggleDoctorFields() {
            const roleId = document.getElementById('role_id').value;
            const doctorFields = document.getElementById('doctorFields');
            
            if (roleId == 2) { // Doctor role
                doctorFields.style.display = 'block';
                if (document.getElementById('scheduleList').children.length === 0) {
                    // Add default schedule if creating new doctor
                    addScheduleRow();
                }
            } else {
                doctorFields.style.display = 'none';
            }
        }
        
        function addScheduleRow(schedule = null) {
            const scheduleList = document.getElementById('scheduleList');
            const rowId = scheduleCounter++;
            
            const row = document.createElement('div');
            row.className = 'medicine-row'; // Sử dụng class có sẵn từ CSS
            row.id = 'schedule-row-' + rowId;
            row.innerHTML = `
                <select name="schedules[${rowId}][day_of_week]" required>
                    <option value="">-- Chọn ngày --</option>
                    <option value="Monday" ${schedule && schedule.day_of_week === 'Monday' ? 'selected' : ''}>Thứ 2</option>
                    <option value="Tuesday" ${schedule && schedule.day_of_week === 'Tuesday' ? 'selected' : ''}>Thứ 3</option>
                    <option value="Wednesday" ${schedule && schedule.day_of_week === 'Wednesday' ? 'selected' : ''}>Thứ 4</option>
                    <option value="Thursday" ${schedule && schedule.day_of_week === 'Thursday' ? 'selected' : ''}>Thứ 5</option>
                    <option value="Friday" ${schedule && schedule.day_of_week === 'Friday' ? 'selected' : ''}>Thứ 6</option>
                    <option value="Saturday" ${schedule && schedule.day_of_week === 'Saturday' ? 'selected' : ''}>Thứ 7</option>
                    <option value="Sunday" ${schedule && schedule.day_of_week === 'Sunday' ? 'selected' : ''}>Chủ nhật</option>
                </select>
                <input type="time" name="schedules[${rowId}][start_time]" value="${schedule ? schedule.start_time : '08:00'}" required>
                <input type="time" name="schedules[${rowId}][end_time]" value="${schedule ? schedule.end_time : '17:00'}" required>
                <div></div>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeScheduleRow(${rowId})">✗</button>
            `;
            
            scheduleList.appendChild(row);
        }
        
        function removeScheduleRow(rowId) {
            const row = document.getElementById('schedule-row-' + rowId);
            if (row) {
                row.remove();
            }
        }
        
        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }
        
        function deleteUser(id, username) {
            if (confirm('Bạn có chắc chắn muốn xóa người dùng "' + username + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>