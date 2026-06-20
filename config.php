<?php
ob_start();
// إجبار ترميز UTF-8 على جميع الصفحات
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
mb_internal_encoding('UTF-8');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$host    = 'localhost';
$db      = 'hypermarket_db';
$user    = 'root';
$pass    = ''; // افتراضي في XAMPP يكون فارغاً
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=3306;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET CHARACTER SET utf8mb4");
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// التحقق من صحة الجلسة وحساب المستخدم في قاعدة البيانات لتفادي المشاكل بعد تصفير قاعدة البيانات
if (isset($_SESSION['user_id'])) {
    $session_user_stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $session_user_stmt->execute([$_SESSION['user_id']]);
    if (!$session_user_stmt->fetch()) {
        // الحساب غير موجود (حالة جلسة قديمة تالفة) - يتم تنظيف الجلسة
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['role']);
        unset($_SESSION['full_name']);
    }
}

// تحميل الإعدادات الحساسة من ملف credentials.php إذا كان موجوداً
if (file_exists(__DIR__ . '/credentials.php')) {
    include __DIR__ . '/credentials.php';
} else {
    define('GOOGLE_CLIENT_ID', '');
    define('GOOGLE_CLIENT_SECRET', '');
    define('GOOGLE_REDIRECT_URI', 'http://localhost:8000/google_callback.php');
}
?>
