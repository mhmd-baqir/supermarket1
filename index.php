<?php 
ob_start();
include 'config.php';
include 'header.php';

// جلب الأقسام للـ Sidebar
$cat_stmt = $pdo->query("SELECT * FROM categories");
$categories = $cat_stmt->fetchAll();

// جلب المنتجات (مع دعم الفلترة حسب القسم والبحث، وجلب التقييمات والمفضلة باستعلام واحد)
$category_filter = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : null;
$search_query    = isset($_GET['search']) ? trim($_GET['search']) : '';

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$is_customer = isset($_SESSION['role']) && $_SESSION['role'] === 'customer';

// تجهيز حقول الاستعلام
$select_fields = "p.*, 
                  COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id), 0) as avg_rating,
                  COALESCE((SELECT COUNT(r.id) FROM reviews r WHERE r.product_id = p.id), 0) as total_reviews";
if ($is_customer) {
    $select_fields .= ", EXISTS(SELECT 1 FROM wishlist w WHERE w.product_id = p.id AND w.user_id = $user_id) as in_wishlist";
} else {
    $select_fields .= ", 0 as in_wishlist";
}

if ($search_query) {
    $prod_stmt = $pdo->prepare("SELECT $select_fields FROM products p WHERE p.name LIKE ? OR p.description LIKE ?");
    $prod_stmt->execute(["%$search_query%", "%$search_query%"]);
} elseif ($category_filter) {
    $prod_stmt = $pdo->prepare("SELECT $select_fields FROM products p WHERE p.category_id = ?");
    $prod_stmt->execute([$category_filter]);
} else {
    $prod_stmt = $pdo->query("SELECT $select_fields FROM products p");
}
$products = $prod_stmt->fetchAll();
?>

<!-- HERO SLIDER / CAROUSEL -->
<div id="heroCarousel" class="carousel slide fade-in-up mb-4" data-bs-ride="carousel" style="border-radius: 20px; overflow: hidden; border: 1px solid rgba(22, 163, 74, 0.25);">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
  </div>
  <div class="carousel-inner">
    <!-- Slide 1 -->
    <div class="carousel-item active" style="height: 320px; background: linear-gradient(rgba(10,40,20,0.45), rgba(10,40,20,0.75)), url('https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80') center/cover;">
      <div class="carousel-caption d-flex flex-column h-100 align-items-center justify-content-center" style="bottom: 0;">
        <h2 class="fw-black text-success" style="font-weight: 900; text-shadow: 0 4px 10px rgba(0,0,0,0.8); font-size: 2.2rem;">🍏 الأغذية الطازجة يومياً</h2>
        <p class="text-white small mb-3" style="text-shadow: 0 2px 5px rgba(0,0,0,0.8); font-size: 1rem;">خضروات وفواكه ولحوم طازجة تصلك بأعلى جودة وأفضل سعر</p>
        <a href="index.php?cat_id=1" class="btn btn-success fw-bold px-4 py-2" style="background: linear-gradient(135deg, #16a34a, #15803d); border: none; border-radius: 8px;">تسوق الآن</a>
      </div>
    </div>
    <!-- Slide 2 -->
    <div class="carousel-item" style="height: 320px; background: linear-gradient(rgba(10,40,20,0.45), rgba(10,40,20,0.75)), url('https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1200&auto=format&fit=crop&q=80') center/cover;">
      <div class="carousel-caption d-flex flex-column h-100 align-items-center justify-content-center" style="bottom: 0;">
        <h2 class="fw-black text-warning" style="font-weight: 900; text-shadow: 0 4px 10px rgba(0,0,0,0.8); font-size: 2.2rem;">⚡ أحدث الأجهزة الإلكترونية</h2>
        <p class="text-white small mb-3" style="text-shadow: 0 2px 5px rgba(0,0,0,0.8); font-size: 1rem;">هواتف شواحن وسماعات من ماركات عالمية بضمان حقيقي</p>
        <a href="index.php?cat_id=4" class="btn btn-warning fw-bold px-4 py-2 text-dark" style="border-radius: 8px;">استعرض العروض</a>
      </div>
    </div>
    <!-- Slide 3 -->
    <div class="carousel-item" style="height: 320px; background: linear-gradient(rgba(10,40,20,0.45), rgba(10,40,20,0.75)), url('https://images.unsplash.com/photo-1583947215259-38e31be8751f?w=1200&auto=format&fit=crop&q=80') center/cover;">
      <div class="carousel-caption d-flex flex-column h-100 align-items-center justify-content-center" style="bottom: 0;">
        <h2 class="fw-black text-success" style="font-weight: 900; text-shadow: 0 4px 10px rgba(0,0,0,0.8); font-size: 2.2rem;">🧼 منظفات وعناية بالمنزل</h2>
        <p class="text-white small mb-3" style="text-shadow: 0 2px 5px rgba(0,0,0,0.8); font-size: 1rem;">كل ما يحتاجه منزلك للبقاء نظيفاً ومعقماً بأقل التكاليف</p>
        <a href="index.php?cat_id=3" class="btn btn-success fw-bold px-4 py-2" style="background: linear-gradient(135deg, #16a34a, #15803d); border: none; border-radius: 8px;">تصفح المنتجات</a>
      </div>
    </div>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
  </button>
</div>

<!-- شريط البحث السريع والفلترة -->
<div class="glass-card p-4 fade-in-up mb-4">
    <form method="GET" action="index.php" class="row justify-content-center g-2">
        <div class="col-md-8">
            <input type="text" name="search" class="form-control form-control-lg" 
                   placeholder="🔍 ابحث عن منتج من آلاف المواد المتوفرة لدينا..." 
                   value="<?php echo htmlspecialchars($search_query); ?>"
                   style="border-radius: 12px !important;">
        </div>
        <div class="col-md-2">
            <button class="btn btn-success fw-bold w-100 py-3" type="submit" 
                    style="border-radius: 12px; background: linear-gradient(135deg,#16a34a,#15803d); border:none;">
                بحث
            </button>
        </div>
    </form>
</div>

<div class="row">
    <!-- SIDEBAR CATEGORIES -->
    <div class="col-md-3 mb-4 fade-in-up delay-1">
        <div class="sidebar-card">
            <h6 class="sidebar-title">📂 الأقسام</h6>
            <a href="index.php" class="sidebar-link <?php echo (!$category_filter && !$search_query) ? 'active' : ''; ?>">
                🏪 كل الأقسام
            </a>
            <?php foreach($categories as $cat): ?>
                <a href="index.php?cat_id=<?php echo $cat['id']; ?>" 
                   class="sidebar-link <?php echo $category_filter == $cat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- PRODUCTS GRID -->
    <div class="col-md-9 fade-in-up delay-2">
        <?php if($search_query): ?>
            <div class="alert-modern alert-info-modern mb-3 p-3 rounded-3">
                🔍 نتائج البحث عن: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong>
                — وُجد <?php echo count($products); ?> منتج
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $i => $product): ?>
                    <?php 
                    $image_src = $product['image'];
                    if (strpos($image_src, 'http') !== 0) {
                        $image_src = 'uploads/' . $image_src;
                    }
                    ?>
                    <div class="col-md-4 col-sm-6 fade-in-up" style="animation-delay: <?php echo $i * 0.04; ?>s">
                        <div class="product-card h-100 d-flex flex-column" style="position: relative;">
                            
                            <!-- صورة المنتج مع زر المفضلة العائم -->
                            <div style="position: relative; overflow: hidden; border-radius: 16px 16px 0 0;">
                                <img src="<?php echo htmlspecialchars($image_src); ?>" 
                                     class="card-img-top w-100" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     style="height: 190px; object-fit: cover;"
                                     onerror="this.src='https://placehold.co/400x200/d1fae5/059669?text=\u0645\u0646\u062a\u062c'">
                                
                                <!-- زر المفضلة العائم -->
                                <?php if ($is_customer): ?>
                                    <a href="wishlist.php?action=<?php echo $product['in_wishlist'] ? 'remove' : 'add'; ?>&id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm position-absolute top-0 start-0 m-2 rounded-circle shadow"
                                       style="background: rgba(255,255,255,0.85); border: 1px solid rgba(16,185,129,0.2); width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; z-index: 5;"
                                       title="<?php echo $product['in_wishlist'] ? 'إزالة من المفضلة' : 'إضافة إلى المفضلة'; ?>">
                                        <?php echo $product['in_wishlist'] ? '❤️' : '🤍'; ?>
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" 
                                       class="btn btn-sm position-absolute top-0 start-0 m-2 rounded-circle shadow"
                                       style="background: rgba(255,255,255,0.85); border: 1px solid rgba(16,185,129,0.2); width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; z-index: 5;"
                                       title="سجل دخول لحفظ المفضلة">
                                        🤍
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="card-body d-flex flex-column flex-fill">
                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($product['name']); ?></h5>
                                
                                <!-- التقييم النجمي للمنتج -->
                                <div class="mb-2 text-warning small" style="font-size: 0.8rem;">
                                    <?php 
                                    $rating_val = round($product['avg_rating']);
                                    for ($star = 1; $star <= 5; $star++) {
                                        echo $star <= $rating_val ? '★' : '☆';
                                    }
                                    ?>
                                    <span class="text-secondary ms-1">(<?php echo $product['total_reviews']; ?>)</span>
                                </div>

                                <p class="card-text mb-3 text-truncate" style="font-size: 0.82rem; color: #94a3b8;"><?php echo htmlspecialchars($product['description']); ?></p>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3 mt-auto">
                                    <span class="price-badge"><?php echo number_format($product['price'], 0); ?> د.ع / <?php echo htmlspecialchars($product['unit']); ?></span>
                                    
                                    <?php if($product['stock'] < 10): ?>
                                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 small px-2 py-1" style="border-radius: 20px; font-size: 0.75rem;">📦 متبقي <?php echo $product['stock']; ?></span>
                                    <?php else: ?>
                                        <span class="stock-badge">📦 <?php echo $product['stock']; ?></span>
                                    <?php endif; ?>
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
                        <div style="font-size: 3rem;">📭</div>
                        <h5 class="mt-2">لا توجد منتجات متوفرة حالياً</h5>
                        <p class="mb-0 opacity-75">جرّب قسماً آخر أو تحقق لاحقاً</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- FOOTER -->
include 'footer.php';
