<?php
ob_start();
include 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ===== جلب المجموع الكلي للسلة (للـ checkout) =====
if (isset($_GET['get_total'])) {
    $subtotal = 0;
    foreach ($_SESSION['cart'] as $pid => $qty) {
        $s = $pdo->prepare("SELECT price FROM products WHERE id=?");
        $s->execute([$pid]);
        $p = $s->fetch();
        if ($p) $subtotal += $p['price'] * $qty;
    }
    echo json_encode(['subtotal' => $subtotal, 'cart_count' => array_sum($_SESSION['cart'])], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== إزالة منتج من السلة =====
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $rid = intval($_GET['id']);
    unset($_SESSION['cart'][$rid]);
    echo json_encode([
        'success' => true,
        'cart_count' => array_sum($_SESSION['cart']),
        'message' => 'تم حذف المنتج من السلة.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== إضافة منتج للسلة =====
$id     = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'add';

if ($id > 0) {
    // التحقق من المخزون قبل الإضافة
    $ps = $pdo->prepare("SELECT name, stock FROM products WHERE id=?");
    $ps->execute([$id]);
    $prod = $ps->fetch();

    if (!$prod) {
        echo json_encode(['success' => false, 'message' => 'المنتج غير موجود.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $current_in_cart = $_SESSION['cart'][$id] ?? 0;

    if ($current_in_cart >= $prod['stock']) {
        echo json_encode([
            'success' => false,
            'message' => 'لا يوجد مخزون كافٍ لإضافة المزيد من هذا المنتج.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'add') {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]++;
        } else {
            $_SESSION['cart'][$id] = 1;
        }
    }

    $cart_count = array_sum($_SESSION['cart']);
    echo json_encode([
        'success'    => true,
        'cart_count' => $cart_count,
        'message'    => 'تمت إضافة «' . $prod['name'] . '» إلى السلة!'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'message' => 'معرف منتج غير صالح.'], JSON_UNESCAPED_UNICODE);
exit;
