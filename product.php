<?php
ob_start();
include 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if(!$product) {
    include 'header.php';
    echo "<div class='alert-modern alert-danger-modern p-4 text-center rounded-3'>❌ المنتج غير موجود!</div>";
    echo "</div></body></html>";
    exit;
}

// التحقق من المفضلة
$is_customer = isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer';
$in_wishlist = false;
if ($is_customer) {
    $wish_check = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $wish_check->execute([$_SESSION['user_id'], $id]);
    if ($wish_check->fetch()) {
        $in_wishlist = true;
    }
}

// معالجة إضافة تقييم جديد
$review_error = '';
$review_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!$is_customer) {
        $review_error = 'يجب عليك تسجيل الدخول كعميل لإضافة مراجعة.';
    } else {
        $rating = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $user_id = $_SESSION['user_id'];

        if ($rating < 1 || $rating > 5) {
            $review_error = 'يرجى اختيار تقييم بين 1 و 5 نجوم.';
        } else {
            try {
                // إدخال التقييم
                $ins_rev = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
                $ins_rev->execute([$id, $user_id, $rating, $comment]);
                $review_success = 'تم نشر تقييمك بنجاح! شكرًا لك.';
            } catch (\PDOException $e) {
                $review_error = 'حدث خطأ أثناء إضافة التقييم: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// جلب التقييمات الحالية
$reviews_stmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.username 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? 
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$id]);
$reviews = $reviews_stmt->fetchAll();

// حساب متوسط التقييمات
$avg_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM reviews WHERE product_id = ?");
$avg_stmt->execute([$id]);
$stats = $avg_stmt->fetch();
$avg_rating = round($stats['avg_rating'], 1) ?: 0;
$total_reviews = $stats['total_reviews'];

// جلب منتجات ذات صلة (في نفس القسم)
$related_stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 3");
$related_stmt->execute([$product['category_id'], $id]);
$related_products = $related_stmt->fetchAll();

// تحديد مصدر الصورة
$image_src = $product['image'];
if (strpos($image_src, 'http') !== 0) {
    $image_src = 'uploads/' . $image_src;
}

include 'header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4 fade-in-up">
    <ol class="breadcrumb" style="background: transparent; padding: 0;">
        <li class="breadcrumb-item">
            <a href="index.php" style="color: #16a34a; text-decoration: none;">الرئيسية</a>
        </li>
        <li class="breadcrumb-item">
            <a href="index.php?cat_id=<?php echo $product['category_id']; ?>" 
               style="color: #16a34a; text-decoration: none;">
                <?php echo htmlspecialchars($product['cat_name']); ?>
            </a>
        </li>
        <li class="breadcrumb-item active" style="color: #94a3b8;">
            <?php echo htmlspecialchars($product['name']); ?>
        </li>
    </ol>
</nav>

<div class="glass-card p-4 fade-in-up delay-1 mb-5">
    <div class="row g-4">
        <!-- صورة المنتج -->
        <div class="col-md-5">
            <div style="border-radius: 16px; overflow: hidden; position: relative;">
                <img src="<?php echo htmlspecialchars($image_src); ?>" 
                     class="img-fluid w-100" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     style="height: 380px; object-fit: cover;"
                     onerror="this.src='https://placehold.co/600x400/1e293b/4ade80?text=صورة+المنتج'">
                <div style="position: absolute; top: 15px; right: 15px;">
                    <span style="background: linear-gradient(135deg,#16a34a,#15803d); color:white; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 0.85rem;">
                        <?php echo htmlspecialchars($product['cat_name']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- تفاصيل المنتج -->
        <div class="col-md-7 d-flex flex-column justify-content-center">
            <div class="d-flex justify-content-between align-items-start">
                <h1 class="fw-bold mb-2" style="color: #f1f5f9; font-size: 2rem;">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>
                
                <!-- زر المفضلة -->
                <?php if ($is_customer): ?>
                    <a href="wishlist.php?action=<?php echo $in_wishlist ? 'remove' : 'add'; ?>&id=<?php echo $product['id']; ?>" 
                       class="btn btn-outline-danger border-0 fs-4" 
                       title="<?php echo $in_wishlist ? 'إزالة من المفضلة' : 'إضافة إلى المفضلة'; ?>">
                        <?php echo $in_wishlist ? '❤️' : '🤍'; ?>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-secondary border-0 fs-4" title="سجل دخول لإضافة المفضلة">
                        🤍
                    </a>
                <?php endif; ?>
            </div>

            <!-- عرض التقييم الإجمالي -->
            <div class="mb-3 d-flex align-items-center gap-2">
                <span class="text-warning fs-5">
                    <?php 
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= round($avg_rating) ? '★' : '☆';
                    }
                    ?>
                </span>
                <span class="text-white fw-bold small"><?php echo $avg_rating; ?> / 5</span>
                <span class="text-muted small">(<?php echo $total_reviews; ?> تقييم)</span>
            </div>

            <p style="color: #94a3b8; font-size: 1.05rem; line-height: 1.8; margin-bottom: 25px;">
                <?php echo htmlspecialchars($product['description']); ?>
            </p>

            <!-- السعر والمخزون -->
            <div class="d-flex align-items-center gap-3 mb-4">
                <div style="background: linear-gradient(135deg, rgba(22,163,74,0.2), rgba(21,128,61,0.1)); 
                            border: 1px solid rgba(22,163,74,0.4); 
                            border-radius: 16px; padding: 15px 25px;">
                    <div style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 4px;">السعر</div>
                    <div style="color: #4ade80; font-weight: 900; font-size: 2rem;">
                        <?php echo number_format($product['price'], 0); ?> <small style="font-size:1rem;">د.ع / <?php echo htmlspecialchars($product['unit']); ?></small>
                    </div>
                </div>

                <div style="background: rgba(30,41,59,0.6); border: 1px solid rgba(255,255,255,0.08); 
                            border-radius: 16px; padding: 15px 25px; text-align: center;">
                    <div style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 4px;">المخزون</div>
                    <div style="color: <?php echo $product['stock'] < 10 ? '#f87171' : '#60a5fa'; ?>; font-weight: 700; font-size: 1.5rem;">
                        <?php echo $product['stock']; ?> <small style="font-size:0.85rem;"><?php echo htmlspecialchars($product['unit']); ?></small>
                    </div>
                </div>
            </div>

            <!-- الباركود -->
            <div class="mb-4 p-3" style="background: rgba(15,23,42,0.6); border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1);">
                <span style="color:#94a3b8; font-size:0.85rem;">📦 رقم الباركود: </span>
                <code style="color: #fbbf24; font-size:0.95rem; font-weight: 700;">
                    <?php echo htmlspecialchars($product['barcode']); ?>
                </code>
            </div>

            <!-- أزرار الإجراء -->
            <div class="d-flex gap-3">
                <a href="#" onclick="addToCartAJAX(event, <?php echo $product['id']; ?>)" 
                   class="btn btn-lg fw-bold flex-fill"
                   style="background: linear-gradient(135deg,#16a34a,#15803d); border:none; color:white; 
                          border-radius: 14px; padding: 14px; transition: all 0.3s;"
                   onmouseover="this.style.boxShadow='0 8px 25px rgba(22,163,74,0.5)'; this.style.transform='translateY(-2px)'"
                   onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    🛒 إضافة إلى السلة
                </a>
                <a href="index.php" 
                   class="btn btn-lg fw-bold"
                   style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); 
                          color: #cbd5e1; border-radius: 14px; padding: 14px 25px; transition: all 0.3s;"
                   onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                   onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                    ← العودة
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- التقييمات والتعليقات -->
    <div class="col-md-7 fade-in-up delay-2">
        <h3 class="fw-bold text-white mb-3">💬 المراجعات والتقييمات (<?php echo $total_reviews; ?>)</h3>

        <!-- نموذج إضافة تقييم -->
        <div class="glass-card p-4 mb-4">
            <h5 class="fw-bold text-success mb-3">✍️ أضف مراجعة وتقييم</h5>
            
            <?php if ($review_error): ?>
                <div class="alert-modern alert-danger-modern mb-3">
                    ⚠️ <?php echo $review_error; ?>
                </div>
            <?php endif; ?>

            <?php if ($review_success): ?>
                <div class="alert-modern alert-success-modern mb-3">
                    ✅ <?php echo $review_success; ?>
                </div>
            <?php endif; ?>

            <?php if ($is_customer): ?>
                <form method="POST" action="product.php?id=<?php echo $id; ?>">
                    <div class="mb-3">
                        <label class="form-label">التقييم بالنجوم</label>
                        <select name="rating" class="form-select" required style="max-width: 200px;">
                            <option value="">اختر التقييم...</option>
                            <option value="5">⭐⭐⭐⭐⭐ (ممتاز)</option>
                            <option value="4">⭐⭐⭐⭐ (جيد جداً)</option>
                            <option value="3">⭐⭐⭐ (متوسط)</option>
                            <option value="2">⭐⭐ (مقبول)</option>
                            <option value="1">⭐ (ضعيف)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">التعليق والملاحظات</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="اكتب رأيك بالمنتج هنا..." required></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-success fw-bold px-4" style="background: linear-gradient(135deg, #16a34a, #15803d); border: none;">
                        نشر التعليق
                    </button>
                </form>
            <?php else: ?>
                <div class="p-3 text-center rounded-3 bg-secondary bg-opacity-10 text-muted">
                    🔐 يرجى <a href="login.php" class="text-success fw-bold text-decoration-none">تسجيل الدخول كعميل</a> للمشاركة وكتابة مراجعتك للمنتج.
                </div>
            <?php endif; ?>
        </div>

        <!-- قائمة التقييمات السابقة -->
        <div class="d-flex flex-column gap-3">
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $rev): ?>
                    <div class="p-3 rounded-3" style="background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.05);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-white mb-0"><?php echo htmlspecialchars($rev['full_name'] ?: $rev['username']); ?></h6>
                            <span class="text-warning">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rev['rating'] ? '★' : '☆';
                                }
                                ?>
                            </span>
                        </div>
                        <p class="mb-1 text-muted small" style="line-height: 1.6;"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                        <small class="text-secondary" style="font-size: 0.75rem;">📅 <?php echo date('Y-m-d H:i', strtotime($rev['created_at'])); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-4 text-center rounded-3 bg-secondary bg-opacity-10 text-muted">
                    لا توجد تعليقات أو مراجعات للمنتج بعد. كن أول من يقيّم المنتج!
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- منتجات ذات صلة -->
    <div class="col-md-5 fade-in-up delay-2">
        <h3 class="fw-bold text-white mb-3">🛍️ منتجات قد تعجبك</h3>
        <div class="d-flex flex-column gap-3">
            <?php if (count($related_products) > 0): ?>
                <?php foreach ($related_products as $rel_prod): ?>
                    <?php 
                    $rel_image = $rel_prod['image'];
                    if (strpos($rel_image, 'http') !== 0) {
                        $rel_image = 'uploads/' . $rel_image;
                    }
                    ?>
                    <a href="product.php?id=<?php echo $rel_prod['id']; ?>" class="text-decoration-none glass-card p-3 d-flex align-items-center gap-3">
                        <img src="<?php echo htmlspecialchars($rel_image); ?>" 
                             alt="<?php echo htmlspecialchars($rel_prod['name']); ?>" 
                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;"
                             onerror="this.src='https://placehold.co/100x100/1e293b/4ade80?text=منتج'">
                        <div class="flex-fill">
                            <h6 class="fw-bold text-white mb-1"><?php echo htmlspecialchars($rel_prod['name']); ?></h6>
                            <p class="text-muted small mb-2 text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($rel_prod['description']); ?></p>
                            <span class="price-badge py-1 px-3" style="font-size: 0.8rem;"><?php echo number_format($rel_prod['price'], 0); ?> د.ع</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-4 text-center rounded-3 bg-secondary bg-opacity-10 text-muted">
                    لا توجد منتجات أخرى في هذا القسم.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="main-footer mt-5">
    <p class="mb-0">© 2024 الهايبر ماركت المتكامل — جميع الحقوق محفوظة | PR122-3</p>
</footer>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
