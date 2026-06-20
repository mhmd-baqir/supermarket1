<?php 
ob_start();
include 'config.php';
include 'header.php';

// جلب الأقسام
$cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY id");
$categories = $cat_stmt->fetchAll();

// فلترة المنتجات
$category_filter = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : null;
$search_query    = isset($_GET['search']) ? trim($_GET['search']) : '';

$user_id     = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$is_customer = isset($_SESSION['role']) && $_SESSION['role'] === 'customer';

$select_fields = "p.*, 
                  COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id), 0) as avg_rating,
                  COALESCE((SELECT COUNT(r.id) FROM reviews r WHERE r.product_id = p.id), 0) as total_reviews";
if ($is_customer) {
    $select_fields .= ", EXISTS(SELECT 1 FROM wishlist w WHERE w.product_id = p.id AND w.user_id = $user_id) as in_wishlist";
} else {
    $select_fields .= ", 0 as in_wishlist";
}

if ($search_query) {
    $prod_stmt = $pdo->prepare("SELECT $select_fields FROM products p WHERE p.name LIKE ? OR p.description LIKE ? ORDER BY p.id DESC");
    $prod_stmt->execute(["%$search_query%", "%$search_query%"]);
} elseif ($category_filter) {
    $prod_stmt = $pdo->prepare("SELECT $select_fields FROM products p WHERE p.category_id = ? ORDER BY p.id DESC");
    $prod_stmt->execute([$category_filter]);
} else {
    $prod_stmt = $pdo->query("SELECT $select_fields FROM products p ORDER BY p.id DESC");
}
$products = $prod_stmt->fetchAll();

// تحديث حالة المفضلة لزوار الجلسة المؤقتة
if (!$is_customer && isset($_SESSION['guest_wishlist']) && is_array($_SESSION['guest_wishlist'])) {
    foreach ($products as &$product) {
        if (in_array($product['id'], $_SESSION['guest_wishlist'])) {
            $product['in_wishlist'] = 1;
        }
    }
    unset($product);
}

// أفضل 6 منتجات للعروض
$featured_stmt = $pdo->query("SELECT p.*, COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id), 0) as avg_rating FROM products p ORDER BY p.id DESC LIMIT 6");
$featured_products = $featured_stmt->fetchAll();

$cat_icons = ['🥩','🥦','🧀','🥫','🍬','🧃','🧹','📦','🍗','🛒','🏠','🌿'];
?>

<style>
/* ===== CAROUSEL ===== */
#heroCarousel { border-radius: 22px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.18); margin-bottom: 22px; border: 1px solid rgba(22,163,74,0.2); }

/* ===== DEAL BADGE ===== */
.deal-badge {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white; font-weight: 900; font-size: 0.72rem;
    padding: 4px 14px; border-radius: 30px; letter-spacing: 0.5px;
    display: inline-block; animation: pulse-deal 1.6s ease-in-out infinite;
    box-shadow: 0 4px 14px rgba(245,158,11,0.5);
}
@keyframes pulse-deal {
    0%,100%{ transform:scale(1); } 50%{ transform:scale(1.07); }
}

/* ===== FLASH DEALS SECTION ===== */
.deals-section {
    background: linear-gradient(135deg, #10b981 0%, #059669 60%, #047857 100%);
    border-radius: 22px; padding: 26px 30px;
    position: relative; overflow: hidden; margin-bottom: 24px;
}
.deals-section::before {
    content:''; position:absolute; top:-70px; right:-70px;
    width:240px; height:240px; background:rgba(255,255,255,0.07); border-radius:50%;
}
.deals-section::after {
    content:''; position:absolute; bottom:-90px; left:-50px;
    width:300px; height:300px; background:rgba(255,255,255,0.05); border-radius:50%;
}
.countdown-box {
    background: rgba(255,255,255,0.18); backdrop-filter:blur(10px);
    border:1px solid rgba(255,255,255,0.25); border-radius:12px;
    padding: 8px 13px; text-align:center; min-width:55px;
}
.countdown-num { font-size:1.6rem; font-weight:900; color:#fff; line-height:1; display:block; }
.countdown-label { font-size:0.6rem; color:rgba(255,255,255,0.8); display:block; margin-top:2px; }
.offer-card {
    background: rgba(255,255,255,0.13); backdrop-filter:blur(10px);
    border:1px solid rgba(255,255,255,0.22); border-radius:16px;
    padding: 14px; transition: all 0.3s; text-decoration:none;
    display:block; color:white;
}
.offer-card:hover { background:rgba(255,255,255,0.24); transform:translateY(-5px); box-shadow:0 16px 40px rgba(0,0,0,0.2); color:white; }
.offer-card img { width:65px; height:65px; object-fit:cover; border-radius:10px; flex-shrink:0; border:2px solid rgba(255,255,255,0.3); }

/* ===== PROMO BANNERS ===== */
.promo-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px; }
.promo-banner {
    border-radius:18px; overflow:hidden; position:relative; min-height:160px;
    cursor:pointer; text-decoration:none; display:block;
    transition: transform 0.3s, box-shadow 0.3s;
}
.promo-banner:hover { transform:translateY(-4px); box-shadow:0 20px 50px rgba(0,0,0,0.25); }
.promo-banner img { width:100%; height:160px; object-fit:cover; display:block; }
.promo-overlay {
    position:absolute; inset:0;
    display:flex; flex-direction:column; justify-content:flex-end; padding:18px;
}
.promo-tag {
    background: rgba(255,255,255,0.2); backdrop-filter:blur(8px);
    border:1px solid rgba(255,255,255,0.3); color:white;
    font-size:0.65rem; font-weight:800; padding:3px 10px;
    border-radius:20px; display:inline-block; margin-bottom:6px; width:fit-content;
}

/* ===== STICKY CATEGORY BAR ===== */
.sticky-cats-wrapper {
    position: sticky;
    top: 65px;
    z-index: 500;
    margin-bottom: 20px;
}
.sticky-cats-inner {
    background: var(--navbar-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 12px 16px;
    box-shadow: var(--shadow-md);
    overflow-x: auto;
    scrollbar-width: none;
}
.sticky-cats-inner::-webkit-scrollbar { display: none; }
.cats-scroll-row {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: nowrap;
    min-width: max-content;
}
.cat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 16px;
    border-radius: 30px;
    font-size: 0.82rem;
    font-weight: 700;
    white-space: nowrap;
    text-decoration: none;
    color: var(--text-main);
    background: var(--card-bg);
    border: 1.5px solid var(--card-border);
    transition: all 0.22s;
    cursor: pointer;
}
.cat-pill:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: var(--primary-light);
    transform: translateY(-2px);
}
.cat-pill.active {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white !important;
    border-color: transparent;
    box-shadow: 0 6px 18px rgba(16,185,129,0.4);
}
body.dark-mode .cat-pill.active { color: white !important; }
body.dark-mode .cat-pill:hover { background: rgba(16,185,129,0.15); }

/* ===== SECTION HEADER ===== */
.section-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.section-title { font-size:1.2rem; font-weight:900; color:var(--text-main); display:flex; align-items:center; gap:8px; }
.section-title::before { content:''; display:block; width:4px; height:22px; background:linear-gradient(180deg,var(--primary),var(--primary-dark)); border-radius:4px; }

/* ===== PRODUCT CARDS ===== */
.discount-ribbon { position:absolute; top:12px; left:0; background:linear-gradient(135deg,#dc2626,#b91c1c); color:white; font-size:0.68rem; font-weight:900; padding:3px 10px; border-radius:0 6px 6px 0; box-shadow:2px 2px 8px rgba(220,38,38,0.4); z-index:6; }
.new-ribbon { position:absolute; top:12px; left:0; background:linear-gradient(135deg,#2563eb,#1d4ed8); color:white; font-size:0.68rem; font-weight:900; padding:3px 10px; border-radius:0 6px 6px 0; z-index:6; }

/* ===== WHY US ===== */
.why-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-top:32px; }
.why-item { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; padding:18px 14px; text-align:center; box-shadow:var(--shadow-sm); transition:all 0.3s; }
.why-item:hover { transform:translateY(-4px); box-shadow:var(--shadow-md); border-color:var(--primary); }
.why-item .wi-icon { font-size:2.2rem; margin-bottom:8px; display:block; }
.why-item .wi-title { font-weight:800; color:var(--primary); font-size:0.9rem; margin-bottom:3px; }
.why-item .wi-desc { font-size:0.75rem; color:var(--text-muted); }

@media(max-width:768px){
    .promo-grid { grid-template-columns:1fr; }
    .why-strip { grid-template-columns:1fr 1fr; }
    .sticky-cats-wrapper { top: 60px; }
}
</style>

<!-- ===== HERO CAROUSEL ===== -->
<div id="heroCarousel" class="carousel slide fade-in-up" data-bs-ride="carousel" data-bs-interval="4500">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="3"></button>
  </div>
  <div class="carousel-inner">
    <div class="carousel-item active" style="height:340px;background:linear-gradient(rgba(5,30,10,0.55),rgba(5,30,10,0.82)),url('https://images.unsplash.com/photo-1588347818133-1d4b4c0f3a50?w=1400&auto=format&fit=crop&q=85')center/cover;">
      <div class="carousel-caption d-flex flex-column h-100 align-items-center justify-content-center" style="bottom:0;">
        <span class="deal-badge mb-3">🔥 عروض يومية حصرية</span>
        <h2 class="fw-black text-white" style="font-weight:900;text-shadow:0 4px 15px rgba(0,0,0,0.9);font-size:2.3rem;">🥩 لحوم بلدية طازجة يومياً</h2>
        <p class="text-white mb-4" style="text-shadow:0 2px 8px rgba(0,0,0,0.9);font-size:1rem;max-width:500px;">أجود اللحوم العراقية الطازجة مباشرة من المزرعة إلى مائدتكم</p>
        <div class="d-flex gap-3">
          <a href="index.php?cat_id=1" class="btn btn-success fw-bold px-5 py-2" style="background:linear-gradient(135deg,#16a34a,#15803d);border:none;border-radius:10px;">تسوق الآن</a>
          <a href="about.php" class="btn fw-bold px-4 py-2" style="background:rgba(255,255,255,0.15);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.3);border-radius:10px;color:white;">تعرف علينا</a>
        </div>
      </div>
    </div>
    <div class="carousel-item" style="height:340px;background:linear-gradient(rgba(5,40,5,0.5),rgba(5,40,5,0.78)),url('https://images.unsplash.com/photo-1542838132-92c53300491e?w=1400&auto=format&fit=crop&q=85')center/cover;">
      <div class="carousel-caption d-flex flex-column h-100 align-items-center justify-content-center" style="bottom:0;">
        <span class="deal-badge mb-3">🌿 وصل حديثاً</span>
        <h2 class="fw-black text-success" style="font-weight:900;text-shadow:0 4px 15px rgba(0,0,0,0.9);font-size:2.3rem;">🥬 خضار وفواكه قطاف اليوم</h2>
        <p class="text-white mb-4" style="text-shadow:0 2px 8px rgba(0,0,0,0.9);font-size:1rem;max-width:500px;">طازجة كل صباح من المزارع مباشرة — صحة ونكهة لا مثيل لها</p>
        <a href="index.php?cat_id=2" class="btn btn-success fw-bold px-5 py-2" style="background:linear-gradient(135deg,#16a34a,#15803d);border:none;border-radius:10px;">اكتشف المنتجات</a>
      </div>
    </div>
    <div class="carousel-item" style="height:340px;background:linear-gradient(rgba(0,30,60,0.6),rgba(0,20,40,0.87)),url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1400&auto=format&fit=crop&q=85')center/cover;">
      <div class="carousel-caption d-flex flex-column h-100 align-items-center justify-content-center" style="bottom:0;">
        <span style="background:linear-gradient(135deg,#f59e0b,#d97706);color:white;font-weight:900;font-size:0.72rem;padding:4px 14px;border-radius:30px;display:inline-block;margin-bottom:14px;">⚡ سرعة التوصيل</span>
        <h2 class="fw-black text-warning" style="font-weight:900;text-shadow:0 4px 15px rgba(0,0,0,0.9);font-size:2.3rem;">🚚 توصيل لباب بيتك</h2>
        <p class="text-white mb-4" style="text-shadow:0 2px 8px rgba(0,0,0,0.9);font-size:1rem;max-width:500px;">فريق دليفري يغطي جميع أحياء كربلاء المقدسة بأسرع وقت</p>
        <a href="index.php" class="btn btn-warning fw-bold px-5 py-2 text-dark" style="border-radius:10px;">اطلب الآن</a>
      </div>
    </div>
    <div class="carousel-item" style="height:340px;background:linear-gradient(rgba(10,20,40,0.55),rgba(10,20,40,0.82)),url('https://images.unsplash.com/photo-1583947215259-38e31be8751f?w=1400&auto=format&fit=crop&q=85')center/cover;">
      <div class="carousel-caption d-flex flex-column h-100 align-items-center justify-content-center" style="bottom:0;">
        <span class="deal-badge mb-3">✨ منتجات جديدة</span>
        <h2 class="fw-black text-white" style="font-weight:900;text-shadow:0 4px 15px rgba(0,0,0,0.9);font-size:2.3rem;">🧴 مواد منزلية وتنظيف</h2>
        <p class="text-white mb-4" style="text-shadow:0 2px 8px rgba(0,0,0,0.9);font-size:1rem;max-width:500px;">كل ما يحتاجه منزلك من منظفات ومعقمات بأفضل الأسعار</p>
        <a href="index.php?cat_id=7" class="btn btn-light fw-bold px-5 py-2 text-dark" style="border-radius:10px;">تصفح المنتجات</a>
      </div>
    </div>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
  <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
</div>

<!-- ===== شريط البحث ===== -->
<div class="glass-card p-4 fade-in-up mb-4">
    <form method="GET" action="index.php" class="row justify-content-center g-2 align-items-center">
        <div class="col-md-8">
            <input type="text" name="search" class="form-control form-control-lg"
                   placeholder="🔍 ابحث عن منتج من آلاف المواد المتوفرة لدينا..."
                   value="<?php echo htmlspecialchars($search_query); ?>"
                   style="border-radius:12px !important;">
        </div>
        <div class="col-md-2">
            <button class="btn btn-success fw-bold w-100 py-3" type="submit"
                    style="border-radius:12px;background:linear-gradient(135deg,#16a34a,#15803d);border:none;">
                🔍 بحث
            </button>
        </div>
    </form>
</div>

<!-- ===== عروض اليوم ===== -->
<?php if(!$search_query && !$category_filter && count($featured_products) > 0): ?>
<div class="deals-section fade-in-up">
    <div class="row align-items-center mb-4">
        <div class="col">
            <span class="deal-badge mb-2">🔥 لا تفوّت الفرصة</span>
            <h3 class="text-white fw-black mt-2 mb-0" style="font-size:1.45rem;">⚡ وصل جديد — أطازج المنتجات</h3>
            <p style="color:rgba(255,255,255,0.82);font-size:0.83rem;margin-top:4px;">منتجات مختارة بعناية لتناسب ذوق وميزانية عائلتكم الكريمة</p>
        </div>
        <div class="col-auto d-none d-md-block">
            <div class="d-flex gap-2 align-items-center">
                <div class="countdown-box"><span class="countdown-num" id="cd-h">00</span><span class="countdown-label">ساعة</span></div>
                <span class="text-white fw-black fs-5">:</span>
                <div class="countdown-box"><span class="countdown-num" id="cd-m">00</span><span class="countdown-label">دقيقة</span></div>
                <span class="text-white fw-black fs-5">:</span>
                <div class="countdown-box"><span class="countdown-num" id="cd-s">00</span><span class="countdown-label">ثانية</span></div>
            </div>
        </div>
    </div>
    <div class="row g-3">
        <?php foreach($featured_products as $fp):
            $fp_img = $fp['image'];
            if(strpos($fp_img,'http')!==0) $fp_img='uploads/'.$fp_img;
            $stars = round($fp['avg_rating']);
        ?>
        <div class="col-md-4 col-sm-6 col-lg-2" style="min-width:160px; flex:1;">
            <a href="product.php?id=<?php echo $fp['id']; ?>" class="offer-card">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="<?php echo htmlspecialchars($fp_img); ?>" alt="<?php echo htmlspecialchars($fp['name']); ?>"
                         onerror="this.src='https://placehold.co/65x65/d1fae5/059669?text=منتج'">
                    <div class="flex-fill">
                        <div style="font-weight:700;font-size:0.85rem;line-height:1.3;"><?php echo htmlspecialchars($fp['name']); ?></div>
                        <div style="color:rgba(255,255,255,0.75);font-size:0.7rem;margin-top:2px;">
                            <?php for($s=1;$s<=5;$s++) echo $s<=$stars?'★':'☆'; ?>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <span style="color:#fde68a;font-weight:900;font-size:0.95rem;"><?php echo number_format($fp['price'],0); ?> <small style="font-size:0.65rem;opacity:0.85;">د.ع</small></span>
                    <span style="background:rgba(255,255,255,0.2);color:white;font-size:0.67rem;padding:3px 8px;border-radius:20px;font-weight:700;">🛒 اطلب</span>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ===== بانرات العروض الترويجية ===== -->
<div class="promo-grid fade-in-up">
    <a href="index.php?cat_id=1" class="promo-banner">
        <img src="https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?w=700&auto=format&fit=crop&q=80" alt="عروض اللحوم">
        <div class="promo-overlay" style="background:linear-gradient(to top,rgba(5,150,105,0.92),rgba(5,150,105,0.3));">
            <span class="promo-tag">🔥 عرض اليوم</span>
            <h4 class="text-white fw-black mb-1" style="font-size:1.25rem;text-shadow:0 2px 8px rgba(0,0,0,0.5);">اللحوم والمشويات</h4>
            <p class="text-white mb-0" style="font-size:0.82rem;opacity:0.9;">أجود اللحوم البلدية الطازجة يومياً</p>
        </div>
    </a>
    <a href="index.php?cat_id=2" class="promo-banner">
        <img src="https://images.unsplash.com/photo-1610348725531-843dff563e2c?w=700&auto=format&fit=crop&q=80" alt="عروض الخضار">
        <div class="promo-overlay" style="background:linear-gradient(to top,rgba(6,78,59,0.92),rgba(6,78,59,0.3));">
            <span class="promo-tag">🌿 طازج يومياً</span>
            <h4 class="text-white fw-black mb-1" style="font-size:1.25rem;text-shadow:0 2px 8px rgba(0,0,0,0.5);">خضار وفواكه</h4>
            <p class="text-white mb-0" style="font-size:0.82rem;opacity:0.9;">قطاف اليوم من المزارع مباشرة</p>
        </div>
    </a>
</div>
<?php endif; ?>

<!-- ===== شريط الأقسام الثابت (Sticky) ===== -->
<div class="sticky-cats-wrapper fade-in-up">
    <div class="sticky-cats-inner">
        <div class="cats-scroll-row">
            <a href="index.php" class="cat-pill <?php echo (!$category_filter && !$search_query)?'active':''; ?>">
                🏪 كل الأقسام
            </a>
            <?php foreach($categories as $ci => $cat): ?>
            <a href="index.php?cat_id=<?php echo $cat['id']; ?>"
               class="cat-pill <?php echo $category_filter==$cat['id']?'active':''; ?>">
                <?php echo ($cat_icons[$ci % count($cat_icons)] ?? '📦') . ' ' . htmlspecialchars($cat['name']); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ===== شبكة المنتجات ===== -->
<div class="fade-in-up">
    <div class="section-header mb-3">
        <div class="section-title">
            <?php if($search_query): ?>🔍 نتائج البحث
            <?php elseif($category_filter):
                $active_cat = array_filter($categories, fn($c)=>$c['id']==$category_filter);
                $active_cat = reset($active_cat);
                echo '📂 '.htmlspecialchars($active_cat['name']??'القسم');
            else: ?>📦 جميع المنتجات
            <?php endif; ?>
        </div>
        <span style="background:var(--primary-light);color:var(--primary-dark);font-size:0.78rem;font-weight:700;padding:5px 14px;border-radius:20px;border:1px solid var(--primary-border);">
            <?php echo count($products); ?> منتج
        </span>
    </div>

    <?php if($search_query): ?>
    <div class="alert-modern alert-info-modern mb-3 p-3 rounded-3">
        🔍 نتائج البحث عن: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong> — <?php echo count($products); ?> نتيجة
        <a href="index.php" class="ms-2 text-decoration-none fw-bold" style="color:var(--primary);">✕ مسح</a>
    </div>
    <?php endif; ?>

    <div class="row g-3">
        <?php if(count($products) > 0): ?>
            <?php foreach($products as $i => $product):
                $image_src = $product['image'];
                if(strpos($image_src,'http')!==0) $image_src='uploads/'.$image_src;
                $rating_val = round($product['avg_rating']);
                $is_new = ($i < 8);
            ?>
            <div class="col-xl-3 col-md-4 col-sm-6 fade-in-up" style="animation-delay:<?php echo min($i*0.025,0.4); ?>s">
                <div class="product-card h-100 d-flex flex-column" style="position:relative;">
                    <?php if($is_new && !$category_filter): ?>
                        <div class="new-ribbon">✨ جديد</div>
                    <?php elseif($product['stock']>0 && $product['stock']<10): ?>
                        <div class="discount-ribbon">📦 متبقي <?php echo $product['stock']; ?></div>
                    <?php endif; ?>

                    <div style="position:relative;overflow:hidden;border-radius:16px 16px 0 0;">
                        <img src="<?php echo htmlspecialchars($image_src); ?>"
                             class="card-img-top w-100"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="height:190px;object-fit:cover;transition:transform 0.4s;"
                             onmouseover="this.style.transform='scale(1.06)'"
                             onmouseout="this.style.transform='scale(1)'"
                             onerror="this.src='https://placehold.co/400x200/d1fae5/059669?text=منتج'">

                        <?php 
                        $show_wishlist_btn = (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin');
                        if($show_wishlist_btn): ?>
                            <button onclick="toggleWishlist(this, <?php echo $product['id']; ?>, <?php echo $product['in_wishlist'] ? 1 : 0; ?>)"
                               class="btn btn-sm position-absolute top-0 start-0 m-2 rounded-circle shadow wishlist-btn"
                               style="background:rgba(255,255,255,0.9);border:1px solid rgba(16,185,129,0.2);width:34px;height:34px;display:flex;align-items:center;justify-content:center;z-index:5;cursor:pointer;transition:all 0.3s;">
                                <?php echo $product['in_wishlist']?'❤️':'🤍'; ?>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-sm position-absolute top-0 start-0 m-2 rounded-circle shadow" disabled
                               style="background:rgba(255,255,255,0.9);border:1px solid rgba(16,185,129,0.2);width:34px;height:34px;display:flex;align-items:center;justify-content:center;z-index:5;opacity:0.5;">🤍</button>
                        <?php endif; ?>
                    </div>

                    <div class="card-body d-flex flex-column flex-fill">
                        <h5 class="card-title mb-1" style="font-size:0.9rem;line-height:1.3;"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <div class="mb-1 text-warning" style="font-size:0.8rem;">
                            <?php for($s=1;$s<=5;$s++) echo $s<=$rating_val?'★':'☆'; ?>
                            <span style="color:var(--text-muted);font-size:0.7rem;margin-right:3px;">(<?php echo $product['total_reviews']; ?>)</span>
                        </div>
                        <p class="card-text mb-3 text-truncate" style="font-size:0.78rem;color:var(--text-muted);"><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center mb-3 mt-auto">
                            <span class="price-badge" style="font-size:0.88rem;"><?php echo number_format($product['price'],0); ?> <span style="font-size:0.62rem;opacity:0.8;">د.ع/<?php echo htmlspecialchars($product['unit']); ?></span></span>
                            <?php if($product['stock']==0): ?>
                                <span class="badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 small" style="border-radius:20px;">نفد</span>
                            <?php elseif($product['stock']<10): ?>
                                <span class="stock-badge" style="background:rgba(245,158,11,0.15);color:#d97706;border-color:rgba(245,158,11,0.3);">⚡ أوشك</span>
                            <?php else: ?>
                                <span class="stock-badge">✅ متوفر</span>
                            <?php endif; ?>
                        </div>
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-details mb-2">👁️ تفاصيل المنتج</a>
                        <?php if($product['stock']>0): ?>
                            <a href="#" onclick="addToCartAJAX(event,<?php echo $product['id']; ?>)" class="btn-add-cart">🛒 إضافة إلى السلة</a>
                        <?php else: ?>
                            <button class="btn-add-cart" disabled style="opacity:0.45;cursor:not-allowed;">نفد المخزون</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert-modern alert-warning-modern p-5 text-center rounded-4">
                    <div style="font-size:4rem;margin-bottom:12px;">📭</div>
                    <h5>لا توجد منتجات متوفرة حالياً</h5>
                    <p style="color:var(--text-muted);">جرّب قسماً آخر أو ابحث بكلمة مختلفة</p>
                    <a href="index.php" class="btn btn-success mt-2">🏪 عرض كل المنتجات</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== لماذا نحن؟ ===== -->
<?php if(!$search_query): ?>
<div class="why-strip fade-in-up">
    <div class="why-item">
        <span class="wi-icon">🚚</span>
        <div class="wi-title">توصيل سريع</div>
        <div class="wi-desc">نوصل لباب بيتك في كل أحياء كربلاء المقدسة</div>
    </div>
    <div class="why-item">
        <span class="wi-icon">🛡️</span>
        <div class="wi-title">جودة مضمونة</div>
        <div class="wi-desc">منتجات طازجة ومفحوصة وفق أعلى معايير الجودة</div>
    </div>
    <div class="why-item">
        <span class="wi-icon">💰</span>
        <div class="wi-title">أسعار تنافسية</div>
        <div class="wi-desc">أفضل الأسعار مع عروض وخصومات يومية متجددة</div>
    </div>
    <div class="why-item">
        <span class="wi-icon">🎧</span>
        <div class="wi-title">دعم متواصل</div>
        <div class="wi-desc">فريق دعم على مدار الساعة لخدمتكم وحل مشكلاتكم</div>
    </div>
</div>
<?php endif; ?>

<!-- FOOTER -->
<?php include 'footer.php'; ?>

<script>
// عداد تنازلي حتى منتصف الليل
(function(){
    function tick(){
        var now=new Date(), end=new Date();
        end.setHours(23,59,59,0);
        var diff=end-now;
        if(diff<=0) return;
        var h=Math.floor(diff/3600000), m=Math.floor((diff%3600000)/60000), s=Math.floor((diff%60000)/1000);
        var eh=document.getElementById('cd-h'), em=document.getElementById('cd-m'), es=document.getElementById('cd-s');
        if(eh) eh.textContent=String(h).padStart(2,'0');
        if(em) em.textContent=String(m).padStart(2,'0');
        if(es) es.textContent=String(s).padStart(2,'0');
    }
    tick();
    setInterval(tick,1000);
})();

// ===== AJAX زر المفضلة =====
function toggleWishlist(btn, productId, currentState) {
    var inWishlist = parseInt(currentState);
    var action = inWishlist ? 'remove' : 'add';

    // تعطيل الزر أثناء الطلب
    btn.disabled = true;
    btn.style.opacity = '0.6';

    var formData = new FormData();
    formData.append('product_id', productId);
    formData.append('action', action);

    fetch('wishlist_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res){ return res.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.style.opacity = '1';

        if (data.success) {
            var newState = data.in_wishlist ? 1 : 0;
            btn.setAttribute('onclick', 'toggleWishlist(this, ' + productId + ', ' + newState + ')');
            btn.innerHTML = data.in_wishlist ? '❤️' : '🤍';
            // أنيميشن بسيط
            btn.style.transform = 'scale(1.3)';
            setTimeout(function(){ btn.style.transform = 'scale(1)'; }, 250);
            showWishlistToast(data.message, data.in_wishlist ? 'success' : 'info');
        } else {
            showWishlistToast(data.message || 'حدث خطأ', 'danger');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.style.opacity = '1';
        showWishlistToast('حدث خطأ في الاتصال', 'danger');
    });
}

function showWishlistToast(msg, type) {
    var colors = {
        'success': 'rgba(16,185,129,0.95)',
        'info':    'rgba(59,130,246,0.95)',
        'danger':  'rgba(220,38,38,0.95)',
        'warning': 'rgba(245,158,11,0.95)'
    };
    var toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;bottom:24px;left:24px;z-index:9999;background:' + (colors[type]||colors.info) + ';color:#fff;padding:12px 22px;border-radius:12px;font-weight:700;font-size:0.92rem;box-shadow:0 8px 25px rgba(0,0,0,0.25);direction:rtl;transition:all 0.4s;opacity:0;transform:translateY(20px);';
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(function(){ toast.style.opacity='1'; toast.style.transform='translateY(0)'; }, 10);
    setTimeout(function(){
        toast.style.opacity='0';
        toast.style.transform='translateY(20px)';
        setTimeout(function(){ toast.remove(); }, 400);
    }, 2800);
}
</script>
