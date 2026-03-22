<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('patient.view');

$db = getDB();
$message = '';
$messageType = '';
// Handle Create/Update/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && hasPermission('patient.create')) {
        // Kiểm tra bệnh nhân đã tồn tại chưa (theo SĐT hoặc CMND)
        $existingPatient = null;
        if (!empty($_POST['phone']) || !empty($_POST['identity_card'])) {
            $checkQuery = "SELECT * FROM patients WHERE 1=0";
            $checkParams = [];
            
            if (!empty($_POST['phone'])) {
                $checkQuery .= " OR phone = ?";
                $checkParams[] = $_POST['phone'];
            }
            
            if (!empty($_POST['identity_card'])) {
                $checkQuery .= " OR identity_card = ?";
                $checkParams[] = $_POST['identity_card'];
            }
            
            $stmt = $db->prepare($checkQuery);
            $stmt->execute($checkParams);
            $existingPatient = $stmt->fetch();
        }
        
        // ===== XỬ LÝ THEO TRƯỜNG HỢP =====
        if ($existingPatient && $_SESSION['role_id'] == 2) {
            // TRƯỜNG HỢP 1: Bác sĩ + Bệnh nhân đã tồn tại
            $patientId = $existingPatient['id'];
            $doctor_id = $db->query("SELECT id FROM doctors WHERE user_id = {$_SESSION['user_id']}")->fetch(PDO::FETCH_ASSOC)['id'];
            
            // Kiểm tra xem quan hệ bác sĩ - bệnh nhân đã tồn tại chưa
            $stmt = $db->prepare("SELECT id FROM doctor_patients WHERE doctor_id = ? AND patient_id = ?");
            $stmt->execute([$doctor_id, $patientId]);
            $relationExists = $stmt->fetch();
            
            if (!$relationExists) {
                // Thêm quan hệ mới
                $stmt = $db->prepare("INSERT INTO doctor_patients (doctor_id, patient_id) VALUES (?, ?)");
                $stmt->execute([$doctor_id, $patientId]);
                
                auditLog('link', 'doctor_patients', $patientId, null, [
                    'doctor_id' => $doctor_id,
                    'patient_id' => $patientId,
                    'note' => 'Liên kết bệnh nhân đã tồn tại'
                ]);
                
                $message = 'Bệnh nhân "' . htmlspecialchars($existingPatient['full_name']) . '" đã tồn tại trong hệ thống. Đã thêm vào danh sách quản lý của bạn!';
                $messageType = 'warning';
            } else {
                $message = 'Bệnh nhân này đã có trong danh sách quản lý của bạn!';
                $messageType = 'info';
            }
            
        } elseif ($existingPatient && $_SESSION['role_id'] != 2) {
            // TRƯỜNG HỢP 2: Admin/Lễ tân + Bệnh nhân đã tồn tại
            $message = 'Bệnh nhân với số điện thoại hoặc CMND này đã tồn tại trong hệ thống!';
            $messageType = 'info';
            
        } else {
            // TRƯỜNG HỢP 3: Bệnh nhân chưa tồn tại (Cả bác sĩ và admin/lễ tân đều tạo mới)
            $patient_code = generateCode('BN', 'patients', 'patient_code');
            $data = [
                $patient_code,
                $_POST['full_name'],
                $_POST['date_of_birth'],
                $_POST['gender'],
                $_POST['phone'],
                $_POST['email'],
                $_POST['address'],
                $_POST['identity_card'],
                $_POST['blood_group'],
                $_POST['allergies']
            ];
            
            $stmt = $db->prepare("INSERT INTO patients (patient_code, full_name, date_of_birth, gender, phone, email, address, identity_card, blood_group, allergies) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute($data);
            $patientId = $db->lastInsertId();
            
            // Chỉ bác sĩ mới cần thêm quan hệ
            if ($_SESSION['role_id'] == 2) {
                $doctor_id = $db->query("SELECT id FROM doctors WHERE user_id = {$_SESSION['user_id']}")->fetch(PDO::FETCH_ASSOC)['id'];
                $stmt = $db->prepare("INSERT INTO doctor_patients (doctor_id, patient_id) VALUES (?, ?)");
                $stmt->execute([$doctor_id, $patientId]);
            }
            
            auditLog('create', 'patients', $patientId, null, $_POST);
            $message = 'Thêm bệnh nhân mới thành công!';
            $messageType = 'success';
        }
        
    } elseif ($_POST['action'] === 'update' && hasPermission('patient.edit')) {
        $id = $_POST['id'];
        $stmt = $db->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        $oldData = $stmt->fetch();
        
        $stmt = $db->prepare("UPDATE patients SET full_name=?, date_of_birth=?, gender=?, phone=?, email=?, address=?, identity_card=?, blood_group=?, allergies=? WHERE id=?");
        $stmt->execute([
            $_POST['full_name'], $_POST['date_of_birth'], $_POST['gender'],
            $_POST['phone'], $_POST['email'], $_POST['address'],
            $_POST['identity_card'], $_POST['blood_group'], $_POST['allergies'], $id
        ]);
        
        auditLog('update', 'patients', $id, $oldData, $_POST);
        $message = 'Cập nhật thông tin bệnh nhân thành công!';
        $messageType = 'success';
        
    } elseif ($_POST['action'] === 'delete' && hasPermission('patient.delete')) {
        $id = $_POST['id'];
        
        if ($_SESSION['role_id'] == 2) {
            // Bác sĩ xóa
            $doctor_id = $db->query("SELECT id FROM doctors WHERE user_id = {$_SESSION['user_id']}")->fetch(PDO::FETCH_ASSOC)['id'];
            
            // Kiểm tra xem có bác sĩ nào khác đang quản lý bệnh nhân này không
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM doctor_patients WHERE patient_id = ?");
            $stmt->execute([$id]);
            $doctorCount = $stmt->fetch()['count'];
            
            if ($doctorCount > 1) {
                // Chỉ xóa quan hệ của bác sĩ hiện tại
                $stmt = $db->prepare("DELETE FROM doctor_patients WHERE doctor_id = ? AND patient_id = ?");
                $stmt->execute([$doctor_id, $id]);
                
                auditLog('unlink', 'doctor_patients', $id, null, [
                    'doctor_id' => $doctor_id,
                    'patient_id' => $id,
                    'note' => 'Gỡ liên kết bệnh nhân'
                ]);
                
                $message = 'Đã gỡ bệnh nhân khỏi danh sách quản lý của bạn (bệnh nhân vẫn tồn tại trong hệ thống)!';
                $messageType = 'success';
            } else {
                // Xóa hoàn toàn bệnh nhân và quan hệ
                $stmt = $db->prepare("SELECT * FROM patients WHERE id = ?");
                $stmt->execute([$id]);
                $oldData = $stmt->fetch();
                
                $stmt = $db->prepare("DELETE FROM doctor_patients WHERE patient_id = ?");
                $stmt->execute([$id]);
                
                $stmt = $db->prepare("DELETE FROM patients WHERE id = ?");
                $stmt->execute([$id]);
                
                auditLog('delete', 'patients', $id, $oldData, null);
                $message = 'Xóa bệnh nhân hoàn toàn khỏi hệ thống!';
                $messageType = 'success';
            }
        } else {
            // Admin/Lễ tân xóa - xóa trực tiếp
            $stmt = $db->prepare("SELECT * FROM patients WHERE id = ?");
            $stmt->execute([$id]);
            $oldData = $stmt->fetch();
            
            // Xóa tất cả quan hệ với bác sĩ trước
            $stmt = $db->prepare("DELETE FROM doctor_patients WHERE patient_id = ?");
            $stmt->execute([$id]);
            
            // Xóa bệnh nhân
            $stmt = $db->prepare("DELETE FROM patients WHERE id = ?");
            $stmt->execute([$id]);
            
            auditLog('delete', 'patients', $id, $oldData, null);
            $message = 'Xóa bệnh nhân thành công!';
            $messageType = 'success';
        }
    }
}

// Search
$params = [];
$search = $_GET['search'] ?? '';

if ($_SESSION['role_id'] == 2) {
    // Bác sĩ chỉ thấy bệnh nhân của mình
    $doctor_id = $db->query("SELECT id FROM doctors WHERE user_id = {$_SESSION['user_id']}")->fetch(PDO::FETCH_ASSOC)['id'];
    $query = "SELECT p.* FROM patients p 
              INNER JOIN doctor_patients dp ON p.id = dp.patient_id
              WHERE dp.doctor_id = ?";
    $params[] = $doctor_id;
} else {
    // Admin/Lễ tân thấy tất cả bệnh nhân
    $query = "SELECT p.* FROM patients p WHERE 1 = 1";
}

if ($search) {
    $query .= " AND (p.patient_code LIKE ? OR p.full_name LIKE ? OR p.phone LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " ORDER BY p.id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý bệnh nhân</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>👥 Quản lý bệnh nhân</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <?php if (hasPermission('patient.create')): ?>
                    <button class="btn btn-primary" onclick="showCreateModal()">➕ Thêm bệnh nhân</button>
                    <?php endif; ?>
                    <form method="GET" style="display: inline-block; margin-left: 20px;">
                        <input type="text" name="search" placeholder="Tìm kiếm..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px;">
                        <button type="submit" class="btn btn-secondary">🔍 Tìm</button>
                    </form>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php if ($_SESSION['role_id'] != 2): ?>
                            <th>Mã BN</th>
                            <?php endif; ?>
                            <th>Họ tên</th>
                            <th>Ngày sinh</th>
                            <th>Giới tính</th>
                            <th>Điện thoại</th>
                            <th>Nhóm máu</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                        <tr>
                            <?php if ($_SESSION['role_id'] != 2): ?>
                            <td><?php echo htmlspecialchars($patient['patient_code']); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($patient['date_of_birth'])); ?></td>
                            <td><?php echo $patient['gender']; ?></td>
                            <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                            <td><?php echo htmlspecialchars($patient['blood_group'] ?? " "); ?></td>
                            <td class="table-actions">
                                <?php if (hasPermission('patient.edit')): ?>
                                <button class="btn btn-warning btn-sm" onclick='editPatient(<?php echo json_encode($patient); ?>)'>✏️</button>
                                <?php endif; ?>
                                <?php if (hasPermission('patient.delete')): ?>
                                <button class="btn btn-danger btn-sm" onclick="deletePatient(<?php echo $patient['id']; ?>, '<?php echo htmlspecialchars($patient['full_name']); ?>')">🗑️</button>
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
    <div id="patientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm bệnh nhân</h2>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="patientId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Họ tên *</label>
                        <input type="text" name="full_name" id="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Ngày sinh *</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" required>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Giới tính *</label>
                        <select name="gender" id="gender" required>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Điện thoại</label>
                        <input type="text" name="phone" id="phone">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="email">
                    </div>
                    <div class="form-group">
                        <label>CMND/CCCD</label>
                        <input type="text" name="identity_card" id="identity_card">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nhóm máu</label>
                        <select name="blood_group" id="blood_group">
                            <option value="">-- Chọn --</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="AB">AB</option>
                            <option value="O">O</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <input type="text" name="address" id="address">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Dị ứng</label>
                    <textarea name="allergies" id="allergies"></textarea>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">💾 Lưu</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">❌ Hủy</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showCreateModal() {
            document.getElementById('modalTitle').textContent = 'Thêm bệnh nhân';
            document.getElementById('formAction').value = 'create';
            document.getElementById('patientId').value = '';
            document.querySelector('form').reset();
            document.getElementById('patientModal').classList.add('active');
        }
        
        function editPatient(patient) {
            document.getElementById('modalTitle').textContent = 'Sửa thông tin bệnh nhân';
            document.getElementById('formAction').value = 'update';
            document.getElementById('patientId').value = patient.id;
            document.getElementById('full_name').value = patient.full_name;
            document.getElementById('date_of_birth').value = patient.date_of_birth;
            document.getElementById('gender').value = patient.gender;
            document.getElementById('phone').value = patient.phone || '';
            document.getElementById('email').value = patient.email || '';
            document.getElementById('address').value = patient.address || '';
            document.getElementById('identity_card').value = patient.identity_card || '';
            document.getElementById('blood_group').value = patient.blood_group || '';
            document.getElementById('allergies').value = patient.allergies || '';
            document.getElementById('patientModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('patientModal').classList.remove('active');
        }
        
        function deletePatient(id, name) {
            if (confirm('Xóa bệnh nhân "' + name + '"?')) {
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
