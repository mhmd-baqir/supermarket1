<?php
ob_start();
include 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$msg = '';
$msg_type = 'success';

// ===== حذف كوبون =====
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM coupons WHERE id=?")->execute([intval($_GET['delete'])]);
    $msg = 'تم حذف كود الخصم بنجاح.';
}

// ===== تفعيل/تعطيل كوبون =====
if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE coupons SET is_active = 1 - is_active WHERE id=?")->execute([intval($_GET['toggle'])]);
    header('Location: admin_coupons.php');
    exit;
}

// ===== إضافة / تعديل كوبون =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code           = strtoupper(trim($_POST['code'] ?? ''));
    $discount_type  = $_POST['discount_type'] ?? 'percentage';
    $discount_value = floatval($_POST['discount_value'] ?? 0);
    $min_order      = floatval($_POST['min_order'] ?? 0);
    $max_uses       = intval($_POST['max_uses'] ?? 100);
    $is_active      = isset($_POST['is_active']) ? 1 : 0;
    $expires_at     = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $edit_id        = intval($_POST['edit_id'] ?? 0);

    if (empty($code) || $discount_value <= 0) {
        $msg = 'يرجى إدخال كود صالح وقيمة خصم أكبر من صفر.';
        $msg_type = 'danger';
    } else {
        try {
            if ($edit_id > 0) {
                $pdo->prepare("UPDATE coupons SET code=?, discount_type=?, discount_value=?, min_order=?, max_uses=?, is_active=?, expires_at=? WHERE id=?")
                    ->execute([$code, $discount_type, $discount_value, $min_order, $max_uses, $is_active, $expires_at, $edit_id]);
                $msg = 'تم تحديث كود الخصم بنجاح!';
            } else {
                $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order, max_uses, is_active, expires_at) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$code, $discount_type, $discount_value, $min_order, $max_uses, $is_active, $expires_at]);
                $msg = 'تمت إضافة كود الخصم بنجاح! 🎉';
            }
        } catch (Exception $e) {
            $msg = 'خطأ: كود الخصم موجود مسبقاً أو حدث خطأ آخر.';
            $msg_type = 'danger';
        }
    }
}

// جلب كوبون للتعديل
$edit_coupon = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM coupons WHERE id=?");
    $s->execute([intval($_GET['edit'])]);
    $edit_coupon = $s->fetch();
}

// جلب جميع الكوبونات
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();

include 'header.php';
?>

<!-- شريط تنقل الأدمن -->
<div class="row mb-4 fade-in-up">
    <div class="col-12">
        <div class="glass-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h4 class="fw-bold text-success m-0">🎟️ إدارة أكواد الخصم</h4>
            <div class="d-flex flex-wrap gap-2">
                <a href="admin_dashboard.php" class="btn btn-outline-success fw-bold btn-sm px-3">📊 الرئيسية</a>
                <a href="admin_products.php" class="btn btn-outline-success fw-bold btn-sm px-3">📦 المنتجات</a>
                <a href="admin_orders.php" class="btn btn-outline-success fw-bold btn-sm px-3">🧾 الطلبات</a>
                <a href="admin_coupons.php" class="btn btn-success fw-bold btn-sm px-3 active">🎟️ الكوبونات</a>
                <a href="logout.php" class="btn btn-danger fw-bold btn-sm px-3">🚪 خروج</a>
            </div>
        </div>
    </div>
</div>

<?php if($msg): ?>
    <div class="alert-modern alert-<?= $msg_type === 'danger' ? 'danger' : 'success' ?>-modern p-3 rounded-3 mb-4 fw-bold">
        <?= $msg_type === 'danger' ? '❌' : '✅' ?> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- نموذج إضافة/تعديل كوبون -->
    <div class="col-lg-5">
        <div class="glass-card p-4 fade-in-up">
            <h5 class="fw-bold text-success mb-4"><?= $edit_coupon ? '✏️ تعديل كود الخصم' : '➕ إضافة كود خصم جديد' ?></h5>
            <form method="POST">
                <input type="hidden" name="edit_id" value="<?= $edit_coupon ? $edit_coupon['id'] : 0 ?>">

                <div class="mb-3">
                    <label class="form-label">🔤 كود الخصم <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control text-uppercase fw-bold"
                           placeholder="مثال: WELCOME10" required
                           style="letter-spacing: 2px; font-size: 1.1rem;"
                           value="<?= htmlspecialchars($edit_coupon['code'] ?? '') ?>"
                           oninput="this.value=this.value.toUpperCase()">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">🏷️ نوع الخصم</label>
                        <select name="discount_type" class="form-select">
                            <option value="percentage" <?= ($edit_coupon['discount_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>نسبة مئوية (%)</option>
                            <option value="fixed" <?= ($edit_coupon['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>مبلغ ثابت (د.ع)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">💰 قيمة الخصم <span class="text-danger">*</span></label>
                        <input type="number" name="discount_value" class="form-control" step="0.01" min="0.01" required
                               placeholder="10"
                               value="<?= $edit_coupon['discount_value'] ?? '' ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">💵 الحد الأدنى للطلب (د.ع)</label>
                        <input type="number" name="min_order" class="form-control" step="500" min="0"
                               placeholder="10000"
                               value="<?= $edit_coupon['min_order'] ?? 0 ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">🔢 الحد الأقصى للاستخدام</label>
                        <input type="number" name="max_uses" class="form-control" min="1"
                               placeholder="100"
                               value="<?= $edit_coupon['max_uses'] ?? 100 ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">📅 تاريخ انتهاء الصلاحية (اختياري)</label>
                    <input type="date" name="expires_at" class="form-control"
                           value="<?= $edit_coupon['expires_at'] ?? '' ?>">
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                               style="width: 3em; height: 1.5em;"
                               <?= ($edit_coupon['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold ms-2" for="isActive" style="color: #4ade80;">
                            تفعيل الكود فوراً
                        </label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn fw-bold flex-fill py-3"
                            style="background: linear-gradient(135deg,#16a34a,#15803d); color:white; border:none; border-radius:12px;">
                        <?= $edit_coupon ? '💾 حفظ التعديلات' : '➕ إضافة الكود' ?>
                    </button>
                    <?php if($edit_coupon): ?>
                        <a href="admin_coupons.php" class="btn fw-bold px-4 py-3"
                           style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; border-radius:12px;">
                            إلغاء
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- أكواد جاهزة مقترحة -->
            <div class="mt-4 p-3 rounded-3" style="background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3);">
                <h6 class="fw-bold mb-2" style="color:#fbbf24;">💡 أكواد الخصم الافتراضية المدخلة:</h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach(['WELCOME10','SAVE5000','KARBALA20','NEWUSER15'] as $c): ?>
                        <span class="badge fw-bold px-3 py-2"
                              style="background: rgba(245,158,11,0.2); border: 1px solid rgba(245,158,11,0.4); color:#fbbf24; font-size:0.85rem; border-radius: 8px; letter-spacing:1px;">
                            <?= $c ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- قائمة الكوبونات -->
    <div class="col-lg-7 fade-in-up delay-1">
        <div class="glass-card overflow-hidden">
            <div class="p-3 d-flex justify-content-between align-items-center"
                 style="background: rgba(22,163,74,0.1); border-bottom: 1px solid rgba(22,163,74,0.2);">
                <h6 class="fw-bold text-white m-0">🎟️ أكواد الخصم المتوفرة (<?= count($coupons) ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table modern-table mb-0 align-middle text-center">
                    <thead>
                        <tr>
                            <th>الكود</th>
                            <th>نوع الخصم</th>
                            <th>القيمة</th>
                            <th>الاستخدامات</th>
                            <th>الحالة</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($coupons) > 0): ?>
                            <?php foreach($coupons as $c): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold px-3 py-1 rounded"
                                              style="background: rgba(245,158,11,0.15); color:#fbbf24; font-size:0.9rem; letter-spacing:1px; font-family: monospace;">
                                            <?= htmlspecialchars($c['code']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <?= $c['discount_type'] === 'percentage' ? 'نسبة %' : 'مبلغ ثابت' ?>
                                    </td>
                                    <td class="fw-bold text-success">
                                        <?= $c['discount_type'] === 'percentage'
                                            ? $c['discount_value'] . '%'
                                            : number_format($c['discount_value'], 0) . ' د.ع' ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= $c['used_count'] ?> / <?= $c['max_uses'] ?>
                                    </td>
                                    <td>
                                        <?php if($c['is_active']): ?>
                                            <span class="badge bg-success fw-bold">✅ فعّال</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger fw-bold">⛔ معطّل</span>
                                        <?php endif; ?>
                                        <?php if($c['expires_at']): ?>
                                            <div class="text-muted" style="font-size:0.7rem;">
                                                ينتهي: <?= $c['expires_at'] ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="admin_coupons.php?edit=<?= $c['id'] ?>"
                                               class="btn btn-sm btn-outline-warning fw-bold px-2 py-1">✏️</a>
                                            <a href="admin_coupons.php?toggle=<?= $c['id'] ?>"
                                               class="btn btn-sm fw-bold px-2 py-1"
                                               style="background: rgba(59,130,246,0.2); border: 1px solid rgba(59,130,246,0.4); color:#60a5fa;">
                                               <?= $c['is_active'] ? '⛔' : '✅' ?>
                                            </a>
                                            <a href="admin_coupons.php?delete=<?= $c['id'] ?>"
                                               onclick="return confirm('هل أنت متأكد من حذف هذا الكود؟')"
                                               class="btn btn-sm btn-outline-danger fw-bold px-2 py-1">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-muted p-4">لا توجد أكواد خصم بعد. أضف واحداً من النموذج.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

include 'footer.php';
