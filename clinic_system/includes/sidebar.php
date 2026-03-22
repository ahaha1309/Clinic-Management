<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$BASE_URL = "/clinic_system/";
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h1>CLINIC SYSTEM</h1>
        <p>Quản lý Phòng khám</p>
    </div>

    <ul class="sidebar-menu">

        <li>
            <a href="<?= $BASE_URL ?>index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                🏠 Trang chủ
            </a>
        </li>

        <?php if (hasPermission('user.view')): ?>
        <li>
            <a href="<?= $BASE_URL ?>users.php" class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">
                👤 Quản lý người dùng
            </a>
        </li>
        <?php endif; ?>

        <?php if (hasPermission('patient.view')): ?>
        <li>
            <a href="<?= $BASE_URL ?>patients.php" class="<?= $currentPage === 'patients.php' ? 'active' : '' ?>">
                👥 Quản lý bệnh nhân
            </a>
        </li>
        <?php endif; ?>

        <?php if (hasPermission('appointment.view')): ?>
        <li>
            <a href="<?= $BASE_URL ?>appointments.php" class="<?= $currentPage === 'appointments.php' ? 'active' : '' ?>">
                📅 Lịch hẹn
            </a>
        </li>
        <?php endif; ?>
        <?php if (hasPermission('appointment_request.view')): ?>
        <li>
            <a href="<?= $BASE_URL ?>appointment_request.php" class="<?= $currentPage === 'appointment_request.php' ? 'active' : '' ?>">
                📝 Yêu cầu đặt lịch
            </a>
        </li>
        <?php endif; ?>
        <?php if (hasPermission('medical_record.view')): ?>
        <li>
            <a href="<?= $BASE_URL ?>medical_records.php" class="<?= $currentPage === 'medical_records.php' ? 'active' : '' ?>">
                📋 Bệnh án
            </a>
            <a class="submenu" href="<?= $BASE_URL ?>medical_record_create.php">➕ Tạo bệnh án</a>
        </li>
        <?php endif; ?>

        <?php if (hasPermission('prescription.view')): ?>
        <li>
            <a href="<?= $BASE_URL ?>prescriptions.php" class="<?= $currentPage === 'prescriptions.php' ? 'active' : '' ?>">
                💊 Đơn thuốc
            </a>
            <a class="submenu" href="<?= $BASE_URL ?>prescription_create.php">➕ Kê đơn thuốc</a>
        </li>
        <?php endif; ?>

        <?php if (hasPermission('service.view')): ?>
        <li>
            <a href="<?= $BASE_URL ?>services.php" class="<?= $currentPage === 'services.php' ? 'active' : '' ?>">
                🔧 Dịch vụ
            </a>
        </li>
        <?php endif; ?>

        <?php if (hasPermission('medicine.view')): ?>
        <li>
            <a href="<?= $BASE_URL ?>medicines.php" class="<?= $currentPage === 'medicines.php' ? 'active' : '' ?>">
                💊 Kho thuốc
            </a>
        </li>
        <?php endif; ?>

        <?php if (hasPermission('invoice.view')): ?>
        <li>
            <a href="<?= $BASE_URL ?>invoices.php" class="<?= $currentPage === 'invoices.php' ? 'active' : '' ?>">
                📄 Hóa đơn
            </a>
            <a class="submenu" href="<?= $BASE_URL ?>invoice_create.php">➕ Tạo hóa đơn</a>
        </li>
        <?php endif; ?>

        <?php if (hasPermission('medicine.import')): ?>
        <li>
            <a href="<?= $BASE_URL ?>medicine_imports.php" class="<?= $currentPage === 'medicine_imports.php' ? 'active' : '' ?>">
                📦 Nhập thuốc
            </a>
            <a class="submenu" href="<?= $BASE_URL ?>medicine_import_create.php">➕ Tạo phiếu nhập</a>
        </li>
        <?php endif; ?>
        <?php if (hasPermission('payment.view')): ?>
        <li>
            <a href="<?= $BASE_URL ?>payments.php" class="<?= $currentPage === 'payments.php' ? 'active' : '' ?>">
                💰 Thanh toán
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="<?= $BASE_URL ?>appointment_history.php" class="<?= $currentPage === 'appointment_history.php' ? 'active' : '' ?>">
                📚 Lịch sử khám
            </a>
        </li>
        <li>
            <a href="<?= $BASE_URL ?>profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                🪪 Trang cá nhân
            </a>
        </li>

    </ul>
</div>