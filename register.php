<?php
ob_start();
include 'config.php';

// إذا كان المستخدم مسجلاً بالفعل، يتم توجيهه للرئيسية
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone     = trim($_POST['phone'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $password  = trim($_POST['password'] ?? '');

    if (empty($phone) || empty($full_name) || empty($password)) {
        $error = 'يرجى ملء جميع الحقول الإلزامية (رقم الهاتف، كلمة المرور، الاسم الكامل).';
    } else {
        try {
            // التحقق من تكرار رقم الهاتف
            $check_user = $pdo->prepare("SELECT id FROM users WHERE username = ? OR phone = ?");
            $check_user->execute([$phone, $phone]);
            if ($check_user->fetch()) {
                $error = 'رقم الهاتف هذا مسجل بالفعل، يرجى تسجيل الدخول أو استخدام رقم آخر.';
            } else {
                // تشفير كلمة المرور وإدخال المستخدم الجديد
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                // نسجل الهاتف في خانتي username و phone لتوافق النظام
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, phone, address, role) VALUES (?, ?, ?, NULL, ?, ?, 'customer')");
                $stmt->execute([$phone, $hashed_password, $full_name, $phone, $address]);

                $new_user_id = $pdo->lastInsertId();

                // تسجيل الدخول التلقائي
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['username'] = $phone;
                $_SESSION['role'] = 'customer';
                $_SESSION['full_name'] = $full_name;

                header('Location: index.php');
                exit;
            }
        } catch (\PDOException $e) {
            $error = 'حدث خطأ أثناء التسجيل: ' . htmlspecialchars($e->getMessage());
        }
    }
}
include 'header.php';
?>

<div class="row justify-content-center align-items-center" style="min-height: 75vh;">
    <div class="col-md-6 col-lg-5 fade-in-up">
        <div class="glass-card p-4 shadow-lg border border-success border-opacity-25">
            <div class="text-center mb-4">
                <h3 class="fw-bold text-success">📝 إنشاء حساب جديد</h3>
                <p class="text-muted small">انضم إلينا واستمتع بتسوق سهل وتتبع طلباتك</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-modern alert-danger-modern mb-3">
                     <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <!-- الحقول الأساسية -->
                <div class="mb-3">
                    <label class="form-label fw-bold">رقم الهاتف <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" required value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">كلمة المرور <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="أدخل كلمة مرور قوية للمستقبل" required>
                </div>

                <!-- البيانات الشخصية -->
                <div class="mb-3">
                    <label class="form-label fw-bold">الاسم الكامل <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" placeholder="مثال: أحمد علي عبد الله" required value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">العنوان الافتراضي للتوصيل</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="المدينة، المنطقة، حي الحسين، اسم الشارع، معلم دال..."><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                </div>

                <button type="submit" class="btn btn-success w-100 fw-bold py-2 mb-3" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border: none;">
                    🚀 إكمال التسجيل
                </button>
            </form>

            <div class="text-center">
                <span class="text-muted small">لديك حساب بالفعل؟ </span>
                <a href="login.php" class="text-success text-decoration-none fw-bold small">تسجيل الدخول هنا</a>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>
