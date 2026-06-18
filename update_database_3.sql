USE `hypermarket_db`;

-- ===== 1. جدول أكواد الخصم (Coupons) =====
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` ENUM('percentage', 'fixed') DEFAULT 'percentage',
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_order` DECIMAL(10,2) DEFAULT 0,
  `max_uses` INT DEFAULT 100,
  `used_count` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `expires_at` DATE NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 2. جدول الإشعارات (Notifications) =====
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL COMMENT 'NULL = للجميع / Admin notifications',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('order', 'promo', 'system', 'alert') DEFAULT 'system',
  `is_read` TINYINT(1) DEFAULT 0,
  `link` VARCHAR(500) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 3. إضافة حقل الكوبون للطلبات =====
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `coupon_code` VARCHAR(50) NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(10,2) DEFAULT 0 AFTER `coupon_code`,
  ADD COLUMN IF NOT EXISTS `notes` TEXT NULL AFTER `discount_amount`;

-- ===== 4. تصحيح أسماء المنتجات وزيادة المنتجات =====
-- تصحيح المنتجات الموجودة
UPDATE `products` SET `name` = 'تفاح أحمر طازج', `description` = 'تفاح أحمر عراقي حلو وطازج - مباشر من البستان' WHERE `id` = 1;
UPDATE `products` SET `name` = 'طماطم حمراء طازجة', `description` = 'طماطم حمراء طازجة محلية الإنتاج - قطاف اليوم - مثالية للطبخ والسلطة' WHERE `id` = 2;
UPDATE `products` SET `name` = 'برتقال طازج', `description` = 'برتقال طازج مليء بالعصير وفيتامين C - مثالي للعصير' WHERE `id` = 3;
UPDATE `products` SET `name` = 'خبز صمون فرنسي', `description` = 'صمون فرنسي طازج من الفرن - كيس 5 قطع مقرمش ولذيذ' WHERE `id` = 4;
UPDATE `products` SET `name` = 'كيكة شوكولاتة', `description` = 'كيكة شوكولاتة إسفنجية غنية بالكريمة - تكفي لـ 6 أشخاص' WHERE `id` = 5;
UPDATE `products` SET `name` = 'شامبو للشعر بزيت الأرجان', `description` = 'شامبو مغذي للشعر بالفيتامينات وزيت الأرجان - 400 مل' WHERE `id` = 6;
UPDATE `products` SET `name` = 'كريم مرطب للبشرة', `description` = 'كريم مرطب ومغذي للبشرة الجافة يدوم 24 ساعة - 200 مل' WHERE `id` = 7;
UPDATE `products` SET `name` = 'سماعات بلوتوث لاسلكية', `description` = 'سماعات بلوتوث عالية الجودة مع عزل للضوضاء - بطارية 30 ساعة' WHERE `id` = 8;
UPDATE `products` SET `name` = 'شاحن جداري سريع 65W', `description` = 'شاحن سريع 65 واط - منفذ USB-C مزدوج وتقنية الشحن السريع' WHERE `id` = 9;
UPDATE `products` SET `name` = 'لحم عجل طازج', `description` = 'لحم عجل عراقي طازج وخالي من الدهون - مقطع حسب الطلب', `unit` = 'كيلو' WHERE `id` = 10;
UPDATE `products` SET `name` = 'دجاج طازج كامل', `description` = 'دجاج عراقي طازج - وزن 1.1 كغم تقريباً' WHERE `id` = 11;
UPDATE `products` SET `name` = 'سمك شبوط طازج', `description` = 'سمك شبوط عراقي طازج من الحوض مباشرة - مثالي للمسكوف' WHERE `id` = 12;
UPDATE `products` SET `name` = 'تونة بالزيت النباتي', `description` = 'علبة تونة خفيفة بالزيت النباتي سهلة الفتح - 185 غرام' WHERE `id` = 13;
UPDATE `products` SET `name` = 'فاصوليا بصلصة الطماطم', `description` = 'علبة فاصوليا بيضاء بصلصة الطماطم الحارة - جاهزة للأكل 400 غرام' WHERE `id` = 14;
UPDATE `products` SET `name` = 'مسحوق غسيل أوماتيك 3 كغم', `description` = 'مسحوق غسيل للغسالة الأوتوماتيك للملابس الملونة والبيضاء - 3 كغم' WHERE `id` = 15;
UPDATE `products` SET `name` = 'سائل غسيل الصحون برائحة الليمون', `description` = 'سائل غسيل الصحون بتركيبة قوية برائحة الليمون المنعش - 1 لتر' WHERE `id` = 16;

-- ===== 5. إضافة منتجات جديدة =====
INSERT IGNORE INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image`, `stock`, `unit`, `barcode`) VALUES
-- فواكه وخضار إضافية
(17, 1, 'موز فيليبيني', 'موز طازج حلو ومغذي - غني بالبوتاسيوم', 2000.00, 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=600&auto=format&fit=crop&q=80', 100, 'كيلو', 'BAR-VEG-04'),
(18, 1, 'بطاطا بيضاء', 'بطاطا بيضاء محلية طازجة صالحة للقلي والطبخ', 1000.00, 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=600&auto=format&fit=crop&q=80', 300, 'كيلو', 'BAR-VEG-05'),
-- مخبوزات إضافية
(19, 2, 'بقلاوة بالفستق', 'بقلاوة شرقية أصيلة بالفستق الحلبي والعسل - 500 غرام', 8000.00, 'https://images.unsplash.com/photo-1519676867240-f03562e64548?w=600&auto=format&fit=crop&q=80', 30, 'قطعة', 'BAR-BAK-03'),
-- كوزمتك إضافية
(20, 3, 'مزيل عرق رول-أون', 'مزيل عرق رول أون بدون أملاح الألمنيوم - يدوم 48 ساعة', 3500.00, 'https://images.unsplash.com/photo-1526045612212-70caf35c14df?w=600&auto=format&fit=crop&q=80', 80, 'قطعة', 'BAR-COS-03'),
-- الكترونيات إضافية
(21, 4, 'كيبل شحن USB-C متين', 'كيبل شحن USB-C سريع - طول 2 متر ومقاوم للكسر', 5000.00, 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&auto=format&fit=crop&q=80', 100, 'قطعة', 'BAR-ELE-03'),
-- لحوم إضافية
(22, 5, 'قيمة لحم مفروم طازج', 'لحم مفروم طازج خالي من الدهون - مثالي للكباب والقيمة', 12000.00, 'https://images.unsplash.com/photo-1603360946369-dc9bb6258143?w=600&auto=format&fit=crop&q=80', 25, 'كيلو', 'BAR-MEAT-04'),
-- معلبات إضافية
(23, 6, 'حمص جاهز بالليمون', 'حمص مطبوخ جاهز للأكل بالليمون والزيت - 400 غرام', 2500.00, 'https://images.unsplash.com/photo-1548516173-3cabfa4607e9?w=600&auto=format&fit=crop&q=80', 200, 'قطعة', 'BAR-CAN-03'),
-- منزلية إضافية
(24, 7, 'مبيد حشري رذاذ', 'مبيد حشري رذاذ فعّال ضد البق والناموس والصراصير - 400 مل', 4000.00, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=600&auto=format&fit=crop&q=80', 60, 'قطعة', 'BAR-HOM-03');

-- ===== 6. إضافة أكواد خصم تجريبية =====
INSERT IGNORE INTO `coupons` (`code`, `discount_type`, `discount_value`, `min_order`, `max_uses`, `is_active`) VALUES
('WELCOME10', 'percentage', 10.00, 10000.00, 500, 1),
('SAVE5000', 'fixed', 5000.00, 30000.00, 200, 1),
('KARBALA20', 'percentage', 20.00, 50000.00, 100, 1),
('NEWUSER15', 'percentage', 15.00, 20000.00, 300, 1);

-- ===== 7. إشعارات ترحيبية =====
INSERT IGNORE INTO `notifications` (`user_id`, `title`, `message`, `type`, `link`) VALUES
(NULL, '🎉 عرض الأسبوع', 'خصم 20% على جميع الفواكه والخضروات! استخدم كود KARBALA20', 'promo', 'index.php?cat_id=1'),
(NULL, '🆕 منتجات جديدة', 'تم إضافة منتجات جديدة في قسم اللحوم والأسماك', 'system', 'index.php?cat_id=5'),
(NULL, '🚚 خدمة التوصيل', 'التوصيل مجاني للطلبات التي تزيد عن 50,000 دينار عراقي', 'promo', 'index.php');
