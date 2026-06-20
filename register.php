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

            <!-- فاصل "أو" -->
            <div class="d-flex align-items-center my-3">
                <hr class="flex-grow-1" style="border-color: rgba(255,255,255,0.1);">
                <span class="px-3 text-muted small fw-bold">أو سجّل بسرعة</span>
                <hr class="flex-grow-1" style="border-color: rgba(255,255,255,0.1);">
            </div>

            <!-- زر التسجيل بـ Google -->
            <a href="google_login.php" class="btn btn-lg w-100 fw-bold d-flex align-items-center justify-content-center gap-2 mb-3"
               style="background: #ffffff; color: #3c4043; border: 1px solid #dadce0; border-radius: 14px; padding: 12px 16px; transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.08);"
               onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.15)'; this.style.background='#f8f9fa';"
               onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'; this.style.background='#ffffff';">
                <svg width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                التسجيل بواسطة Google
            </a>

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
