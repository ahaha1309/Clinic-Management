# 🏥 WEBSITE KHÁCH HÀNG - PHÒNG KHÁM ĐA KHOA

## 📋 TỔNG QUAN

Website dành cho khách hàng để:
- ✅ Giới thiệu phòng khám, dịch vụ, bác sĩ
- ✅ Đặt lịch khám online
- ✅ Liên kết trực tiếp với hệ thống quản lý phòng khám
- ✅ Dữ liệu đồng bộ 100% với admin

---

## 📁 CẤU TRÚC THƯ MỤC

```
customer_portal/
├── index.php              # Trang chủ
├── services.php           # Trang dịch vụ (lấy từ DB)
├── doctors.php            # Trang bác sĩ (lấy từ DB)
├── facilities.php         # Trang cơ sở vật chất
├── booking.php            # Form đặt lịch khám ⭐
├── config/
│   └── database.php       # Kết nối DB (dùng chung với admin)
├── api/
│   ├── create_appointment.php    # API tạo lịch hẹn ⭐
│   └── get_doctor_schedule.php   # API lấy lịch bác sĩ
├── includes/
│   ├── header.php         # Header chung
│   └── footer.php         # Footer chung
├── css/
│   └── style.css          # CSS responsive đẹp
├── js/
│   └── main.js            # JavaScript xử lý
└── images/                # Thư mục chứa hình ảnh
```

---

## 🚀 HƯỚNG DẪN CÀI ĐẶT

### **Bước 1: Upload file**

Upload toàn bộ thư mục `customer_portal` vào server:

```
/var/www/html/
├── clinic_system/          # Hệ thống quản lý (admin)
└── customer_portal/        # Website khách hàng (mới)
```

**Hoặc đặt trong subdomain:**
```
admin.clinic.vn  → /var/www/html/clinic_system
www.clinic.vn    → /var/www/html/customer_portal
```

---

### **Bước 2: Cấu hình Database**

Website khách hàng **dùng chung database** với hệ thống quản lý.

**Kiểm tra file:** `customer_portal/config/database.php`

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clinic_system');  // ← Cùng DB với admin
```

**Lưu ý:** 
- ✅ KHÔNG CẦN tạo database mới
- ✅ Dùng chung bảng: `patients`, `doctors`, `appointments`, `services`
- ✅ Dữ liệu tự động đồng bộ

---

### **Bước 3: Cấu hình quyền file**

```bash
cd /var/www/html/customer_portal

# Cấp quyền thực thi cho PHP
chmod 755 *.php
chmod 755 api/*.php
chmod 755 config/*.php
chmod 755 includes/*.php

# Cấp quyền đọc cho CSS/JS
chmod 644 css/*.css
chmod 644 js/*.js
```

---

### **Bước 4: Kiểm tra hoạt động**

1. **Mở trình duyệt:** `http://localhost/customer_portal/`
2. **Kiểm tra:**
   - ✅ Trang chủ hiển thị đúng
   - ✅ Trang dịch vụ load dữ liệu từ DB
   - ✅ Trang bác sĩ hiển thị danh sách
   - ✅ Form đặt lịch hoạt động

3. **Test đặt lịch:**
   - Vào `booking.php`
   - Điền đầy đủ thông tin
   - Click "Gửi yêu cầu"
   - **Kết quả:** Hiển thị thông báo thành công

4. **Kiểm tra trong admin:**
   - Vào `admin/appointments.php`
   - **Kết quả:** Lịch hẹn mới xuất hiện với status `pending`
   - Ghi chú có dòng: "Đặt lịch từ website khách hàng"

---

## 🎯 LUỒNG HOẠT ĐỘNG

### **Khi khách hàng đặt lịch:**

```
1. Khách điền form (booking.php)
   ↓
2. JavaScript validate dữ liệu
   ↓
3. Gửi AJAX đến api/create_appointment.php
   ↓
4. API xử lý:
   - Kiểm tra bệnh nhân (theo SĐT)
   - Nếu mới → Tạo bản ghi trong bảng patients
   - Nếu cũ → Cập nhật thông tin
   - Tạo lịch hẹn trong bảng appointments
   - Status: pending
   - Notes: "Đặt lịch từ website khách hàng"
   ↓
5. Trả về kết quả (JSON)
   ↓
6. Hiển thị thông báo cho khách
```

### **Trong hệ thống admin:**

```
Nhân viên vào appointments.php
   ↓
Thấy lịch hẹn mới (status: pending)
   ↓
Gọi điện xác nhận khách hàng
   ↓
Cập nhật status → confirmed
   ↓
Sắp xếp bác sĩ (nếu chưa có)
```

---

## 📊 MAPPING DỮ LIỆU

| Trường trong form khách hàng | Lưu vào bảng | Cột |
|------------------------------|--------------|-----|
| Họ tên | `patients` | `full_name` |
| Ngày sinh | `patients` | `date_of_birth` |
| Giới tính | `patients` | `gender` |
| Số điện thoại | `patients` | `phone` |
| Email | `patients` | `email` |
| Dịch vụ | Lưu vào notes | `appointments.notes` |
| Bác sĩ | `appointments` | `doctor_id` |
| Ngày khám | `appointments` | `appointment_date` |
| Giờ khám | `appointments` | `appointment_time` |
| Triệu chứng | `appointments` | `reason` |

**Trạng thái mặc định:**
- `appointments.status` = `pending`
- `appointments.notes` = "Đặt lịch từ website khách hàng"

---

## 🔧 TÙY CHỈNH

### **1. Thay đổi thông tin phòng khám**

**File cần sửa:** `includes/footer.php`

```php
<li><i class="fas fa-map-marker-alt"></i> 123 Nguyễn Huệ, Q1, TP.HCM</li>
<li><i class="fas fa-phone"></i> 1900-xxxx</li>
<li><i class="fas fa-envelope"></i> contact@clinic.vn</li>
```

**File:** `index.php`, `booking.php` (nhiều chỗ)

Tìm và thay đổi:
- Địa chỉ
- Số điện thoại
- Email
- Giờ làm việc

---

### **2. Thay đổi màu sắc**

**File:** `css/style.css`

```css
:root {
    --primary-color: #667eea;      /* Màu chính */
    --primary-dark: #764ba2;       /* Màu tối */
    --secondary-color: #00d4ff;    /* Màu phụ */
}
```

---

### **3. Thêm logo phòng khám**

**Bước 1:** Upload logo vào `images/logo.png`

**Bước 2:** Sửa file `includes/header.php`

```php
<a href="index.php" class="logo">
    <img src="images/logo.png" alt="Logo" style="height: 40px;">
    <span>Phòng khám Đa khoa</span>
</a>
```

---

### **4. Thêm hình ảnh phòng khám**

**File:** `facilities.php`

Tìm:
```php
<div class="facility-image">
    <i class="fas fa-door-open"></i>
</div>
```

Thay bằng:
```php
<div class="facility-image" style="background-image: url('images/phong-kham-1.jpg'); background-size: cover;">
</div>
```

---

## ⚙️ TÍNH NĂNG NÂNG CAO (Tùy chọn)

### **1. Gửi email xác nhận sau khi đặt lịch**

Thêm vào `api/create_appointment.php` (sau khi tạo lịch thành công):

```php
// Gửi email cho khách hàng
if ($email) {
    $subject = "Xác nhận đặt lịch khám - Mã: " . $appointment_code;
    $message = "Kính chào $full_name,\n\n";
    $message .= "Cảm ơn bạn đã đặt lịch khám tại phòng khám.\n";
    $message .= "Mã lịch hẹn: $appointment_code\n";
    $message .= "Ngày khám: " . date('d/m/Y', strtotime($appointment_date)) . "\n";
    $message .= "Giờ khám: " . date('H:i', strtotime($appointment_time)) . "\n\n";
    $message .= "Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.\n";
    $message .= "Trân trọng,\nPhòng khám Đa khoa";
    
    mail($email, $subject, $message);
}
```

---

### **2. SMS thông báo**

Tích hợp API SMS (ví dụ: Twilio, Esms.vn)

---

### **3. Thanh toán online**

Tích hợp cổng thanh toán:
- VNPay
- MoMo
- ZaloPay

---

## 🐛 XỬ LÝ LỖI THƯỜNG GẶP

### ❌ **Lỗi: "Connection failed"**

**Nguyên nhân:** Sai thông tin database

**Giải pháp:**
1. Kiểm tra `config/database.php`
2. Đảm bảo DB_NAME đúng với database admin
3. Kiểm tra user/password MySQL

---

### ❌ **Lỗi: "Không thể tạo lịch hẹn"**

**Nguyên nhân:** Thiếu bảng hoặc cột trong database

**Giải pháp:**
```sql
-- Kiểm tra bảng
SHOW TABLES LIKE 'appointments';
SHOW TABLES LIKE 'patients';

-- Kiểm tra cấu trúc
DESC appointments;
DESC patients;
```

---

### ❌ **Lỗi: Không hiển thị dịch vụ/bác sĩ**

**Nguyên nhân:** Không có dữ liệu trong DB hoặc `is_active = 0`

**Giải pháp:**
```sql
-- Kiểm tra dịch vụ
SELECT * FROM services WHERE is_active = 1;

-- Kiểm tra bác sĩ
SELECT d.*, u.* 
FROM doctors d 
JOIN users u ON d.user_id = u.id 
WHERE u.is_active = 1;
```

---

### ❌ **Lỗi: AJAX không hoạt động**

**Nguyên nhân:** Đường dẫn API sai

**Giải pháp:**
1. Kiểm tra file `api/create_appointment.php` có tồn tại
2. Kiểm tra quyền file: `chmod 755 api/*.php`
3. Mở Developer Tools → Network → Xem lỗi

---

## 📱 RESPONSIVE

Website tự động responsive trên:
- ✅ Desktop (>1200px)
- ✅ Tablet (768px - 1200px)
- ✅ Mobile (<768px)

**Test responsive:**
```
Chrome DevTools → Ctrl+Shift+M
```

---

## 🔒 BẢO MẬT

### **Đã tích hợp:**
- ✅ PDO Prepared Statements (chống SQL Injection)
- ✅ Validate input (phone, email, date)
- ✅ Sanitize output (htmlspecialchars)
- ✅ CSRF protection (có thể thêm)

### **Khuyến nghị thêm:**

1. **HTTPS:** Bắt buộc dùng SSL
   ```bash
   sudo certbot --apache -d clinic.vn
   ```

2. **Rate Limiting:** Giới hạn số lần gửi form
3. **ReCAPTCHA:** Chống bot spam

---

## 📞 HỖ TRỢ

Nếu gặp vấn đề:

1. ✅ Kiểm tra log lỗi PHP: `/var/log/apache2/error.log`
2. ✅ Kiểm tra console browser (F12)
3. ✅ Test API trực tiếp: `api/create_appointment.php`

---

## ✨ TÍNH NĂNG HIỆN CÓ

✅ Trang chủ giới thiệu  
✅ Trang dịch vụ (lấy từ DB)  
✅ Trang bác sĩ (lấy từ DB)  
✅ Trang cơ sở vật chất  
✅ Form đặt lịch hoàn chỉnh  
✅ Validation form  
✅ AJAX submit  
✅ Tự động tạo/cập nhật bệnh nhân  
✅ Tự động tạo lịch hẹn  
✅ Hiển thị lịch làm việc bác sĩ  
✅ Responsive mobile  
✅ Animations mượt mà  
✅ SEO friendly  

---

## 🎯 ROADMAP (Tương lai)

- [ ] Tích hợp thanh toán online
- [ ] Gửi email/SMS tự động
- [ ] Tra cứu lịch hẹn (cho khách)
- [ ] Đánh giá sau khám
- [ ] Chat online
- [ ] Đa ngôn ngữ

---

**Phiên bản:** 1.0.0  
**Ngày tạo:** 2025-02-06  
**Tác giả:** Claude AI Assistant  
**License:** Free to use  

---

## 🎉 HOÀN TẤT!

Website khách hàng đã sẵn sàng sử dụng! 🚀

Hãy test kỹ và tùy chỉnh theo nhu cầu của phòng khám. Chúc thành công! 💪
