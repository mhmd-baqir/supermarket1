<?php
include 'config.php';

try {
    echo "Starting database migration...\n";

    // 1. Update orders table
    echo "Updating orders table (adding customer_received, delivery_rating, delivery_feedback)...\n";
    $pdo->exec("
        ALTER TABLE `orders`
        ADD COLUMN IF NOT EXISTS `customer_received` TINYINT DEFAULT 0 AFTER `status`,
        ADD COLUMN IF NOT EXISTS `delivery_rating` INT NULL DEFAULT NULL AFTER `customer_received`,
        ADD COLUMN IF NOT EXISTS `delivery_feedback` TEXT NULL DEFAULT NULL AFTER `delivery_rating`
    ");

    // 2. Update categories table
    echo "Updating categories table (adding admin_id for section admin allocation)...\n";
    $pdo->exec("
        ALTER TABLE `categories`
        ADD COLUMN IF NOT EXISTS `admin_id` INT NULL DEFAULT NULL AFTER `description`
    ");

    // Check if constraint exists, if not add it (wrapped in try/catch in case constraint is already added)
    try {
        $pdo->exec("
            ALTER TABLE `categories`
            ADD CONSTRAINT `fk_category_admin` 
            FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) 
            ON DELETE SET NULL
        ");
        echo "Foreign key constraint fk_category_admin added successfully.\n";
    } catch (PDOException $ex) {
        echo "Note on fk_category_admin: " . $ex->getMessage() . " (might already exist)\n";
    }

    // 3. Create support_messages table
    echo "Creating support_messages table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `support_messages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `category_id` INT NOT NULL,
            `order_id` INT NULL DEFAULT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `admin_reply` TEXT NULL DEFAULT NULL,
            `status` ENUM('pending', 'replied', 'closed') DEFAULT 'pending',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo "✅ Database migration completed successfully!\n";
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
