<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('medicine.import');

$db = getDB();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $import_code = generateCode('PN', 'medicine_imports', 'import_code');
        
        $stmt = $db->prepare("INSERT INTO medicine_imports (import_code, supplier_name, notes, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $import_code,
            $_POST['supplier_name'],
            $_POST['notes'],
            $_SESSION['user_id']
        ]);
        
        $import_id = $db->lastInsertId();
        $total_amount = 0;
        
        // Add import details
        if (isset($_POST['medicines'])) {
            foreach ($_POST['medicines'] as $med) {
                $total = $med['quantity'] * $med['import_price'];
                $total_amount += $total;
                
                $stmt = $db->prepare("INSERT INTO medicine_import_details (import_id, medicine_id, quantity, import_price, total, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $import_id,
                    $med['medicine_id'],
                    $med['quantity'],
                    $med['import_price'],
                    $total,
                    $med['expiry_date']
                ]);
            }
        }
        
        // Update total amount
        $stmt = $db->prepare("UPDATE medicine_imports SET total_amount = ? WHERE id = ?");
        $stmt->execute([$total_amount, $import_id]);
        
        auditLog('create', 'medicine_imports', $import_id, null, $_POST);
        $message = 'Nhập thuốc thành công!';
        $messageType = 'success';
    }
}

$stmt = $db->query("
    SELECT 
        mi.*,
        u.full_name as created_by_name
    FROM medicine_imports mi
    LEFT JOIN users u ON mi.created_by = u.id
    ORDER BY mi.id DESC
");
$imports = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nhập thuốc</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>📦 Quản lý nhập thuốc</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="location.href='/clinic_system/medicine_import_create.php'">➕ Tạo phiếu nhập</button>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã PN</th>
                            <th>Nhà cung cấp</th>
                            <th>Tổng tiền</th>
                            <th>Ngày nhập</th>
                            <th>Người tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($imports as $imp): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($imp['import_code']); ?></td>
                            <td><?php echo htmlspecialchars($imp['supplier_name']); ?></td>
                            <td><strong><?php echo number_format($imp['total_amount']); ?>đ</strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($imp['import_date'])); ?></td>
                            <td><?php echo htmlspecialchars($imp['created_by']); ?></td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="viewImport(<?php echo $imp['id']; ?>)">👁️ Xem</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function viewImport(id) {
            window.location.href = '/clinic_system/medicine_import_view.php?id=' + id;
        }
    </script>
</body>
</html>
