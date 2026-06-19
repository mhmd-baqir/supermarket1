-- =====================================================
-- UPDATE 4: جدول السائقين وتحديث جدول الطلبات
-- نفّذ هذا الملف مرة واحدة فقط في phpMyAdmin
-- =====================================================

-- إنشاء جدول السائقين
CREATE TABLE IF NOT EXISTS drivers (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    phone       VARCHAR(20)  NOT NULL,
    vehicle     VARCHAR(80)  DEFAULT 'دراجة نارية',
    status      ENUM('available','busy','offline') DEFAULT 'available',
    lat         DECIMAL(10,7) NULL COMMENT 'موقع السائق الحالي - خط العرض',
    lng         DECIMAL(10,7) NULL COMMENT 'موقع السائق الحالي - خط الطول',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- إضافة عمود driver_id إلى جدول orders (إذا لم يكن موجوداً)
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS driver_id INT NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS delivery_notes TEXT NULL;

-- إضافة سائقين تجريبيين
INSERT IGNORE INTO drivers (id, name, phone, vehicle, status) VALUES
(1, 'أحمد الكربلائي',  '07801234567', 'دراجة نارية',  'available'),
(2, 'محمد الحسيني',   '07709876543', 'سيارة تويوتا', 'available'),
(3, 'علي الموسوي',    '07811112233', 'دراجة نارية',  'offline');
