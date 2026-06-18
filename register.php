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
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
        $error = 'يرجى ملء جميع الحقول الإلزامية (اسم المستخدم، كلمة المرور، الاسم الكامل، البريد الإلكتروني).';
    } else {
        try {
            // التحقق من تكرار اسم المستخدم
            $check_user = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check_user->execute([$username]);
            if ($check_user->fetch()) {
                $error = 'اسم المستخدم هذا مستخدم بالفعل، يرجى اختيار اسم آخر.';
            } else {
                // التحقق من تكرار البريد الإلكتروني
                $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $check_email->execute([$email]);
                if ($check_email->fetch()) {
                    $error = 'البريد الإلكتروني هذا مسجل بالفعل.';
                } else {
                    // تشفير كلمة المرور وإدخال المستخدم الجديد
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')");
                    $stmt->execute([$username, $hashed_password, $full_name, $email, $phone, $address]);

                    $new_user_id = $pdo->lastInsertId();

                    // تسجيل الدخول التلقائي
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'customer';
                    $_SESSION['full_name'] = $full_name;

                    header('Location: index.php');
                    exit;
                }
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
                    ⚠️ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <!-- الحقول الأساسية -->
                <div class="mb-3">
                    <label class="form-label">اسم المستخدم (للتسجيل) <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" placeholder="مثال: ahmed99" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <hr class="border-secondary my-3">

                <!-- البيانات الشخصية -->
                <div class="mb-3">
                    <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" placeholder="مثال: أحمد علي عبد الله" required value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="example@mail.com" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">العنوان الافتراضي للتوصيل</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="المدينة، المنطقة، اسم الشارع، معلم دال..."><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                </div>

                <button type="submit" class="btn btn-success w-100 fw-bold py-2 mb-3" style="background: linear-gradient(135deg, #16a34a, #15803d); border: none;">
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

<?php include 'header.php'; // سيتم إغلاقه عبر الفوتر ولكن لا بأس بالـ HTML والفوتر الملحق بالملف الرئيسي ?>
<?php
// تضمين الفوتر في PHP
echo '</div><footer class="main-footer mt-5"><p class="mb-0">© 2024 الهايبر ماركت المتكامل — جميع الحقوق محفوظة | PR122-3</p></footer></div>';
echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script></body></html>';
?>
