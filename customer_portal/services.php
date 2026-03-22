<?php
require_once 'config/database.php';
$pageTitle = 'Dịch vụ - Phòng khám Đa khoa';
include 'includes/header.php';

$db = getDB();

// Get all active services
$stmt = $db->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name");
$services = $stmt->fetchAll();
?>

<!-- Page Header -->
<section class="hero" style="padding: 80px 0;">
    <div class="container">
        <h1>Dịch vụ khám chữa bệnh</h1>
        <p>Chăm sóc sức khỏe toàn diện với đội ngũ bác sĩ chuyên môn cao</p>
    </div>
</section>

<!-- Services Section -->
<section class="services" style="padding: 80px 0; background: #f8f9fa;">
    <div class="container">
        <?php if (count($services) > 0): ?>
        <div class="services-grid">
            <?php foreach ($services as $service): ?>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div class="service-content">
                    <h3><?php echo e($service['service_name']); ?></h3>
                    <p><?php echo e($service['description'] ?? 'Dịch vụ y tế chất lượng cao, được thực hiện bởi đội ngũ bác sĩ giàu kinh nghiệm với trang thiết bị hiện đại.'); ?></p>
                    
                    <div class="service-price">
                        Giá: <?php echo number_format($service['price']); ?>đ
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <a href="booking.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i> Đặt lịch ngay
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-info-circle" style="font-size: 4rem; color: #667eea; margin-bottom: 20px;"></i>
            <h3 style="color: #333; margin-bottom: 15px;">Danh sách dịch vụ đang được cập nhật</h3>
            <p style="color: #666; margin-bottom: 30px;">Vui lòng liên hệ trực tiếp với phòng khám để biết thêm chi tiết về các dịch vụ khám chữa bệnh.</p>
            <a href="booking.php" class="btn btn-primary btn-large">
                Đặt lịch tư vấn
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Why Choose Us -->
<section class="features" style="padding: 80px 0;">
    <div class="container">
        <div class="section-title">
            <h2>Ưu điểm của dịch vụ</h2>
            <p>Những lợi ích khi sử dụng dịch vụ tại phòng khám</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3>Chất lượng đảm bảo</h3>
                <p>Quy trình khám chữa bệnh theo tiêu chuẩn quốc tế</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-microscope"></i>
                </div>
                <h3>Thiết bị hiện đại</h3>
                <p>Trang bị các máy móc y tế tiên tiến nhất</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-hand-holding-medical"></i>
                </div>
                <h3>Chăm sóc tận tâm</h3>
                <p>Đội ngũ y bác sĩ nhiệt tình, chu đáo</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="hero" style="padding: 60px 0;">
    <div class="container" style="text-align: center;">
        <h2 style="font-size: 2rem; margin-bottom: 15px;">Cần tư vấn về dịch vụ?</h2>
        <p style="font-size: 1.1rem; margin-bottom: 25px;">Đội ngũ của chúng tôi luôn sẵn sàng hỗ trợ bạn</p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="booking.php" class="btn btn-primary btn-large">
                <i class="fas fa-calendar-check"></i> Đặt lịch khám
            </a>
            <a href="tel:1900xxxx" class="btn btn-secondary btn-large">
                <i class="fas fa-phone"></i> Gọi ngay: 1900-xxxx
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
