<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('service.view');

$db = getDB();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && hasPermission('service.manage')) {
    if ($_POST['action'] === 'create') {
        $service_code = generateCode('DV', 'services', 'service_code');
        
        $stmt = $db->prepare("INSERT INTO services (service_code, service_name, description, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $service_code,
            $_POST['service_name'],
            $_POST['description'],
            $_POST['price']
        ]);
        
        auditLog('create', 'services', $db->lastInsertId(), null, $_POST);
        $message = 'Thêm dịch vụ thành công!';
        $messageType = 'success';
    } elseif ($_POST['action'] === 'update') {
        $stmt = $db->prepare("UPDATE services SET service_name=?, description=?, price=? WHERE id=?");
        $stmt->execute([
            $_POST['service_name'],
            $_POST['description'],
            $_POST['price'],
            $_POST['id']
        ]);
        
        auditLog('update', 'services', $_POST['id'], null, $_POST);
        $message = 'Cập nhật dịch vụ thành công!';
        $messageType = 'success';
    }
}

$stmt = $db->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_code");
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý dịch vụ</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>🔧 Quản lý dịch vụ</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if (hasPermission('service.manage')): ?>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="showCreateModal()">➕ Thêm dịch vụ</button>
                </div>
                <?php endif; ?>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã DV</th>
                            <th>Tên dịch vụ</th>
                            <th>Mô tả</th>
                            <th>Giá</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $svc): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($svc['service_code']); ?></td>
                            <td><?php echo htmlspecialchars($svc['service_name']); ?></td>
                            <td><?php echo htmlspecialchars($svc['description']); ?></td>
                            <td><?php echo number_format($svc['price']); ?>đ</td>
                            <td>
                                <?php if (hasPermission('service.manage')): ?>
                                <button class="btn btn-warning btn-sm" onclick='editService(<?php echo json_encode($svc); ?>)'>✏️</button>
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
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm dịch vụ</h2>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="serviceId">
                
                <div class="form-group">
                    <label>Tên dịch vụ *</label>
                    <input type="text" name="service_name" id="service_name" required>
                </div>
                
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" id="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Giá *</label>
                    <input type="number" name="price" id="price" required>
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
            document.getElementById('modalTitle').textContent = 'Thêm dịch vụ';
            document.getElementById('formAction').value = 'create';
            document.querySelector('form').reset();
            document.getElementById('serviceModal').classList.add('active');
        }
        
        function editService(svc) {
            document.getElementById('modalTitle').textContent = 'Sửa dịch vụ';
            document.getElementById('formAction').value = 'update';
            document.getElementById('serviceId').value = svc.id;
            document.getElementById('service_name').value = svc.service_name;
            document.getElementById('description').value = svc.description || '';
            document.getElementById('price').value = svc.price;
            document.getElementById('serviceModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('serviceModal').classList.remove('active');
        }
    </script>
</body>
</html>
