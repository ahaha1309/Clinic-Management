-- =====================================================
-- HỆ THỐNG QUẢN LÝ PHÒNG KHÁM - DATABASE SCHEMA
-- Tổng cộng: 20 bảng + Triggers + Sample Data
-- =====================================================

-- Xóa database cũ nếu có
DROP DATABASE IF EXISTS clinic_system;
CREATE DATABASE clinic_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinic_system;

-- =====================================================
-- 1. BẢNG ROLES - Vai trò người dùng
-- =====================================================
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 2. BẢNG PERMISSIONS - Quyền hạn
-- =====================================================
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_code VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 3. BẢNG ROLE_PERMISSIONS - Phân quyền cho vai trò
-- =====================================================
CREATE TABLE role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 4. BẢNG USERS - Người dùng hệ thống
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    full_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    avatar VARCHAR(255) Null,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- =====================================================
-- 5. BẢNG PATIENTS - Bệnh nhân
-- =====================================================
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_code VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    gender ENUM('Nam', 'Nữ', 'Khác') NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    identity_card VARCHAR(20),
    blood_group VARCHAR(5),
    allergies TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 6. BẢNG DOCTORS - Bác sĩ
-- =====================================================
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    doctor_code VARCHAR(20) NOT NULL UNIQUE,
    specialization VARCHAR(100),
    qualification TEXT,
    experience_years INT,
    consultation_fee DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 7. BẢNG DOCTOR_SCHEDULES - Lịch làm việc bác sĩ
-- =====================================================
CREATE TABLE doctor_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 8. BẢNG SERVICES - Dịch vụ khám
-- =====================================================
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_code VARCHAR(20) NOT NULL UNIQUE,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 9. BẢNG MEDICINES - Thuốc
-- =====================================================
CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_code VARCHAR(20) NOT NULL UNIQUE,
    medicine_name VARCHAR(100) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 10,
    expiry_date DATE,
    manufacturer VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 10. BẢNG APPOINTMENTS - Lịch hẹn khám
-- =====================================================
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_code VARCHAR(20) NOT NULL UNIQUE,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled') DEFAULT 'scheduled',
    reason TEXT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- =====================================================
-- 11. BẢNG MEDICAL_RECORDS - Bệnh án
-- =====================================================
CREATE TABLE medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_code VARCHAR(20) NOT NULL UNIQUE,
    appointment_id INT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    symptoms TEXT,
    diagnosis TEXT,
    treatment_plan TEXT,
    notes TEXT,
    record_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
) ENGINE=InnoDB;

-- =====================================================
-- 12. BẢNG PRESCRIPTIONS - Đơn thuốc
-- =====================================================
CREATE TABLE prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_code VARCHAR(20) NOT NULL UNIQUE,
    medical_record_id INT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    prescription_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    UNIQUE KEY unique_medical_record (medical_record_id)
) ENGINE=InnoDB;

-- =====================================================
-- 13. BẢNG PRESCRIPTION_DETAILS - Chi tiết đơn thuốc
-- =====================================================
CREATE TABLE prescription_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    dosage VARCHAR(100),
    instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
) ENGINE=InnoDB;

-- =====================================================
-- 14. BẢNG INVOICES - Hóa đơn
-- =====================================================
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_code VARCHAR(20) NOT NULL UNIQUE,
    patient_id INT NOT NULL,
    appointment_id INT,
    medical_record_id INT,
    consultation_fee DECIMAL(10,2) DEFAULT 0,
    service_total DECIMAL(10,2) DEFAULT 0,
    medicine_total DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'partial', 'paid', 'cancelled') DEFAULT 'pending',
    invoice_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- =====================================================
-- 15. BẢNG INVOICE_SERVICES - Dịch vụ trong hóa đơn
-- =====================================================
CREATE TABLE invoice_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    service_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB;

-- =====================================================
-- 16. BẢNG INVOICE_MEDICINES - Thuốc trong hóa đơn
-- =====================================================
CREATE TABLE invoice_medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
) ENGINE=InnoDB;

-- =====================================================
-- 17. BẢNG PAYMENTS - Thanh toán
-- =====================================================
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_code VARCHAR(20) NOT NULL UNIQUE,
    invoice_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'transfer', 'other') DEFAULT 'cash',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- =====================================================
-- 18. BẢNG MEDICINE_IMPORTS - Phiếu nhập thuốc
-- =====================================================
CREATE TABLE medicine_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_code VARCHAR(20) NOT NULL UNIQUE,
    supplier_name VARCHAR(100) NOT NULL,
    import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- =====================================================
-- 19. BẢNG MEDICINE_IMPORT_DETAILS - Chi tiết nhập thuốc
-- =====================================================
CREATE TABLE medicine_import_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    import_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    expiry_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (import_id) REFERENCES medicine_imports(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
) ENGINE=InnoDB;

-- =====================================================
-- 20. BẢNG APPOINTMENT_HISTORY - Lịch sử khám bệnh
-- =====================================================
CREATE TABLE appointment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    diagnosis TEXT,
    treatment TEXT,
    prescription_summary TEXT,
    total_cost DECIMAL(10,2),
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
) ENGINE=InnoDB;

-- =====================================================
-- 21. BẢNG AUDIT_LOGS - Nhật ký hệ thống
-- =====================================================
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    old_data TEXT,
    new_data TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Bảng quản lý bệnh nhân của bác sĩ
-- =====================================================
CREATE TABLE doctor_patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT,
    patient_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    UNIQUE KEY unique_relation (doctor_id, patient_id)
)ENGINE=InnoDB;
-- =====================================================
-- Bảng yêu cầu đặt lịch hẹn
-- =====================================================
CREATE TABLE appointment_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,

    request_code VARCHAR(20) NOT NULL UNIQUE,

    full_name VARCHAR(150) NOT NULL,
    date_of_birth DATE NULL,
    gender ENUM('male','female','other') NOT NULL,

    phone VARCHAR(15) NOT NULL,
    email VARCHAR(150) NULL,

    service_id INT NOT NULL,
    doctor_id INT NULL,

    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,

    reason TEXT NULL,

    status ENUM('pending','approved','rejected') 
        DEFAULT 'pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        ON UPDATE CURRENT_TIMESTAMP,

    -- Index giúp truy vấn nhanh hơn
    INDEX idx_phone (phone),
    INDEX idx_status (status),
    INDEX idx_appointment_date (appointment_date),

    -- Khóa ngoại
    CONSTRAINT fk_request_service 
        FOREIGN KEY (service_id) 
        REFERENCES services(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_request_doctor 
        FOREIGN KEY (doctor_id) 
        REFERENCES doctors(id)
        ON DELETE SET NULL
)ENGINE=InnoDB;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger 1: Tự động cập nhật tổng tiền hóa đơn khi thêm dịch vụ
DELIMITER //
CREATE TRIGGER after_invoice_service_insert
AFTER INSERT ON invoice_services
FOR EACH ROW
BEGIN
    UPDATE invoices 
    SET service_total = (SELECT IFNULL(SUM(total), 0) FROM invoice_services WHERE invoice_id = NEW.invoice_id),
        total_amount = (consultation_fee + (SELECT IFNULL(SUM(total), 0) FROM invoice_services WHERE invoice_id = NEW.invoice_id) + medicine_total)*(100 - discount)/100
    WHERE id = NEW.invoice_id;
END//
DELIMITER ;

-- Trigger 2: Tự động cập nhật tổng tiền hóa đơn khi xóa dịch vụ
DELIMITER //
CREATE TRIGGER after_invoice_service_delete
AFTER DELETE ON invoice_services
FOR EACH ROW
BEGIN
    UPDATE invoices 
    SET service_total = (SELECT IFNULL(SUM(total), 0) FROM invoice_services WHERE invoice_id = OLD.invoice_id),
        total_amount = (consultation_fee + (SELECT IFNULL(SUM(total), 0) FROM invoice_services WHERE invoice_id = OLD.invoice_id) + medicine_total)*(100 - discount)/100
    WHERE id = OLD.invoice_id;
END//
DELIMITER ;

-- Trigger 3: Tự động cập nhật tổng tiền hóa đơn khi thêm thuốc
DELIMITER //
CREATE TRIGGER after_invoice_medicine_insert
AFTER INSERT ON invoice_medicines
FOR EACH ROW
BEGIN
    UPDATE invoices 
    SET medicine_total = (SELECT IFNULL(SUM(total), 0) FROM invoice_medicines WHERE invoice_id = NEW.invoice_id),
        total_amount = (consultation_fee + service_total + (SELECT IFNULL(SUM(total), 0) FROM invoice_medicines WHERE invoice_id = NEW.invoice_id))*(100 - discount)/100
    WHERE id = NEW.invoice_id;
END//
DELIMITER ;

-- Trigger 4: Tự động cập nhật tổng tiền hóa đơn khi xóa thuốc
DELIMITER //
CREATE TRIGGER after_invoice_medicine_delete
AFTER DELETE ON invoice_medicines
FOR EACH ROW
BEGIN
    UPDATE invoices 
    SET medicine_total = (SELECT IFNULL(SUM(total), 0) FROM invoice_medicines WHERE invoice_id = OLD.invoice_id),
        total_amount = (consultation_fee + service_total + (SELECT IFNULL(SUM(total), 0) FROM invoice_medicines WHERE invoice_id = OLD.invoice_id))*(100 - discount)/100
    WHERE id = OLD.invoice_id;
END//
DELIMITER ;

-- Trigger 5: Cập nhật số tiền đã thanh toán và trạng thái hóa đơn
DELIMITER //
CREATE TRIGGER after_payment_insert
AFTER INSERT ON payments
FOR EACH ROW
BEGIN
    DECLARE v_total DECIMAL(10,2);
    DECLARE v_paid DECIMAL(10,2);
    
    SELECT total_amount INTO v_total FROM invoices WHERE id = NEW.invoice_id;
    SELECT IFNULL(SUM(amount), 0) INTO v_paid FROM payments WHERE invoice_id = NEW.invoice_id;
    
    UPDATE invoices 
    SET paid_amount = v_paid,
        status = CASE 
            WHEN v_paid >= v_total THEN 'paid'
            WHEN v_paid > 0 THEN 'partial'
            ELSE 'pending'
        END
    WHERE id = NEW.invoice_id;
END//
DELIMITER ;

-- Trigger 6: Trừ thuốc từ kho khi hóa đơn được thanh toán đủ
DELIMITER //
CREATE TRIGGER after_invoice_paid
AFTER UPDATE ON invoices
FOR EACH ROW
BEGIN
    IF NEW.status = 'paid' AND OLD.status != 'paid' THEN
        UPDATE medicines m
        INNER JOIN invoice_medicines im ON m.id = im.medicine_id
        SET m.stock_quantity = m.stock_quantity - im.quantity
        WHERE im.invoice_id = NEW.id;
    END IF;
END//
DELIMITER ;

-- Trigger 7: Cộng thuốc vào kho khi nhập thuốc
DELIMITER //
CREATE TRIGGER after_medicine_import_detail_insert
AFTER INSERT ON medicine_import_details
FOR EACH ROW
BEGIN
    UPDATE medicines 
    SET stock_quantity = stock_quantity + NEW.quantity
    WHERE id = NEW.medicine_id;
END//
DELIMITER ;

-- Trigger 8: Chuyển lịch hẹn vào lịch sử khi completed
DELIMITER //
CREATE TRIGGER after_appointment_completed
AFTER UPDATE ON appointments
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        INSERT INTO appointment_history (appointment_id, patient_id, doctor_id, appointment_date, diagnosis, treatment, completed_at)
        SELECT 
            NEW.id,
            NEW.patient_id,
            NEW.doctor_id,
            NEW.appointment_date,
            mr.diagnosis,
            mr.treatment_plan,
            NOW()
        FROM medical_records mr
        WHERE mr.appointment_id = NEW.id
        LIMIT 1;
    END IF;
END//
DELIMITER ;

--Triger 9:Tính lại tổng tiền hóa đơn trước khi insert 
DELIMITER //

CREATE TRIGGER before_invoice_insert_recalc_total
BEFORE INSERT ON invoices
FOR EACH ROW
BEGIN
    SET NEW.total_amount =
        (NEW.consultation_fee + NEW.service_total + NEW.medicine_total)
        * (100 - NEW.discount) / 100;
END//

DELIMITER ;

-- =====================================================
-- DỮ LIỆU MẪU
-- =====================================================

-- Insert Roles
INSERT INTO roles (role_name, description) VALUES
('admin', 'Quản trị viên hệ thống'),
('doctor', 'Bác sĩ'),
('receptionist', 'Lễ tân'),
('pharmacist', 'Dược sĩ'),
('cashier', 'Thu ngân');

-- Insert Permissions
INSERT INTO permissions (permission_code, description) VALUES
('user.view', 'Xem danh sách người dùng'),
('user.create', 'Tạo người dùng mới'),
('user.edit', 'Sửa thông tin người dùng'),
('user.delete', 'Xóa người dùng'),
('patient.view', 'Xem danh sách bệnh nhân'),
('patient.create', 'Tạo hồ sơ bệnh nhân'),
('patient.edit', 'Sửa hồ sơ bệnh nhân'),
('patient.delete', 'Xóa hồ sơ bệnh nhân'),
('appointment.view', 'Xem lịch hẹn'),
('appointment.create', 'Tạo lịch hẹn'),
('appointment.edit', 'Sửa lịch hẹn'),
('appointment.delete', 'Hủy lịch hẹn'),
('medical_record.view', 'Xem bệnh án'),
('medical_record.create', 'Tạo bệnh án'),
('medical_record.edit', 'Sửa bệnh án'),
('prescription.view', 'Xem đơn thuốc'),
('prescription.create', 'Kê đơn thuốc'),
('prescription.edit', 'Sửa đơn thuốc'),
('medicine.view', 'Xem kho thuốc'),
('medicine.create', 'Thêm thuốc'),
('medicine.edit', 'Sửa thông tin thuốc'),
('medicine.import', 'Nhập thuốc'),
('invoice.view', 'Xem hóa đơn'),
('invoice.create', 'Tạo hóa đơn'),
('invoice.edit', 'Sửa hóa đơn'),
('payment.view', 'Xem thanh toán'),
('payment.create', 'Thanh toán'),
('report.view', 'Xem báo cáo'),
('service.view', 'Xem dịch vụ'),
('service.manage', 'Quản lý dịch vụ'),
('appointment_request.view', 'Xem yêu cầu đặt lịch');

-- Phân quyền cho Admin (tất cả)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Phân quyền cho Doctor
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE permission_code IN (
    'patient.view', 'patient.create', 'patient.edit',
    'appointment.view', 'appointment.edit',
    'medical_record.view', 'medical_record.create', 'medical_record.edit',
    'prescription.view', 'prescription.create', 'prescription.edit',
    'invoice.view', 'service.view'
);

-- Phân quyền cho Receptionist
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE permission_code IN (
    'patient.view', 'patient.create', 'patient.edit',
    'appointment.view', 'appointment.create', 'appointment.edit', 'appointment.delete','appointment_request.view'
);

-- Phân quyền cho Pharmacist
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE permission_code IN (
    'medicine.view', 'medicine.create', 'medicine.edit', 'medicine.import',
    'prescription.view'
);

-- Phân quyền cho Cashier
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE permission_code IN (
    'invoice.view', 'invoice.create', 'invoice.edit',
    'payment.view', 'payment.create',
    'patient.view'
);

-- Insert Users (password: 123456 - md5 hash)
INSERT INTO users (username, password, role_id, full_name, email, phone) VALUES
('admin', 'e10adc3949ba59abbe56e057f20f883e', 1, 'Quản trị viên', 'admin@clinic.com', '0123456789'),
('doctor1', 'e10adc3949ba59abbe56e057f20f883e', 2, 'Bác sĩ Nguyễn Văn A', 'doctor1@clinic.com', '0123456788'),
('doctor2', 'e10adc3949ba59abbe56e057f20f883e', 2, 'Bác sĩ Trần Thị B', 'doctor2@clinic.com', '0123456787'),
('receptionist1', 'e10adc3949ba59abbe56e057f20f883e', 3, 'Lễ tân Lê Văn C', 'receptionist@clinic.com', '0123456786'),
('pharmacist1', 'e10adc3949ba59abbe56e057f20f883e', 4, 'Dược sĩ Phạm Thị D', 'pharmacist@clinic.com', '0123456785'),
('cashier1', 'e10adc3949ba59abbe56e057f20f883e', 5, 'Thu ngân Hoàng Văn E', 'cashier@clinic.com', '0123456784');

-- Insert Doctors
INSERT INTO doctors (user_id, doctor_code, specialization, qualification, experience_years, consultation_fee) VALUES
(2, 'BS001', 'Nội khoa', 'Bác sĩ Chuyên khoa I', 10, 200000),
(3, 'BS002', 'Nhi khoa', 'Bác sĩ Chuyên khoa II', 8, 250000);

-- Insert Doctor Schedules
INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time) VALUES
(1, 'Monday', '08:00:00', '12:00:00'),
(1, 'Monday', '14:00:00', '17:00:00'),
(1, 'Wednesday', '08:00:00', '12:00:00'),
(1, 'Friday', '08:00:00', '12:00:00'),
(2, 'Tuesday', '08:00:00', '12:00:00'),
(2, 'Thursday', '08:00:00', '12:00:00'),
(2, 'Saturday', '08:00:00', '12:00:00');

-- Insert Services (10 dịch vụ mẫu)
INSERT INTO services (service_code, service_name, description, price) VALUES
('DV001', 'Xét nghiệm máu tổng quát', 'Kiểm tra các chỉ số máu cơ bản', 150000),
('DV002', 'Xét nghiệm nước tiểu', 'Phân tích nước tiểu', 80000),
('DV003', 'Chụp X-quang', 'Chụp X-quang các bộ phận', 200000),
('DV004', 'Siêu âm bụng', 'Siêu âm tổng quát ổ bụng', 300000),
('DV005', 'Điện tâm đồ', 'Đo điện tim', 150000),
('DV006', 'Đo huyết áp', 'Đo và theo dõi huyết áp', 50000),
('DV007', 'Tiêm tĩnh mạch', 'Dịch vụ tiêm truyền', 100000),
('DV008', 'Băng vết thương', 'Chăm sóc và băng bó vết thương', 80000),
('DV009', 'Xét nghiệm đường huyết', 'Kiểm tra chỉ số đường trong máu', 60000),
('DV010', 'Tư vấn dinh dưỡng', 'Tư vấn chế độ ăn uống', 200000);

-- Insert Medicines (20 loại thuốc mẫu)
INSERT INTO medicines (medicine_code, medicine_name, unit, price, stock_quantity, min_stock_level, expiry_date, manufacturer) VALUES
('TH001', 'Paracetamol 500mg', 'Viên', 500, 1000, 100, '2025-12-31', 'Domesco'),
('TH002', 'Amoxicillin 500mg', 'Viên', 1200, 800, 100, '2025-10-30', 'DHG Pharma'),
('TH003', 'Vitamin C 1000mg', 'Viên', 800, 500, 50, '2026-06-30', 'Traphaco'),
('TH004', 'Omeprazole 20mg', 'Viên', 1500, 600, 80, '2025-09-15', 'Hasan'),
('TH005', 'Cetirizine 10mg', 'Viên', 700, 400, 50, '2025-11-20', 'Pymepharco'),
('TH006', 'Metformin 500mg', 'Viên', 900, 700, 100, '2026-03-25', 'Stellapharm'),
('TH007', 'Aspirin 100mg', 'Viên', 600, 900, 100, '2025-08-10', 'Imexpharm'),
('TH008', 'Ibuprofen 400mg', 'Viên', 1000, 600, 80, '2025-12-05', 'Boston'),
('TH009', 'Cảm cúm A-Z', 'Gói', 3000, 300, 50, '2025-07-30', 'Sanofi'),
('TH010', 'Đại tràng Sôi', 'Viên', 2500, 400, 60, '2025-10-15', 'OPC'),
('TH011', 'Tiffy 60ml', 'Chai', 45000, 200, 30, '2025-11-30', 'Mebiphar'),
('TH012', 'Sorbitol 70%', 'Chai', 35000, 150, 20, '2026-01-20', 'Meyer'),
('TH013', 'Gargarin', 'Chai', 28000, 180, 25, '2025-09-28', 'Mekophar'),
('TH014', 'Eugica', 'Viên', 1800, 500, 70, '2025-12-18', 'Pharbaco'),
('TH015', 'Vitamin B1 100mg', 'Viên', 400, 800, 100, '2026-02-14', 'Davipharm'),
('TH016', 'Cao dán Salonpas', 'Miếng', 5000, 600, 80, '2026-04-10', 'Hisamitsu'),
('TH017', 'Tantum Verde', 'Chai', 55000, 120, 20, '2025-08-25', 'Angelini'),
('TH018', 'Gaviscon', 'Gói', 8000, 350, 50, '2025-10-08', 'Reckitt'),
('TH019', 'Decolgen', 'Viên', 2200, 450, 60, '2025-11-12', 'USP'),
('TH020', 'Berberin 100mg', 'Viên', 300, 1200, 150, '2026-05-30', 'Nadyphar');

-- Insert Sample Patients
INSERT INTO patients (patient_code, full_name, date_of_birth, gender, phone, address, blood_group) VALUES
('BN001', 'Nguyễn Văn An', '1985-05-15', 'Nam', '0987654321', '123 Nguyễn Huệ, Q1, TP.HCM', 'O'),
('BN002', 'Trần Thị Bình', '1990-08-22', 'Nữ', '0987654322', '456 Lê Lợi, Q3, TP.HCM', 'A'),
('BN003', 'Lê Văn Cường', '1978-12-10', 'Nam', '0987654323', '789 Trần Hưng Đạo, Q5, TP.HCM', 'B'),
('BN004', 'Phạm Thị Dung', '2000-03-18', 'Nữ', '0987654324', '321 Điện Biên Phủ, Q10, TP.HCM', 'AB'),
('BN005', 'Hoàng Văn Em', '1995-07-25', 'Nam', '0987654325', '654 Nguyễn Thị Minh Khai, Q3, TP.HCM', 'O');

-- =====================================================
-- END OF SCHEMA
-- =====================================================
