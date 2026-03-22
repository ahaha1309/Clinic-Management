<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('medicine.view');

$db = getDB();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && hasPermission('medicine.create')) {
        $medicine_code = generateCode('TH', 'medicines', 'medicine_code');
        
        $stmt = $db->prepare("INSERT INTO medicines (medicine_code, medicine_name, unit, price, stock_quantity, min_stock_level, expiry_date, manufacturer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $medicine_code,
            $_POST['medicine_name'],
            $_POST['unit'],
            $_POST['price'],
            $_POST['stock_quantity'] ?? 0,
            $_POST['min_stock_level'] ?? 10,
            $_POST['expiry_date'],
            $_POST['manufacturer']
        ]);
        
        auditLog('create', 'medicines', $db->lastInsertId(), null, $_POST);
        $message = 'Thêm thuốc thành công!';
        $messageType = 'success';
    } elseif ($_POST['action'] === 'update' && hasPermission('medicine.edit')) {
        $stmt = $db->prepare("UPDATE medicines SET medicine_name=?, unit=?, price=?, min_stock_level=?, expiry_date=?, manufacturer=? WHERE id=?");
        $stmt->execute([
            $_POST['medicine_name'],
            $_POST['unit'],
            $_POST['price'],
            $_POST['min_stock_level'],
            $_POST['expiry_date'],
            $_POST['manufacturer'],
            $_POST['id']
        ]);
        
        auditLog('update', 'medicines', $_POST['id'], null, $_POST);
        $message = 'Cập nhật thuốc thành công!';
        $messageType = 'success';
    }
}

$stmt = $db->query("SELECT * FROM medicines WHERE is_active = 1 ORDER BY medicine_name");
$medicines = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý kho thuốc</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>🏪 Quản lý kho thuốc</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if (hasPermission('medicine.create')): ?>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="showCreateModal()">➕ Thêm thuốc</button>
                </div>
                <?php endif; ?>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã thuốc</th>
                            <th>Tên thuốc</th>
                            <th>Đơn vị</th>
                            <th>Giá bán</th>
                            <th>Tồn kho</th>
                            <th>Mức tối thiểu</th>
                            <th>HSD</th>
                            <th>Nhà SX</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicines as $med): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($med['medicine_code']); ?></td>
                            <td><?php echo htmlspecialchars($med['medicine_name']); ?></td>
                            <td><?php echo htmlspecialchars($med['unit']); ?></td>
                            <td><?php echo number_format($med['price']); ?>đ</td>
                            <td>
                                <?php if ($med['stock_quantity'] <= $med['min_stock_level']): ?>
                                    <span class="badge badge-danger"><?php echo $med['stock_quantity']; ?></span>
                                <?php else: ?>
                                    <?php echo $med['stock_quantity']; ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $med['min_stock_level']; ?></td>
                            <td><?php echo $med['expiry_date'] ? date('d/m/Y', strtotime($med['expiry_date'])) : ''; ?></td>
                            <td><?php echo htmlspecialchars($med['manufacturer']); ?></td>
                            <td>
                                <?php if (hasPermission('medicine.edit')): ?>
                                <button class="btn btn-warning btn-sm" onclick='editMedicine(<?php echo json_encode($med); ?>)'>✏️</button>
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
    <div id="medicineModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm thuốc</h2>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="medicineId">
                
                <div class="form-group">
                    <label>Tên thuốc *</label>
                    <input type="text" name="medicine_name" id="medicine_name" required>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Đơn vị *</label>
                        <input type="text" name="unit" id="unit" required>
                    </div>
                    <div class="form-group">
                        <label>Giá bán *</label>
                        <input type="number" name="price" id="price" required>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Số lượng ban đầu</label>
                        <input type="number" name="stock_quantity" id="stock_quantity" value="0">
                    </div>
                    <div class="form-group">
                        <label>Mức tồn tối thiểu</label>
                        <input type="number" name="min_stock_level" id="min_stock_level" value="10">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Hạn sử dụng</label>
                        <input type="date" name="expiry_date" id="expiry_date">
                    </div>
                    <div class="form-group">
                        <label>Nhà sản xuất</label>
                        <input type="text" name="manufacturer" id="manufacturer">
                    </div>
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
            document.getElementById('modalTitle').textContent = 'Thêm thuốc';
            document.getElementById('formAction').value = 'create';
            document.querySelector('form').reset();
            document.getElementById('medicineModal').classList.add('active');
        }
        
        function editMedicine(med) {
            document.getElementById('modalTitle').textContent = 'Sửa thông tin thuốc';
            document.getElementById('formAction').value = 'update';
            document.getElementById('medicineId').value = med.id;
            document.getElementById('medicine_name').value = med.medicine_name;
            document.getElementById('unit').value = med.unit;
            document.getElementById('price').value = med.price;
            document.getElementById('min_stock_level').value = med.min_stock_level;
            document.getElementById('expiry_date').value = med.expiry_date;
            document.getElementById('manufacturer').value = med.manufacturer || '';
            document.getElementById('medicineModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('medicineModal').classList.remove('active');
        }
    </script>
</body>
</html>
