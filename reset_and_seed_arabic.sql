-- =====================================================
-- إعادة تهيئة قاعدة البيانات بالترميز العربي الصحيح
-- رضا أبو لحمة - Hypermarket Reda Abu Lahma
-- =====================================================

CREATE DATABASE IF NOT EXISTS `hypermarket_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hypermarket_db`;

-- إيقاف التحقق من المفاتيح الخارجية مؤقتاً لتسهيل التهيئة
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `wishlist`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `drivers`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `coupons`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. جدول الأقسام
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. جدول المستخدمين
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NULL,
  `email` VARCHAR(255) NULL UNIQUE,
  `phone` VARCHAR(50) NULL,
  `address` TEXT NULL,
  `role` ENUM('admin', 'customer') DEFAULT 'customer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. جدول المنتجات
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `image` VARCHAR(255) DEFAULT 'default.jpg',
  `stock` INT NOT NULL DEFAULT 0,
  `unit` VARCHAR(50) DEFAULT 'قطعة',
  `barcode` VARCHAR(100) UNIQUE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. جدول السائقين
CREATE TABLE `drivers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `vehicle` VARCHAR(80) DEFAULT 'دراجة نارية',
  `status` ENUM('available','busy','offline') DEFAULT 'available',
  `lat` DECIMAL(10,7) NULL,
  `lng` DECIMAL(10,7) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. جدول الطلبات
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(100) NOT NULL,
  `address` TEXT NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `lat` DECIMAL(10,8) NULL,
  `lng` DECIMAL(11,8) NULL,
  `status` ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
  `coupon_code` VARCHAR(50) NULL,
  `discount_amount` DECIMAL(10,2) DEFAULT 0,
  `notes` TEXT NULL,
  `driver_id` INT NULL,
  `delivery_notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. جدول عناصر الطلب
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `qty` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. جدول التقييمات والمراجعات
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `comment` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. جدول المفضلة
CREATE TABLE `wishlist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  UNIQUE KEY `user_prod_unique` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. جدول أكواد الخصم
CREATE TABLE `coupons` (
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

-- 10. جدول الإشعارات
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('order', 'promo', 'system', 'alert') DEFAULT 'system',
  `is_read` TINYINT(1) DEFAULT 0,
  `link` VARCHAR(500) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- إدخال البيانات الافتراضية
-- =====================================================

-- الأقسام السبعة المطلوبة
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, '🥩 اللحوم والمشويات', 'لحوم طازجة ومجمدة، دجاج، مشاوي وكباب عالي الجودة'),
(2, '🥦 الفواكه والخضروات', 'فواكه وخضروات طازجة يومياً من أفضل المصادر والمزارع'),
(3, '🧀 الألبان والأجبان', 'ألبان، أجبان، زبدة، قشطة ومشتقات الألبان الطازجة'),
(4, '🥫 المواد الغذائية والمعلبات', 'مواد غذائية جافة، معلبات، توابل وبهارات أساسية للبقالة'),
(5, '🍬 الحلويات والمقرمشات', 'شوكولاتة، حلويات شرقية وغربية، بسكويت، شيبس ومقرمشات لجميع الأوقات'),
(6, '🧃 المشروبات والعصائر', 'مياه معدنية، عصائر طبيعية، مشروبات غازية، شاي وقهوة ممتازة'),
(7, '🧹 المواد المنزلية', 'منظفات، معقمات، أدوات تنظيف ومستلزمات منزلية وكوزمتك أساسي');

-- إدخال المنتجات الموزعة على الأقسام الجديدة
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image`, `stock`, `unit`, `barcode`) VALUES
-- قسم اللحوم والمشويات (1)
(10, 1, 'لحم عجل طازج مقطع', 'لحم عجل عراقي طازج وخالي من الدهون - مقطع حسب الرغبة ومثالي للطبخ', 16000.00, 'https://images.unsplash.com/photo-1603048588665-791ca8aea617?w=600&auto=format&fit=crop&q=80', 40, 'كيلو', 'BAR-MEAT-01'),
(11, 1, 'دجاج طازج كامل', 'دجاج عراقي طازج ومبرد - وزن 1.1 كغم تقريباً', 7000.00, 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=600&auto=format&fit=crop&q=80', 60, 'قطعة', 'BAR-MEAT-02'),
(12, 1, 'سمك شبوط طازج', 'سمك شبوط عراقي طازج من الحوض مباشرة - مثالي للمسكوف العراقي الأصيل', 8500.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600&auto=format&fit=crop&q=80', 30, 'كيلو', 'BAR-MEAT-03'),
(22, 1, 'لحم مفروم طازج (قيمة)', 'لحم مفروم طازج عالي الجودة - مثالي لعمل الكباب والقيمة العراقية', 12000.00, 'https://images.unsplash.com/photo-1603360946369-dc9bb6258143?w=600&auto=format&fit=crop&q=80', 25, 'كيلو', 'BAR-MEAT-04'),

-- قسم الفواكه والخضروات (2)
(1, 2, 'تفاح أحمر طازج', 'تفاح أحمر عراقي حلو ومقرمش - مباشر من البستان', 2500.00, 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=600&auto=format&fit=crop&q=80', 120, 'كيلو', 'BAR-VEG-01'),
(2, 2, 'طماطم حمراء طازجة', 'طماطم حمراء طازجة محلية الإنتاج - قطاف اليوم - مثالية للطبخ والسلطة', 1500.00, 'https://images.unsplash.com/photo-1595855759920-86582396756a?w=600&auto=format&fit=crop&q=80', 200, 'كيلو', 'BAR-VEG-02'),
(3, 2, 'برتقال طازج', 'برتقال طازج مليء بالعصير وفيتامين C - مثالي للعصير اليومي المنعش', 2000.00, 'https://images.unsplash.com/photo-1547514701-42782101795e?w=600&auto=format&fit=crop&q=80', 150, 'كيلو', 'BAR-VEG-03'),
(17, 2, 'موز فيليبيني فاخر', 'موز طازج حلو ومغذي - غني بالبوتاسيوم ومستورد بجودة عالية', 2000.00, 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=600&auto=format&fit=crop&q=80', 100, 'كيلو', 'BAR-VEG-04'),
(18, 2, 'بطاطا بيضاء للطبخ والقلي', 'بطاطا بيضاء محلية طازجة صالحة للقلي والطبخ اليومي', 1000.00, 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=600&auto=format&fit=crop&q=80', 300, 'كيلو', 'BAR-VEG-05'),

-- قسم الألبان والأجبان (3)
(25, 3, 'جبن شيدر قالب فاخر', 'قالب جبن شيدر طبيعي غني بالنكهة - مناسب للسندوتشات والمعجنات', 5000.00, 'https://images.unsplash.com/photo-1618265341355-d0e2d1fdf26b?w=600&auto=format&fit=crop&q=80', 40, 'قطعة', 'BAR-DAI-01'),
(26, 3, 'حليب كامل الدسم طازج', 'حليب بقري مبستر كامل الدسم طازج - عبوة 1 لتر', 1500.00, 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=600&auto=format&fit=crop&q=80', 100, 'قطعة', 'BAR-DAI-02'),
(27, 3, 'لبن زبادي عراقي طازج', 'زبادي طبيعي طازج ومخثر بنكهة لذيذة - عبوة عائلية 1 كغم', 2000.00, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600&auto=format&fit=crop&q=80', 50, 'قطعة', 'BAR-DAI-03'),

-- قسم المواد الغذائية والمعلبات (4)
(13, 4, 'تونة بالزيت النباتي', 'علبة تونة خفيفة بالزيت النباتي سهلة الفتح - وزن 185 غرام', 3500.00, 'https://images.unsplash.com/photo-1622484211148-716598e0662c?w=600&auto=format&fit=crop&q=80', 250, 'قطعة', 'BAR-CAN-01'),
(14, 4, 'فاصوليا مطبوخة بصلصة الطماطم', 'علبة فاصوليا بيضاء بصلصة الطماطم الغنية - جاهزة للأكل 400 غرام', 2000.00, 'https://images.unsplash.com/photo-1551248429-40975aa4de74?w=600&auto=format&fit=crop&q=80', 180, 'قطعة', 'BAR-CAN-02'),
(23, 4, 'حمص بطحينة بالليمون', 'حمص مطبوخ جاهز للأكل بالطحينة والليمون وزيت الزيتون - 400 غرام', 2500.00, 'https://images.unsplash.com/photo-1548516173-3cabfa4607e9?w=600&auto=format&fit=crop&q=80', 200, 'قطعة', 'BAR-CAN-03'),
(4, 4, 'خبز صمون فرنسي طازج', 'صمون فرنسي طازج من الفرن - كيس يحتوي على 5 قطع مقرمشة', 1000.00, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600&auto=format&fit=crop&q=80', 80, 'قطعة', 'BAR-BAK-01'),

-- قسم الحلويات والمقرمشات (5)
(5, 5, 'كيكة شوكولاتة فاخرة', 'كيكة شوكولاتة إسفنجية غنية بالكريمة والصلصة اللذيذة - تكفي لـ 6 أشخاص', 12000.00, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=600&auto=format&fit=crop&q=80', 15, 'قطعة', 'BAR-BAK-02'),
(19, 5, 'بقلاوة بالفستق الحلبي', 'بقلاوة شرقية أصيلة بالفستق الحلبي المقرمش والعسل - علبة 500 غرام', 8000.00, 'https://images.unsplash.com/photo-1519676867240-f03562e64548?w=600&auto=format&fit=crop&q=80', 30, 'قطعة', 'BAR-BAK-03'),
(28, 5, 'شيبس بطاطس حجم عائلي', 'رقائق بطاطس مقرمشة بنكهة الملح والخل الطبيعي - كيس حجم عائلي', 1000.00, 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?w=600&auto=format&fit=crop&q=80', 150, 'قطعة', 'BAR-SWE-01'),
(29, 5, 'شوكولاتة داكنة فاخرة 70%', 'لوح شوكولاتة داكنة غنية بنسبة 70% كاكاو بلجيكي - 100 غرام', 2500.00, 'https://images.unsplash.com/photo-1548907040-4d42b52125ca?w=600&auto=format&fit=crop&q=80', 80, 'قطعة', 'BAR-SWE-02'),

-- قسم المشروبات والعصائر (6)
(30, 6, 'بيبسي كولا علبة معدنية', 'مشروب غازي بيبسي بارد ومنعش - عبوة 330 مل', 500.00, 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=600&auto=format&fit=crop&q=80', 300, 'قطعة', 'BAR-BEV-01'),
(31, 6, 'عصير برتقال طبيعي 100%', 'عصير برتقال طبيعي معصور بدون سكر مضاف - عبوة 1 لتر', 2500.00, 'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?w=600&auto=format&fit=crop&q=80', 60, 'قطعة', 'BAR-BEV-02'),
(32, 6, 'مياه معدنية نقية', 'زجاجة مياه معدنية طبيعية نقية - عبوة اقتصادية 1.5 لتر', 250.00, 'https://images.unsplash.com/photo-1608885898957-a599fb1ee4a4?w=600&auto=format&fit=crop&q=80', 500, 'قطعة', 'BAR-BEV-03'),

-- قسم المواد المنزلية (7)
(6, 7, 'شامبو للشعر بزيت الأرجان', 'شامبو مغذي للشعر التالف والجاف بالفيتامينات وزيت الأرجان - 400 مل', 4500.00, 'https://images.unsplash.com/photo-1535585209827-a15fcdbc4c2d?w=600&auto=format&fit=crop&q=80', 60, 'قطعة', 'BAR-COS-01'),
(7, 7, 'كريم مرطب للبشرة 24 ساعة', 'كريم مرطب ومغذي للبشرة الجافة والعادية يدوم طوال اليوم - 200 مل', 6000.00, 'https://images.unsplash.com/photo-1608248597279-f99d160bfcbc?w=600&auto=format&fit=crop&q=80', 45, 'قطعة', 'BAR-COS-02'),
(15, 7, 'مسحوق غسيل أوتوماتيك 3 كغم', 'مسحوق غسيل متطور للغسالات الأوتوماتيكية للملابس الملونة والبيضاء', 12000.00, 'https://images.unsplash.com/photo-1583947215259-38e31be8751f?w=600&auto=format&fit=crop&q=80', 50, 'قطعة', 'BAR-HOM-01'),
(16, 7, 'سائل غسيل الصحون بالليمون', 'سائل غسيل الصحون بتركيبة قوية لازالة الدهون برائحة الليمون - 1 لتر', 3000.00, 'https://images.unsplash.com/photo-1607006342411-92fc46485959?w=600&auto=format&fit=crop&q=80', 120, 'قطعة', 'BAR-HOM-02'),
(20, 7, 'مزيل عرق رول-أون طبيعي', 'مزيل عرق رول أون طبيعي بدون ألومنيوم حماية تدوم 48 ساعة', 3500.00, 'https://images.unsplash.com/photo-1526045612212-70caf35c14df?w=600&auto=format&fit=crop&q=80', 80, 'قطعة', 'BAR-COS-03'),
(21, 7, 'كيبل شحن سريع USB-C متين', 'كيبل شحن سريع USB-C مغطى بالنايلون المقاوم للقطع - طول 2 متر', 5000.00, 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&auto=format&fit=crop&q=80', 100, 'قطعة', 'BAR-ELE-03'),
(24, 7, 'مبيد حشري رذاذ فعّال', 'مبيد حشري رذاذ قوي وفعّال ضد جميع الحشرات الطائرة والزاحفة - 400 مل', 4000.00, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=600&auto=format&fit=crop&q=80', 60, 'قطعة', 'BAR-HOM-03');

-- إدخال السائقين بالترميز الصحيح
INSERT INTO `drivers` (`id`, `name`, `phone`, `vehicle`, `status`) VALUES
(1, 'أحمد الكربلائي',  '07801234567', 'دراجة نارية',  'available'),
(2, 'محمد الحسيني',   '07709876543', 'سيارة تويوتا', 'available'),
(3, 'علي الموسوي',    '07811112233', 'دراجة نارية',  'offline');

-- إدخال حساب مدير افتراضي (اسم المستخدم: admin ، كلمة المرور: admin123)
-- الباسورد مشفر بـ bcrypt (admin123)
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`) VALUES 
(1, 'admin', '$2y$10$y4ruJ9z12.cflqbnBDNhs.MtwotaCGDw4tKeXp8ijs8VnXWHdCeCy', 'مدير النظام رضا أبو لحمة', 'admin'),
(2, '07700000000', '$2y$10$y4ruJ9z12.cflqbnBDNhs.MtwotaCGDw4tKeXp8ijs8VnXWHdCeCy', 'Ahmed Customer', 'customer');


-- أكواد الخصم الترويجية
INSERT INTO `coupons` (`code`, `discount_type`, `discount_value`, `min_order`, `max_uses`, `is_active`) VALUES
('WELCOME10', 'percentage', 10.00, 10000.00, 500, 1),
('REDA5000', 'fixed', 5000.00, 30000.00, 200, 1),
('BAGHDAD20', 'percentage', 20.00, 50000.00, 100, 1);

-- إشعارات ترويجية ونظامية
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `link`) VALUES
(NULL, '🎉 أهلاً بكم في رضا أبو لحمة', 'خصم 10% لجميع العملاء الجدد باستخدام كود WELCOME10 عند الدفع!', 'promo', 'index.php'),
(NULL, '🥩 قسم اللحوم والمشويات الجديد', 'نوفر لكم أفضل اللحوم الطازجة والمشاوي اللذيذة يومياً بأسعار ممتازة.', 'system', 'index.php?cat_id=1'),
(NULL, '🚚 خدمة توصيل سريعة', 'احصل على توصيل مجاني لجميع الطلبات التي تتجاوز قيمتها 50,000 دينار عراقي.', 'promo', 'index.php');
