<?php
/**
 * google_callback.php
 * معالجة استجابة Google OAuth 2.0 بعد موافقة المستخدم
 * - استبدال الكود بـ Access Token
 * - جلب بيانات المستخدم من Google
 * - تسجيل الدخول أو إنشاء حساب جديد
 */
include 'config.php';

// التحقق من وجود أخطاء من Google
if (isset($_GET['error'])) {
    $_SESSION['google_error'] = 'تم إلغاء تسجيل الدخول بواسطة Google.';
    header('Location: login.php');
    exit;
}

// التحقق من وجود الكود والـ state
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    $_SESSION['google_error'] = 'طلب غير صالح، يرجى المحاولة مرة أخرى.';
    header('Location: login.php');
    exit;
}

// التحقق من CSRF (مطابقة state)
if (!isset($_SESSION['google_oauth_state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
    $_SESSION['google_error'] = 'خطأ في التحقق الأمني، يرجى المحاولة مرة أخرى.';
    header('Location: login.php');
    exit;
}
unset($_SESSION['google_oauth_state']);

$code = $_GET['code'];

// === الخطوة 1: استبدال الكود بـ Access Token ===
$token_url = 'https://oauth2.googleapis.com/token';
$token_data = [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$token_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    $_SESSION['google_error'] = 'فشل في الحصول على رمز الدخول من Google.';
    header('Location: login.php');
    exit;
}

$token_data = json_decode($token_response, true);
if (!isset($token_data['access_token'])) {
    $_SESSION['google_error'] = 'استجابة غير صالحة من Google.';
    header('Location: login.php');
    exit;
}

$access_token = $token_data['access_token'];

// === الخطوة 2: جلب بيانات المستخدم من Google ===
$userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($userinfo_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$userinfo_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    $_SESSION['google_error'] = 'فشل في جلب بيانات الحساب من Google.';
    header('Location: login.php');
    exit;
}

$google_user = json_decode($userinfo_response, true);
$google_id    = $google_user['id'] ?? null;
$google_email = $google_user['email'] ?? null;
$google_name  = $google_user['name'] ?? '';
$google_pic   = $google_user['picture'] ?? '';

if (!$google_id || !$google_email) {
    $_SESSION['google_error'] = 'لم نتمكن من الحصول على بيانات حسابك من Google.';
    header('Location: login.php');
    exit;
}

// === الخطوة 3: البحث في قاعدة البيانات وتسجيل الدخول ===
try {
    // البحث بواسطة google_id أولاً
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? AND role = 'customer'");
    $stmt->execute([$google_id]);
    $user = $stmt->fetch();

    if ($user) {
        // المستخدم موجود بالفعل بـ Google ID → تسجيل دخول مباشر
        loginUser($user);
    } else {
        // البحث بالبريد الإلكتروني (ربط حساب موجود)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'customer'");
        $stmt->execute([$google_email]);
        $user = $stmt->fetch();

        if ($user) {
            // ربط الحساب الموجود بـ Google
            $update = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            $update->execute([$google_id, $user['id']]);

            // تحديث الاسم إذا كان فارغاً
            if (empty($user['full_name']) && !empty($google_name)) {
                $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?")->execute([$google_name, $user['id']]);
                $user['full_name'] = $google_name;
            }

            loginUser($user);
        } else {
            // إنشاء حساب جديد
            $username = 'google_' . $google_id;

            $stmt = $pdo->prepare(
                "INSERT INTO users (username, password, full_name, email, google_id, role, created_at) 
                 VALUES (?, NULL, ?, ?, ?, 'customer', NOW())"
            );
            $stmt->execute([$username, $google_name, $google_email, $google_id]);

            $new_user_id = $pdo->lastInsertId();

            $user = [
                'id'        => $new_user_id,
                'username'  => $username,
                'full_name' => $google_name,
                'role'      => 'customer',
            ];

            loginUser($user);
        }
    }
} catch (\PDOException $e) {
    $_SESSION['google_error'] = 'حدث خطأ أثناء معالجة تسجيل الدخول: ' . htmlspecialchars($e->getMessage());
    header('Location: login.php');
    exit;
}

/**
 * تسجيل دخول المستخدم وتعيين الجلسة
 */
function loginUser($user) {
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = 'customer';
    $_SESSION['full_name'] = $user['full_name'];

    // التوجيه لصفحة الشحن إذا كان قادماً منها، وإلا للرئيسية
    $redirect = $_SESSION['google_redirect_after'] ?? $_SESSION['redirect_after_login'] ?? 'index.php';
    unset($_SESSION['redirect_after_login']);
    unset($_SESSION['google_redirect_after']);

    header("Location: $redirect");
    exit;
}
?>
