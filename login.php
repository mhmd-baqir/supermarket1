<?php
ob_start();
include 'config.php';

// إذا كان المستخدم مسجلاً بالفعل، يتم توجيهه للرئيسية
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
} elseif (isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['role'] === 'admin') {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username']  = $user['username'];
                    $_SESSION['role']            = 'admin';
                    header('Location: admin_dashboard.php');
                    exit;
                } else {
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['role']      = 'customer';
                    $_SESSION['full_name'] = $user['full_name'];
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = 'اسم المستخدم أو كلمة المرور غير صحيحة!';
            }
        } catch (\PDOException $e) {
            $error = 'حدث خطأ أثناء الاتصال بقاعدة البيانات: ' . htmlspecialchars($e->getMessage());
        }
    }
}
include 'header.php';
?>

<div class="row justify-content-center align-items-center" style="min-height: 75vh;">
    <div class="col-md-5 col-lg-4 fade-in-up">
        <div class="text-center mb-4">
            <div style="font-size: 3.5rem;">🔐</div>
            <h2 class="fw-bold mt-2" style="color: #f1f5f9;">تسجيل الدخول</h2>
            <p style="color: #94a3b8; font-size: 0.9rem;">مرحباً بك مجدداً في الهايبر ماركت المتكامل</p>
        </div>

        <div class="checkout-card">
            <?php if($error): ?>
                <div class="alert-modern alert-danger-modern p-3 rounded-3 mb-4">
                    ⚠️ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="mb-4">
                    <label class="form-label">👤 اسم المستخدم</label>
                    <input type="text" name="username" id="username" class="form-control" 
                           required placeholder="أدخل اسم المستخدم"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                <div class="mb-4 position-relative">
                    <label class="form-label">🔑 كلمة المرور</label>
                    <input type="password" name="password" id="password" class="form-control" 
                           required placeholder="أدخل كلمة المرور">
                    <button type="button" onclick="togglePassword()" 
                             style="position:absolute; left:12px; top:38px; background:none; border:none; color:#94a3b8; cursor:pointer; font-size:1.1rem;">
                        👁️
                    </button>
                </div>

                <button type="submit" class="btn btn-lg w-100 fw-bold mb-3"
                        style="background: linear-gradient(135deg,#16a34a,#15803d); color:white; border:none; border-radius:14px; padding:14px;">
                    تسجيل الدخول
                </button>
            </form>

            <div class="text-center mb-3">
                <span class="text-muted small">ليس لديك حساب؟ </span>
                <a href="register.php" class="text-success text-decoration-none fw-bold small">أنشئ حساباً جديداً</a>
            </div>

            <div class="text-center p-3 mt-2" 
                 style="background: rgba(245,158,11,0.05); border: 1px dashed rgba(245,158,11,0.2); border-radius: 12px;">
                <div style="color: #94a3b8; font-size: 0.75rem; margin-bottom: 4px;">حساب المسؤول الافتراضي:</div>
                <div style="color: #fbbf24; font-weight: 700; font-size: 0.8rem;">
                    المستخدم: <code>admin</code> | الباسورد: <code>admin123</code>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="main-footer mt-5">
    <p class="mb-0">© 2024 الهايبر ماركت المتكامل — جميع الحقوق محفوظة</p>
</footer>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
