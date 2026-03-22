# 🏥 HỆ THỐNG QUẢN LÝ PHÒNG KHÁM

## 📋 MÔ TẢ DỰ ÁN

Hệ thống quản lý phòng khám hoàn chỉnh được xây dựng bằng PHP thuần, MySQL, HTML, CSS, JavaScript với đầy đủ 20 bảng dữ liệu và các tính năng quản lý chuyên nghiệp.

## 🎯 TÍNH NĂNG CHÍNH

### ✅ Đã triển khai 100%

1. **Quản lý người dùng & Phân quyền (RBAC)**
   - 5 vai trò: Admin, Doctor, Receptionist, Pharmacist, Cashier
   - 30+ quyền hạn chi tiết
   - Kiểm tra quyền trên mọi chức năng

2. **Quản lý bệnh nhân**
   - CRUD đầy đủ
   - Tìm kiếm theo mã, tên, SĐT
   - Lưu thông tin chi tiết (dị ứng, nhóm máu...)

3. **Quản lý lịch hẹn**
   - Đặt lịch với bác sĩ
   - Kiểm tra ca làm việc tự động
   - Cập nhật trạng thái (scheduled → confirmed → completed)

4. **Quản lý bệnh án**
   - Ghi nhận triệu chứng, chẩn đoán
   - Kế hoạch điều trị
   - Liên kết với lịch hẹn

5. **Quản lý đơn thuốc**
   - 1 bệnh án → 1 đơn thuốc (ràng buộc UNIQUE)
   - Chi tiết: thuốc, liều lượng, hướng dẫn

6. **Quản lý kho thuốc**
   - Theo dõi tồn kho
   - Cảnh báo thuốc sắp hết
   - HSD, nhà sản xuất

7. **Quản lý dịch vụ**
   - 10 dịch vụ mẫu sẵn
   - CRUD dịch vụ khám

8. **Quản lý hóa đơn**
   - **Tính tổng tiền TỰ ĐỘNG**: Phí khám + Dịch vụ + Thuốc - Giảm giá
   - Trigger tự động khi thêm/xóa dịch vụ, thuốc
   - Trạng thái: pending → partial → paid

9. **Quản lý thanh toán**
   - Thanh toán nhiều lần (partial payment)
   - Cập nhật trạng thái hóa đơn tự động
   - Phương thức: tiền mặt, thẻ, chuyển khoản

10. **Tự động trừ thuốc**
    - Trigger `after_invoice_paid`
    - Khi hóa đơn = "paid" → trừ thuốc từ kho

11. **Nhập thuốc**
    - Tạo phiếu nhập
    - Trigger tự động cộng vào kho
    - Theo dõi nhà cung cấp, HSD

12. **Lịch sử khám bệnh**
    - Trigger chuyển tự động khi appointment completed
    - Lưu trữ lịch sử khám đầy đủ

13. **Audit Log**
    - Ghi lại mọi thao tác: Create, Update, Delete
    - Ghi log thanh toán, nhập kho
    - IP address, user, thời gian

## 🗃️ CẤU TRÚC DATABASE (20 BẢNG)

```
1. roles                    - Vai trò
2. permissions              - Quyền hạn
3. role_permissions         - Phân quyền cho vai trò
4. users                    - Người dùng
5. patients                 - Bệnh nhân
6. doctors                  - Bác sĩ
7. doctor_schedules         - Lịch làm việc bác sĩ
8. services                 - Dịch vụ
9. medicines                - Thuốc
10. appointments            - Lịch hẹn
11. medical_records         - Bệnh án
12. prescriptions           - Đơn thuốc
13. prescription_details    - Chi tiết đơn thuốc
14. invoices                - Hóa đơn
15. invoice_services        - Dịch vụ trong hóa đơn
16. invoice_medicines       - Thuốc trong hóa đơn
17. payments                - Thanh toán
18. medicine_imports        - Phiếu nhập thuốc
19. medicine_import_details - Chi tiết nhập thuốc
20. appointment_history     - Lịch sử khám
21. audit_logs              - Nhật ký hệ thống
```

## 🔧 TRIGGERS (8 TRIGGERS)

1. `after_invoice_service_insert` - Cập nhật tổng tiền khi thêm dịch vụ
2. `after_invoice_service_delete` - Cập nhật tổng tiền khi xóa dịch vụ
3. `after_invoice_medicine_insert` - Cập nhật tổng tiền khi thêm thuốc
4. `after_invoice_medicine_delete` - Cập nhật tổng tiền khi xóa thuốc
5. `after_payment_insert` - Cập nhật số tiền đã trả và trạng thái hóa đơn
6. `after_invoice_paid` - **Trừ thuốc từ kho khi hóa đơn paid**
7. `after_medicine_import_detail_insert` - **Cộng thuốc vào kho khi nhập**
8. `after_appointment_completed` - Chuyển lịch hẹn vào lịch sử

## 📂 CẤU TRÚC THƯ MỤC

```
clinic_system/
├── config/
│   ├── database.php        - Kết nối database
│   └── auth.php           - Xác thực & phân quyền
├── includes/
│   ├── header.php         - Header
│   └── sidebar.php        - Sidebar menu
├── assets/
│   └── css/
│       └── style.css      - CSS toàn hệ thống
├── database/
│   └── schema.sql         - SQL script đầy đủ
├── login.php              - Đăng nhập
├── logout.php             - Đăng xuất
├── index.php              - Trang chủ
├── users.php              - Quản lý người dùng
├── patients.php           - Quản lý bệnh nhân
├── appointments.php       - Quản lý lịch hẹn
├── medical_records.php    - Quản lý bệnh án
├── prescriptions.php      - Quản lý đơn thuốc
├── medicines.php          - Quản lý kho thuốc
├── services.php           - Quản lý dịch vụ
├── invoices.php           - Quản lý hóa đơn
├── payments.php           - Quản lý thanh toán
├── medicine_imports.php   - Nhập thuốc
├── appointment_history.php - Lịch sử khám
└── README.md              - Hướng dẫn
```

## 🚀 HƯỚNG DẪN CÀI ĐẶT

### Bước 1: Cài đặt môi trường
- XAMPP/WAMP/LAMP (PHP 7.4+, MySQL 5.7+)
- Web server: Apache

### Bước 2: Tạo database
```bash
1. Mở phpMyAdmin
2. Tạo database mới: clinic_system
3. Import file: database/schema.sql
```

### Bước 3: Cấu hình kết nối
Mở file `config/database.php` và cập nhật:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Mật khẩu MySQL của bạn
define('DB_NAME', 'clinic_system');
```

### Bước 4: Chạy hệ thống
```
1. Copy thư mục clinic_system vào htdocs (XAMPP)
2. Truy cập: http://localhost/clinic_system/
3. Đăng nhập bằng tài khoản demo
```

## 🔑 TÀI KHOẢN DEMO

| Vai trò | Username | Password | Quyền hạn |
|---------|----------|----------|-----------|
| Admin | admin | 123456 | Toàn quyền |
| Bác sĩ | doctor1 | 123456 | Khám bệnh, kê đơn |
| Lễ tân | receptionist1 | 123456 | Đặt lịch, quản lý BN |
| Dược sĩ | pharmacist1 | 123456 | Quản lý kho, nhập thuốc |
| Thu ngân | cashier1 | 123456 | Hóa đơn, thanh toán |

## 🎨 GIAO DIỆN

- Responsive design
- Modern UI với gradient colors
- Dashboard với thống kê realtime
- Modal popup cho form
- Table với hover effects
- Alert notifications

## 🔐 BẢO MẬT

- Password hash bằng MD5
- Session-based authentication
- Role-based access control (RBAC)
- Permission checking trên mọi action
- SQL injection prevention (PDO Prepared Statements)
- XSS protection (htmlspecialchars)
- Audit log đầy đủ

## 📊 LUỒNG HOẠT ĐỘNG

### Luồng khám bệnh cơ bản:
```
1. Lễ tân tạo hồ sơ bệnh nhân
2. Đặt lịch hẹn với bác sĩ (kiểm tra ca làm việc)
3. Bác sĩ khám và tạo bệnh án (triệu chứng, chẩn đoán)
4. Bác sĩ kê đơn thuốc (1 bệnh án = 1 đơn thuốc)
5. Thu ngân tạo hóa đơn (phí khám + dịch vụ + thuốc)
6. Thu ngân nhận thanh toán (có thể trả nhiều lần)
7. Khi thanh toán đủ → thuốc tự động trừ khỏi kho
8. Lịch hẹn completed → chuyển vào lịch sử
```

## 📈 DỮ LIỆU MẪU

- 5 users (mỗi vai trò 1 user)
- 2 bác sĩ với lịch làm việc
- 10 dịch vụ khám
- 20 loại thuốc
- 5 bệnh nhân mẫu

## ⚡ TÍNH NĂNG NỔI BẬT

### 1. Tính tiền tự động
```sql
Tổng tiền = consultation_fee + service_total + medicine_total - discount
```
Trigger tự động cập nhật khi thêm/xóa dịch vụ hoặc thuốc

### 2. Quản lý tồn kho thông minh
- Cảnh báo thuốc sắp hết (stock <= min_stock_level)
- Tự động cộng khi nhập kho (trigger)
- Tự động trừ khi thanh toán (trigger)

### 3. Kiểm tra ca làm việc
Chỉ đặt lịch trong khung giờ bác sĩ làm việc:
```php
$dayOfWeek = date('l', strtotime($appointment_date));
// Kiểm tra doctor_schedules
```

### 4. Phân quyền chi tiết
```
- user.view, user.create, user.edit, user.delete
- patient.*, appointment.*, medical_record.*
- prescription.*, medicine.*, invoice.*
- payment.*, service.*, report.view
```

## 🛠️ CÔNG NGHỆ

- **Backend**: PHP thuần (không framework)
- **Database**: MySQL với PDO
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Architecture**: MVC-like structure
- **Security**: RBAC, PDO Prepared Statements

## 📝 GHI CHÚ

- Mật khẩu demo đều là: `123456` (MD5 hash)
- Tất cả trigger đã được test và hoạt động
- Code có comments đầy đủ
- Tuân thủ 100% yêu cầu đặc tả

## 🐛 TROUBLESHOOTING

### Lỗi kết nối database:
```
- Kiểm tra MySQL đã chạy chưa
- Kiểm tra username/password trong config/database.php
```

### Lỗi 403 Forbidden:
```
- Kiểm tra quyền của user đang đăng nhập
- Đảm bảo role_permissions đã được import đúng
```

### Trigger không hoạt động:
```sql
-- Kiểm tra trigger
SHOW TRIGGERS;

-- Nếu thiếu, import lại schema.sql
```

## 📞 HỖ TRỢ

Hệ thống được xây dựng hoàn chỉnh theo đúng 100% yêu cầu đặc tả:
- ✅ 20 bảng database
- ✅ 8 triggers
- ✅ RBAC hoàn chỉnh
- ✅ Tính tiền tự động
- ✅ Quản lý kho tự động
- ✅ Audit log đầy đủ
- ✅ Form validation
- ✅ Responsive design

---

**Phiên bản:** 1.0.0  
**Ngày phát hành:** 2025  
**Giấy phép:** Free to use
