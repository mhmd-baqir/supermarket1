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
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $password  = $_POST['password'] ?? '';
    $avatar_name = $user['avatar']; // الافتراضي هو الملف الحالي

    if (empty($full_name)) {
        $error = 'الاسم الكامل حقل إلزامي.';
    } else {
        try {
            // معالجة رفع الصورة الشخصية
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['avatar']['tmp_name'];
                $fileName = $_FILES['avatar']['name'];
                $fileSize = $_FILES['avatar']['size'];
                $fileType = $_FILES['avatar']['type'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');
                
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    // اسم فريد للملف
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $uploadFileDir = 'uploads/';
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0755, true);
                    }
                    $dest_path = $uploadFileDir . $newFileName;
                    
                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        $avatar_name = $newFileName;
                    } else {
                        $error = 'حدث خطأ أثناء حفظ ملف الصورة.';
                    }
                } else {
                    $error = 'صيغة الصورة غير مدعومة. يرجى اختيار صورة بصيغة: JPG, JPEG, PNG, WEBP';
                }
            }

            if (empty($error)) {
                // تحديث البيانات الأساسية في جدول المستخدمين
                if (!empty($password)) {
                    // تحديث بكلمة مرور جديدة
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ?, password = ?, avatar = ? WHERE id = ?");
                    $update_stmt->execute([$full_name, $phone, $address, $hashed_password, $avatar_name, $user_id]);
                } else {
                    // تحديث بدون تغيير كلمة المرور
                    $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ?, avatar = ? WHERE id = ?");
                    $update_stmt->execute([$full_name, $phone, $address, $avatar_name, $user_id]);
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
            <form method="POST" action="my_account.php" enctype="multipart/form-data">
                
                <!-- عرض وتحميل الصورة الشخصية -->
                <div class="mb-4 d-flex align-items-center gap-4 p-3 rounded-3" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
                    <img src="<?php echo (!empty($user['avatar']) && file_exists('uploads/' . $user['avatar'])) ? 'uploads/' . htmlspecialchars($user['avatar']) : 'https://cdn-icons-png.flaticon.com/512/149/149071.png'; ?>" 
                         alt="Avatar" class="rounded-circle shadow" style="width: 90px; height: 90px; object-fit: cover; border: 3px solid var(--primary);">
                    <div>
                        <label class="form-label fw-bold text-success">👤 الصورة الشخصية</label>
                        <input type="file" name="avatar" class="form-control text-white" style="background: var(--input-bg);" accept="image/*">
                        <small class="text-muted mt-1 d-block">الامتدادات المسموحة: JPG, PNG, JPEG, WEBP</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control text-white" style="background:var(--input-bg);" required value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">رقم الهاتف</label>
                        <input type="text" name="phone" class="form-control text-white" style="background:var(--input-bg);" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
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
include 'footer.php';
?>
