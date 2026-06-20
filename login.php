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
$active_tab = 'customer'; // التبويب الافتراضي هو الزبائن

// التحقق من أخطاء Google OAuth
if (isset($_SESSION['google_error'])) {
    $error = $_SESSION['google_error'];
    unset($_SESSION['google_error']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_type = $_POST['login_type'] ?? 'customer';
    $active_tab = $login_type;

    if ($login_type === 'customer') {
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if (empty($phone) || empty($password)) {
            $error = 'يرجى إدخال رقم الهاتف وكلمة المرور للدخول.';
        } else {
            try {
                // جلب المستخدم ذو الدور 'customer' برقم الهاتف
                $stmt = $pdo->prepare("SELECT * FROM users WHERE (phone = ? OR username = ?) AND role = 'customer'");
                $stmt->execute([$phone, $phone]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['role']      = 'customer';
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    // التوجية لصفحة الشحن إذا كان قادماً منها، وإلا للرئيسية
                    $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $redirect");
                    exit;
                } else {
                    $error = 'رقم الهاتف أو كلمة المرور غير صحيحة، يرجى المحاولة مرة أخرى.';
                }
            } catch (\PDOException $e) {
                $error = 'حدث خطأ أثناء الاتصال بقاعدة البيانات: ' . htmlspecialchars($e->getMessage());
            }
        }
    } else { // admin
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'يرجى إدخال اسم المستخدم وكلمة المرور للمسؤول.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username']  = $user['username'];
                    $_SESSION['role']            = 'admin';
                    header('Location: admin_dashboard.php');
                    exit;
                } else {
                    $error = 'اسم مستخدم المسؤول أو كلمة المرور غير صحيحة!';
                }
            } catch (\PDOException $e) {
                $error = 'حدث خطأ أثناء الاتصال بقاعدة البيانات: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
include 'header.php';
?>

<div class="row justify-content-center align-items-center" style="min-height: 75vh;">
    <div class="col-md-6 col-lg-5 fade-in-up">
        <div class="text-center mb-4">
            <div style="font-size: 3.5rem;">🔐</div>
            <h2 class="fw-bold mt-2" style="color: var(--text-main);">تسجيل الدخول</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">مرحباً بك مجدداً في هايبر ماركت رضا أبو لحمة</p>
        </div>

        <div class="checkout-card">
            <?php if($error): ?>
                <div class="alert-modern alert-danger-modern p-3 rounded-3 mb-4">
                    ⚠️ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- تبويبات الدفع والتسجيل -->
            <ul class="nav nav-pills nav-fill mb-4 p-1 rounded-3" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
                <li class="nav-item">
                    <button class="nav-link fw-bold <?php echo $active_tab === 'customer' ? 'active text-white bg-success' : 'text-muted'; ?>" 
                            id="customer-tab" data-bs-toggle="pill" data-bs-target="#customer-form" type="button" role="tab" style="border-radius: 8px;">
                        👥 دخول الزبائن (سريع)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold <?php echo $active_tab === 'admin' ? 'active text-white bg-danger' : 'text-muted'; ?>" 
                            id="admin-tab" data-bs-toggle="pill" data-bs-target="#admin-form" type="button" role="tab" style="border-radius: 8px;">
                        🔐 دخول الإدارة
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- فورم الزبائن -->
                <div class="tab-pane fade <?php echo $active_tab === 'customer' ? 'show active' : ''; ?>" id="customer-form" role="tabpanel">
                    <form method="POST">
                        <input type="hidden" name="login_type" value="customer">
                        <div class="mb-3">
                            <label class="form-label fw-bold">📞 رقم الهاتف للزبون</label>
                            <input type="text" name="phone" class="form-control form-control-lg text-white" style="background:var(--input-bg);"
                                   required placeholder="أدخل رقم هاتفك المسجل (مثال: 07XXXXXXXX)"
                                   value="<?php echo $active_tab === 'customer' && isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                        </div>
                        <div class="mb-4 position-relative">
                            <label class="form-label fw-bold">🔑 كلمة المرور</label>
                            <input type="password" name="password" id="customer_password" class="form-control form-control-lg text-white" style="background:var(--input-bg);"
                                   required placeholder="أدخل كلمة المرور الخاصة بك">
                            <button type="button" onclick="toggleCustomerPassword()" 
                                     style="position:absolute; left:12px; top:42px; background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1.1rem;">
                                👁️
                            </button>
                        </div>
                        
                        <button type="submit" class="btn btn-lg w-100 fw-bold mb-3"
                                style="background: linear-gradient(135deg,#16a34a,#15803d); color:white; border:none; border-radius:14px; padding:14px;">
                            🚀 تسجيل الدخول للماركت
                        </button>
                    </form>

                    <!-- فاصل "أو" -->
                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1" style="border-color: rgba(255,255,255,0.1);">
                        <span class="px-3 text-muted small fw-bold">أو</span>
                        <hr class="flex-grow-1" style="border-color: rgba(255,255,255,0.1);">
                    </div>

                    <!-- زر تسجيل الدخول بـ Google -->
                    <a href="google_login.php" class="btn btn-lg w-100 fw-bold d-flex align-items-center justify-content-center gap-2"
                       style="background: #ffffff; color: #3c4043; border: 1px solid #dadce0; border-radius: 14px; padding: 12px 16px; transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.08);"
                       onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.15)'; this.style.background='#f8f9fa';"
                       onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'; this.style.background='#ffffff';">
                        <svg width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                        تسجيل الدخول بواسطة Google
                    </a>
                </div>

                <!-- فورم المسؤولين -->
                <div class="tab-pane fade <?php echo $active_tab === 'admin' ? 'show active' : ''; ?>" id="admin-form" role="tabpanel">
                    <form method="POST">
                        <input type="hidden" name="login_type" value="admin">
                        <div class="mb-3">
                            <label class="form-label fw-bold">👤 اسم مستخدم المدير</label>
                            <input type="text" name="username" class="form-control" 
                                   required placeholder="اسم المستخدم الخاص بالمدير"
                                   value="<?php echo $active_tab === 'admin' && isset($username) ? htmlspecialchars($username) : ''; ?>">
                        </div>
                        <div class="mb-4 position-relative">
                            <label class="form-label fw-bold">🔑 كلمة المرور</label>
                            <input type="password" name="password" id="admin_password" class="form-control" 
                                   required placeholder="أدخل كلمة المرور السرية">
                            <button type="button" onclick="toggleAdminPassword()" 
                                     style="position:absolute; left:12px; top:38px; background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1.1rem;">
                                👁️
                            </button>
                        </div>

                        <button type="submit" class="btn btn-lg w-100 fw-bold mb-3 btn-danger"
                                style="background: linear-gradient(135deg,#dc2626,#b91c1c); color:white; border:none; border-radius:14px; padding:14px;">
                            🔐 تسجيل دخول المسؤول
                        </button>
                    </form>
                </div>
            </div>

            <div class="text-center mt-3 pt-3 border-top border-secondary border-opacity-10">
                <span class="text-muted small">ليس لديك حساب؟ </span>
                <a href="register.php" class="text-success text-decoration-none fw-bold small">أنشئ حساباً جديداً بالهاتف فقط</a>
            </div>


        </div>
    </div>
</div>

<script>
function toggleAdminPassword() {
    const input = document.getElementById('admin_password');
    input.type = input.type === 'password' ? 'text' : 'password';
}
function toggleCustomerPassword() {
    const input = document.getElementById('customer_password');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

<?php include 'footer.php'; ?>

