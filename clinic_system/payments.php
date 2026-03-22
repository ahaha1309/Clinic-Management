<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('payment.view');

$db = getDB();
$message = '';
$messageType = '';

$stmt = $db->query("
    SELECT 
        p.*,
        i.invoice_code,
        pt.full_name as patient_name
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN patients pt ON i.patient_id = pt.id
    ORDER BY p.id DESC
");
$payments = $stmt->fetchAll();
if (isset($_GET['success'])) {
    $message = 'Thanh toán thành công!';
    $messageType = 'success';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý thanh toán</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>💰 Quản lý thanh toán</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã TT</th>
                            <th>Mã hóa đơn</th>
                            <th>Bệnh nhân</th>
                            <th>Số tiền</th>
                            <th>Phương thức</th>
                            <th>Ngày TT</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pay['payment_code']); ?></td>
                            <td><?php echo htmlspecialchars($pay['invoice_code']); ?></td>
                            <td><?php echo htmlspecialchars($pay['patient_name']); ?></td>
                            <td><strong><?php echo number_format($pay['amount']); ?>đ</strong></td>
                            <td>
                                <?php
                                $methods = ['cash' => 'Tiền mặt', 'card' => 'Thẻ tín dụng', 'transfer' => 'Chuyển khoản', 'other' => 'Khác'];
                                echo $methods[$pay['payment_method']];
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pay['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($pay['notes']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
