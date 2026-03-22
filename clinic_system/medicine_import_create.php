<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('medicine.import');

$db = getDB();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->beginTransaction();

    try {
        $import_code = generateCode('PN', 'medicine_imports', 'import_code');
        
        $stmt = $db->prepare("
            INSERT INTO medicine_imports (import_code, supplier_name, notes, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $import_code,
            $_POST['supplier_name'],
            $_POST['notes'] ?? '',
            $_SESSION['user_id']
        ]);
        $import_id = $db->lastInsertId();
        $total_amount = 0;

        if (isset($_POST['medicine_id']) && is_array($_POST['medicine_id'])) {
            for ($i = 0; $i < count($_POST['medicine_id']); $i++) {
                if (!empty($_POST['medicine_id'][$i])) {
                    $quantity = $_POST['quantity'][$i];
                    $import_price = $_POST['import_price'][$i];
                    $total = $quantity * $import_price;
                    $total_amount += $total;
                    
                    $stmt = $db->prepare("
                        INSERT INTO medicine_import_details 
                        (import_id, medicine_id, quantity, import_price, total, expiry_date)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $import_id,
                        $_POST['medicine_id'][$i],
                        $quantity,
                        $import_price,
                        $total,
                        $_POST['expiry_date'][$i] ?? null
                    ]);
                }
            }
        }

        $stmt = $db->prepare("UPDATE medicine_imports SET total_amount = ? WHERE id = ?");
        $stmt->execute([$total_amount, $import_id]);

        $db->commit();
        
        auditLog('create', 'medicine_imports', $import_id, null, $_POST);
        
        header('Location: medicine_imports.php?success=1');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $message = 'Lỗi: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$medicines = $db->query("
    SELECT id, medicine_name, unit 
    FROM medicines 
    WHERE is_active = 1
    ORDER BY medicine_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo phiếu nhập thuốc</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>📦 Tạo phiếu nhập thuốc</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-section">
                        <h3>1. Thông tin nhà cung cấp</h3>
                        <div class="form-group">
                            <label>Tên nhà cung cấp *</label>
                            <input type="text" name="supplier_name" required placeholder="VD: Công ty Dược phẩm ABC">
                        </div>
                        <div class="form-group">
                            <label>Ghi chú</label>
                            <textarea name="notes" rows="2" placeholder="Ghi chú về phiếu nhập (nếu có)"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>2. Danh sách thuốc nhập</h3>
                        <div class="medicine-list">
                            <h4>Thuốc nhập kho</h4>
                            <div id="medicine-container">
                                <div class="medicine-row" style="border-left-color: #28a745;">
                                    <div>
                                        <label>Thuốc *</label>
                                        <select name="medicine_id[]" required>
                                            <option value="">-- Chọn thuốc --</option>
                                            <?php foreach ($medicines as $med): ?>
                                            <option value="<?php echo $med['id']; ?>">
                                                <?php echo htmlspecialchars($med['medicine_name']); ?> 
                                                (<?php echo $med['unit']; ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label>Số lượng *</label>
                                        <input type="number" name="quantity[]" required min="1" value="1" onchange="calculateTotal()">
                                    </div>
                                    <div>
                                        <label>Giá nhập *</label>
                                        <input type="number" name="import_price[]" required min="0" value="0" onchange="calculateTotal()">
                                    </div>
                                    <div>
                                        <label>Hạn sử dụng</label>
                                        <input type="date" name="expiry_date[]">
                                    </div>
                                    <div class="medicine-row-actions">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)" title="Xóa dòng">🗑️</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-success" onclick="addMedicineRow()">➕ Thêm thuốc</button>
                            
                            <div class="total-display">
                                <h3>Tổng tiền dự kiến:</h3>
                                <div class="amount" id="total_amount">0đ</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>📌 Lưu ý:</strong> Sau khi lưu phiếu nhập, số lượng thuốc sẽ tự động cộng vào kho (trigger).
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">💾 Lưu phiếu nhập</button>
                        <a href="medicine_imports.php" class="btn btn-secondary">❌ Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function addMedicineRow() {
            const container = document.getElementById('medicine-container');
            const firstRow = container.querySelector('.medicine-row');
            const newRow = firstRow.cloneNode(true);
            
            newRow.querySelectorAll('select, input').forEach(input => {
                if (input.type === 'number') {
                    input.value = input.name.includes('quantity') ? 1 : 0;
                } else if (input.type === 'date') {
                    input.value = '';
                } else if (input.tagName === 'SELECT') {
                    input.value = '';
                }
            });
            
            container.appendChild(newRow);
            calculateTotal();
        }
        
        function removeRow(btn) {
            const container = document.getElementById('medicine-container');
            if (container.querySelectorAll('.medicine-row').length > 1) {
                btn.closest('.medicine-row').remove();
                calculateTotal();
            } else {
                alert('⚠️ Phải có ít nhất 1 thuốc trong phiếu nhập!');
            }
        }
        
        function calculateTotal() {
            let total = 0;
            const rows = document.querySelectorAll('.medicine-row');
            
            rows.forEach(row => {
                const quantity = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
                const price = parseFloat(row.querySelector('input[name="import_price[]"]').value) || 0;
                total += quantity * price;
            });
            
            document.getElementById('total_amount').textContent = total.toLocaleString('vi-VN') + 'đ';
        }
        
        calculateTotal();
    </script>
</body>
</html>