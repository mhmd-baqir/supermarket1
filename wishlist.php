<?php
ob_start();
include 'config.php';

// فرض تسجيل الدخول للعملاء فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$message_type = '';

// معالجة الإضافة والإزالة
if ($product_id > 0) {
    if ($action === 'add') {
        try {
            // تحقق إذا كان المنتج موجوداً بالفعل في المفضلة
            $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            if ($stmt->fetch()) {
                $message = 'المنتج موجود بالفعل في قائمتك المفضلة!';
                $message_type = 'warning';
            } else {
                $insert = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
                $insert->execute([$user_id, $product_id]);
                $message = 'تم إضافة المنتج للمفضلة بنجاح! ❤️';
                $message_type = 'success';
            }
        } catch (\PDOException $e) {
            $message = 'حدث خطأ: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    } elseif ($action === 'remove') {
        try {
            $delete = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $delete->execute([$user_id, $product_id]);
            $message = 'تم إزالة المنتج من المفضلة. 💔';
            $message_type = 'success';
        } catch (\PDOException $e) {
            $message = 'حدث خطأ: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
        }
    }

    // إذا تم استدعاء هذا عبر الرابط المباشر، يفضل إرجاع المستخدم لصفحته السابقة أو إظهار الرسالة
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'wishlist.php';
    // في حال الانتقال إلى wishlist.php كطلب إضافة، لا تقم بالتحويل اللانهائي
    if (strpos($redirect, 'action=add') !== false || strpos($redirect, 'action=remove') !== false) {
        $redirect = 'wishlist.php';
    }
    
    $_SESSION['wishlist_msg'] = $message;
    $_SESSION['wishlist_msg_type'] = $message_type;
    header("Location: " . $redirect);
    exit;
}

// جلب رسائل الحالة من الجلسة
if (isset($_SESSION['wishlist_msg'])) {
    $message = $_SESSION['wishlist_msg'];
    $message_type = $_SESSION['wishlist_msg_type'];
    unset($_SESSION['wishlist_msg']);
    unset($_SESSION['wishlist_msg_type']);
}

// جلب منتجات المفضلة للمستخدم الحالي
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name 
    FROM wishlist w 
    JOIN products p ON w.product_id = p.id 
    JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = ?
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();

include 'header.php';
?>

<div class="row">
    <!-- قائمة العميل الجانبية -->
    <div class="col-md-3 mb-4 fade-in-up">
        <div class="sidebar-card">
            <h6 class="sidebar-title">👤 حسابي الشخصي</h6>
            <a href="my_account.php" class="sidebar-link">🛠️ تعديل الملف الشخصي</a>
            <a href="my_orders.php" class="sidebar-link">📦 طلباتي السابقة</a>
            <a href="wishlist.php" class="sidebar-link active">❤️ قائمة المفضلة</a>
            <a href="logout.php" class="sidebar-link text-danger">🚪 تسجيل الخروج</a>
        </div>
    </div>

    <!-- قائمة المفضلة -->
    <div class="col-md-9 fade-in-up delay-1">
        <h2 class="page-title">❤️ قائمتي المفضلة</h2>

        <?php if ($message): ?>
            <div class="alert-modern alert-<?php echo $message_type; ?>-modern mb-3">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <?php if (count($wishlist_items) > 0): ?>
                <?php foreach ($wishlist_items as $i => $product): ?>
                    <div class="col-md-4 col-sm-6 fade-in-up" style="animation-delay: <?php echo $i * 0.05; ?>s">
                        <div class="product-card h-100">
                            <div style="position: relative; overflow: hidden;">
                                <?php 
                                $image_src = $product['image'];
                                if (strpos($image_src, 'http') !== 0) {
                                    $image_src = 'uploads/' . $image_src;
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($image_src); ?>" 
                                     class="card-img-top w-100" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     style="height: 180px; object-fit: cover;"
                                     onerror="this.src='https://placehold.co/400x200/1e293b/4ade80?text=منتج'">
                                <a href="wishlist.php?action=remove&id=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-danger position-absolute top-0 start-0 m-2 rounded-circle"
                                   title="إزالة من المفضلة"
                                   style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; z-index: 10;">
                                    ✕
                                </a>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-secondary mb-1 align-self-start" style="font-size: 0.75rem;">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </span>
                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text mb-3 text-truncate"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center mb-3 mt-auto">
                                    <span class="price-badge"><?php echo number_format($product['price'], 0); ?> د.ع / <?php echo htmlspecialchars($product['unit']); ?></span>
                                    <span class="stock-badge">📦 <?php echo $product['stock']; ?></span>
                                </div>
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-details mb-2">👁️ تفاصيل المنتج</a>
                                <a href="#" onclick="addToCartAJAX(event, <?php echo $product['id']; ?>)" class="btn-add-cart">
                                    🛒 إضافة إلى السلة
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert-modern alert-warning-modern p-4 text-center rounded-3">
                        <div style="font-size: 3rem;">💔</div>
                        <h5 class="mt-2">قائمة المفضلة فارغة حالياً</h5>
                        <p class="mb-0 opacity-75">تصفح المتجر وأضف بعض المنتجات لشرائها لاحقاً.</p>
                        <a href="index.php" class="btn btn-success mt-3 fw-bold">🏪 اذهب للمتجر الآن</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
echo '</div><footer class="main-footer mt-5"><p class="mb-0">© 2024 الهايبر ماركت المتكامل — جميع الحقوق محفوظة | PR122-3</p></footer></div>';
echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script></body></html>';
?>
