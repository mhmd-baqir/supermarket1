<?php
ob_start();
include 'config.php';

// التحقق من صلاحية المدير
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$msg_success = '';
$msg_error   = '';

// ====== إضافة منتج جديد ======
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name      = trim($_POST['name']);
    $cat_id    = intval($_POST['category_id']);
    $desc      = trim($_POST['description']);
    $price     = floatval($_POST['price']);
    $stock     = intval($_POST['stock']);
    $unit      = trim($_POST['unit'] ?? 'قطعة');
    $barcode   = trim($_POST['barcode']);
    $image_url = trim($_POST['image_url'] ?? '');

    $img_name = 'default.jpg';

    if (!empty($image_url)) {
        // إذا قام المسؤول بإدخال رابط خارجي للصورة
        $img_name = $image_url;
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // إذا قام برفع ملف محلي
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $img_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._]/', '_', $_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $img_name);
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, image, stock, unit, barcode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$cat_id, $name, $desc, $price, $img_name, $stock, $unit, $barcode]);
        $msg_success = '✅ تم إضافة المنتج الجديد بنجاح للمخازن!';
    } catch (\PDOException $e) {
        $msg_error = '❌ خطأ: رقم الباركود مكرر أو هناك مشكلة في اتصال قاعدة البيانات: ' . $e->getMessage();
    }
}

// ====== تعديل منتج ======
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    $edit_id       = intval($_POST['edit_id']);
    $edit_name     = trim($_POST['edit_name']);
    $edit_cat_id   = intval($_POST['edit_category_id']);
    $edit_price    = floatval($_POST['edit_price']);
    $edit_stock    = intval($_POST['edit_stock']);
    $edit_unit     = trim($_POST['edit_unit'] ?? 'قطعة');
    $edit_desc     = trim($_POST['edit_description']);
    $edit_barcode  = trim($_POST['edit_barcode']);
    $edit_img_url  = trim($_POST['edit_image_url'] ?? '');

    try {
        // جلب الصورة الحالية للتحقق
        $img_q = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $img_q->execute([$edit_id]);
        $curr_img = $img_q->fetchColumn() ?: 'default.jpg';

        $img_name = $curr_img;

        if (!empty($edit_img_url)) {
            $img_name = $edit_img_url;
        } elseif (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext     = strtolower(pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $img_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._]/', '_', $_FILES['edit_image']['name']);
                move_uploaded_file($_FILES['edit_image']['tmp_name'], 'uploads/' . $img_name);
                
                // حذف الصورة القديمة إن كانت محلية ومرفوعة سابقاً وليست الرابط المبدئي
                if ($curr_img !== 'default.jpg' && strpos($curr_img, 'http') !== 0 && file_exists('uploads/' . $curr_img)) {
                    unlink('uploads/' . $curr_img);
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE products SET name = ?, category_id = ?, description = ?, price = ?, stock = ?, unit = ?, barcode = ?, image = ? WHERE id = ?");
        $stmt->execute([$edit_name, $edit_cat_id, $edit_desc, $edit_price, $edit_stock, $edit_unit, $edit_barcode, $img_name, $edit_id]);
        $msg_success = '✅ تم تعديل بيانات وصورة المنتج بنجاح!';
    } catch (\PDOException $e) {
        $msg_error = '❌ خطأ أثناء التعديل: رقم الباركود مكرر أو هناك خطأ بقاعدة البيانات: ' . $e->getMessage();
    }
}

// ====== حذف منتج ======
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    try {
        // حذف الصورة إن وجدت ومحلية
        $img_q = $pdo->prepare("SELECT image FROM products WHERE id=?");
        $img_q->execute([$del_id]);
        $img_row = $img_q->fetch();
        if ($img_row && $img_row['image'] !== 'default.jpg' && strpos($img_row['image'], 'http') !== 0 && file_exists('uploads/' . $img_row['image'])) {
            unlink('uploads/' . $img_row['image']);
        }
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$del_id]);
        $msg_success = '🗑️ تم حذف المنتج بالكامل من المخازن.';
    } catch (\PDOException $e) {
        $msg_error = '❌ خطأ: لا يمكن حذف المنتج لارتباطه بفواتير طلبات سابقة.';
    }
}

// جلب البيانات
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$products   = $pdo->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();

// منتج محدد للتعديل
$edit_product = null;
if (isset($_GET['edit_id'])) {
    $est = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $est->execute([intval($_GET['edit_id'])]);
    $edit_product = $est->fetch();
}

include 'header.php';
?>

<!-- شريط التنقل للوحة التحكم -->
<div class="row mb-4 fade-in-up">
    <div class="col-12">
        <div class="glass-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h4 class="fw-bold m-0" style="color:var(--primary);">⚙️ إدارة منتجات المتجر</h4>
            <div class="d-flex flex-wrap gap-2">
                <a href="admin_dashboard.php"  class="btn btn-outline-success fw-bold btn-sm px-3">📊 الرئيسية</a>
                <a href="admin_products.php"   class="btn btn-success fw-bold btn-sm active px-3" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));border:none;">📦 المنتجات</a>
                <a href="admin_categories.php" class="btn btn-outline-success fw-bold btn-sm px-3">🏷️ الأقسام</a>
                <a href="admin_orders.php"     class="btn btn-outline-success fw-bold btn-sm px-3">🧾 الطلبات</a>
                <a href="admin_users.php"      class="btn btn-outline-success fw-bold btn-sm px-3">👥 المستخدمين</a>
                <a href="admin_settings.php"   class="btn btn-outline-success fw-bold btn-sm px-3">⚙️ الإعدادات</a>
                <a href="logout.php"           class="btn btn-danger fw-bold btn-sm px-3">🚪 خروج</a>
            </div>
        </div>
    </div>
</div>

<!-- رسائل النجاح/الخطأ -->
<?php if ($msg_success): ?>
    <div class="alert-modern alert-success-modern p-3 rounded-3 mb-4 fade-in-up"><?php echo $msg_success; ?></div>
<?php endif; ?>
<?php if ($msg_error): ?>
    <div class="alert-modern alert-danger-modern p-3 rounded-3 mb-4 fade-in-up"><?php echo $msg_error; ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- FORM إضافة/تعديل -->
    <div class="col-md-4 fade-in-up delay-1">
        <div class="glass-card p-4">
            <?php if ($edit_product): ?>
                <!-- فورم التعديل -->
                <h5 class="fw-bold mb-4 text-warning">✏️ تعديل المنتج</h5>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_product" value="1">
                    <input type="hidden" name="edit_id" value="<?php echo $edit_product['id']; ?>">

                    <div class="mb-3">
                        <label class="form-label">اسم المنتج</label>
                        <input type="text" name="edit_name" class="form-control" required value="<?php echo htmlspecialchars($edit_product['name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">القسم</label>
                        <select name="edit_category_id" class="form-select" required>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $edit_product['category_id'] == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">السعر (د.ع)</label>
                        <input type="number" step="0.01" name="edit_price" class="form-control" required value="<?php echo $edit_product['price']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الكمية المتوفرة</label>
                        <input type="number" name="edit_stock" class="form-control" required value="<?php echo $edit_product['stock']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وحدة البيع</label>
                        <select name="edit_unit" class="form-select" required>
                            <option value="قطعة" <?php echo $edit_product['unit'] === 'قطعة' ? 'selected' : ''; ?>>قطعة (Piece)</option>
                            <option value="كيلو" <?php echo $edit_product['unit'] === 'كيلو' ? 'selected' : ''; ?>>كيلو غرام (Kilo)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الباركود</label>
                        <input type="text" name="edit_barcode" class="form-control" required value="<?php echo htmlspecialchars($edit_product['barcode']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رابط صورة خارجية من الإنترنت</label>
                        <input type="text" name="edit_image_url" id="editImgUrl" class="form-control"
                               placeholder="https://example.com/image.jpg"
                               value="<?php echo (strpos($edit_product['image'], 'http') === 0) ? htmlspecialchars($edit_product['image']) : ''; ?>"
                               oninput="previewImage('editImgUrl','editImgPreview')">
                        <img id="editImgPreview"
                             src="<?php echo (strpos($edit_product['image'],'http')===0) ? htmlspecialchars($edit_product['image']) : 'uploads/'.htmlspecialchars($edit_product['image']); ?>"
                             alt="" style="width:100%;height:100px;object-fit:cover;border-radius:8px;margin-top:8px;border:1px solid var(--card-border);">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">أو ارفع ملف صورة محلي</label>
                        <input type="file" name="edit_image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="edit_description" class="form-control" rows="2"><?php echo htmlspecialchars($edit_product['description']); ?></textarea>
                    </div>
                    <button type="submit" class="btn w-100 fw-bold mb-2 btn-warning text-dark" style="border-radius:12px; padding:12px;">
                        💾 حفظ التعديلات
                    </button>
                    <a href="admin_products.php" class="btn w-100" style="background: rgba(255,255,255,0.05); color:#94a3b8; border: 1px solid rgba(255,255,255,0.1); border-radius:12px;">
                        ← إلغاء
                    </a>
                </form>
            <?php else: ?>
                <!-- فورم الإضافة -->
                <h5 class="fw-bold mb-4 text-success">➕ إضافة منتج جديد</h5>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_product" value="1">

                    <div class="mb-3">
                        <label class="form-label">اسم المنتج</label>
                        <input type="text" name="name" class="form-control" required placeholder="مثال: تفاح أحمر">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">القسم</label>
                        <select name="category_id" class="form-select" required>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">السعر (د.ع)</label>
                        <input type="number" step="0.01" name="price" class="form-control" required placeholder="5000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الكمية المتوفرة</label>
                        <input type="number" name="stock" class="form-control" required placeholder="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وحدة البيع</label>
                        <select name="unit" class="form-select" required>
                            <option value="قطعة">قطعة (Piece)</option>
                            <option value="كيلو">كيلو غرام (Kilo)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الباركود</label>
                        <input type="text" name="barcode" class="form-control" required placeholder="BAR-XXX">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رابط صورة من الإنترنت (توصية)</label>
                        <input type="text" name="image_url" id="addImgUrl" class="form-control"
                               placeholder="https://example.com/image.jpg"
                               oninput="previewImage('addImgUrl','addImgPreview')">
                        <img id="addImgPreview" src="" alt="" style="display:none;width:100%;height:100px;object-fit:cover;border-radius:8px;margin-top:8px;border:1px solid var(--card-border);">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">أو ارفع صورة محلياً</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="وصف مختصر للمنتج..."></textarea>
                    </div>
                    <button type="submit" class="btn w-100 fw-bold btn-success" style="border-radius:12px; padding:12px; background: linear-gradient(135deg,#16a34a,#15803d); border:none;">
                        ✅ حفظ المنتج في المخزن
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- جدول المنتجات -->
    <div class="col-md-8 fade-in-up delay-2">
        <div class="glass-card overflow-hidden">
            <div class="p-4" style="border-bottom: 1px solid var(--card-border);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0" style="color:var(--text-main);">📋 قائمة المنتجات الحالية</h5>
                    <span style="background: rgba(16,185,129,0.15); color:var(--primary-dark); padding: 4px 14px; border-radius:20px; font-size:0.85rem; font-weight:700;">
                        <?php echo count($products); ?> منتج بالمخزن
                    </span>
                </div>
                <input type="text" id="productSearch" class="form-control" placeholder="🔍 ابحث باسم المنتج أو الباركود أو القسم..." oninput="filterProducts()">
            </div>
            <div class="table-responsive">
                <table id="productsTable" class="table modern-table mb-0 text-center align-middle">
                    <thead>
                        <tr>
                            <th>صورة</th>
                            <th>الاسم والباركود</th>
                            <th>القسم</th>
                            <th>السعر</th>
                            <th>المخزن</th>
                            <th>التحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <?php 
                            $image_src = $p['image'];
                            if (strpos($image_src, 'http') !== 0) {
                                $image_src = 'uploads/' . $image_src;
                            }
                            ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($image_src); ?>" 
                                         width="48" height="48" 
                                         style="object-fit:cover; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1);"
                                         onerror="this.src='https://placehold.co/48x48/d1fae5/059669?text=📦'">
                                </td>
                                <td class="text-start">
                                     <div style="font-weight: 700; color: var(--text-main); font-size:0.9rem;">
                                        <?php echo htmlspecialchars($p['name']); ?>
                                    </div>
                                     <div style="color: var(--text-muted); font-size:0.75rem;">
                                        <?php echo $p['barcode']; ?>
                                    </div>
                                </td>
                                 <td style="color: var(--text-muted); font-size:0.85rem;">
                                    <?php echo htmlspecialchars($p['cat_name']); ?>
                                </td>
                                 <td style="color: var(--primary); font-weight: 700;">
                                    <?php echo number_format($p['price'], 0); ?> د.ع / <?php echo htmlspecialchars($p['unit']); ?>
                                </td>
                                <td>
                                    <?php if ($p['stock'] < 10): ?>
                                        <span class="badge-stock-low"><?php echo $p['stock']; ?> <?php echo htmlspecialchars($p['unit']); ?></span>
                                    <?php else: ?>
                                        <span class="badge-stock-ok"><?php echo $p['stock']; ?> <?php echo htmlspecialchars($p['unit']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="admin_products.php?edit_id=<?php echo $p['id']; ?>" 
                                           class="btn btn-sm fw-bold btn-outline-warning py-1 px-3">
                                           ✏️
                                        </a>
                                        <a href="admin_products.php?delete_id=<?php echo $p['id']; ?>" 
                                           class="btn btn-sm fw-bold btn-outline-danger py-1 px-3"
                                           onclick="return confirm('هل أنت متأكد من حذف هذا المنتج نهائياً من قاعدة البيانات والمخزن؟')">
                                           🗑️
                                        </a>
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

<script>
function previewImage(inputId, previewId) {
    const url = document.getElementById(inputId).value.trim();
    const img = document.getElementById(previewId);
    if (url.startsWith('http')) {
        img.src = url; img.style.display = 'block';
        img.onerror = () => img.style.display = 'none';
    } else {
        img.style.display = 'none';
    }
}
function filterProducts() {
    const q = document.getElementById('productSearch').value.toLowerCase();
    document.querySelectorAll('#productsTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
document.addEventListener('DOMContentLoaded', () => {
    const editUrl = document.getElementById('editImgUrl');
    if (editUrl) previewImage('editImgUrl','editImgPreview');
});
</script>
<?php include 'footer.php'; ?>
