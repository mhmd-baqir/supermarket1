<?php
ob_start();
include 'config.php';

// التحقق من صلاحية المدير
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// 1. إضافة قسم جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $message = 'اسم القسم حقل إلزامي.';
        $message_type = 'danger';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $message = "تم إضافة القسم الجديد '{$name}' بنجاح.";
            $message_type = "success";
        } catch (\PDOException $e) {
            $message = "حدث خطأ أثناء الإضافة: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}

// 2. تعديل قسم قائم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = intval($_POST['cat_id']);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || $id <= 0) {
        $message = 'البيانات المدخلة غير صالحة.';
        $message_type = 'danger';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
            $message = "تم تحديث بيانات القسم بنجاح.";
            $message_type = "success";
        } catch (\PDOException $e) {
            $message = "حدث خطأ أثناء التحديث: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}

// 3. حذف قسم
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        // التحقق أولاً من وجود منتجات مرتبطة بهذا القسم لتنبيه المسؤول
        $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $check->execute([$id]);
        $prod_count = $check->fetchColumn();

        if ($prod_count > 0) {
            $message = "تحذير: لا يمكن حذف هذا القسم لأنه يحتوي على ({$prod_count}) منتجات مرتبطة به. يرجى نقل أو حذف المنتجات أولاً.";
            $message_type = "danger";
        } else {
            $delete = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $delete->execute([$id]);
            $message = "تم حذف القسم بنجاح.";
            $message_type = "success";
        }
    } catch (\PDOException $e) {
        $message = "حدث خطأ أثناء الحذف: " . htmlspecialchars($e->getMessage());
        $message_type = "danger";
    }
}

// جلب تفاصيل قسم محدد للتعديل
$edit_cat = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_cat = $stmt->fetch();
}

// جلب جميع الأقسام مع عدادات المنتجات التابعة لها
$categories_stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) AS products_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.id ASC
");
$categories = $categories_stmt->fetchAll();

include 'header.php';
?>

<!-- شريط التنقل للوحة التحكم -->
<div class="row mb-4 fade-in-up">
    <div class="col-12">
        <div class="glass-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h4 class="fw-bold text-success m-0">⚙️ إدارة أقسام المتجر</h4>
            <div class="d-flex flex-wrap gap-2">
                <a href="admin_dashboard.php" class="btn btn-outline-success fw-bold btn-sm px-3">📊 الرئيسية</a>
                <a href="admin_products.php" class="btn btn-outline-success fw-bold btn-sm px-3">📦 المنتجات</a>
                <a href="admin_categories.php" class="btn btn-success fw-bold btn-sm active px-3">🏷️ الأقسام</a>
                <a href="admin_orders.php" class="btn btn-outline-success fw-bold btn-sm px-3">🧾 الطلبات</a>
                <a href="admin_users.php" class="btn btn-outline-success fw-bold btn-sm px-3">👥 المستخدمين</a>
                <a href="logout.php" class="btn btn-danger fw-bold btn-sm px-3">🚪 خروج</a>
            </div>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert-modern alert-<?php echo $message_type; ?>-modern mb-4 fade-in-up">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- نموذج الإضافة / التعديل الجانبي -->
    <div class="col-md-4 fade-in-up delay-1">
        <div class="glass-card p-4">
            <?php if ($edit_cat): ?>
                <h5 class="fw-bold text-warning mb-3">✏️ تعديل القسم: <?php echo htmlspecialchars($edit_cat['name']); ?></h5>
                <form method="POST" action="admin_categories.php">
                    <input type="hidden" name="cat_id" value="<?php echo $edit_cat['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">اسم القسم</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_cat['name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وصف القسم</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_cat['description']); ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="edit_category" class="btn btn-warning fw-bold flex-fill text-dark">💾 حفظ التعديلات</button>
                        <a href="admin_categories.php" class="btn btn-secondary fw-bold">إلغاء</a>
                    </div>
                </form>
            <?php else: ?>
                <h5 class="fw-bold text-success mb-3">➕ إضافة قسم جديد</h5>
                <form method="POST" action="admin_categories.php">
                    <div class="mb-3">
                        <label class="form-label">اسم القسم</label>
                        <input type="text" name="name" class="form-control" required placeholder="مثال: البقالة والألبان">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وصف القسم</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="ملاحظات أو وصف مختصر عن القسم..."></textarea>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-success fw-bold w-100" style="background: linear-gradient(135deg, #16a34a, #15803d); border: none;">
                        💾 إضافة القسم
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- جدول عرض الأقسام الحالية -->
    <div class="col-md-8 fade-in-up delay-1">
        <div class="glass-card">
            <div class="p-3 border-bottom border-secondary border-opacity-10">
                <h6 class="fw-bold text-white m-0">📁 أقسام المتجر النشطة</h6>
            </div>
            <div class="table-responsive">
                <table class="table modern-table text-center align-middle mb-0">
                    <thead>
                        <tr>
                            <th>الرقم</th>
                            <th>القسم</th>
                            <th>الوصف</th>
                            <th>عدد المنتجات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td class="fw-bold text-white text-start"><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td class="text-muted text-start small"><?php echo htmlspecialchars($cat['description']); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $cat['products_count']; ?> منتج</span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="admin_categories.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-warning py-1 px-3">تعديل</a>
                                        <a href="admin_categories.php?action=delete&id=<?php echo $cat['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger py-1 px-3"
                                           onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا القسم نهائياً؟')">حذف</a>
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
<footer class="main-footer mt-5">
    <p class="mb-0">© 2024 الهايبر ماركت المتكامل — جميع الحقوق محفوظة | PR122-3</p>
</footer>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
