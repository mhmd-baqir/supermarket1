<?php
/**
 * google_login.php
 * يقوم بتوليد رابط Google OAuth 2.0 وتوجيه المستخدم إلى صفحة موافقة Google
 */
include 'config.php';

// توليد state عشوائي لحماية CSRF
$state = bin2hex(random_bytes(32));
$_SESSION['google_oauth_state'] = $state;

// حفظ صفحة الإعادة بعد تسجيل الدخول
if (isset($_SESSION['redirect_after_login'])) {
    $_SESSION['google_redirect_after'] = $_SESSION['redirect_after_login'];
}

// بناء رابط التفويض
$params = [
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',
];

$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

header('Location: ' . $auth_url);
exit;
?>
