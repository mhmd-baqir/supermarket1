<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();
include 'config.php';

// استخراج بيانات الطلب أولاً حتى تكون متاحة لجميع الحالات
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$action     = isset($_POST['action'])     ? trim($_POST['action'])        : '';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    // دعم مفضلة الزائر (Guest Wishlist) عبر الجلسة
    if (!isset($_SESSION['guest_wishlist'])) {
        $_SESSION['guest_wishlist'] = [];
    }
    if ($product_id <= 0 || !in_array($action, ['add','remove'])) {
        echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
        exit;
    }
    if ($action === 'add') {
        if (!in_array($product_id, $_SESSION['guest_wishlist'])) {
            $_SESSION['guest_wishlist'][] = $product_id;
        }
        echo json_encode(['success' => true, 'in_wishlist' => true, 'message' => '❤️ تم إضافة المنتج للمفضلة']);
    } else {
        $key = array_search($product_id, $_SESSION['guest_wishlist']);
        if ($key !== false) {
            unset($_SESSION['guest_wishlist'][$key]);
            $_SESSION['guest_wishlist'] = array_values($_SESSION['guest_wishlist']);
        }
        echo json_encode(['success' => true, 'in_wishlist' => false, 'message' => '💔 تم إزالة المنتج من المفضلة']);
    }
    exit;
}

$user_id = $_SESSION['user_id'];

if ($product_id <= 0 || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
    exit;
}


try {
    if ($action === 'add') {
        $check = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check->execute([$user_id, $product_id]);
        if ($check->fetch()) {
            echo json_encode(['success' => true, 'in_wishlist' => true, 'message' => 'المنتج موجود في المفضلة']);
        } else {
            $insert = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $insert->execute([$user_id, $product_id]);
            echo json_encode(['success' => true, 'in_wishlist' => true, 'message' => '❤️ تم إضافة المنتج للمفضلة']);
        }
    } elseif ($action === 'remove') {
        $delete = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $delete->execute([$user_id, $product_id]);
        echo json_encode(['success' => true, 'in_wishlist' => false, 'message' => '💔 تم إزالة المنتج من المفضلة']);
    }
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات']);
}
?>
