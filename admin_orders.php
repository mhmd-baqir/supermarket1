<?php
ob_start();
include 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}

$message = ''; $message_type = '';

// ====== تحديث حالة الطلب ======
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_status'])) {
    $order_id  = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    if (in_array($new_status, ['pending','processing','completed','cancelled'])) {
        $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$new_status, $order_id]);
        $message = "تم تحديث حالة الطلب #{$order_id}."; $message_type='success';
    }
}

// ====== تعيين سائق للطلب ======
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['assign_driver'])) {
    $order_id  = intval($_POST['order_id']);
    $driver_id = intval($_POST['driver_id']) ?: null;
    // تحديث حالة الطلب إلى "قيد التجهيز" عند تعيين سائق
    if ($driver_id) {
        $pdo->prepare("UPDATE orders SET driver_id=?, status='processing' WHERE id=?")->execute([$driver_id, $order_id]);
        $pdo->prepare("UPDATE drivers SET status='busy' WHERE id=?")->execute([$driver_id]);
        $message="✅ تم تعيين السائق للطلب #{$order_id} وتحديث حالته."; $message_type='success';
    } else {
        $pdo->prepare("UPDATE orders SET driver_id=NULL WHERE id=?")->execute([$order_id]);
        $message="تم إلغاء تعيين السائق من الطلب #{$order_id}."; $message_type='info';
    }
}

// ====== إضافة سائق ======
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_driver'])) {
    $dname   = trim($_POST['driver_name']);
    $dphone  = trim($_POST['driver_phone']);
    $dvehicle= trim($_POST['driver_vehicle'] ?? 'دراجة نارية');
    if ($dname && $dphone) {
        $pdo->prepare("INSERT INTO drivers (name,phone,vehicle) VALUES (?,?,?)")->execute([$dname,$dphone,$dvehicle]);
        $message="✅ تم إضافة السائق $dname بنجاح."; $message_type='success';
    }
}

// ====== تغيير حالة السائق ======
if (isset($_GET['driver_status'])) {
    $did   = intval($_GET['did']);
    $dstat = $_GET['driver_status'];
    if (in_array($dstat,['available','busy','offline'])) {
        $pdo->prepare("UPDATE drivers SET status=? WHERE id=?")->execute([$dstat,$did]);
        header("Location: admin_orders.php"); exit;
    }
}

// ====== حذف سائق ======
if (isset($_GET['del_driver'])) {
    $pdo->prepare("UPDATE orders SET driver_id=NULL WHERE driver_id=?")->execute([intval($_GET['del_driver'])]);
    $pdo->prepare("DELETE FROM drivers WHERE id=?")->execute([intval($_GET['del_driver'])]);
    header("Location: admin_orders.php"); exit;
}

// جلب تفاصيل طلب
$view_order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$view_order = null; $order_items = []; $assigned_driver = null;

if ($view_order_id > 0) {
    $view_order  = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    $view_order->execute([$view_order_id]);
    $view_order = $view_order->fetch();
    if ($view_order) {
        $si = $pdo->prepare("SELECT oi.*, p.name, p.unit FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
        $si->execute([$view_order_id]);
        $order_items = $si->fetchAll();
        if ($view_order['driver_id']) {
            $sd = $pdo->prepare("SELECT * FROM drivers WHERE id=?");
            $sd->execute([$view_order['driver_id']]);
            $assigned_driver = $sd->fetch();
        }
    }
}

// فلترة الطلبات
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$query = "SELECT o.*, IFNULL(d.name,'—') as driver_name FROM orders o LEFT JOIN drivers d ON o.driver_id=d.id";
$params = [];
if (in_array($filter_status,['pending','processing','completed','cancelled'])) {
    $query .= " WHERE o.status=?"; $params[] = $filter_status;
}
$query .= " ORDER BY o.created_at DESC";
$orders_stmt = $pdo->prepare($query);
$orders_stmt->execute($params);
$orders = $orders_stmt->fetchAll();

// جلب السائقين
$all_drivers = $pdo->query("SELECT * FROM drivers ORDER BY status, name")->fetchAll();
$avail_drivers = array_filter($all_drivers, fn($d) => $d['status']==='available');

include 'header.php';
?>

<!-- شريط التنقل -->
<div class="row mb-4 fade-in-up">
  <div class="col-12">
    <div class="glass-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
      <h4 class="fw-bold m-0" style="color:var(--primary);">⚙️ إدارة طلبات المتجر والتوصيل</h4>
      <div class="d-flex flex-wrap gap-2">
        <a href="admin_dashboard.php"  class="btn btn-outline-success fw-bold btn-sm px-3">📊 الرئيسية</a>
        <a href="admin_products.php"   class="btn btn-outline-success fw-bold btn-sm px-3">📦 المنتجات</a>
        <a href="admin_categories.php" class="btn btn-outline-success fw-bold btn-sm px-3">🏷️ الأقسام</a>
        <a href="admin_orders.php"     class="btn btn-success fw-bold btn-sm px-3" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));border:none;">🧾 الطلبات</a>
        <a href="admin_users.php"      class="btn btn-outline-success fw-bold btn-sm px-3">👥 المستخدمين</a>
        <a href="logout.php"           class="btn btn-danger fw-bold btn-sm px-3">🚪 خروج</a>
      </div>
    </div>
  </div>
</div>

<?php if ($message): ?>
  <div class="alert-modern alert-<?= $message_type ?>-modern mb-4 p-3 rounded-3 fade-in-up"><?= $message ?></div>
<?php endif; ?>

<!-- ====== تفاصيل طلب محدد ====== -->
<?php if ($view_order): ?>
<div class="col-12 mb-4 fade-in-up">
  <div class="glass-card overflow-hidden" style="border-color:rgba(16,185,129,0.35);">
    <div class="p-3 d-flex justify-content-between align-items-center" style="background:rgba(16,185,129,0.08);border-bottom:1px solid var(--card-border);">
      <h5 class="fw-bold m-0" style="color:var(--primary);">🔎 تفاصيل الطلب رقم #<?= $view_order['id'] ?></h5>
      <a href="admin_orders.php" class="btn btn-sm btn-outline-success fw-bold">✕ إغلاق</a>
    </div>
    <div class="p-4">
      <div class="row g-4 mb-4">
        <!-- بيانات العميل -->
        <div class="col-md-4">
          <div class="glass-card p-3 h-100" style="border-color:rgba(59,130,246,0.2);">
            <h6 class="fw-bold mb-3" style="color:var(--text-main);">👤 بيانات العميل</h6>
            <p class="small mb-1" style="color:var(--text-muted);">الاسم: <strong style="color:var(--text-main);"><?= htmlspecialchars($view_order['full_name']) ?></strong></p>
            <p class="small mb-1" style="color:var(--text-muted);">الهاتف: <strong style="color:var(--text-main);"><?= htmlspecialchars($view_order['phone']) ?></strong></p>
            <p class="small mb-1" style="color:var(--text-muted);">العنوان: <?= htmlspecialchars($view_order['address']) ?></p>
            <?php if ($view_order['lat']): ?>
              <span class="badge mt-1" style="background:rgba(16,185,129,0.15);color:var(--primary-dark);">📍 موقع جغرافي متوفر</span>
            <?php endif; ?>
          </div>
        </div>
        <!-- تفاصيل الطلب وتعديل الحالة -->
        <div class="col-md-4">
          <div class="glass-card p-3 h-100" style="border-color:rgba(99,102,241,0.2);">
            <h6 class="fw-bold mb-3" style="color:var(--text-main);">📅 معلومات الطلب</h6>
            <p class="small mb-1" style="color:var(--text-muted);">التاريخ: <?= date('Y-m-d H:i', strtotime($view_order['created_at'])) ?></p>
            <p class="small mb-2" style="color:var(--text-muted);">المجموع: <strong style="color:var(--primary);"><?= number_format($view_order['total'],0) ?> د.ع</strong></p>
            <form method="POST" action="admin_orders.php?id=<?= $view_order['id'] ?>" class="d-flex gap-2 mt-2">
              <input type="hidden" name="order_id" value="<?= $view_order['id'] ?>">
              <select name="status" class="form-select form-select-sm" required>
                <option value="pending"    <?= $view_order['status']==='pending'?'selected':'' ?>>⏳ قيد الانتظار</option>
                <option value="processing" <?= $view_order['status']==='processing'?'selected':'' ?>>⚙️ قيد التجهيز</option>
                <option value="completed"  <?= $view_order['status']==='completed'?'selected':'' ?>>✅ تم التوصيل</option>
                <option value="cancelled"  <?= $view_order['status']==='cancelled'?'selected':'' ?>>❌ ملغى</option>
              </select>
              <button type="submit" name="update_status" class="btn btn-sm btn-success fw-bold">حفظ</button>
            </form>
          </div>
        </div>
        <!-- تعيين السائق -->
        <div class="col-md-4">
          <div class="glass-card p-3 h-100" style="border-color:rgba(245,158,11,0.25);">
            <h6 class="fw-bold mb-3" style="color:var(--text-main);">🚗 تعيين سائق التوصيل</h6>
            <?php if ($assigned_driver): ?>
              <div class="p-2 rounded-2 mb-3" style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.25);">
                <div class="fw-bold small" style="color:var(--primary);">✅ السائق المُعيَّن:</div>
                <div class="fw-bold mt-1" style="color:var(--text-main);">🧑 <?= htmlspecialchars($assigned_driver['name']) ?></div>
                <div class="small" style="color:var(--text-muted);">📞 <?= htmlspecialchars($assigned_driver['phone']) ?></div>
                <div class="small" style="color:var(--text-muted);">🚗 <?= htmlspecialchars($assigned_driver['vehicle']) ?></div>
              </div>
            <?php endif; ?>
            <form method="POST" action="admin_orders.php?id=<?= $view_order['id'] ?>">
              <input type="hidden" name="order_id" value="<?= $view_order['id'] ?>">
              <select name="driver_id" class="form-select form-select-sm mb-2">
                <option value="">— بدون سائق —</option>
                <?php foreach ($all_drivers as $drv): ?>
                  <option value="<?= $drv['id'] ?>" <?= $view_order['driver_id']==$drv['id']?'selected':'' ?>>
                    <?= htmlspecialchars($drv['name']) ?>
                    (<?= $drv['status']==='available'?'✅ متاح':($drv['status']==='busy'?'🔴 مشغول':'⚫ غير متوفر') ?>)
                    — <?= htmlspecialchars($drv['vehicle']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="submit" name="assign_driver" class="btn btn-sm btn-warning fw-bold w-100 text-dark">
                🚴 تعيين السائق
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- خريطة الموقع -->
      <?php if ($view_order['lat'] && $view_order['lng']): ?>
      <div class="mb-4">
        <h6 class="fw-bold mb-2" style="color:var(--primary);">🗺️ خريطة التوصيل</h6>
        <div id="adminOrderMap" style="height:280px;border-radius:12px;border:1px solid var(--card-border);"></div>
      </div>
      <script>
      document.addEventListener("DOMContentLoaded", function() {
        const storeLat=32.6160, storeLng=44.0249;
        const custLat=<?= floatval($view_order['lat']) ?>, custLng=<?= floatval($view_order['lng']) ?>;
        const map = L.map('adminOrderMap').setView([custLat,custLng],13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'©OSM'}).addTo(map);
        const storeIcon=L.icon({iconUrl:'https://cdn-icons-png.flaticon.com/512/869/869636.png',iconSize:[28,28],iconAnchor:[14,14]});
        const homeIcon =L.icon({iconUrl:'https://cdn-icons-png.flaticon.com/512/25/25694.png',iconSize:[24,24],iconAnchor:[12,12]});
        L.marker([storeLat,storeLng],{icon:storeIcon}).addTo(map).bindPopup('🏪 المتجر');
        L.marker([custLat,custLng],{icon:homeIcon}).addTo(map).bindPopup('📍 منزل العميل').openPopup();
        <?php if ($assigned_driver && $assigned_driver['lat'] && $assigned_driver['lng']): ?>
        const drvIcon=L.icon({iconUrl:'https://cdn-icons-png.flaticon.com/512/2972/2972185.png',iconSize:[28,28],iconAnchor:[14,14]});
        L.marker([<?= floatval($assigned_driver['lat']) ?>,<?= floatval($assigned_driver['lng']) ?>],{icon:drvIcon})
         .addTo(map).bindPopup('🚴 <?= addslashes($assigned_driver['name']) ?>');
        <?php endif; ?>
        const line=L.polyline([[storeLat,storeLng],[custLat,custLng]],{color:'#10b981',weight:3,dashArray:'6,10'}).addTo(map);
        map.fitBounds(line.getBounds(),{padding:[40,40]});
      });
      </script>
      <?php endif; ?>

      <!-- بنود الطلب -->
      <h6 class="fw-bold mb-2" style="color:var(--text-main);">🛒 مواد الطلب</h6>
      <div class="table-responsive">
        <table class="table modern-table text-center align-middle mb-0">
          <thead><tr><th class="text-start">المنتج</th><th>السعر</th><th>الكمية</th><th>المجموع الفرعي</th></tr></thead>
          <tbody>
            <?php foreach ($order_items as $item): ?>
              <tr>
                <td class="text-start"><?= htmlspecialchars($item['name']) ?></td>
                <td style="color:var(--text-muted);"><?= number_format($item['price'],0) ?> د.ع</td>
                <td>× <?= $item['qty'] ?></td>
                <td class="fw-bold" style="color:var(--primary);"><?= number_format($item['price']*$item['qty'],0) ?> د.ع</td>
              </tr>
            <?php endforeach; ?>
            <tr style="background:rgba(16,185,129,0.05);">
              <td colspan="3" class="fw-bold text-end">الإجمالي:</td>
              <td class="fw-black" style="color:var(--primary);font-size:1.1rem;"><?= number_format($view_order['total'],0) ?> د.ع</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ====== قائمة الطلبات ====== -->
<div class="glass-card mb-4 overflow-hidden fade-in-up delay-1">
  <div class="p-3 d-flex flex-wrap align-items-center justify-content-between gap-2" style="border-bottom:1px solid var(--card-border);">
    <h6 class="fw-bold m-0" style="color:var(--text-main);">📋 قائمة طلبات المتجر</h6>
    <div class="d-flex gap-2 flex-wrap">
      <a href="admin_orders.php"                   class="btn btn-sm <?= $filter_status===''?'btn-success':'btn-outline-success' ?> fw-bold">الكل</a>
      <a href="admin_orders.php?status=pending"    class="btn btn-sm <?= $filter_status==='pending'?'btn-warning text-dark':'btn-outline-warning' ?> fw-bold">⏳ انتظار</a>
      <a href="admin_orders.php?status=processing" class="btn btn-sm <?= $filter_status==='processing'?'btn-info text-dark':'btn-outline-info' ?> fw-bold">⚙️ تجهيز</a>
      <a href="admin_orders.php?status=completed"  class="btn btn-sm <?= $filter_status==='completed'?'btn-success':'btn-outline-success' ?> fw-bold">✅ مكتمل</a>
      <a href="admin_orders.php?status=cancelled"  class="btn btn-sm <?= $filter_status==='cancelled'?'btn-danger':'btn-outline-danger' ?> fw-bold">❌ ملغى</a>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table modern-table text-center align-middle mb-0">
      <thead>
        <tr><th>رقم</th><th>المستلم</th><th>الهاتف</th><th>المجموع</th><th>التاريخ</th><th>الحالة</th><th>السائق</th><th>إجراء</th></tr>
      </thead>
      <tbody>
        <?php if (count($orders)>0): ?>
          <?php foreach ($orders as $ord): ?>
            <tr>
              <td class="fw-bold" style="color:var(--primary);">#<?= $ord['id'] ?></td>
              <td class="text-start fw-bold" style="color:var(--text-main);"><?= htmlspecialchars($ord['full_name']) ?></td>
              <td style="color:var(--text-muted);"><?= htmlspecialchars($ord['phone']) ?></td>
              <td class="fw-bold" style="color:var(--primary);"><?= number_format($ord['total'],0) ?> د.ع</td>
              <td class="small" style="color:var(--text-muted);"><?= date('m-d H:i', strtotime($ord['created_at'])) ?></td>
              <td>
                <?php
                $s=$ord['status'];
                if ($s==='pending')    echo '<span class="badge bg-warning text-dark">⏳ انتظار</span>';
                elseif ($s==='processing') echo '<span class="badge bg-info text-dark">⚙️ تجهيز</span>';
                elseif ($s==='completed')  echo '<span class="badge bg-success">✅ مكتمل</span>';
                elseif ($s==='cancelled')  echo '<span class="badge bg-danger">❌ ملغى</span>';
                ?>
              </td>
              <td class="small" style="color:<?= $ord['driver_name']==='—'?'var(--text-muted)':'var(--primary-dark)' ?>;">
                <?= $ord['driver_name']==='—'?'—':'🚴 '.htmlspecialchars($ord['driver_name']) ?>
              </td>
              <td><a href="admin_orders.php?id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-success fw-bold py-1 px-2">🔎 تفاصيل</a></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8" class="text-muted p-4">لا توجد طلبات في هذه الفئة.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ====== إدارة السائقين ====== -->
<div class="row g-4 mb-4 fade-in-up delay-2">
  <!-- إضافة سائق -->
  <div class="col-md-4">
    <div class="glass-card p-4" style="border-color:rgba(245,158,11,0.25);">
      <h5 class="fw-bold mb-3" style="color:var(--accent-dark);">🚴 إضافة سائق دلفري</h5>
      <form method="POST" action="admin_orders.php">
        <div class="mb-3">
          <label class="form-label">اسم السائق <span class="text-danger">*</span></label>
          <input type="text" name="driver_name" class="form-control" required placeholder="مثال: أحمد الكربلائي">
        </div>
        <div class="mb-3">
          <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
          <input type="text" name="driver_phone" class="form-control" required placeholder="07XXXXXXXX">
        </div>
        <div class="mb-3">
          <label class="form-label">المركبة</label>
          <select name="driver_vehicle" class="form-select">
            <option value="دراجة نارية">🏍️ دراجة نارية</option>
            <option value="سيارة">🚗 سيارة</option>
            <option value="سيارة تويوتا">🚙 سيارة تويوتا</option>
            <option value="دراجة هوائية">🚲 دراجة هوائية</option>
          </select>
        </div>
        <button type="submit" name="add_driver" class="btn btn-warning fw-bold w-100 text-dark">
          ➕ إضافة السائق
        </button>
      </form>
    </div>
  </div>

  <!-- جدول السائقين -->
  <div class="col-md-8">
    <div class="glass-card overflow-hidden">
      <div class="p-3" style="border-bottom:1px solid var(--card-border);">
        <h6 class="fw-bold m-0" style="color:var(--text-main);">👥 فريق التوصيل (<?= count($all_drivers) ?> سائق)</h6>
      </div>
      <?php if (count($all_drivers) > 0): ?>
      <div class="table-responsive">
        <table class="table modern-table text-center align-middle mb-0">
          <thead>
            <tr><th>الاسم</th><th>الهاتف</th><th>المركبة</th><th>الحالة</th><th>تغيير الحالة</th><th>حذف</th></tr>
          </thead>
          <tbody>
            <?php foreach ($all_drivers as $drv): ?>
              <tr>
                <td class="fw-bold text-start" style="color:var(--text-main);">🧑 <?= htmlspecialchars($drv['name']) ?></td>
                <td style="color:var(--text-muted);"><?= htmlspecialchars($drv['phone']) ?></td>
                <td style="color:var(--text-muted);"><?= htmlspecialchars($drv['vehicle']) ?></td>
                <td>
                  <?php if ($drv['status']==='available'): ?>
                    <span class="badge" style="background:rgba(16,185,129,0.15);color:var(--primary-dark);">✅ متاح</span>
                  <?php elseif ($drv['status']==='busy'): ?>
                    <span class="badge bg-danger bg-opacity-25 text-danger">🔴 مشغول</span>
                  <?php else: ?>
                    <span class="badge bg-secondary bg-opacity-25 text-secondary">⚫ غير متوفر</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="d-flex gap-1 justify-content-center">
                    <a href="admin_orders.php?did=<?= $drv['id'] ?>&driver_status=available"
                       class="btn btn-sm py-1 px-2 fw-bold <?= $drv['status']==='available'?'btn-success':'btn-outline-success' ?>">✅</a>
                    <a href="admin_orders.php?did=<?= $drv['id'] ?>&driver_status=offline"
                       class="btn btn-sm py-1 px-2 fw-bold <?= $drv['status']==='offline'?'btn-secondary':'btn-outline-secondary' ?>">⚫</a>
                  </div>
                </td>
                <td>
                  <a href="admin_orders.php?del_driver=<?= $drv['id'] ?>"
                     class="btn btn-sm btn-outline-danger py-1 px-2 fw-bold"
                     onclick="return confirm('حذف السائق <?= addslashes($drv['name']) ?>؟')">🗑️</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="p-4 text-center" style="color:var(--text-muted);">
          <div style="font-size:2.5rem;">🚴</div>
          <p class="mt-2">لا يوجد سائقون مضافون بعد. أضف سائقاً من النموذج الجانبي.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- FOOTER -->
include 'footer.php';
