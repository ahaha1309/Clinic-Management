<?php
require_once 'config/database.php';
$pageTitle = 'Đặt lịch khám - Phòng khám Đa khoa';

$db = getDB();

// Get services
$stmt = $db->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name");
$services = $stmt->fetchAll();

// Get doctors
$stmt = $db->query("
    SELECT 
        d.id,
        d.doctor_code,
        d.specialization,
        u.full_name
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    WHERE u.is_active = 1
    ORDER BY u.full_name
");
$doctors = $stmt->fetchAll();

// Pre-fill data from URL parameters
$preselect_service = $_GET['service_id'] ?? '';
$preselect_doctor = $_GET['doctor_id'] ?? '';

include 'includes/header.php';
?>

<!-- Page Header -->
<section class="hero" style="padding: 60px 0;">
    <div class="container">
        <h1><i class="fas fa-calendar-check"></i> Đặt lịch khám</h1>
        <p>Vui lòng điền đầy đủ thông tin để chúng tôi có thể phục vụ bạn tốt nhất</p>
    </div>
</section>

<!-- Booking Form Section -->
<section class="booking-section">
    <div class="container">
        <div class="booking-container">
            
            <div id="messageContainer"></div>
            
            <form id="bookingForm" method="POST" action="api/create_appointment.php">
                
                <!-- Thông tin cá nhân -->
                <div style="margin-bottom: 35px;">
                    <h3 style="color: #667eea; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">
                        <i class="fas fa-user"></i> Thông tin cá nhân
                    </h3>
                    
                    <div class="form-group">
                        <label>Họ và tên <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" required placeholder="Nguyễn Văn A">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ngày sinh</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" max="<?php echo date('Y-m-d'); ?>">
                            <div class="form-note">Không bắt buộc, giúp bác sĩ tư vấn tốt hơn</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Giới tính <span class="required">*</span></label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="gender" value="Nam" checked> Nam
                                </label>
                                <label>
                                    <input type="radio" name="gender" value="Nữ"> Nữ
                                </label>
                                <label>
                                    <input type="radio" name="gender" value="Khác"> Khác
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Số điện thoại <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" required placeholder="0987654321">
                            <div class="form-note">Số điện thoại để liên hệ xác nhận</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="email" name="email" placeholder="example@email.com">
                            <div class="form-note">Không bắt buộc</div>
                        </div>
                    </div>
                </div>
                
                <!-- Thông tin đặt lịch -->
                <div style="margin-bottom: 35px;">
                    <h3 style="color: #667eea; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">
                        <i class="fas fa-calendar-alt"></i> Thông tin đặt lịch
                    </h3>
                    
                    <div class="form-group">
                        <label>Dịch vụ khám <span class="required">*</span></label>
                        <select id="service_id" name="service_id" required>
                            <option value="">-- Chọn dịch vụ khám --</option>
                            <?php foreach ($services as $service): ?>
                            <option value="<?php echo $service['id']; ?>" 
                                    <?php echo ($preselect_service == $service['id']) ? 'selected' : ''; ?>>
                                <?php echo e($service['service_name']); ?> 
                                (<?php echo number_format($service['price']); ?>đ)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Bác sĩ mong muốn</label>
                        <select id="doctor_id" name="doctor_id">
                            <option value="">-- Để phòng khám sắp xếp --</option>
                            <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>"
                                    <?php echo ($preselect_doctor == $doctor['id']) ? 'selected' : ''; ?>>
                                <?php echo e($doctor['full_name']); ?>
                                <?php if ($doctor['specialization']): ?>
                                    - <?php echo e($doctor['specialization']); ?>
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-note">Không bắt buộc. Nếu không chọn, phòng khám sẽ sắp xếp bác sĩ phù hợp</div>
                    </div>
                    
                    <!-- Hiển thị lịch làm việc của bác sĩ -->
                    <div id="scheduleInfo" style="display: none; background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ngày khám mong muốn <span class="required">*</span></label>
                            <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                            <div class="form-note">Ngày sớm nhất: <?php echo date('d/m/Y'); ?></div>
                        </div>
                        
                        <div class="form-group">
                            <label>Khung giờ mong muốn</label>
                            <select id="appointment_time" name="appointment_time">
                                <option value="">-- Để phòng khám sắp xếp --</option>
                                <option value="08:00">8:00 - 9:00</option>
                                <option value="09:00">9:00 - 10:00</option>
                                <option value="10:00">10:00 - 11:00</option>
                                <option value="11:00">11:00 - 12:00</option>
                                <option value="14:00">14:00 - 15:00</option>
                                <option value="15:00">15:00 - 16:00</option>
                                <option value="16:00">16:00 - 17:00</option>
                                <option value="17:00">17:00 - 18:00</option>
                                <option value="18:00">18:00 - 19:00</option>
                                <option value="19:00">19:00 - 20:00</option>
                            </select>
                            <div class="form-note">Không bắt buộc</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Triệu chứng / Ghi chú</label>
                        <textarea id="reason" name="reason" placeholder="Mô tả triệu chứng hoặc lý do khám để bác sĩ chuẩn bị tốt hơn..."></textarea>
                    </div>
                </div>
                
                <!-- Xác nhận -->
                <div style="margin-bottom: 30px;">
                    <div class="checkbox-group">
                        <input type="checkbox" id="consent" name="consent" required value="1">
                        <label for="consent">
                            Tôi đồng ý cho phép phòng khám lưu trữ và xử lý thông tin cá nhân của tôi phục vụ cho việc khám chữa bệnh. 
                            <span class="required">*</span>
                        </label>
                    </div>
                </div>
                
                <!-- Buttons -->
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary btn-large" style="flex: 1; min-width: 200px;">
                        <i class="fas fa-paper-plane"></i> Gửi yêu cầu đặt lịch
                    </button>
                    <button type="reset" class="btn btn-secondary" style="flex: 1; min-width: 200px;">
                        <i class="fas fa-redo"></i> Nhập lại
                    </button>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                    <p style="margin: 0; color: #856404; line-height: 1.8;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Lưu ý:</strong> Sau khi gửi yêu cầu, nhân viên phòng khám sẽ liên hệ xác nhận trong vòng 30 phút (giờ hành chính). 
                        Vui lòng để ý điện thoại.
                    </p>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section style="padding: 60px 0; background: #f8f9fa;">
    <div class="container">
        <div class="section-title">
            <h2>Cần hỗ trợ?</h2>
            <p>Liên hệ với chúng tôi nếu bạn có bất kỳ câu hỏi nào</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; max-width: 900px; margin: 0 auto;">
            <div style="background: white; padding: 30px; border-radius: 15px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <i class="fas fa-phone" style="font-size: 3rem; color: #667eea; margin-bottom: 15px;"></i>
                <h4 style="margin-bottom: 10px;">Hotline</h4>
                <p style="color: #666; margin: 0;">1900-xxxx</p>
            </div>
            
            <div style="background: white; padding: 30px; border-radius: 15px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <i class="fas fa-envelope" style="font-size: 3rem; color: #667eea; margin-bottom: 15px;"></i>
                <h4 style="margin-bottom: 10px;">Email</h4>
                <p style="color: #666; margin: 0;">contact@clinic.vn</p>
            </div>
            
            <div style="background: white; padding: 30px; border-radius: 15px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <i class="fas fa-map-marker-alt" style="font-size: 3rem; color: #667eea; margin-bottom: 15px;"></i>
                <h4 style="margin-bottom: 10px;">Địa chỉ</h4>
                <p style="color: #666; margin: 0;">123 Nguyễn Huệ, Q1, TP.HCM</p>
            </div>
        </div>
    </div>
</section>

<script>
// Handle form submission via AJAX
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const messageContainer = document.getElementById('messageContainer');
    const submitButton = this.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
    
    fetch('api/create_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageContainer.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <strong>Thành công!</strong><br>
                    ${data.message}<br>
                    Mã lịch hẹn của bạn: <strong>${data.appointment_code || 'Đang cập nhật'}</strong><br>
                    Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.
                </div>
            `;
            this.reset();
            window.scrollTo({top: 0, behavior: 'smooth'});
        } else {
            messageContainer.innerHTML = `
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Lỗi!</strong><br>
                    ${data.message || 'Có lỗi xảy ra. Vui lòng thử lại.'}
                </div>
            `;
            window.scrollTo({top: 0, behavior: 'smooth'});
        }
    })
    .catch(error => {
        console.error('Error:', error);
        messageContainer.innerHTML = `
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Lỗi kết nối!</strong><br>
                Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng và thử lại.
            </div>
        `;
        window.scrollTo({top: 0, behavior: 'smooth'});
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi yêu cầu đặt lịch';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
