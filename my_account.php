<?php
ob_start();
include 'config.php';

// فرض تسجيل الدخول للعميل
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// جلب بيانات العميل الحالية
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (empty($full_name) || empty($email)) {
        $error = 'الاسم الكامل والبريد الإلكتروني حقول إلزامية.';
    } else {
        try {
            // التحقق من تكرار البريد الإلكتروني لمستخدم آخر
            $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_email->execute([$email, $user_id]);
            if ($check_email->fetch()) {
                $error = 'البريد الإلكتروني هذا مستخدم بالفعل من قبل حساب آخر.';
            } else {
                // تحديث البيانات الأساسية
                if (!empty($password)) {
                    // تحديث بكلمة مرور جديدة
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?");
                    $update_stmt->execute([$full_name, $email, $phone, $address, $hashed_password, $user_id]);
                } else {
                    // تحديث بدون تغيير كلمة المرور
                    $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                    $update_stmt->execute([$full_name, $email, $phone, $address, $user_id]);
                }

                $_SESSION['full_name'] = $full_name; // تحديث الاسم في الجلسة
                $success = 'تم تحديث معلومات حسابك بنجاح.';
                
                // إعادة جلب البيانات بعد التحديث
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            }
        } catch (\PDOException $e) {
            $error = 'حدث خطأ أثناء التحديث: ' . htmlspecialchars($e->getMessage());
        }
    }
}

include 'header.php';
?>

<div class="row">
    <!-- قائمة العميل الجانبية -->
    <div class="col-md-3 mb-4 fade-in-up">
        <div class="sidebar-card">
            <h6 class="sidebar-title">👤 حسابي الشخصي</h6>
            <a href="my_account.php" class="sidebar-link active">🛠️ تعديل الملف الشخصي</a>
            <a href="my_orders.php" class="sidebar-link">📦 طلباتي السابقة</a>
            <a href="wishlist.php" class="sidebar-link">❤️ قائمة المفضلة</a>
            <a href="logout.php" class="sidebar-link text-danger">🚪 تسجيل الخروج</a>
        </div>
    </div>

    <!-- فورم تعديل البيانات -->
    <div class="col-md-9 fade-in-up delay-1">
        <h2 class="page-title">⚙️ إدارة الحساب الشخصي</h2>

        <?php if ($error): ?>
            <div class="alert-modern alert-danger-modern mb-3">
                ⚠️ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-modern alert-success-modern mb-3">
                ✅ <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="checkout-card">
            <form method="POST" action="my_account.php">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">رقم الهاتف</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">اسم المستخدم (لا يمكن تغييره)</label>
                        <input type="text" class="form-control text-muted" disabled value="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">عنوان التوصيل الافتراضي</label>
                    <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">تغيير كلمة المرور (اتركها فارغة إذا لم تكن تريد تغييرها)</label>
                    <input type="password" name="password" class="form-control" placeholder="أدخل كلمة مرور جديدة">
                </div>

                <button type="submit" class="btn btn-success fw-bold px-4 py-2" style="background: linear-gradient(135deg, #16a34a, #15803d); border: none; border-radius: 10px;">
                    💾 حفظ التغييرات
                </button>
            </form>
        </div>
    </div>
</div>

<?php
echo '</div><footer class="main-footer mt-5"><p class="mb-0">© 2024 الهايبر ماركت المتكامل — جميع الحقوق محفوظة | PR122-3</p></footer></div>';
echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script></body></html>';
?>
