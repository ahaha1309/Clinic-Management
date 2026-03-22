<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('invoice.view');

$db = getDB();
$message = '';
$messageType = '';
$invoice_id = $_GET['id'] ?? 0;
$path = parse_url($_SESSION['back_url'] ?? '', PHP_URL_PATH);
$file = basename($path);

if (!isset($_SESSION['invoice_back_url'][$invoice_id])) {
    $_SESSION['invoice_back_url'][$invoice_id] = $_SERVER['HTTP_REFERER'] ?? 'invoices.php';
}

if (isset($_GET['rollback']) && $_GET['rollback'] == 1&& $file=="invoice_create.php") {
    $_SESSION['rollback'] = true;
    
    $db->beginTransaction();
    
    try {
        // Lấy thông tin invoice để lưu vào session
        $stmt = $db->prepare("SELECT appointment_id, discount FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $invoiceData = $stmt->fetch();
        
        if ($invoiceData) {
            // Lưu thông tin để hiển thị lại ở trang create
            $_SESSION['invoice_restore_data'] = [
                'appointment_id' => $invoiceData['appointment_id'],
                'consultation_fee' => $invoiceData['consultation_fee'],
                'discount' => $invoiceData['discount']
            ];
        }
        
        // Xóa dịch vụ
        $stmt = $db->prepare("DELETE FROM invoice_services WHERE invoice_id = ?");
        $stmt->execute([$invoice_id]);
        
        // Xóa thuốc
        $stmt = $db->prepare("DELETE FROM invoice_medicines WHERE invoice_id = ?");
        $stmt->execute([$invoice_id]);
        
        // Xóa payments (nếu có)
        $stmt = $db->prepare("DELETE FROM payments WHERE invoice_id = ?");
        $stmt->execute([$invoice_id]);
        
        // Xóa invoice
        $stmt = $db->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        
        // Ghi audit log
        auditLog('delete_rollback', 'invoices', $invoice_id, null, null);
        
        $db->commit();
        
        // Xóa session temp
        unset($_SESSION['invoice_temp_added'][$invoice_id]);
        unset($_SESSION['invoice_back_url'][$invoice_id]);
        
        // Redirect về trang tạo hóa đơn
        header("Location: invoice_create.php?restored=1");
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $message = 'Lỗi khi xóa hóa đơn: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

if (isset($_GET['success'])) {
    $message = 'Thao tác thành công!';
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && hasPermission('invoice.edit')) {
    if ($_POST['action'] === 'add_service') {
        $stmt = $db->prepare("SELECT price FROM services WHERE id = ?");
        $stmt->execute([$_POST['service_id']]);
        $price = $stmt->fetch()['price'];
        $quantity = $_POST['quantity'];
        $total = $price * $quantity;
        
        $stmt = $db->prepare("INSERT INTO invoice_services (invoice_id, service_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
 $stmt->execute([$invoice_id, $_POST['service_id'], $quantity, $price, $total]);

$insertId = $db->lastInsertId();

$_SESSION['invoice_temp_added'][$invoice_id]['services'][] = $insertId;

auditLog('add_service', 'invoice_services', $insertId, null, $_POST);
        $message = 'Thêm dịch vụ thành công!';
        $messageType = 'success';
    } elseif ($_POST['action'] === 'add_medicine') {
        $stmt = $db->prepare("SELECT price FROM medicines WHERE id = ?");
        $stmt->execute([$_POST['medicine_id']]);
        $price = $stmt->fetch()['price'];
        $quantity = $_POST['quantity'];
        $total = $price * $quantity;
        
        $stmt = $db->prepare("INSERT INTO invoice_medicines (invoice_id, medicine_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
   $stmt->execute([$invoice_id, $_POST['medicine_id'], $quantity, $price, $total]);

$insertId = $db->lastInsertId();

$_SESSION['invoice_temp_added'][$invoice_id]['medicines'][] = $insertId;

auditLog('add_medicine', 'invoice_medicines', $insertId, null, $_POST);
        $message = 'Thêm thuốc thành công!';
        $messageType = 'success';
    } elseif ($_POST['action'] === 'remove_service') {
        $stmt = $db->prepare("DELETE FROM invoice_services WHERE id = ?");
        $stmt->execute([$_POST['item_id']]);
        
        auditLog('remove_service', 'invoice_services', $_POST['item_id'], null, null);
        $message = 'Xóa dịch vụ thành công!';
        $messageType = 'success';
    } elseif ($_POST['action'] === 'remove_medicine') {
        $stmt = $db->prepare("DELETE FROM invoice_medicines WHERE id = ?");
        $stmt->execute([$_POST['item_id']]);
        
        auditLog('remove_medicine', 'invoice_medicines', $_POST['item_id'], null, null);
        $message = 'Xóa thuốc thành công!';
        $messageType = 'success';
    }
}

$stmt = $db->prepare("
    SELECT 
        i.*,
        p.patient_code,
        p.full_name as patient_name,
        p.phone as patient_phone,
        a.appointment_code
    FROM invoices i
    JOIN patients p ON i.patient_id = p.id
    LEFT JOIN appointments a ON i.appointment_id = a.id
    WHERE i.id = ?
");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header('Location: invoices.php');
    exit();
}

$stmt = $db->prepare("
    SELECT 
        ins.*,
        s.service_name
    FROM invoice_services ins
    JOIN services s ON ins.service_id = s.id
    WHERE ins.invoice_id = ?
");
$stmt->execute([$invoice_id]);
$invoice_services = $stmt->fetchAll();

$stmt = $db->prepare("
    SELECT 
        im.*,
        m.medicine_name,
        m.unit
    FROM invoice_medicines im
    JOIN medicines m ON im.medicine_id = m.id
    WHERE im.invoice_id = ?
");
$stmt->execute([$invoice_id]);
$invoice_medicines = $stmt->fetchAll();

$services = $db->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name")->fetchAll();
$medicines = $db->query("SELECT * FROM medicines WHERE is_active = 1 AND stock_quantity > 0 ORDER BY medicine_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết hóa đơn</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>📄 Chi tiết hóa đơn</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <div class="invoice-header">
                    <h2>Hóa đơn: <?php echo htmlspecialchars($invoice['invoice_code']); ?></h2>
                    <p><strong>👤 Bệnh nhân:</strong> <?php echo htmlspecialchars($invoice['patient_name']); ?> (<?php echo $invoice['patient_code']; ?>)</p>
                    <p><strong>📞 Điện thoại:</strong> <?php echo htmlspecialchars($invoice['patient_phone'] ?? 'N/A'); ?></p>
                    <p><strong>📅 Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($invoice['invoice_date'])); ?></p>
                    <p><strong>Trạng thái:</strong> 
                        <?php
                        $badges = ['pending' => '⏳ Chưa trả', 'partial' => '💳 Trả 1 phần', 'paid' => '✅ Đã trả', 'cancelled' => '❌ Đã hủy'];
                        echo $badges[$invoice['status']];
                        ?>
                    </p>
                </div>
                
                <div class="section-card">
                    <h3>🔧 Dịch vụ khám</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tên dịch vụ</th>
                                <th>Số lượng</th>
                                <th>Đơn giá</th>
                                <th>Thành tiền</th>
                                <?php if ($invoice['status'] != 'paid' && hasPermission('invoice.edit')): ?>
                                <th>Thao tác</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoice_services)): ?>
                            <tr><td colspan="5" style="text-align: center; color: #999;">Chưa có dịch vụ</td></tr>
                            <?php else: ?>
                            <?php foreach ($invoice_services as $svc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($svc['service_name']); ?></td>
                                <td><?php echo $svc['quantity']; ?></td>
                                <td><?php echo number_format($svc['price']); ?>đ</td>
                                <td><strong><?php echo number_format($svc['total']); ?>đ</strong></td>
                                <?php if ($invoice['status'] != 'paid' && hasPermission('invoice.edit')): ?>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove_service">
                                        <input type="hidden" name="item_id" value="<?php echo $svc['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa dịch vụ này?')">🗑️</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($invoice['status'] != 'paid' && hasPermission('invoice.edit')): ?>
                    <div class="add-item-form">
                        <strong>➕ Thêm dịch vụ:</strong>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_service">
                            <div class="form-grid">
                                <select name="service_id" required>
                                    <option value="">-- Chọn dịch vụ --</option>
                                    <?php foreach ($services as $s): ?>
                                    <option value="<?php echo $s['id']; ?>">
                                        <?php echo htmlspecialchars($s['service_name']); ?> - <?php echo number_format($s['price']); ?>đ
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="quantity" value="1" min="1" required>
                                <button type="submit" class="btn btn-success btn-sm">➕ Thêm</button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="section-card">
                    <h3>💊 Thuốc</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tên thuốc</th>
                                <th>Số lượng</th>
                                <th>Đơn giá</th>
                                <th>Thành tiền</th>
                                <?php if ($invoice['status'] != 'paid' && hasPermission('invoice.edit')): ?>
                                <th>Thao tác</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoice_medicines)): ?>
                            <tr><td colspan="5" style="text-align: center; color: #999;">Chưa có thuốc</td></tr>
                            <?php else: ?>
                            <?php foreach ($invoice_medicines as $med): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($med['medicine_name']); ?></td>
                                <td><?php echo $med['quantity']; ?> <?php echo $med['unit']; ?></td>
                                <td><?php echo number_format($med['price']); ?>đ</td>
                                <td><strong><?php echo number_format($med['total']); ?>đ</strong></td>
                                <?php if ($invoice['status'] != 'paid' && hasPermission('invoice.edit')): ?>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove_medicine">
                                        <input type="hidden" name="item_id" value="<?php echo $med['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa thuốc này?')">🗑️</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($invoice['status'] != 'paid' && hasPermission('invoice.edit')): ?>
                    <div class="add-item-form">
                        <strong>➕ Thêm thuốc:</strong>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_medicine">
                            <div class="form-grid">
                                <select name="medicine_id" required>
                                    <option value="">-- Chọn thuốc --</option>
                                    <?php foreach ($medicines as $m): ?>
                                    <option value="<?php echo $m['id']; ?>">
                                        <?php echo htmlspecialchars($m['medicine_name']); ?> - 
                                        <?php echo number_format($m['price']); ?>đ 
                                        (Tồn: <?php echo $m['stock_quantity']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="quantity" value="1" min="1" required>
                                <button type="submit" class="btn btn-success btn-sm">➕ Thêm</button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <div class="summary-card">
                    <div class="summary-row">
                        <span>Phí khám bệnh:</span>
                        <span><?php echo number_format($invoice['consultation_fee']); ?>đ</span>
                    </div>
                    <div class="summary-row">
                        <span>Tổng dịch vụ:</span>
                        <span><?php echo number_format($invoice['service_total']); ?>đ</span>
                    </div>
                    <div class="summary-row">
                        <span>Tổng thuốc:</span>
                        <span><?php echo number_format($invoice['medicine_total']); ?>đ</span>
                    </div>
                    <div class="summary-row">
                        <span>Giảm giá:</span>
                        <span>-<?php echo number_format($invoice['discount']); ?>%</span>
                    </div>
                    <div class="summary-row total">
                        <span>TỔNG CỘNG:</span>
                        <span><?php echo number_format($invoice['total_amount']); ?>đ</span>
                    </div>
                    <div class="summary-row">
                        <span>Đã thanh toán:</span>
                        <span><?php echo number_format($invoice['paid_amount']); ?>đ</span>
                    </div>
                    <div class="summary-row remaining">
                        <span>CÒN LẠI:</span>
                        <span><?php echo number_format($invoice['total_amount'] - $invoice['paid_amount']); ?>đ</span>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <?php if ($file=="invoice_create.php"): ?>
                     <a href="invoices.php" class="btn btn-info">💾 Tạo hóa đơn mới</a>
                    <?php endif; ?>                    
                    <?php if ($invoice['status'] != 'paid' && $invoice['total_amount'] > $invoice['paid_amount'] && hasPermission('payment.create')): ?>
                    <a href="payment_create.php?invoice_id=<?php echo $invoice_id; ?>" class="btn btn-primary">💰 Thanh toán</a>
                    <?php endif; ?>
                    <?php if($file=="invoice_create.php"): ?>
                    <a href=" invoice_detail.php?id=<?php echo $invoice_id; ?>&rollback=1"
   class="btn btn-secondary"
   onclick="return confirm('Quay lại sẽ hủy toàn bộ dịch vụ/thuốc vừa thêm. Tiếp tục?')">
   ← Quay lại
</a>
<?php else: ?>
                    <a href="<?php echo $_SESSION['back_url'] ?? 'invoices.php'; ?>" class="btn btn-secondary">← Quay lại</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>