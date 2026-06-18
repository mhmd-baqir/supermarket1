<?php
ob_start();
include 'config.php';

if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id     = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. إضافة منتج للسلة
if($action == 'add' && $id > 0) {
    if(isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]++;
    } else {
        $_SESSION['cart'][$id] = 1;
    }
    // توجيه المستخدم مباشرة إلى السلة لكي يراها
    header('Location: cart.php');
    exit;
}

// 2. زيادة الكمية
if($action == 'increase' && $id > 0) {
    if(isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]++;
    }
    header('Location: cart.php');
    exit;
}

// 3. تقليل الكمية
if($action == 'decrease' && $id > 0) {
    if(isset($_SESSION['cart'][$id]) && $_SESSION['cart'][$id] > 1) {
        $_SESSION['cart'][$id]--;
    } elseif(isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
    header('Location: cart.php');
    exit;
}

// 4. إزالة منتج من السلة
if($action == 'remove' && $id > 0) {
    unset($_SESSION['cart'][$id]);
    header('Location: cart.php');
    exit;
}

// 5. إفراغ السلة
if($action == 'clear') {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

include 'header.php';
?>

<h1 class="page-title fade-in-up">🛒 سلة التسوق</h1>

<?php if(!empty($_SESSION['cart'])): ?>
    <div class="row g-4">
        <div class="col-lg-8 fade-in-up delay-1">
            <div class="glass-card p-0 overflow-hidden">
                <div class="p-3" style="background: rgba(22,163,74,0.1); border-bottom: 1px solid rgba(22,163,74,0.2);">
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="color: #4ade80; font-weight: 700;">
                            📋 المنتجات في السلة (<?php echo count($_SESSION['cart']); ?> صنف)
                        </span>
                        <a href="cart.php?action=clear" 
                           class="btn btn-sm fw-bold"
                           style="background: rgba(220,38,38,0.2); color: #f87171; border: 1px solid rgba(220,38,38,0.3); border-radius: 8px;"
                           onclick="return confirm('هل تريد إفراغ السلة بالكامل؟')">
                           🗑️ إفراغ السلة
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table modern-table mb-0">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th class="text-center">السعر</th>
                                <th class="text-center">الكمية</th>
                                <th class="text-center">الإجمالي</th>
                                <th class="text-center">إزالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_price = 0;
                            foreach($_SESSION['cart'] as $prod_id => $qty): 
                                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                                $stmt->execute([$prod_id]);
                                $prod = $stmt->fetch();
                                if(!$prod) continue;
                                $subtotal = $prod['price'] * $qty;
                                $total_price += $subtotal;
                                
                                // معالجة الصورة
                                $image_src = $prod['image'];
                                if (strpos($image_src, 'http') !== 0) {
                                    $image_src = 'uploads/' . $image_src;
                                }
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?php echo htmlspecialchars($image_src); ?>" 
                                             width="55" height="55" 
                                             style="object-fit:cover; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1);"
                                             onerror="this.src='https://placehold.co/55x55/1e293b/4ade80?text=📦'">
                                        <div>
                                            <div style="color: #f1f5f9; font-weight: 700; font-size: 0.95rem;">
                                                <?php echo htmlspecialchars($prod['name']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center" style="color: #94a3b8;">
                                    <?php echo number_format($prod['price'], 0); ?> د.ع / <?php echo htmlspecialchars($prod['unit']); ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <a href="cart.php?action=decrease&id=<?php echo $prod['id']; ?>" 
                                           class="btn btn-sm" 
                                           style="background: rgba(255,255,255,0.08); color: #cbd5e1; border-radius: 8px; width:30px; height:30px; display:flex; align-items:center; justify-content:center; text-decoration:none; font-weight:700;">−</a>
                                        <span style="background: rgba(22,163,74,0.2); color: #4ade80; padding: 4px 14px; border-radius: 8px; font-weight: 700; font-size: 0.95rem;">
                                            <?php echo $qty; ?> <?php echo htmlspecialchars($prod['unit']); ?>
                                        </span>
                                        <a href="cart.php?action=increase&id=<?php echo $prod['id']; ?>" 
                                           class="btn btn-sm" 
                                           style="background: rgba(255,255,255,0.08); color: #cbd5e1; border-radius: 8px; width:30px; height:30px; display:flex; align-items:center; justify-content:center; text-decoration:none; font-weight:700;">+</a>
                                    </div>
                                </td>
                                <td class="text-center fw-bold" style="color: #4ade80;">
                                    <?php echo number_format($subtotal, 0); ?> د.ع
                                </td>
                                <td class="text-center">
                                    <a href="cart.php?action=remove&id=<?php echo $prod['id']; ?>" 
                                       class="btn btn-sm"
                                       style="background: rgba(220,38,38,0.2); color: #f87171; border: 1px solid rgba(220,38,38,0.3); border-radius: 8px;"
                                       onclick="return confirm('إزالة هذا المنتج؟')">🗑️</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ملخص الطلب -->
        <div class="col-lg-4 fade-in-up delay-2">
            <div class="glass-card p-4 sticky-top" style="top: 90px;">
                <h5 class="fw-bold mb-4" style="color: #f1f5f9;">📊 ملخص الطلب</h5>

                <div class="d-flex justify-content-between mb-3 pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <span style="color: #94a3b8;">عدد الأصناف</span>
                    <span style="color: #f1f5f9; font-weight: 700;"><?php echo count($_SESSION['cart']); ?> صنف</span>
                </div>
                <div class="d-flex justify-content-between mb-3 pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <span style="color: #94a3b8;">إجمالي القطع</span>
                    <span style="color: #f1f5f9; font-weight: 700;"><?php echo array_sum($_SESSION['cart']); ?> قطعة</span>
                </div>
                <div class="d-flex justify-content-between mb-4 py-3 px-3" 
                     style="background: rgba(22,163,74,0.1); border-radius: 12px; border: 1px solid rgba(22,163,74,0.2);">
                    <span style="color: #4ade80; font-weight: 700; font-size: 1.1rem;">المجموع الكلي</span>
                    <span style="color: #4ade80; font-weight: 900; font-size: 1.3rem;">
                        <?php echo number_format($total_price, 0); ?> د.ع
                    </span>
                </div>

                <a href="checkout.php" 
                   class="btn btn-lg w-100 fw-bold"
                   style="background: linear-gradient(135deg,#16a34a,#15803d); color:white; border:none; border-radius:14px; padding:14px;">
                    💳 إتمام الشراء
                </a>
                <a href="index.php" class="btn w-100 mt-2" 
                   style="background: rgba(255,255,255,0.05); color: #94a3b8; border: 1px solid rgba(255,255,255,0.1); border-radius:14px;">
                    ← مواصلة التسوق
                </a>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="text-center py-5 fade-in-up">
        <div style="font-size: 6rem; margin-bottom: 20px;">🛒</div>
        <h3 class="fw-bold mb-3" style="color: #f1f5f9;">سلة التسوق فارغة!</h3>
        <p class="mb-4" style="color: #94a3b8;">لم تقم بإضافة أي منتجات بعد. تفضل واستعرض تشكيلتنا الرائعة!</p>
        <a href="index.php" 
           class="btn btn-lg fw-bold"
           style="background: linear-gradient(135deg,#16a34a,#15803d); color:white; border:none; border-radius:14px; padding: 14px 35px;">
            🏪 تصفح المنتجات
        </a>
    </div>
<?php endif; ?>

<!-- FOOTER -->
<footer class="main-footer mt-5">
    <p class="mb-0">© 2024 الهايبر ماركت المتكامل — جميع الحقوق محفوظة | PR122-3</p>
</footer>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
