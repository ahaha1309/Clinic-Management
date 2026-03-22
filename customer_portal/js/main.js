// =====================================================
// CUSTOMER PORTAL - MAIN JAVASCRIPT
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Mobile Menu Toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.navbar')) {
                navMenu.classList.remove('active');
            }
        });
    }
    
    // Smooth Scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Form Validation for Booking
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc3545';
                } else {
                    field.style.borderColor = '#e0e0e0';
                }
            });
            
            // Validate phone number
            const phone = document.getElementById('phone');
            if (phone && phone.value) {
                const phonePattern = /^[0-9]{10,11}$/;
                if (!phonePattern.test(phone.value.replace(/\s/g, ''))) {
                    isValid = false;
                    phone.style.borderColor = '#dc3545';
                    alert('Số điện thoại không hợp lệ. Vui lòng nhập 10-11 số.');
                }
            }
            
            // Validate email if provided
            const email = document.getElementById('email');
            if (email && email.value) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(email.value)) {
                    isValid = false;
                    email.style.borderColor = '#dc3545';
                    alert('Email không hợp lệ.');
                }
            }
            
            // Validate consent checkbox
            const consent = document.getElementById('consent');
            if (consent && !consent.checked) {
                isValid = false;
                alert('Vui lòng đồng ý cho phép phòng khám xử lý thông tin cá nhân.');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Clear error styling on input
        const inputs = bookingForm.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.style.borderColor = '#e0e0e0';
            });
        });
    }
    
    // Load doctor schedule when doctor is selected
    const doctorSelect = document.getElementById('doctor_id');
    if (doctorSelect) {
        doctorSelect.addEventListener('change', function() {
            const scheduleInfo = document.getElementById('scheduleInfo');
            if (this.value && scheduleInfo) {
                // Show loading
                scheduleInfo.innerHTML = '<p style="color: #666;"><i class="fas fa-spinner fa-spin"></i> Đang tải lịch làm việc...</p>';
                scheduleInfo.style.display = 'block';
                
                // Fetch doctor schedule via AJAX
                fetch(`api/get_doctor_schedule.php?doctor_id=${this.value}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.schedules.length > 0) {
                            let html = '<p style="color: #667eea; font-weight: 600; margin-bottom: 10px;"><i class="fas fa-calendar-check"></i> Lịch làm việc của bác sĩ:</p><div style="display: flex; flex-wrap: wrap; gap: 10px;">';
                            
                            const dayNames = {
                                'Monday': 'Thứ 2',
                                'Tuesday': 'Thứ 3',
                                'Wednesday': 'Thứ 4',
                                'Thursday': 'Thứ 5',
                                'Friday': 'Thứ 6',
                                'Saturday': 'Thứ 7',
                                'Sunday': 'Chủ nhật'
                            };
                            
                            data.schedules.forEach(schedule => {
                                html += `<span style="background: #667eea; color: white; padding: 8px 15px; border-radius: 20px; font-size: 0.9rem;">${dayNames[schedule.day_of_week]}: ${schedule.start_time.substring(0,5)} - ${schedule.end_time.substring(0,5)}</span>`;
                            });
                            
                            html += '</div>';
                            scheduleInfo.innerHTML = html;
                        } else {
                            scheduleInfo.innerHTML = '<p style="color: #dc3545;"><i class="fas fa-exclamation-circle"></i> Bác sĩ chưa có lịch làm việc cố định. Phòng khám sẽ sắp xếp phù hợp.</p>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        scheduleInfo.innerHTML = '<p style="color: #666;">Không thể tải lịch làm việc.</p>';
                    });
            } else if (scheduleInfo) {
                scheduleInfo.style.display = 'none';
            }
        });
    }
    
    // Animation on scroll
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.feature-card, .service-card, .doctor-card, .facility-card');
        
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementTop < windowHeight - 100) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };
    
    // Initial state for animation
    document.querySelectorAll('.feature-card, .service-card, .doctor-card, .facility-card').forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'all 0.6s ease';
    });
    
    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll(); // Run once on load
    
});
