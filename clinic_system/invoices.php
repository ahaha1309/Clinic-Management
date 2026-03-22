<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requirePermission('invoice.view');

$db = getDB();
$message = '';
$messageType = '';
$_SESSION['back_url'] = $_SERVER['REQUEST_URI'];

if (isset($_GET['success'])) {
    $message = 'Thao tác thành công!';
    $messageType = 'success';
}

$stmt = $db->query("
    SELECT 
        i.*,
        p.patient_code,
        p.full_name as patient_name
    FROM invoices i
    JOIN patients p ON i.patient_id = p.id
    ORDER BY i.id DESC
");
$invoices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý hóa đơn</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h1>📄 Quản lý hóa đơn</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <div class="stats-summary">
                    <div class="stat-box">
                        <h4>💰 Tổng hóa đơn</h4>
                        <p class="number"><?php echo count($invoices); ?></p>
                    </div>
                    <div class="stat-box warning">
                        <h4>⏳ Chưa thanh toán</h4>
                        <p class="number">
                            <?php 
                            $pending = array_filter($invoices, fn($i) => $i['status'] == 'pending');
                            echo count($pending);
                            ?>
                        </p>
                    </div>
                    <div class="stat-box success">
                        <h4>✅ Đã thanh toán</h4>
                        <p class="number">
                            <?php 
                            $paid = array_filter($invoices, fn($i) => $i['status'] == 'paid');
                            echo count($paid);
                            ?>
                        </p>
                    </div>
                </div>
                
                <?php if (hasPermission('invoice.create')): ?>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="location.href='invoice_create.php'">➕ Tạo hóa đơn</button>
                </div>
                <?php endif; ?>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã HD</th>
                            <th>Bệnh nhân</th>
                            <th>Phí khám</th>
                            <th>Dịch vụ</th>
                            <th>Thuốc</th>
                            <th>Giảm giá</th>
                            <th>Tổng tiền</th>
                            <th>Đã trả</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px; color: #999;">
                                📄 Chưa có hóa đơn nào
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($inv['invoice_code']); ?></td>
                            <td><?php echo htmlspecialchars($inv['patient_name']); ?></td>
                            <td><?php echo number_format($inv['consultation_fee']); ?>đ</td>
                            <td><?php echo number_format($inv['service_total']); ?>đ</td>
                            <td><?php echo number_format($inv['medicine_total']); ?>đ</td>
                            <td><?php echo number_format($inv['discount']); ?>đ</td>
                            <td><strong style="color: #28a745;"><?php echo number_format($inv['total_amount']); ?>đ</strong></td>
                            <td><?php echo number_format($inv['paid_amount']); ?>đ</td>
                            <td>
                                <?php
                                $badges = ['pending' => 'badge-warning', 'partial' => 'badge-info', 'paid' => 'badge-success', 'cancelled' => 'badge-danger'];
                                $texts = ['pending' => 'Chưa trả', 'partial' => 'Trả 1 phần', 'paid' => 'Đã trả', 'cancelled' => 'Đã hủy'];
                                ?>
                                <span class="badge <?php echo $badges[$inv['status']]; ?>"><?php echo $texts[$inv['status']]; ?></span>
                            </td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="viewInvoice(<?php echo $inv['id']; ?>)" title="Xem chi tiết">👁️ Xem</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function viewInvoice(id) {
            window.location.href = 'invoice_detail.php?id=' + id;
        }
    </script>
</body>
</html>