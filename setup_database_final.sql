-- ==========================================
-- قاعدة البيانات الكاملة لنظام إدارة العيادة
-- ==========================================
-- Version: 1.0.0
-- Created: 2026-01-03

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ==========================================
-- إنشاء قاعدة البيانات
-- ==========================================
DROP DATABASE IF EXISTS `clinic_system`;
CREATE DATABASE IF NOT EXISTS `clinic_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `clinic_system`;

-- ==========================================
-- 1. جدول المستخدمين
-- ==========================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL UNIQUE,
  `username` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('admin','doctor','staff','patient') DEFAULT 'patient',
  `phone` varchar(20),
  `avatar` varchar(255),
  `is_active` boolean DEFAULT true,
  `last_login` datetime,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 2. جدول المرضى
-- ==========================================
CREATE TABLE IF NOT EXISTS `patients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `phone` varchar(20) NOT NULL,
  `date_of_birth` date,
  `gender` enum('male','female','other') DEFAULT 'other',
  `blood_type` varchar(10),
  `address` text,
  `city` varchar(100),
  `national_id` varchar(50),
  `emergency_contact` varchar(255),
  `allergies` text,
  `chronic_diseases` text,
  `notes` longtext,
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_phone` (`phone`),
  KEY `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 3. جدول الأطباء
-- ==========================================
CREATE TABLE IF NOT EXISTS `doctors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int,
  `name` varchar(255) NOT NULL,
  `email` varchar(255),
  `phone` varchar(20),
  `specialty` varchar(100) NOT NULL,
  `qualification` varchar(255),
  `experience` int DEFAULT 0,
  `license_number` varchar(50) UNIQUE,
  `consultation_fee` decimal(10, 2) DEFAULT 0,
  `bio` longtext,
  `clinic_location` varchar(255),
  `working_hours_start` time,
  `working_hours_end` time,
  `available_days` varchar(50),
  `is_available` boolean DEFAULT true,
  `rating` decimal(3, 2) DEFAULT 0,
  `total_patients` int DEFAULT 0,
  `total_appointments` int DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_specialty` (`specialty`),
  KEY `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 4. جدول المواعيد
-- ==========================================
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `duration_minutes` int DEFAULT 30,
  `status` enum('scheduled','in_progress','completed','cancelled','no_show') DEFAULT 'scheduled',
  `reason` text,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_doctor_id` (`doctor_id`),
  KEY `idx_date` (`date`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_appointment` (`doctor_id`, `date`, `time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 5. جدول السجلات الطبية
-- ==========================================
CREATE TABLE IF NOT EXISTS `medical_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `appointment_id` int,
  `doctor_id` int,
  `symptoms` text NOT NULL,
  `diagnosis` text NOT NULL,
  `treatment_plan` text,
  `prescription` longtext,
  `test_orders` text,
  `follow_up_date` date,
  `notes` longtext,
  `attachment` varchar(255),
  `is_confidential` boolean DEFAULT false,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_doctor_id` (`doctor_id`),
  KEY `idx_appointment_id` (`appointment_id`),
  FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 6. جدول الأدوية
-- ==========================================
CREATE TABLE IF NOT EXISTS `medications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `medical_record_id` int,
  `name` varchar(255) NOT NULL,
  `dosage` varchar(100) NOT NULL,
  `frequency` varchar(100) NOT NULL,
  `duration` varchar(100),
  `instructions` text,
  `quantity` int DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_record_id` (`medical_record_id`),
  FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 7. جدول الفواتير
-- ==========================================
CREATE TABLE IF NOT EXISTS `billing` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `doctor_id` int,
  `appointment_id` int,
  `invoice_number` varchar(50) UNIQUE NOT NULL,
  `amount` decimal(10, 2) NOT NULL,
  `discount` decimal(10, 2) DEFAULT 0,
  `tax` decimal(10, 2) DEFAULT 0,
  `total_amount` decimal(10, 2) NOT NULL,
  `payment_method` enum('cash','credit_card','debit_card','bank_transfer','insurance') DEFAULT 'cash',
  `status` enum('pending','paid','partially_paid','cancelled','refunded') DEFAULT 'pending',
  `paid_amount` decimal(10, 2) DEFAULT 0,
  `due_date` date,
  `payment_date` datetime,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_doctor_id` (`doctor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_invoice_number` (`invoice_number`),
  FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 8. جدول سجل الأنشطة
-- ==========================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int,
  `action` varchar(100) NOT NULL,
  `details` text,
  `resource_type` varchar(50),
  `resource_id` int,
  `ip_address` varchar(45),
  `user_agent` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 9. جدول الإعدادات
-- ==========================================
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL UNIQUE,
  `value` longtext,
  `description` text,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- إدراج البيانات الاختبارية
-- ==========================================

-- المستخدمين (كلمة المرور: password123)
INSERT INTO `users` (`email`, `username`, `password`, `full_name`, `role`, `phone`, `is_active`) VALUES
('admin@clinic.com', 'admin', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLM', 'مدير النظام', 'admin', '0501234567', true),
('doctor@clinic.com', 'dr_ahmed', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLM', 'د. أحمد محمد', 'doctor', '0505555555', true),
('patient@clinic.com', 'patient1', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLM', 'علي أحمد', 'patient', '0509999999', true),
('staff@clinic.com', 'staff1', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLM', 'موظف الاستقبال', 'staff', '0508888888', true);

-- المرضى
INSERT INTO `patients` (`user_id`, `name`, `email`, `phone`, `date_of_birth`, `gender`, `blood_type`, `address`, `city`, `emergency_contact`) VALUES
(3, 'علي أحمد', 'patient@clinic.com', '0509999999', '1990-05-15', 'male', 'O+', 'شارع النيل', 'الرياض', '0501234567'),
(NULL, 'فاطمة محمد', 'fatima@example.com', '0508888888', '1995-08-22', 'female', 'A+', 'طريق الملك فهد', 'جدة', '0505555555'),
(NULL, 'محمود حسن', 'mahmoud@example.com', '0507777777', '1988-12-10', 'male', 'B+', 'حي النخيل', 'الدمام', '0501111111');

-- الأطباء
INSERT INTO `doctors` (`user_id`, `name`, `email`, `phone`, `specialty`, `qualification`, `experience`, `license_number`, `consultation_fee`, `is_available`) VALUES
(2, 'د. أحمد محمد', 'doctor@clinic.com', '0505555555', 'طب عام', 'دكتوراه في الطب', 15, 'LIC001', 150.00, true),
(NULL, 'د. سارة علي', 'sarah@clinic.com', '0506666666', 'أمراض النساء', 'دكتوراه في الطب', 12, 'LIC002', 200.00, true),
(NULL, 'د. خالد إبراهيم', 'khaled@clinic.com', '0504444444', 'طب الأسنان', 'دكتوراه في طب الأسنان', 10, 'LIC003', 120.00, true);

-- المواعيد
INSERT INTO `appointments` (`patient_id`, `doctor_id`, `date`, `time`, `status`, `reason`) VALUES
(1, 1, '2026-01-10', '10:00:00', 'scheduled', 'كشف عام'),
(2, 2, '2026-01-11', '14:30:00', 'scheduled', 'متابعة'),
(1, 1, '2025-12-28', '09:00:00', 'completed', 'كشف دوري'),
(3, 3, '2026-01-12', '11:00:00', 'scheduled', 'فحص أسنان');

-- السجلات الطبية
INSERT INTO `medical_records` (`patient_id`, `doctor_id`, `appointment_id`, `symptoms`, `diagnosis`, `treatment_plan`, `follow_up_date`) VALUES
(1, 1, 3, 'صداع وحمى خفيفة', 'نزلة برد عادية', 'راحة وسوائل', '2026-01-05'),
(2, 2, NULL, 'ألم في البطن', 'التهاب بسيط', 'مراقبة والراحة', '2026-01-15');

-- الفواتير
INSERT INTO `billing` (`patient_id`, `doctor_id`, `appointment_id`, `invoice_number`, `amount`, `total_amount`, `status`) VALUES
(1, 1, 3, 'INV-001-2025-12-28', 150.00, 150.00, 'paid'),
(2, 2, NULL, 'INV-002-2026-01-03', 200.00, 200.00, 'pending');

-- الإعدادات
INSERT INTO `settings` (`key`, `value`, `description`) VALUES
('app_name', 'نظام إدارة العيادة', 'اسم التطبيق'),
('clinic_name', 'عيادة النور الطبية', 'اسم العيادة'),
('clinic_phone', '0112000000', 'رقم هاتف العيادة'),
('clinic_email', 'info@clinic.com', 'بريد العيادة'),
('clinic_address', 'حي الملز، الرياض', 'عنوان العيادة'),
('working_hours_start', '08:00', 'وقت الفتح'),
('working_hours_end', '20:00', 'وقت الإغلاق'),
('currency', 'SAR', 'العملة'),
('timezone', 'Asia/Riyadh', 'المنطقة الزمنية'),
('language', 'ar', 'اللغة الافتراضية'),
('enable_notifications', '1', 'تفعيل الإشعارات'),
('appointment_reminder', '24', 'تذكير الموعد (بالساعات)');

-- ==========================================
-- إنشاء الفهارس الإضافية للأداء
-- ==========================================
CREATE INDEX idx_appointments_date ON appointments(date);
CREATE INDEX idx_appointments_doctor_date ON appointments(doctor_id, date);
CREATE INDEX idx_appointments_patient_date ON appointments(patient_id, date);
CREATE INDEX idx_billing_created_at ON billing(created_at);
CREATE INDEX idx_medical_records_patient ON medical_records(patient_id);
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);

-- ==========================================
-- عروض (Views) للتقارير
-- ==========================================

-- عرض الإحصائيات الشاملة
CREATE OR REPLACE VIEW `vw_dashboard_stats` AS
SELECT
  (SELECT COUNT(*) FROM patients) as total_patients,
  (SELECT COUNT(*) FROM doctors) as total_doctors,
  (SELECT COUNT(*) FROM appointments WHERE status = 'scheduled') as scheduled_appointments,
  (SELECT COUNT(*) FROM appointments WHERE status = 'completed') as completed_appointments,
  (SELECT SUM(total_amount) FROM billing WHERE status = 'paid') as total_revenue,
  (SELECT COUNT(*) FROM appointments WHERE DATE(date) = CURDATE()) as today_appointments;

-- عرض المواعيد مع التفاصيل
CREATE OR REPLACE VIEW `vw_appointments_details` AS
SELECT
  a.id,
  a.patient_id,
  a.doctor_id,
  p.name as patient_name,
  d.name as doctor_name,
  d.specialty,
  a.date,
  a.time,
  a.status,
  CONCAT(DATE_FORMAT(a.date, '%d/%m/%Y'), ' - ', TIME_FORMAT(a.time, '%H:%i')) as appointment_datetime
FROM appointments a
LEFT JOIN patients p ON a.patient_id = p.id
LEFT JOIN doctors d ON a.doctor_id = d.id;

-- عرض الإيرادات حسب الطبيب
CREATE OR REPLACE VIEW `vw_revenue_by_doctor` AS
SELECT
  d.id,
  d.name,
  d.specialty,
  COUNT(b.id) as invoice_count,
  SUM(CASE WHEN b.status = 'paid' THEN b.total_amount ELSE 0 END) as paid_amount,
  SUM(CASE WHEN b.status = 'pending' THEN b.total_amount ELSE 0 END) as pending_amount,
  SUM(b.total_amount) as total_revenue
FROM doctors d
LEFT JOIN billing b ON d.id = b.doctor_id
GROUP BY d.id;

COMMIT;
