<?php
ob_start();
include 'config.php';

// فرض تسجيل الدخول للعميل
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// 1. معالجة إرسال رسالة دعم جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $category_id = intval($_POST['category_id']);
    $order_id    = !empty($_POST['order_id']) ? intval($_POST['order_id']) : null;
    $subject     = trim($_POST['subject']);
    $msg_text    = trim($_POST['message']);

    if (empty($subject) || empty($msg_text) || $category_id <= 0) {
        $message = "الرجاء ملء جميع الحقول المطلوبة واختيار القسم المعني.";
        $message_type = "danger";
    } else {
        // إدخال رسالة الدعم
        $stmt = $pdo->prepare("INSERT INTO support_messages (user_id, category_id, order_id, subject, message, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $category_id, $order_id, $subject, $msg_text]);
        $msg_id = $pdo->lastInsertId();

        // جلب اسم القسم
        $cat_stmt = $pdo->prepare("SELECT name, admin_id FROM categories WHERE id = ?");
        $cat_stmt->execute([$category_id]);
        $cat = $cat_stmt->fetch();
        $cat_name = $cat ? $cat['name'] : 'عام';
        $admin_id = $cat ? $cat['admin_id'] : null;

        // إرسال إشعار للمشرف المعني (أو لجميع المدراء)
        $sender_name = $_SESSION['full_name'] ?? $_SESSION['username'];
        $notif_title = "💬 رسالة دعم جديدة #{$msg_id}";
        $notif_desc = "أرسل العميل '{$sender_name}' رسالة بخصوص قسم '{$cat_name}': " . mb_substr($subject, 0, 50) . "...";
        
        // إشعار عام للوحة الإدارة (يظهر للجميع)
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (NULL, ?, ?, 'alert', 'admin_support.php')");
        $notif_stmt->execute([$notif_title, $notif_desc]);

        $message = "✅ تم إرسال رسالتك بنجاح إلى القسم المعني. سيقوم المشرف بالرد عليك قريباً.";
        $message_type = "success";
    }
}

// 2. معالجة إغلاق الرسالة من قبل العميل
if (isset($_GET['action']) && $_GET['action'] === 'close' && isset($_GET['msg_id'])) {
    $msg_id = intval($_GET['msg_id']);
    // التأكد من أن الرسالة تخص المستخدم
    $check = $pdo->prepare("SELECT id FROM support_messages WHERE id = ? AND user_id = ?");
    $check->execute([$msg_id, $user_id]);
    if ($check->fetch()) {
        $pdo->prepare("UPDATE support_messages SET status = 'closed' WHERE id = ?")->execute([$msg_id]);
        $message = "تم إغلاق تذكرة الدعم بنجاح.";
        $message_type = "info";
    }
}

// جلب تفاصيل الطلب إذا كان ممرراً عبر الرابط
$pre_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$pre_order_categories = [];
if ($pre_order_id > 0) {
    // جلب تصنيفات المنتجات الموجودة في هذا الطلب لتسهيل مراسلة القسم المعني
    $cat_stmt = $pdo->prepare("
        SELECT DISTINCT c.id, c.name 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        JOIN categories c ON p.category_id = c.id 
        WHERE oi.order_id = ?
    ");
    $cat_stmt->execute([$pre_order_id]);
    $pre_order_categories = $cat_stmt->fetchAll();
}

// جلب جميع تصنيفات المتجر
$all_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

// جلب رسائل الدعم السابقة للعميل
$my_messages = $pdo->prepare("
    SELECT sm.*, c.name as category_name 
    FROM support_messages sm 
    JOIN categories c ON sm.category_id = c.id 
    WHERE sm.user_id = ? 
    ORDER BY sm.created_at DESC
");
$my_messages->execute([$user_id]);
$support_tickets = $my_messages->fetchAll();

include 'header.php';
?>

<div class="row">
    <!-- قائمة العميل الجانبية -->
    <div class="col-md-3 mb-4 fade-in-up">
        <div class="sidebar-card">
            <h6 class="sidebar-title">👤 حسابي الشخصي</h6>
            <a href="my_account.php" class="sidebar-link">🛠️ تعديل الملف الشخصي</a>
            <a href="my_orders.php" class="sidebar-link">📦 طلباتي السابقة</a>
            <a href="wishlist.php" class="sidebar-link">❤️ قائمة المفضلة</a>
            <a href="support.php" class="sidebar-link active">💬 الدعم والمراسلة</a>
            <a href="logout.php" class="sidebar-link text-danger">🚪 تسجيل الخروج</a>
        </div>
    </div>

    <!-- لوحة الدعم الفني -->
    <div class="col-md-9 fade-in-up delay-1">
        <h2 class="page-title">💬 الدعم الفني ومراسلة الأقسام</h2>

        <?php if ($message): ?>
            <div class="alert-modern alert-<?php echo $message_type; ?>-modern mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- نموذج إرسال رسالة جديدة -->
            <div class="col-lg-5">
                <div class="glass-card p-4">
                    <h5 class="fw-bold mb-3 text-success">✉️ تذكرة دعم جديدة</h5>
                    <form method="POST" action="support.php">
                        
                        <?php if ($pre_order_id > 0): ?>
                            <div class="alert alert-info py-2 small mb-3 border-0 bg-info bg-opacity-10 text-info">
                                🔗 هذه الرسالة مرتبطة بالطلب رقم <strong>#<?php echo $pre_order_id; ?></strong>
                            </div>
                            <input type="hidden" name="order_id" value="<?php echo $pre_order_id; ?>">
                        <?php else: ?>
                            <!-- اختيار الطلب (اختياري) -->
                            <div class="mb-3">
                                <label class="form-label">ربط بطلب سابق (اختياري)</label>
                                <select name="order_id" class="form-select text-white" style="background: var(--input-bg);">
                                    <option value="">— اختر الطلب المرتبط بالمشكلة —</option>
                                    <?php
                                    $orders_stmt = $pdo->prepare("SELECT id, total, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
                                    $orders_stmt->execute([$user_id]);
                                    while ($o = $orders_stmt->fetch()) {
                                        echo "<option value='{$o['id']}'>طلب #{$o['id']} ({$o['total']} د.ع) - " . date('Y/m/d', strtotime($o['created_at'])) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <!-- اختيار القسم المعني -->
                        <div class="mb-3">
                            <label class="form-label">القسم المعني بمشكلتك <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select text-white" style="background: var(--input-bg);" required>
                                <?php if (!empty($pre_order_categories)): ?>
                                    <option value="">— اختر من أقسام منتجات الطلب —</option>
                                    <?php foreach ($pre_order_categories as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" selected><?php echo htmlspecialchars($c['name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <option value="">— اختر القسم المختص —</option>
                                <?php foreach ($all_categories as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted d-block mt-1">سيتم توجيه رسالتك إلى المدير المسؤول عن هذا القسم مباشرة لحلها.</small>
                        </div>

                        <!-- موضوع الرسالة -->
                        <div class="mb-3">
                            <label class="form-label">عنوان المشكلة / الموضوع <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control text-white" placeholder="مثال: تأخير التوصيل، نقص في المواد المرسلة" required>
                        </div>

                        <!-- نص الرسالة -->
                        <div class="mb-3">
                            <label class="form-label">تفاصيل المشكلة بالتفصيل <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control text-white" rows="5" placeholder="اكتب تفاصيل مشكلتك هنا بوضوح لكي نتمكن من مساعدتك..." required></textarea>
                        </div>

                        <button type="submit" name="send_message" class="btn btn-success fw-bold w-100 py-2" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border: none;">
                            🚀 إرسال الرسالة للمشرف
                        </button>
                    </form>
                </div>
            </div>

            <!-- قائمة الرسائل السابقة ومتابعتها -->
            <div class="col-lg-7">
                <div class="glass-card p-4">
                    <h5 class="fw-bold mb-3">📋 تذاكر الدعم السابقة وتتبعها</h5>

                    <?php if (count($support_tickets) === 0): ?>
                        <div class="text-center py-5 text-muted">
                            <div style="font-size: 3rem;">💬</div>
                            <p class="mt-2">لا توجد رسائل دعم سابقة لديك.</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($support_tickets as $ticket): ?>
                                <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold text-success">#<?php echo $ticket['id']; ?> - <?php echo htmlspecialchars($ticket['subject']); ?></span>
                                        <div>
                                            <?php
                                            $status = $ticket['status'];
                                            if ($status === 'pending') {
                                                echo '<span class="badge bg-warning text-dark">⏳ قيد الانتظار</span>';
                                            } elseif ($status === 'replied') {
                                                echo '<span class="badge bg-info text-dark">💬 تم الرد</span>';
                                            } elseif ($status === 'closed') {
                                                echo '<span class="badge bg-secondary">🔒 مغلقة</span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="small text-muted mb-2">
                                        📁 القسم: <strong><?php echo htmlspecialchars($ticket['category_name']); ?></strong>
                                        <?php if ($ticket['order_id']): ?>
                                            | 📦 الطلب المرتبط: <strong>#<?php echo $ticket['order_id']; ?></strong>
                                        <?php endif; ?>
                                        | 📅 التاريخ: <?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?>
                                    </div>
                                    
                                    <div class="p-2 rounded mb-2 small text-light bg-dark bg-opacity-25" style="border-right: 3px solid var(--primary);">
                                        <strong>رسالتك:</strong> <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                                    </div>

                                    <?php if (!empty($ticket['admin_reply'])): ?>
                                        <div class="p-2 rounded mb-2 small text-dark bg-info bg-opacity-10 border border-info border-opacity-25" style="border-right: 3px solid #0dcaf0; color: var(--text-main) !important;">
                                            <strong class="text-info d-block">✍️ رد المشرف المختص:</strong>
                                            <span style="color: var(--text-main);"><?php echo nl2br(htmlspecialchars($ticket['admin_reply'])); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-end gap-2 mt-2">
                                        <?php if ($ticket['status'] !== 'closed'): ?>
                                            <a href="support.php?action=close&msg_id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-secondary fw-bold px-3 py-1" onclick="return confirm('هل تريد إغلاق تذكرة الدعم هذه؟')">
                                                🔒 إغلاق التذكرة
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
