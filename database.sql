CREATE DATABASE IF NOT EXISTS `hypermarket_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hypermarket_db`;

-- 1. جدول الأقسام
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. جدول المستخدمين (المدراء والعملاء)
CREATE TABLE IF NOT EXISTS `users` (
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
CREATE TABLE IF NOT EXISTS `products` (
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

-- 4. جدول الطلبات (orders)
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(100) NOT NULL,
  `address` TEXT NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `lat` DECIMAL(10,8) NULL,
  `lng` DECIMAL(11,8) NULL,
  `status` ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. جدول عناصر الطلب (order_items)
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `qty` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. جدول التقييمات والمراجعات (reviews)
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `comment` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. جدول المفضلة (wishlist)
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  UNIQUE KEY `user_prod_unique` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدخال الأقسام السبعة المطلوبة
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'الفواكه والخضروات', 'خضار وفواكه طازجة يومياً تباع بالكيلو'),
(2, 'المخبوزات والمعجنات', 'خبز وصمون ومعجنات طازجة من الفرن تباع بالقطعة'),
(3, 'الكوزمتك والعناية الشخصية', 'مستحضرات التجميل، العناية بالبشرة والشعر بالقطعة'),
(4, 'الأجهزة الإلكترونية', 'شواحن، سماعات، هواتف وإلكترونيات منوعة بالقطعة'),
(5, 'اللحوم والأسماك', 'لحوم حمراء طازجة، دجاج مبرد، وأسماك مبردة تباع بالكيلو'),
(6, 'المعلبات واللحوم الباردة', 'أغذية معلبة وجافة ومواد البقالة اليومية بالقطعة'),
(7, 'المستلزمات المنزلية الأخرى', 'أدوات التنظيف ومستلزمات المطبخ والعناية بالمنزل بالقطعة');

-- إدخال منتجات تجريبية جديدة بالأوزان والصور الصحيحة
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image`, `stock`, `unit`, `barcode`) VALUES
-- قسم الفواكه والخضار (بالكيلو)
(1, 1, 'تفاح أحمر طازج', 'تفاح أحمر إيطالي حلو وطازج عالي الجودة', 2500.00, 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=600&auto=format&fit=crop&q=80', 120, 'كيلو', 'BAR-VEG-01'),
(2, 1, 'طماطم محلية طازجة', 'طماطم طازجة محلية الإنتاج قطاف اليوم', 1500.00, 'https://images.unsplash.com/photo-1595855759920-86582396756a?w=600&auto=format&fit=crop&q=80', 200, 'كيلو', 'BAR-VEG-02'),
(3, 1, 'برتقال أبو صرة', 'برتقال أبو صرة طازج ومليء بالعصير وحلو المذاق', 2000.00, 'https://images.unsplash.com/photo-1547514701-42782101795e?w=600&auto=format&fit=crop&q=80', 150, 'كيلو', 'BAR-VEG-03'),

-- قسم المخبوزات والمعجنات (بالقطعة)
(4, 2, 'صمون فرنسي طازج', 'كيس يحتوي على 5 قطع صمون فرنسي مقرمش ولذيذ', 1000.00, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600&auto=format&fit=crop&q=80', 80, 'قطعة', 'BAR-BAK-01'),
(5, 2, 'كيكة الشوكولاتة الفاخرة', 'كيكة شوكولاتة إسفنجية غنية بالكريمة والصلصة اللذيذة تكفي لـ 6 أشخاص', 12000.00, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=600&auto=format&fit=crop&q=80', 15, 'قطعة', 'BAR-BAK-02'),

-- قسم الكوزمتك (بالقطعة)
(6, 3, 'شامبو العناية المتكاملة', 'شامبو مغذي للشعر بالفيتامينات وزيت الأرجان - 400 مل', 4500.00, 'https://images.unsplash.com/photo-1535585209827-a15fcdbc4c2d?w=600&auto=format&fit=crop&q=80', 60, 'قطعة', 'BAR-COS-01'),
(7, 3, 'كريم مرطب للبشرة', 'كريم مرطب ومغذي للبشرة الجافة والعادية يدوم 24 ساعة - 200 مل', 6000.00, 'https://images.unsplash.com/photo-1608248597279-f99d160bfcbc?w=600&auto=format&fit=crop&q=80', 45, 'قطعة', 'BAR-COS-02'),

-- قسم الإلكترونيات (بالقطعة)
(8, 4, 'سماعات رأس لاسلكية', 'سماعات بلوتوث عالية الوضوح مع عزل للضوضاء وبطارية تدوم 30 ساعة', 45000.00, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80', 25, 'قطعة', 'BAR-ELE-01'),
(9, 4, 'شاحن جداري سريع', 'شاحن بقوة 65 واط مزود بمنفذين USB-C وتقنية الشحن السريع', 15000.00, 'https://images.unsplash.com/photo-1616440347437-b1c73416efc2?w=600&auto=format&fit=crop&q=80', 35, 'قطعة', 'BAR-ELE-02'),

-- قسم اللحوم والأسماك (بالكيلو)
(10, 5, 'لحم عجل طازج', 'لحم عجل عراقي طازج وخالي من الدهون مقطع حسب الرغبة', 16000.00, 'https://images.unsplash.com/photo-1603048588665-791ca8aea617?w=600&auto=format&fit=crop&q=80', 40, 'كيلو', 'BAR-MEAT-01'),
(11, 5, 'دجاج مبرد كامل', 'دجاج عراقي مبرد طازج - وزن 1.1 كغم تقريباً', 7000.00, 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=600&auto=format&fit=crop&q=80', 60, 'قطعة', 'BAR-MEAT-02'),
(12, 5, 'سمك كارب طازج', 'سمك مسكوف عراقي طازج من الحوض مباشرة', 8500.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600&auto=format&fit=crop&q=80', 30, 'كيلو', 'BAR-MEAT-03'),

-- قسم المعلبات (بالقطعة)
(13, 6, 'تونة خفيفة بالزيت', 'علبة تونة خفيفة بالزيت النباتي سهلة الفتح - 185 غرام', 3500.00, 'https://images.unsplash.com/photo-1622484211148-716598e0662c?w=600&auto=format&fit=crop&q=80', 250, 'قطعة', 'BAR-CAN-01'),
(14, 6, 'فاصوليا مطبوخة بصلصة الطماطم', 'علبة فاصوليا بيضاء بصلصة الطماطم جاهزة للأكل - 400 غرام', 2000.00, 'https://images.unsplash.com/photo-1551248429-40975aa4de74?w=600&auto=format&fit=crop&q=80', 180, 'قطعة', 'BAR-CAN-02'),

-- قسم المستلزمات المنزلية (بالقطعة)
(15, 7, 'مسحوق غسيل أوتوماتيك', 'مسحوق غسيل عالي الجودة للملابس الملونة والبيضاء - 3 كغم', 12000.00, 'https://images.unsplash.com/photo-1583947215259-38e31be8751f?w=600&auto=format&fit=crop&q=80', 50, 'قطعة', 'BAR-HOM-01'),
(16, 7, 'صابون غسيل الصحون', 'سائل غسيل الصحون برائحة الليمون المنعش وقوة إذابة الدهون - 1 لتر', 3000.00, 'https://images.unsplash.com/photo-1607006342411-92fc46485959?w=600&auto=format&fit=crop&q=80', 120, 'قطعة', 'BAR-HOM-02');

-- إدخال حساب مدير افتراضي (اسم المستخدم: admin ، كلمة المرور: admin123)
-- الباسورد مشفر بـ bcrypt (admin123)
INSERT INTO `users` (`username`, `password`, `role`) VALUES 
('admin', '$2y$10$y4ruJ9z12.cflqbnBDNhs.MtwotaCGDw4tKeXp8ijs8VnXWHdCeCy', 'admin');
