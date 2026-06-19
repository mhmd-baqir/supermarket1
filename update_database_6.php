<?php
include 'config.php';

try {
    echo "Starting database migration 6...\n";
    echo "Adding avatar column to users table...\n";
    $pdo->exec("
        ALTER TABLE `users`
        ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(255) DEFAULT NULL AFTER `role`
    ");
    echo "✅ Database migration 6 completed successfully!\n";
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
