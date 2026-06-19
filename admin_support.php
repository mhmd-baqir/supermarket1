<?php
ob_start();
include 'config.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}

$message = '';
$message_type = '';

// جلب تفاصيل المسؤول الحالي للحصول على معرفه
$admin_username = $_SESSION['admin_username'];
$adm_stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE username = ? AND role = 'admin'");
$adm_stmt->execute([$admin_username]);
$admin_data = $adm_stmt->fetch();
$admin_id = $admin_data ? $admin_data['id'] : 0;
$admin_name = $admin_data ? ($admin_data['full_name'] ?: $admin_username) : 'المشرف';

// 1. معالجة إرسال الرد أو إغلاق التذكرة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $reply_text = trim($_POST['reply']);

    if (empty($reply_text)) {
        $message = "الرجاء كتابة نص الرد."; $message_type = "danger";
    } else {
        // تحديث التذكرة بالرد وتغيير حالتها
        $update = $pdo->prepare("UPDATE support_messages SET admin_reply = ?, status = 'replied' WHERE id = ?");
        $update->execute([$reply_text, $ticket_id]);

        // جلب تفاصيل التذكرة لإشعار المستخدم
        $ticket_stmt = $pdo->prepare("
            SELECT sm.*, c.name as category_name 
            FROM support_messages sm 
            JOIN categories c ON sm.category_id = c.id 
            WHERE sm.id = ?
        ");
        $ticket_stmt->execute([$ticket_id]);
        $t_info = $ticket_stmt->fetch();

        if ($t_info) {
            $customer_id = $t_info['user_id'];
            $cat_name = $t_info['category_name'];
            $notif_msg = "تم الرد على استفسارك بخصوص قسم '{$cat_name}' من قبل المشرف المختص.";
            
            // إرسال إشعار للعميل
            $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, link) 
                VALUES (?, '💬 رد جديد من الدعم الفني', ?, 'system', 'support.php')
            ")->execute([$customer_id, $notif_msg]);
        }

        $message = "✅ تم إرسال الرد بنجاح وإشعار العميل.";
        $message_type = "success";
    }
}

// 2. معالجة إغلاق التذكرة من قبل المسؤول
if (isset($_GET['action']) && $_GET['action'] === 'close' && isset($_GET['id'])) {
    $ticket_id = intval($_GET['id']);
    $pdo->prepare("UPDATE support_messages SET status = 'closed' WHERE id = ?")->execute([$ticket_id]);
    $message = "تم إغلاق تذكرة الدعم بنجاح.";
    $message_type = "info";
}

// 3. جلب الأقسام المرتبطة بهذا المسؤول
$my_cats_stmt = $pdo->prepare("SELECT id, name FROM categories WHERE admin_id = ?");
$my_cats_stmt->execute([$admin_id]);
$my_categories = $my_cats_stmt->fetchAll();
$my_cat_ids = array_column($my_categories, 'id');

// فلترة التذاكر
$filter = isset($_GET['filter']) ? $_GET['filter'] : (empty($my_cat_ids) ? 'all' : 'my_depts');

$query = "
    SELECT sm.*, c.name as category_name, c.admin_id as category_admin_id, u.full_name as customer_name, u.username as customer_username
    FROM support_messages sm
    JOIN categories c ON sm.category_id = c.id
    JOIN users u ON sm.user_id = u.id
";

$params = [];
if ($filter === 'my_depts' && !empty($my_cat_ids)) {
    $placeholders = implode(',', array_fill(0, count($my_cat_ids), '?'));
    $query .= " WHERE sm.category_id IN ($placeholders)";
    $params = $my_cat_ids;
} elseif ($filter === 'pending') {
    $query .= " WHERE sm.status = 'pending'";
}

$query .= " ORDER BY sm.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// جلب تفاصيل تذكرة معينة لعرضها
$view_ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$view_ticket = null;
if ($view_ticket_id > 0) {
    $vt_stmt = $pdo->prepare("
        SELECT sm.*, c.name as category_name, u.full_name as customer_name, u.username as customer_username, u.phone as customer_phone
        FROM support_messages sm
        JOIN categories c ON sm.category_id = c.id
        JOIN users u ON sm.user_id = u.id
        WHERE sm.id = ?
    ");
    $vt_stmt->execute([$view_ticket_id]);
    $view_ticket = $vt_stmt->fetch();
}

include 'header.php';
?>

<!-- شريط التنقل للوحة التحكم -->
<div class="row mb-4 fade-in-up">
  <div class="col-12">
    <div class="glass-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
      <h4 class="fw-bold m-0" style="color:var(--primary);">⚙️ مركز الدعم الفني والمراسلة</h4>
      <div class="d-flex flex-wrap gap-2">
        <a href="admin_dashboard.php"  class="btn btn-outline-success fw-bold btn-sm px-3">📊 الرئيسية</a>
        <a href="admin_products.php"   class="btn btn-outline-success fw-bold btn-sm px-3">📦 المنتجات</a>
        <a href="admin_categories.php" class="btn btn-outline-success fw-bold btn-sm px-3">🏷️ الأقسام</a>
        <a href="admin_orders.php"     class="btn btn-outline-success fw-bold btn-sm px-3">🧾 الطلبات</a>
        <a href="admin_support.php"    class="btn btn-success fw-bold btn-sm px-3" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));border:none;">💬 الرسائل والدعم</a>
        <a href="admin_users.php"      class="btn btn-outline-success fw-bold btn-sm px-3">👥 المستخدمين</a>
        <a href="logout.php"           class="btn btn-danger fw-bold btn-sm px-3">🚪 خروج</a>
      </div>
    </div>
  </div>
</div>

<?php if ($message): ?>
  <div class="alert-modern alert-<?= $message_type ?>-modern mb-4 p-3 rounded-3 fade-in-up"><?= $message ?></div>
<?php endif; ?>

<div class="row g-4">
  <!-- تفاصيل التذكرة المحددة والرد عليها -->
  <?php if ($view_ticket): ?>
    <div class="col-12 fade-in-up">
      <div class="glass-card overflow-hidden mb-4" style="border-color: rgba(13, 202, 240, 0.4);">
        <div class="p-3 d-flex justify-content-between align-items-center" style="background: rgba(13, 202, 240, 0.08); border-bottom: 1px solid var(--card-border);">
          <h5 class="fw-bold m-0 text-info">💬 الرد على التذكرة رقم #<?= $view_ticket['id'] ?></h5>
          <a href="admin_support.php?filter=<?= $filter ?>" class="btn btn-sm btn-outline-secondary fw-bold">✕ إغلاق</a>
        </div>
        <div class="p-4">
          <div class="row g-3 mb-4">
            <div class="col-md-6 small text-muted">
              <div>👤 <strong>اسم العميل:</strong> <?= htmlspecialchars($view_ticket['customer_name'] ?: $view_ticket['customer_username']) ?></div>
              <div>📱 <strong>الهاتف:</strong> <?= htmlspecialchars($view_ticket['customer_phone'] ?: '-') ?></div>
            </div>
            <div class="col-md-6 small text-muted">
              <div>📁 <strong>القسم المعني:</strong> <?= htmlspecialchars($view_ticket['category_name']) ?></div>
              <?php if ($view_ticket['order_id']): ?>
                <div>📦 <strong>الطلب المرتبط:</strong> <a href="admin_orders.php?id=<?= $view_ticket['order_id'] ?>" class="text-success fw-bold text-decoration-none">#<?= $view_ticket['order_id'] ?> (تفاصيل الطلب)</a></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="p-3 rounded-3 mb-4 text-light bg-dark bg-opacity-25" style="border-right: 4px solid var(--primary);">
            <div class="fw-bold text-success mb-1">📝 موضوع المشكلة: <?= htmlspecialchars($view_ticket['subject']) ?></div>
            <p class="mb-0 mt-2 fs-6"><?= nl2br(htmlspecialchars($view_ticket['message'])) ?></p>
            <small class="text-muted d-block mt-2">📅 تم الإرسال في: <?= date('Y-m-d H:i', strtotime($view_ticket['created_at'])) ?></small>
          </div>

          <?php if (!empty($view_ticket['admin_reply'])): ?>
            <div class="p-3 rounded-3 mb-4 text-light bg-info bg-opacity-10 border border-info border-opacity-25" style="border-right: 4px solid #0dcaf0;">
              <div class="fw-bold text-info mb-1">✍️ الرد الحالي للمسؤول:</div>
              <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($view_ticket['admin_reply'])) ?></p>
            </div>
          <?php endif; ?>

          <?php if ($view_ticket['status'] !== 'closed'): ?>
            <form method="POST" action="admin_support.php?id=<?= $view_ticket['id'] ?>&filter=<?= $filter ?>">
              <input type="hidden" name="ticket_id" value="<?= $view_ticket['id'] ?>">
              <div class="mb-3">
                <label class="form-label fw-bold">اكتب ردك للعميل</label>
                <textarea name="reply" class="form-control text-white" rows="4" placeholder="اكتب الحل المقترح أو الاستفسار لتوجيهه للعميل..." required></textarea>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" name="submit_reply" class="btn btn-success fw-bold px-4">
                  🚀 إرسال الرد
                </button>
                <a href="admin_support.php?action=close&id=<?= $view_ticket['id'] ?>&filter=<?= $filter ?>" 
                   class="btn btn-outline-danger fw-bold"
                   onclick="return confirm('هل تريد إغلاق التذكرة نهائياً؟')">
                  🔒 إغلاق التذكرة
                </a>
              </div>
            </form>
          <?php else: ?>
            <div class="alert alert-secondary border-0 text-center fw-bold bg-secondary bg-opacity-10 text-secondary">
              🔒 هذه التذكرة مغلقة ولا يمكن الرد عليها حالياً.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- قائمة التذاكر والرسائل -->
  <div class="col-12 fade-in-up delay-1">
    <div class="glass-card overflow-hidden">
      <div class="p-3 d-flex flex-wrap align-items-center justify-content-between gap-3" style="border-bottom:1px solid var(--card-border);">
        <h6 class="fw-bold m-0" style="color:var(--text-main);">📋 الرسائل والاستفسارات الموجهة للدعم</h6>
        
        <div class="d-flex gap-2 flex-wrap">
          <?php if (!empty($my_cat_ids)): ?>
            <a href="admin_support.php?filter=my_depts" class="btn btn-sm <?= $filter === 'my_depts' ? 'btn-success' : 'btn-outline-success' ?> fw-bold">📁 أقسامي المختصة (<?= count($my_categories) ?>)</a>
          <?php endif; ?>
          <a href="admin_support.php?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-success' : 'btn-outline-success' ?> fw-bold">الكل</a>
          <a href="admin_support.php?filter=pending" class="btn btn-sm <?= $filter === 'pending' ? 'btn-warning text-dark' : 'btn-outline-warning' ?> fw-bold">⏳ قيد الانتظار فقط</a>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table modern-table text-center align-middle mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>العميل</th>
              <th>القسم المعني</th>
              <th>الموضوع</th>
              <th>الطلب</th>
              <th>الحالة</th>
              <th>التاريخ</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($tickets) > 0): ?>
              <?php foreach ($tickets as $t): ?>
                <tr style="<?= ($t['status'] === 'pending') ? 'background: rgba(245, 158, 11, 0.02);' : '' ?>">
                  <td class="fw-bold">#<?= $t['id'] ?></td>
                  <td class="fw-bold text-start" style="color: var(--text-main);"><?= htmlspecialchars($t['customer_name'] ?: $t['customer_username']) ?></td>
                  <td class="text-success fw-bold text-start small"><?= htmlspecialchars($t['category_name']) ?></td>
                  <td class="text-start small"><?= htmlspecialchars($t['subject']) ?></td>
                  <td>
                    <?php if ($t['order_id']): ?>
                      <span class="badge bg-success bg-opacity-15 text-success">#<?= $t['order_id'] ?></span>
                    <?php else: ?>
                      <span class="text-muted small">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php
                    $s = $t['status'];
                    if ($s === 'pending') {
                        echo '<span class="badge bg-warning text-dark">⏳ انتظار</span>';
                    } elseif ($s === 'replied') {
                        echo '<span class="badge bg-info text-dark">💬 تم الرد</span>';
                    } elseif ($s === 'closed') {
                        echo '<span class="badge bg-secondary">🔒 مغلقة</span>';
                    }
                    ?>
                  </td>
                  <td class="small text-muted"><?= date('m-d H:i', strtotime($t['created_at'])) ?></td>
                  <td>
                    <a href="admin_support.php?id=<?= $t['id'] ?>&filter=<?= $filter ?>" class="btn btn-sm btn-outline-success fw-bold py-1 px-3">
                      🔎 عرض والرد
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-muted p-4">لا توجد رسائل دعم مطابقة للمعايير المحددة.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
