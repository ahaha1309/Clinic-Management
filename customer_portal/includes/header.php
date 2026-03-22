<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Phòng khám đa khoa'; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <a href="index.php" class="logo">
                    <i class="fas fa-hospital"></i>
                    <span>Phòng khám Đa khoa</span>
                </a>
                
                <button class="mobile-toggle" id="mobileToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <ul class="nav-menu" id="navMenu">
                    <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Trang chủ</a></li>
                    <li><a href="services.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>">Dịch vụ</a></li>
                    <li><a href="doctors.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'doctors.php' ? 'active' : ''; ?>">Bác sĩ</a></li>
                    <li><a href="facilities.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'facilities.php' ? 'active' : ''; ?>">Cơ sở vật chất</a></li>
                    <li><a href="booking.php" class="">Đặt lịch khám</a></li>
                    <?php if(isset($_SESSION['role_id']) && $_SESSION['role_id'] == 6): ?>
                    <li><a href="profile.php" class="">Chào <?php echo $_SESSION['full_name'] ?? 'Khách hàng'; ?></a></li>
                    <li><a href="logout.php" class="btn-danger">Đăng xuất</a></li>
                    <?php else: ?>
                    <li><a href="../clinic_system/login.php" class="btn-primary">Đăng nhập</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
