<?php
require_once 'config/database.php';
$pageTitle = 'Đội ngũ bác sĩ - Phòng khám Đa khoa';
include 'includes/header.php';

$db = getDB();

// Get all active doctors
$stmt = $db->query("
    SELECT 
        d.*,
        u.full_name,
        u.email,
        u.phone
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    WHERE u.is_active = 1
    ORDER BY d.experience_years DESC, u.full_name
");
$doctors = $stmt->fetchAll();
?>

<!-- Page Header -->
<section class="hero" style="padding: 80px 0;">
    <div class="container">
        <h1>Đội ngũ bác sĩ</h1>
        <p>Các bác sĩ giàu kinh nghiệm, tận tâm với nghề</p>
    </div>
</section>

<!-- Doctors Section -->
<section class="doctors" style="padding: 80px 0;">
    <div class="container">
        <?php if (count($doctors) > 0): ?>
        <div class="doctors-grid">
            <?php foreach ($doctors as $doctor): ?>
            <div class="doctor-card">
                <div class="doctor-image">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="doctor-info">
                    <h3><?php echo e($doctor['full_name']); ?></h3>
                    <div class="doctor-specialty">
                        <i class="fas fa-briefcase-medical"></i>
                        <?php echo e($doctor['specialization'] ?? 'Bác sĩ đa khoa'); ?>
                    </div>
                    
                    <?php if ($doctor['qualification']): ?>
                    <div style="color: #666; font-size: 0.95rem; margin-bottom: 8px;">
                        <i class="fas fa-graduation-cap"></i>
                        <?php echo e($doctor['qualification']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="doctor-experience">
                        <i class="fas fa-award"></i>
                        <?php echo e($doctor['experience_years']); ?> năm kinh nghiệm
                    </div>
                    
                    <div class="doctor-fee">
                        Phí khám: <?php echo number_format($doctor['consultation_fee']); ?>đ
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <a href="booking.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-calendar-check"></i> Đặt lịch khám
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-user-md" style="font-size: 4rem; color: #667eea; margin-bottom: 20px;"></i>
            <h3 style="color: #333; margin-bottom: 15px;">Thông tin bác sĩ đang được cập nhật</h3>
            <p style="color: #666; margin-bottom: 30px;">Vui lòng liên hệ trực tiếp với phòng khám để biết thêm chi tiết.</p>
            <a href="booking.php" class="btn btn-primary btn-large">
                Đặt lịch tư vấn
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Qualifications Section -->
<section class="features" style="padding: 80px 0; background: #f8f9fa;">
    <div class="container">
        <div class="section-title">
            <h2>Cam kết của chúng tôi</h2>
            <p>Những giá trị mà đội ngũ bác sĩ luôn hướng tới</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3>Đào tạo bài bản</h3>
                <p>Được đào tạo tại các trường y khoa uy tín trong và ngoài nước</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-book-medical"></i>
                </div>
                <h3>Cập nhật kiến thức</h3>
                <p>Thường xuyên tham gia các khóa đào tạo chuyên môn</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>Tận tâm với bệnh nhân</h3>
                <p>Luôn đặt lợi ích của bệnh nhân lên hàng đầu</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3>Tư vấn tận tình</h3>
                <p>Giải đáp mọi thắc mắc một cách chi tiết và dễ hiểu</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="hero" style="padding: 60px 0;">
    <div class="container" style="text-align: center;">
        <h2 style="font-size: 2rem; margin-bottom: 15px;">Đặt lịch với bác sĩ ngay hôm nay</h2>
        <p style="font-size: 1.1rem; margin-bottom: 25px;">Chúng tôi sẵn sàng chăm sóc sức khỏe của bạn</p>
        <a href="booking.php" class="btn btn-primary btn-large">
            <i class="fas fa-calendar-plus"></i> Đặt lịch khám ngay
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
