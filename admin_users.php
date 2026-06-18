<?php
ob_start();
include 'config.php';

// التحقق من صلاحية المدير
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

$current_admin_username = $_SESSION['admin_username'];

// 1. تبديل دور المستخدم (ترقية أو خفض الصلاحية)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_role' && isset($_GET['id'])) {
    $target_id = intval($_GET['id']);
    
    try {
        // جلب المستخدم للتحقق
        $stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
        $stmt->execute([$target_id]);
        $target_user = $stmt->fetch();

        if (!$target_user) {
            $message = "المستخدم غير موجود.";
            $message_type = "danger";
        } elseif ($target_user['username'] === $current_admin_username) {
            $message = "عذراً، لا يمكنك تغيير صلاحية حسابك النشط الذي تسجل الدخول به حالياً.";
            $message_type = "warning";
        } else {
            $new_role = $target_user['role'] === 'admin' ? 'customer' : 'admin';
            $update = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $update->execute([$new_role, $target_id]);
            $message = "تم تغيير صلاحية المستخدم '{$target_user['username']}' بنجاح إلى " . ($new_role === 'admin' ? 'مدير (Admin)' : 'عميل (Customer)') . ".";
            $message_type = "success";
        }
    } catch (\PDOException $e) {
        $message = "حدث خطأ: " . htmlspecialchars($e->getMessage());
        $message_type = "danger";
    }
}

// 2. حذف مستخدم
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $target_id = intval($_GET['id']);

    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$target_id]);
        $target_user = $stmt->fetch();

        if (!$target_user) {
            $message = "المستخدم غير موجود.";
            $message_type = "danger";
        } elseif ($target_user['username'] === $current_admin_username) {
            $message = "عذراً، لا يمكنك حذف حسابك الحالي الذي تسجل الدخول به.";
            $message_type = "warning";
        } else {
            $delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $delete->execute([$target_id]);
            $message = "تم حذف حساب المستخدم '{$target_user['username']}' بنجاح من النظام.";
            $message_type = "success";
        }
    } catch (\PDOException $e) {
        $message = "حدث خطأ أثناء الحذف: " . htmlspecialchars($e->getMessage());
        $message_type = "danger";
    }
}

// جلب جميع المستخدمين في النظام
$users_stmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, created_at DESC");
$users = $users_stmt->fetchAll();

include 'header.php';
?>

<!-- شريط التنقل للوحة التحكم -->
<div class="row mb-4 fade-in-up">
    <div class="col-12">
        <div class="glass-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h4 class="fw-bold text-success m-0">⚙️ إدارة مستخدمي النظام</h4>
            <div class="d-flex flex-wrap gap-2">
                <a href="admin_dashboard.php" class="btn btn-outline-success fw-bold btn-sm px-3">📊 الرئيسية</a>
                <a href="admin_products.php" class="btn btn-outline-success fw-bold btn-sm px-3">📦 المنتجات</a>
                <a href="admin_categories.php" class="btn btn-outline-success fw-bold btn-sm px-3">🏷️ الأقسام</a>
                <a href="admin_orders.php" class="btn btn-outline-success fw-bold btn-sm px-3">🧾 الطلبات</a>
                <a href="admin_users.php" class="btn btn-success fw-bold btn-sm active px-3">👥 المستخدمين</a>
                <a href="logout.php" class="btn btn-danger fw-bold btn-sm px-3">🚪 خروج</a>
            </div>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert-modern alert-<?php echo $message_type; ?>-modern mb-4 fade-in-up">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12 fade-in-up delay-1">
        <div class="glass-card">
            <div class="p-3 border-bottom border-secondary border-opacity-10">
                <h6 class="fw-bold text-white m-0">👥 قائمة المستخدمين والمدراء النشطين</h6>
            </div>
            <div class="table-responsive">
                <table class="table modern-table text-center align-middle mb-0">
                    <thead>
                        <tr>
                            <th>الرقم</th>
                            <th>الاسم الكامل</th>
                            <th>اسم المستخدم</th>
                            <th>البريد الإلكتروني</th>
                            <th>الهاتف</th>
                            <th>الدور / الصلاحية</th>
                            <th>تاريخ التسجيل</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td class="fw-bold text-white text-start"><?php echo htmlspecialchars($user['full_name'] ?: 'مدير النظام (مبدئي)'); ?></td>
                                <td class="text-success fw-bold"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="text-muted text-start small"><?php echo htmlspecialchars($user['email'] ?: 'بلا بريد إلكتروني'); ?></td>
                                <td class="text-muted small"><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge bg-danger px-3 py-1 fw-bold">⚙️ مدير (Admin)</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary px-3 py-1 fw-bold">👤 عميل (Customer)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-secondary"><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <?php if ($user['username'] !== $current_admin_username): ?>
                                            <a href="admin_users.php?action=toggle_role&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-outline-warning py-1 px-3"
                                               onclick="return confirm('هل أنت متأكد من رغبتك في تغيير رتبة هذا الحساب؟')">
                                                🔄 تبديل الدور
                                            </a>
                                            <a href="admin_users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger py-1 px-3"
                                               onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم بالكامل من قاعدة البيانات؟')">حذف</a>
                                        <?php else: ?>
                                            <span class="text-secondary small italic">حسابك الحالي</span>
                                        <?php endif; ?>
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

<!-- FOOTER -->
<footer class="main-footer mt-5">
    <p class="mb-0">© 2024 الهايبر ماركت المتكامل — جميع الحقوق محفوظة | PR122-3</p>
</footer>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
