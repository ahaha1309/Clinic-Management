<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - Hệ thống Quản lý Phòng khám</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="dashboard">
                <h1>Chào mừng, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                <p class="subtitle">Vai trò: <strong><?php echo htmlspecialchars($user['role_name']); ?></strong></p>
                
                <div class="stats-grid">
                    <?php
                    $db = getDB();
                    
                    // Thống kê bệnh nhân
                    if (hasPermission('patient.view')) {
                        $stmt = $db->query("SELECT COUNT(*) as count FROM patients");
                        $patientCount = $stmt->fetch()['count'];
                        echo '<div class="stat-card">
                            <div class="stat-icon">👥</div>
                            <div class="stat-info">
                                <h3>' . $patientCount . '</h3>
                                <p>Bệnh nhân</p>
                            </div>
                        </div>';
                    }
                    
                    // Thống kê lịch hẹn hôm nay
                    if (hasPermission('appointment.view')) {
                        $stmt = $db->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = CURDATE()");
                        $appointmentCount = $stmt->fetch()['count'];
                        echo '<div class="stat-card">
                            <div class="stat-icon">📅</div>
                            <div class="stat-info">
                                <h3>' . $appointmentCount . '</h3>
                                <p>Lịch hẹn hôm nay</p>
                            </div>
                        </div>';
                    }
                    
                    // Thống kê hóa đơn chưa thanh toán
                    if (hasPermission('invoice.view')) {
                        $stmt = $db->query("SELECT COUNT(*) as count FROM invoices WHERE status IN ('pending', 'partial')");
                        $pendingInvoices = $stmt->fetch()['count'];
                        echo '<div class="stat-card">
                            <div class="stat-icon">💰</div>
                            <div class="stat-info">
                                <h3>' . $pendingInvoices . '</h3>
                                <p>Hóa đơn chưa thanh toán</p>
                            </div>
                        </div>';
                    }
                    
                    // Thống kê thuốc sắp hết
                    if (hasPermission('medicine.view')) {
                        $stmt = $db->query("SELECT COUNT(*) as count FROM medicines WHERE stock_quantity <= min_stock_level");
                        $lowStockCount = $stmt->fetch()['count'];
                        echo '<div class="stat-card warning">
                            <div class="stat-icon">⚠️</div>
                            <div class="stat-info">
                                <h3>' . $lowStockCount . '</h3>
                                <p>Thuốc sắp hết</p>
                            </div>
                        </div>';
                    }
                    ?>
                </div>
                
                <?php if (hasPermission('appointment.view')): ?>
                <div class="recent-section">
                    <h2>Lịch hẹn hôm nay</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Mã lịch hẹn</th>
                                <th>Bệnh nhân</th>
                                <th>Bác sĩ</th>
                                <th>Giờ hẹn</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $db->query("
                                SELECT 
                                    a.appointment_code,
                                    p.full_name as patient_name,
                                    u.full_name as doctor_name,
                                    a.appointment_time,
                                    a.status
                                FROM appointments a
                                JOIN patients p ON a.patient_id = p.id
                                JOIN doctors d ON a.doctor_id = d.id
                                JOIN users u ON d.user_id = u.id
                                WHERE DATE(a.appointment_date) = CURDATE()
                                ORDER BY a.appointment_time
                                LIMIT 5
                            ");
                            
                            while ($row = $stmt->fetch()) {
                                $statusClass = '';
                                $statusText = '';
                                switch ($row['status']) {
                                    case 'scheduled': $statusClass = 'badge-warning'; $statusText = 'Đã đặt'; break;
                                    case 'confirmed': $statusClass = 'badge-info'; $statusText = 'Đã xác nhận'; break;
                                    case 'completed': $statusClass = 'badge-success'; $statusText = 'Hoàn thành'; break;
                                    case 'cancelled': $statusClass = 'badge-danger'; $statusText = 'Đã hủy'; break;
                                }
                                
                                echo '<tr>
                                    <td>' . htmlspecialchars($row['appointment_code']) . '</td>
                                    <td>' . htmlspecialchars($row['patient_name']) . '</td>
                                    <td>' . htmlspecialchars($row['doctor_name']) . '</td>
                                    <td>' . date('H:i', strtotime($row['appointment_time'])) . '</td>
                                    <td><span class="badge ' . $statusClass . '">' . $statusText . '</span></td>
                                </tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
