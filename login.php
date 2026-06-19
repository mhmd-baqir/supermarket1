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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_type = $_POST['login_type'] ?? 'customer';
    $active_tab = $login_type;

    if ($login_type === 'customer') {
        $phone = trim($_POST['phone'] ?? '');
        if (empty($phone)) {
            $error = 'يرجى إدخال رقم الهاتف للدخول السريع.';
        } else {
            try {
                // جلب المستخدم ذو الدور 'customer' برقم الهاتف
                $stmt = $pdo->prepare("SELECT * FROM users WHERE (phone = ? OR username = ?) AND role = 'customer'");
                $stmt->execute([$phone, $phone]);
                $user = $stmt->fetch();

                if ($user) {
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
                    $error = 'رقم الهاتف هذا غير مسجل لدينا. يمكنك إنشاء حساب جديد مجاناً.';
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
                        <div class="mb-4">
                            <label class="form-label fw-bold">📞 رقم الهاتف للزبون</label>
                            <input type="text" name="phone" class="form-control form-control-lg" 
                                   required placeholder="أدخل رقم هاتفك المسجل (مثال: 07XXXXXXXX)"
                                   value="<?php echo $active_tab === 'customer' && isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                            <small class="text-muted mt-2 d-block">💡 دخول فوري وسريع دون الحاجة لكلمة مرور أو رمز بريدي.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-lg w-100 fw-bold mb-3"
                                style="background: linear-gradient(135deg,#16a34a,#15803d); color:white; border:none; border-radius:14px; padding:14px;">
                            🚀 دخول سريع للماركت
                        </button>
                    </form>
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

            <div class="text-center p-3 mt-4" 
                 style="background: var(--card-bg-secondary); border: 1px dashed var(--card-border); border-radius: 12px;">
                <div style="color: var(--text-muted); font-size: 0.75rem; margin-bottom: 4px;">حساب المسؤول الافتراضي للإدارة:</div>
                <div style="color: var(--accent-dark); font-weight: 700; font-size: 0.8rem;">
                    المستخدم: <code>admin</code> | الباسورد: <code>admin123</code>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAdminPassword() {
    const input = document.getElementById('admin_password');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

<?php include 'footer.php'; ?>

