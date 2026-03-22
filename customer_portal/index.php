<?php
$pageTitle = 'Trang chủ - Phòng khám Đa khoa';
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>Chăm sóc sức khỏe toàn diện</h1>
        <p>Đội ngũ bác sĩ giàu kinh nghiệm - Trang thiết bị hiện đại - Dịch vụ tận tâm</p>
        <div class="hero-buttons">
            <a href="booking.php" class="btn btn-primary btn-large">
                <i class="fas fa-calendar-check"></i> Đặt lịch khám ngay
            </a>
            <a href="services.php" class="btn btn-secondary btn-large">
                <i class="fas fa-stethoscope"></i> Xem dịch vụ
            </a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features">
    <div class="container">
        <div class="section-title">
            <h2>Tại sao chọn chúng tôi?</h2>
            <p>Những lý do bạn nên tin tưởng phòng khám của chúng tôi</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3>Đội ngũ bác sĩ chuyên môn cao</h3>
                <p>Các bác sĩ có nhiều năm kinh nghiệm, được đào tạo bài bản, tận tâm với nghề</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-hospital"></i>
                </div>
                <h3>Cơ sở vật chất hiện đại</h3>
                <p>Trang thiết bị y tế tiên tiến, phòng khám sạch sẽ, thoáng mát</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>An toàn và vệ sinh</h3>
                <p>Tuân thủ nghiêm ngặt các quy định về vệ sinh và an toàn y tế</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Thời gian linh hoạt</h3>
                <p>Mở cửa từ 8:00 - 20:00 hàng ngày, kể cả cuối tuần và lễ tết</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <h3>Chi phí hợp lý</h3>
                <p>Giá cả minh bạch, phù hợp với mọi đối tượng khách hàng</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <h3>Chăm sóc tận tình</h3>
                <p>Phục vụ chu đáo, quan tâm đến từng chi tiết nhỏ nhất</p>
            </div>
        </div>
    </div>
</section>

<!-- Quick Services Section -->
<?php
require_once 'config/database.php';
$db = getDB();

// Get featured services
$stmt = $db->query("SELECT * FROM services WHERE is_active = 1 ORDER BY id LIMIT 6");
$services = $stmt->fetchAll();
?>

<section class="services">
    <div class="container">
        <div class="section-title">
            <h2>Dịch vụ nổi bật</h2>
            <p>Các dịch vụ khám chữa bệnh chuyên nghiệp</p>
        </div>
        
        <div class="services-grid">
            <?php foreach ($services as $service): ?>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div class="service-content">
                    <h3><?php echo e($service['service_name']); ?></h3>
                    <p><?php echo e($service['description'] ?? 'Dịch vụ chất lượng cao với đội ngũ chuyên môn'); ?></p>
                    <div class="service-price">
                        <?php echo number_format($service['price']); ?>đ
                    </div>
                    <a href="booking.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary">
                        Đặt lịch ngay
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="services.php" class="btn btn-secondary btn-large">
                Xem tất cả dịch vụ <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="hero" style="padding: 60px 0;">
    <div class="container">
        <h2 style="font-size: 2rem; margin-bottom: 15px;">Sẵn sàng chăm sóc sức khỏe của bạn</h2>
        <p style="font-size: 1.1rem; margin-bottom: 25px;">Đặt lịch hẹn ngay hôm nay để được tư vấn miễn phí</p>
        <a href="booking.php" class="btn btn-primary btn-large">
            <i class="fas fa-calendar-plus"></i> Đặt lịch khám ngay
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
