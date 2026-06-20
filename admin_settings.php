<?php
ob_start();
include 'config.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// جلب تفاصيل المسؤول الحالي للحصول على بياناته من قاعدة البيانات
$admin_username = $_SESSION['admin_username'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
$stmt->execute([$admin_username]);
$admin = $stmt->fetch();

if (!$admin) {
    // إذا لم يتم العثور على الحساب لسبب ما، يتم تسجيل الخروج
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username     = trim($_POST['new_username'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_username)) {
        $error = 'اسم المستخدم الجديد مطلوب ولا يمكن أن يكون فارغاً.';
    } elseif (empty($current_password)) {
        $error = 'يرجى إدخال كلمة المرور الحالية لتأكيد الهوية وتطبيق التغييرات.';
    } else {
        // 1. التحقق من صحة كلمة المرور الحالية
        if (!password_verify($current_password, $admin['password'])) {
            $error = 'كلمة المرور الحالية غير صحيحة، يرجى المحاولة مرة أخرى.';
        } else {
            // 2. التحقق من فرادة اسم المستخدم الجديد إذا تم تعديله
            $username_taken = false;
            if ($new_username !== $admin['username']) {
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                $check_stmt->execute([$new_username, $admin['id']]);
                if ($check_stmt->fetchColumn() > 0) {
                    $username_taken = true;
                    $error = 'اسم المستخدم الجديد مستخدم بالفعل من قبل حساب آخر. يرجى اختيار اسم آخر.';
                }
            }

            if (!$username_taken) {
                // 3. التحقق إذا كان المسؤول يريد تغيير كلمة المرور أيضاً
                if (!empty($new_password)) {
                    if ($new_password !== $confirm_password) {
                        $error = 'كلمة المرور الجديدة وتأكيدها غير متطابقين.';
                    } elseif (strlen($new_password) < 6) {
                        $error = 'يجب أن تكون كلمة المرور الجديدة مكونة من 6 أحرف أو أرقام على الأقل.';
                    } else {
                        // تحديث اسم المستخدم وكلمة المرور معاً
                        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                        $update_stmt = $pdo->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                        if ($update_stmt->execute([$new_username, $hashed_password, $admin['id']])) {
                            $_SESSION['admin_username'] = $new_username; // تحديث الجلسة
                            $success = 'تم تحديث اسم المستخدم وكلمة المرور للمسؤول بنجاح! 🎉';
                        } else {
                            $error = 'حدث خطأ أثناء تحديث البيانات في قاعدة البيانات.';
                        }
                    }
                } else {
                    // تحديث اسم المستخدم فقط
                    $update_stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                    if ($update_stmt->execute([$new_username, $admin['id']])) {
                        $_SESSION['admin_username'] = $new_username; // تحديث الجلسة
                        $success = 'تم تحديث اسم المستخدم للمسؤول بنجاح! 🎉';
                    } else {
                        $error = 'حدث خطأ أثناء تحديث اسم المستخدم في قاعدة البيانات.';
                    }
                }
            }
        }
    }

    // إعادة جلب بيانات المسؤول المحدثة
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$admin['id']]);
    $admin = $stmt->fetch();
}

include 'header.php';
?>

<!-- شريط التنقل للوحة التحكم -->
<div class="row mb-4 fade-in-up">
    <div class="col-12">
        <div class="glass-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h4 class="fw-bold m-0" style="color:var(--primary);">⚙️ إعدادات حساب المسؤول</h4>
            <div class="d-flex flex-wrap gap-2">
                <a href="admin_dashboard.php"  class="btn btn-outline-success fw-bold btn-sm px-3">📊 الرئيسية</a>
                <a href="admin_products.php"   class="btn btn-outline-success fw-bold btn-sm px-3">📦 المنتجات</a>
                <a href="admin_categories.php" class="btn btn-outline-success fw-bold btn-sm px-3">🏷️ الأقسام</a>
                <a href="admin_orders.php"     class="btn btn-outline-success fw-bold btn-sm px-3">🧾 الطلبات</a>
                <a href="admin_support.php"    class="btn btn-outline-success fw-bold btn-sm px-3">💬 الرسائل والدعم</a>
                <a href="admin_users.php"      class="btn btn-outline-success fw-bold btn-sm px-3">👥 المستخدمين</a>
                <a href="admin_coupons.php"    class="btn btn-outline-warning fw-bold btn-sm px-3">🎟️ الكوبونات</a>
                <a href="admin_settings.php"   class="btn btn-success fw-bold btn-sm active px-3" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));border:none;">⚙️ الإعدادات</a>
                <a href="logout.php"           class="btn btn-danger fw-bold btn-sm px-3">🚪 خروج</a>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8 fade-in-up delay-1">
        <div class="glass-card p-4">
            <div class="text-center mb-4">
                <div style="font-size: 3rem;">🔐</div>
                <h5 class="fw-bold mt-2" style="color:var(--primary);">تعديل بيانات دخول المسؤول</h5>
                <p class="small text-muted">يمكنك هنا تغيير اسم المستخدم وكلمة المرور الخاصة بلوحة التحكم.</p>
            </div>

            <!-- رسائل النجاح والخطأ -->
            <?php if ($success): ?>
                <div class="alert-modern alert-success-modern p-3 rounded-3 mb-4 fw-bold text-center">
                    <?= $success ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert-modern alert-danger-modern p-3 rounded-3 mb-4 fw-bold text-center">
                    ❌ <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin_settings.php" class="needs-validation" novalidate>
                <!-- اسم المستخدم الحالي (للقراءة فقط) -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted">اسم المستخدم الحالي:</label>
                    <input type="text" class="form-control bg-dark bg-opacity-25 border-secondary border-opacity-25 text-white-50 fw-bold" 
                           value="<?= htmlspecialchars($admin['username']) ?>" readonly style="cursor: not-allowed;">
                </div>

                <!-- اسم المستخدم الجديد -->
                <div class="mb-3">
                    <label for="new_username" class="form-label fw-bold">اسم المستخدم الجديد <span class="text-danger">*</span></label>
                    <input type="text" name="new_username" id="new_username" class="form-control text-white fw-bold" 
                           required placeholder="أدخل اسم المستخدم الجديد" value="<?= htmlspecialchars($admin['username']) ?>">
                    <div class="invalid-feedback">يرجى إدخال اسم مستخدم صالح.</div>
                </div>

                <hr class="my-4" style="border-color: var(--card-border);">

                <div class="p-3 rounded-3 mb-4" style="background: rgba(16,185,129,0.05); border: 1px dashed rgba(16,185,129,0.2);">
                    <h6 class="fw-bold mb-2" style="color: var(--primary);">🔑 تغيير كلمة المرور (اختياري)</h6>
                    <p class="small text-muted mb-3">اترك الحقول التالية فارغة إذا كنت تريد الاحتفاظ بكلمة المرور الحالية دون تغيير.</p>

                    <!-- كلمة المرور الجديدة -->
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-bold small">كلمة المرور الجديدة:</label>
                        <input type="password" name="new_password" id="new_password" class="form-control text-white" 
                               autocomplete="new-password" placeholder="أدخل كلمة المرور الجديدة (6 خانات على الأقل)">
                    </div>

                    <!-- تأكيد كلمة المرور الجديدة -->
                    <div class="mb-0">
                        <label for="confirm_password" class="form-label fw-bold small">تأكيد كلمة المرور الجديدة:</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control text-white" 
                               autocomplete="new-password" placeholder="أعد إدخال كلمة المرور الجديدة">
                    </div>
                </div>

                <hr class="my-4" style="border-color: var(--card-border);">

                <!-- كلمة المرور الحالية لتأكيد الهوية -->
                <div class="mb-4 p-3 rounded-3" style="background: rgba(220,38,38,0.05); border: 1px solid rgba(220,38,38,0.15);">
                    <label for="current_password" class="form-label fw-bold text-danger">⚠️ كلمة المرور الحالية لتأكيد التغيير <span class="text-danger">*</span></label>
                    <input type="password" name="current_password" id="current_password" class="form-control text-white border-danger border-opacity-50" 
                           autocomplete="current-password" required placeholder="أدخل كلمة مرورك الحالية لحفظ التعديلات">
                    <div class="invalid-feedback">يرجى إدخال كلمة المرور الحالية لتأكيد الإجراء.</div>
                </div>

                <!-- زر الحفظ -->
                <button type="submit" class="btn w-100 fw-bold py-3" 
                        style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; border: none; border-radius: 12px; font-size: 1.05rem;">
                    💾 حفظ وتطبيق التغييرات
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// تفعيل التحقق من صحة المدخلات في الطرف العميل باستخدام Bootstrap
(function () {
  'use strict'
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add('was-validated')
      }, false)
    })
})()
</script>

<?php
include 'footer.php';
?>
