<?php
ob_start();
include 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}

$message = ''; $message_type = '';

// الأقسام الافتراضية السبعة مع أيقوناتها
$DEFAULT_CATEGORIES = [
    ['🥩', 'اللحوم والمشويات',         'لحوم طازجة ومجمدة، دجاج، مشاوي وكباب'],
    ['🥦', 'الفواكه والخضروات',         'فواكه وخضروات طازجة يومياً من أفضل المصادر'],
    ['🧀', 'الألبان والأجبان',           'ألبان، أجبان، زبدة، قشطة ومشتقات الألبان'],
    ['🥫', 'المواد الغذائية والمعلبات', 'مواد غذائية جافة، معلبات، توابل وبهارات'],
    ['🍬', 'الحلويات والمقرمشات',       'شوكولاتة، حلويات، بسكويت، شيبس ومقرمشات'],
    ['🧃', 'المشروبات والعصائر',        'مياه، عصائر، مشروبات غازية، شاي وقهوة'],
    ['🧹', 'المواد المنزلية',           'منظفات، معقمات، أدوات تنظيف ومستلزمات منزلية'],
];

// ====== إضافة الأقسام الافتراضية دفعة واحدة ======
if (isset($_POST['seed_categories'])) {
    $inserted = 0;
    foreach ($DEFAULT_CATEGORIES as $cat) {
        $exists = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name=?");
        $exists->execute([$cat[1]]);
        if (!$exists->fetchColumn()) {
            $pdo->prepare("INSERT INTO categories (name, description) VALUES (?,?)")->execute([$cat[1], $cat[2]]);
            $inserted++;
        }
    }
    $message = $inserted > 0
        ? "✅ تم إضافة {$inserted} قسم افتراضي بنجاح."
        : "ℹ️ جميع الأقسام الافتراضية موجودة بالفعل.";
    $message_type = $inserted > 0 ? 'success' : 'info';
}

// ====== إضافة قسم ======
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? '📁');
    if (empty($name)) {
        $message='اسم القسم حقل إلزامي.'; $message_type='danger';
    } else {
        try {
            $pdo->prepare("INSERT INTO categories (name,description) VALUES (?,?)")->execute(["$icon $name", $desc]);
            $message="تم إضافة القسم '$icon $name' بنجاح."; $message_type='success';
        } catch (\PDOException $e) {
            $message="خطأ: ".htmlspecialchars($e->getMessage()); $message_type='danger';
        }
    }
}

// ====== تعديل قسم ======
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_category'])) {
    $id   = intval($_POST['cat_id']);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if (empty($name) || $id<=0) {
        $message='البيانات غير صالحة.'; $message_type='danger';
    } else {
        try {
            $pdo->prepare("UPDATE categories SET name=?,description=? WHERE id=?")->execute([$name,$desc,$id]);
            $message="تم تحديث القسم بنجاح."; $message_type='success';
        } catch (\PDOException $e) {
            $message="خطأ: ".htmlspecialchars($e->getMessage()); $message_type='danger';
        }
    }
}

// ====== حذف قسم ======
if (isset($_GET['action']) && $_GET['action']==='delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id=?");
    $count->execute([$id]);
    if ($count->fetchColumn() > 0) {
        $message="⚠️ لا يمكن حذف القسم لارتباطه بمنتجات. انقل المنتجات أولاً."; $message_type='danger';
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
        $message="تم حذف القسم بنجاح."; $message_type='success';
    }
}

// ====== جلب للتعديل ======
$edit_cat = null;
if (isset($_GET['action']) && $_GET['action']==='edit' && isset($_GET['id'])) {
    $s = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $s->execute([intval($_GET['id'])]);
    $edit_cat = $s->fetch();
}

// جلب الأقسام
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS products_count
    FROM categories c LEFT JOIN products p ON c.id=p.category_id
    GROUP BY c.id ORDER BY c.id ASC
")->fetchAll();

// تحديد الأقسام الافتراضية الناقصة
$existing_names = array_column($categories, 'name');
$missing_cats = array_filter($DEFAULT_CATEGORIES, fn($c) => !in_array($c[1], $existing_names));

include 'header.php';
?>

<!-- شريط التنقل -->
<div class="row mb-4 fade-in-up">
  <div class="col-12">
    <div class="glass-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
      <h4 class="fw-bold m-0" style="color:var(--primary);">⚙️ إدارة أقسام المتجر</h4>
      <div class="d-flex flex-wrap gap-2">
        <a href="admin_dashboard.php"  class="btn btn-outline-success fw-bold btn-sm px-3">📊 الرئيسية</a>
        <a href="admin_products.php"   class="btn btn-outline-success fw-bold btn-sm px-3">📦 المنتجات</a>
        <a href="admin_categories.php" class="btn btn-success fw-bold btn-sm px-3" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));border:none;">🏷️ الأقسام</a>
        <a href="admin_orders.php"     class="btn btn-outline-success fw-bold btn-sm px-3">🧾 الطلبات</a>
        <a href="admin_users.php"      class="btn btn-outline-success fw-bold btn-sm px-3">👥 المستخدمين</a>
        <a href="logout.php"           class="btn btn-danger fw-bold btn-sm px-3">🚪 خروج</a>
      </div>
    </div>
  </div>
</div>

<?php if ($message): ?>
  <div class="alert-modern alert-<?= $message_type ?>-modern mb-4 fade-in-up p-3 rounded-3"><?= $message ?></div>
<?php endif; ?>

<!-- تنبيه الأقسام الناقصة -->
<?php if (count($missing_cats) > 0): ?>
<div class="glass-card p-4 mb-4 fade-in-up" style="border-color:rgba(245,158,11,0.3);">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <h6 class="fw-bold mb-1" style="color:var(--accent-dark);">💡 تهيئة الأقسام الافتراضية</h6>
      <p class="small mb-0" style="color:var(--text-muted);">
        هناك <?= count($missing_cats) ?> أقسام افتراضية غير موجودة:
        <?= implode('، ', array_map(fn($c) => $c[0].' '.$c[1], $missing_cats)) ?>
      </p>
    </div>
    <form method="POST">
      <button type="submit" name="seed_categories" class="btn btn-warning fw-bold px-4"
              onclick="return confirm('هل تريد إضافة الأقسام الافتراضية السبعة؟')">
        ✨ إضافة الأقسام الافتراضية
      </button>
    </form>
  </div>
  <div class="row g-2 mt-2">
    <?php foreach ($DEFAULT_CATEGORIES as $dc): ?>
      <div class="col-md-3 col-sm-6">
        <div class="p-2 rounded-2 text-center small fw-bold" style="background:rgba(16,185,129,0.08);border:1px solid var(--card-border);color:var(--text-main);">
          <?= $dc[0] ?> <?= $dc[1] ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- نموذج الإضافة / التعديل -->
  <div class="col-md-4 fade-in-up delay-1">
    <div class="glass-card p-4">
      <?php if ($edit_cat): ?>
        <h5 class="fw-bold text-warning mb-3">✏️ تعديل القسم</h5>
        <form method="POST" action="admin_categories.php">
          <input type="hidden" name="cat_id" value="<?= $edit_cat['id'] ?>">
          <div class="mb-3">
            <label class="form-label">اسم القسم</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_cat['name']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">وصف القسم</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($edit_cat['description']) ?></textarea>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" name="edit_category" class="btn btn-warning fw-bold flex-fill text-dark">💾 حفظ</button>
            <a href="admin_categories.php" class="btn btn-secondary fw-bold">إلغاء</a>
          </div>
        </form>
      <?php else: ?>
        <h5 class="fw-bold mb-3" style="color:var(--primary);">➕ إضافة قسم جديد</h5>
        <form method="POST" action="admin_categories.php">
          <div class="mb-3">
            <label class="form-label">الأيقونة (اختياري)</label>
            <input type="text" name="icon" class="form-control" placeholder="🛒" maxlength="5" value="📁">
          </div>
          <div class="mb-3">
            <label class="form-label">اسم القسم <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="مثال: البقالة">
          </div>
          <div class="mb-3">
            <label class="form-label">وصف القسم</label>
            <textarea name="description" class="form-control" rows="3" placeholder="وصف مختصر..."></textarea>
          </div>
          <button type="submit" name="add_category" class="btn btn-success fw-bold w-100"
                  style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));border:none;">
            💾 إضافة القسم
          </button>
        </form>
      <?php endif; ?>

      <!-- لائحة الأقسام المقترحة -->
      <hr style="border-color:var(--card-border);">
      <div class="small fw-bold mb-2" style="color:var(--text-muted);">الأقسام الافتراضية للمتجر:</div>
      <div class="d-flex flex-wrap gap-1">
        <?php foreach ($DEFAULT_CATEGORIES as $dc): ?>
          <span class="badge fw-bold" style="background:rgba(16,185,129,0.1);color:var(--primary-dark);font-size:0.75rem;padding:4px 8px;">
            <?= $dc[0] ?> <?= $dc[1] ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- جدول الأقسام -->
  <div class="col-md-8 fade-in-up delay-1">
    <div class="glass-card overflow-hidden">
      <div class="p-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid var(--card-border);">
        <h6 class="fw-bold m-0" style="color:var(--text-main);">📁 أقسام المتجر النشطة (<?= count($categories) ?>)</h6>
        <span class="badge" style="background:rgba(16,185,129,0.15);color:var(--primary-dark);"><?= count($categories) ?> قسم</span>
      </div>
      <div class="table-responsive">
        <table class="table modern-table text-center align-middle mb-0">
          <thead>
            <tr>
              <th>#</th><th class="text-start">القسم</th><th>الوصف</th>
              <th>عدد المنتجات</th><th>الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td class="fw-bold" style="color:var(--primary);"><?= $cat['id'] ?></td>
                <td class="fw-bold text-start" style="color:var(--text-main);"><?= htmlspecialchars($cat['name']) ?></td>
                <td class="text-start small" style="color:var(--text-muted);max-width:200px;">
                  <?= htmlspecialchars(mb_substr($cat['description'],0,60)) ?><?= mb_strlen($cat['description'])>60?'…':'' ?>
                </td>
                <td>
                  <span class="badge fw-bold"
                    style="background:<?= $cat['products_count']>0?'rgba(16,185,129,0.15)':'rgba(100,116,139,0.15)' ?>;
                           color:<?= $cat['products_count']>0?'var(--primary-dark)':'var(--text-muted)' ?>;">
                    <?= $cat['products_count'] ?> منتج
                  </span>
                </td>
                <td>
                  <div class="d-flex justify-content-center gap-1">
                    <a href="admin_categories.php?action=edit&id=<?= $cat['id'] ?>"
                       class="btn btn-sm btn-outline-warning py-1 px-2 fw-bold">✏️ تعديل</a>
                    <a href="admin_categories.php?action=delete&id=<?= $cat['id'] ?>"
                       class="btn btn-sm btn-outline-danger py-1 px-2 fw-bold"
                       onclick="return confirm('حذف قسم \'<?= addslashes($cat['name']) ?>\'؟')">🗑️</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- FOOTER -->
include 'footer.php';
