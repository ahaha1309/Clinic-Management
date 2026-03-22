<?php
if (!isset($user)) {
    $user = getCurrentUser();
}
?>
<div class="header">
    <div class="header-left">
        <h2>🏥 Hệ thống Quản lý Phòng khám</h2>
    </div>
    <div class="header-right">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <div>
                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                <!-- <small><?php echo htmlspecialchars($user['role_name']); ?></small> -->
            </div>
        </div>
        <a href="logout.php" class="btn-logout">Đăng xuất</a>
    </div>
</div>
