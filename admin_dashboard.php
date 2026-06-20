<?php
ob_start();
include 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// ====== الإحصائيات السريعة ======
$total_products   = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_users      = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$total_orders     = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue    = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'completed'")->fetchColumn();
$low_stock_count  = $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 10")->fetchColumn();
$pending_orders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$today_revenue    = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='completed' AND DATE(created_at)=CURDATE()")->fetchColumn();

// ====== تنبيهات المخزون ======
$low_stock_products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id WHERE p.stock < 10 ORDER BY p.stock ASC LIMIT 8")->fetchAll();

// ====== أحدث 5 طلبات ======
$recent_orders = $pdo->query("SELECT o.*, IFNULL(d.name,'—') as driver_name FROM orders o LEFT JOIN drivers d ON o.driver_id=d.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();

// ====== مخطط المبيعات اليومية — آخر 14 يوماً ======
$sales_rows = $pdo->query("
    SELECT DATE(created_at) as sale_date, SUM(total) as total_sales
    FROM orders WHERE status='completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at) ORDER BY sale_date ASC
")->fetchAll();

$sales_labels = [];
$sales_data   = [];
// نملأ كل الأيام بـ 0 أولاً
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $sales_labels[$day] = date('m/d', strtotime($day));
    $sales_data[$day]   = 0;
}
foreach ($sales_rows as $row) {
    if (isset($sales_data[$row['sale_date']])) {
        $sales_data[$row['sale_date']] = floatval($row['total_sales']);
    }
}
$chart_labels = array_values($sales_labels);
$chart_data   = array_values($sales_data);

// ====== مخطط دائري — توزيع المبيعات حسب الأقسام ======
$cat_sales = $pdo->query("
    SELECT c.name, COALESCE(SUM(oi.price * oi.qty),0) as total
    FROM categories c
    LEFT JOIN products p ON p.category_id=c.id
    LEFT JOIN order_items oi ON oi.product_id=p.id
    LEFT JOIN orders o ON oi.order_id=o.id AND o.status='completed'
    GROUP BY c.id ORDER BY total DESC LIMIT 7
")->fetchAll();
$cat_labels = array_column($cat_sales, 'name');
$cat_data   = array_map(fn($r) => floatval($r['total']), $cat_sales);

// ====== مخطط حالات الطلبات ======
$status_counts = [
    'pending'    => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(),
    'processing' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='processing'")->fetchColumn(),
    'completed'  => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn(),
    'cancelled'  => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='cancelled'")->fetchColumn(),
];

include 'header.php';
?>

<!-- شريط التنقل -->
<div class="row mb-4 fade-in-up">
  <div class="col-12">
    <div class="glass-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
      <h4 class="fw-bold m-0" style="color:var(--primary);">⚙️ لوحة تحكم المسؤول</h4>
      <div class="d-flex flex-wrap gap-2">
        <a href="admin_dashboard.php"  class="btn btn-success fw-bold btn-sm px-3" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));border:none;">📊 الرئيسية</a>
        <a href="admin_products.php"   class="btn btn-outline-success fw-bold btn-sm px-3">📦 المنتجات</a>
        <a href="admin_categories.php" class="btn btn-outline-success fw-bold btn-sm px-3">🏷️ الأقسام</a>
        <a href="admin_orders.php"     class="btn btn-outline-success fw-bold btn-sm px-3">🧾 الطلبات (<?php echo $total_orders; ?>)</a>
        <a href="admin_support.php"    class="btn btn-outline-success fw-bold btn-sm px-3">💬 الرسائل والدعم</a>
        <a href="admin_users.php"      class="btn btn-outline-success fw-bold btn-sm px-3">👥 المستخدمين</a>
        <a href="admin_coupons.php"    class="btn btn-outline-warning fw-bold btn-sm px-3">🎟️ الكوبونات</a>
        <a href="admin_settings.php"   class="btn btn-outline-success fw-bold btn-sm px-3">⚙️ الإعدادات</a>
        <a href="logout.php"           class="btn btn-danger fw-bold btn-sm px-3">🚪 خروج</a>
      </div>
    </div>
  </div>
</div>

<!-- ====== بطاقات الإحصائيات ====== -->
<div class="row g-3 mb-4 fade-in-up delay-1">
  <!-- الإيرادات الكلية -->
  <div class="col-xl-2 col-md-4 col-sm-6">
    <div class="glass-card p-3 text-center h-100" style="border-color:rgba(16,185,129,0.3);">
      <div style="font-size:2rem;">💰</div>
      <div class="fw-black mt-1" style="color:var(--primary);font-size:1.4rem;line-height:1.1;">
        <?php echo number_format($total_revenue/1000, 1); ?>k
      </div>
      <div class="small fw-bold" style="color:var(--text-muted);">إيرادات كلية (د.ع)</div>
    </div>
  </div>
  <!-- إيرادات اليوم -->
  <div class="col-xl-2 col-md-4 col-sm-6">
    <div class="glass-card p-3 text-center h-100" style="border-color:rgba(16,185,129,0.25);">
      <div style="font-size:2rem;">📅</div>
      <div class="fw-black mt-1" style="color:#2563eb;font-size:1.4rem;line-height:1.1;">
        <?php echo number_format($today_revenue, 0); ?>
      </div>
      <div class="small fw-bold" style="color:var(--text-muted);">إيرادات اليوم (د.ع)</div>
    </div>
  </div>
  <!-- المنتجات المعروضة -->
  <div class="col-xl-2 col-md-4 col-sm-6">
    <div class="glass-card p-3 text-center h-100" style="border-color:rgba(59,130,246,0.25);">
      <div style="font-size:2rem;">📦</div>
      <div class="fw-black mt-1" style="color:#2563eb;font-size:1.4rem;line-height:1.1;">
        <?php echo $total_products; ?>
      </div>
      <div class="small fw-bold" style="color:var(--text-muted);">منتجات معروضة</div>
    </div>
  </div>
  <!-- الطلبات الكلية -->
  <div class="col-xl-2 col-md-4 col-sm-6">
    <div class="glass-card p-3 text-center h-100" style="border-color:rgba(99,102,241,0.25);">
      <div style="font-size:2rem;">🧾</div>
      <div class="fw-black mt-1" style="color:#6366f1;font-size:1.4rem;line-height:1.1;">
        <?php echo $total_orders; ?>
      </div>
      <div class="small fw-bold" style="color:var(--text-muted);">طلبات مسجلة</div>
    </div>
  </div>
  <!-- طلبات معلقة -->
  <div class="col-xl-2 col-md-4 col-sm-6">
    <div class="glass-card p-3 text-center h-100" style="border-color:rgba(245,158,11,0.3);">
      <div style="font-size:2rem;">⏳</div>
      <div class="fw-black mt-1" style="color:var(--accent-dark);font-size:1.4rem;line-height:1.1;">
        <?php echo $pending_orders; ?>
      </div>
      <div class="small fw-bold" style="color:var(--text-muted);">طلبات معلقة</div>
    </div>
  </div>
  <!-- نقص المخزون -->
  <div class="col-xl-2 col-md-4 col-sm-6">
    <div class="glass-card p-3 text-center h-100" style="border-color:rgba(220,38,38,0.3);">
      <div style="font-size:2rem;">⚠️</div>
      <div class="fw-black mt-1" style="color:#dc2626;font-size:1.4rem;line-height:1.1;">
        <?php echo $low_stock_count; ?>
      </div>
      <div class="small fw-bold" style="color:var(--text-muted);">مواد قاربت النفاد</div>
    </div>
  </div>
</div>

<!-- ====== المخططات البيانية ====== -->
<div class="row g-4 mb-4">
  <!-- مخطط المبيعات اليومية -->
  <div class="col-lg-8 fade-in-up delay-2">
    <div class="glass-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold m-0" style="color:var(--text-main);">📈 المبيعات اليومية — آخر 14 يوم</h5>
        <a href="admin_orders.php?status=completed" class="btn btn-sm btn-outline-success fw-bold">عرض الكل</a>
      </div>
      <div style="position:relative;height:260px;">
        <canvas id="salesChart"></canvas>
      </div>
    </div>
  </div>
  <!-- مخطط حالات الطلبات -->
  <div class="col-lg-4 fade-in-up delay-2">
    <div class="glass-card p-4 h-100">
      <h5 class="fw-bold mb-3" style="color:var(--text-main);">🧩 توزيع حالات الطلبات</h5>
      <div style="position:relative;height:220px;">
        <canvas id="statusChart"></canvas>
      </div>
      <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
        <span class="badge" style="background:rgba(245,158,11,0.15);color:#d97706;font-size:0.75rem;">⏳ انتظار: <?= $status_counts['pending'] ?></span>
        <span class="badge" style="background:rgba(59,130,246,0.15);color:#2563eb;font-size:0.75rem;">⚙️ تجهيز: <?= $status_counts['processing'] ?></span>
        <span class="badge" style="background:rgba(16,185,129,0.15);color:var(--primary-dark);font-size:0.75rem;">✅ مكتمل: <?= $status_counts['completed'] ?></span>
        <span class="badge" style="background:rgba(220,38,38,0.15);color:#dc2626;font-size:0.75rem;">❌ ملغى: <?= $status_counts['cancelled'] ?></span>
      </div>
    </div>
  </div>
</div>

<!-- مخطط المبيعات حسب الأقسام -->
<div class="row g-4 mb-4">
  <div class="col-lg-5 fade-in-up delay-2">
    <div class="glass-card p-4 h-100">
      <h5 class="fw-bold mb-3" style="color:var(--text-main);">🏷️ الإيرادات حسب القسم</h5>
      <div style="position:relative;height:260px;">
        <canvas id="catChart"></canvas>
      </div>
    </div>
  </div>

  <!-- أحدث الطلبات -->
  <div class="col-lg-7 fade-in-up delay-3">
    <div class="glass-card overflow-hidden h-100">
      <div class="p-3 d-flex justify-content-between align-items-center" style="background:rgba(16,185,129,0.08);border-bottom:1px solid var(--card-border);">
        <h6 class="fw-bold m-0" style="color:var(--text-main);">🧾 أحدث 5 طلبات</h6>
        <a href="admin_orders.php" class="btn btn-sm btn-outline-success fw-bold">عرض الكل</a>
      </div>
      <div class="table-responsive">
        <table class="table modern-table mb-0 align-middle text-center">
          <thead>
            <tr>
              <th>رقم</th><th>العميل</th><th>المجموع</th><th>الحالة</th><th>السائق</th><th>إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($recent_orders) > 0): ?>
              <?php foreach ($recent_orders as $ord): ?>
                <tr>
                  <td class="fw-bold" style="color:var(--primary);">#<?= $ord['id'] ?></td>
                  <td class="text-start"><?= htmlspecialchars($ord['full_name']) ?></td>
                  <td class="fw-bold"><?= number_format($ord['total'], 0) ?> د.ع</td>
                  <td>
                    <?php
                    $s = $ord['status'];
                    if ($s==='pending')    echo '<span class="badge bg-warning text-dark">⏳ انتظار</span>';
                    elseif ($s==='processing') echo '<span class="badge bg-info text-dark">⚙️ تجهيز</span>';
                    elseif ($s==='completed')  echo '<span class="badge bg-success">✅ مكتمل</span>';
                    elseif ($s==='cancelled')  echo '<span class="badge bg-danger">❌ ملغى</span>';
                    ?>
                  </td>
                  <td class="small" style="color:var(--text-muted);"><?= htmlspecialchars($ord['driver_name']) ?></td>
                  <td><a href="admin_orders.php?id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-success py-1 px-2 fw-bold">تفاصيل</a></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="6" class="text-muted p-4">لا توجد طلبات بعد.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- تنبيهات المخزون -->
<?php if (count($low_stock_products) > 0): ?>
<div class="row mb-4 fade-in-up delay-3">
  <div class="col-12">
    <div class="glass-card overflow-hidden">
      <div class="p-3 d-flex justify-content-between align-items-center" style="background:rgba(220,38,38,0.08);border-bottom:1px solid rgba(220,38,38,0.2);">
        <h6 class="fw-bold m-0 text-danger">⚠️ تنبيهات نقص المخزون (أقل من 10 وحدات)</h6>
        <a href="admin_products.php" class="btn btn-sm btn-outline-danger fw-bold">إدارة المخزون</a>
      </div>
      <div class="p-3">
        <div class="row g-2">
          <?php foreach ($low_stock_products as $p): ?>
            <div class="col-md-3 col-sm-6">
              <div class="d-flex justify-content-between align-items-center p-2 rounded-3" style="background:rgba(220,38,38,0.05);border:1px solid rgba(220,38,38,0.15);">
                <div>
                  <div class="fw-bold small" style="color:var(--text-main);"><?= htmlspecialchars($p['name']) ?></div>
                  <div style="font-size:0.72rem;color:var(--text-muted);"><?= htmlspecialchars($p['cat_name']) ?></div>
                </div>
                <span class="badge bg-danger bg-opacity-15 text-danger fw-bold px-2"><?= $p['stock'] ?> متبقي</span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ملخص سريع -->
<div class="row g-3 mb-4 fade-in-up delay-3">
  <div class="col-md-4">
    <div class="glass-card p-3 d-flex align-items-center gap-3">
      <div style="font-size:2.5rem;">📁</div>
      <div>
        <div class="fw-bold" style="color:var(--text-main);"><?= $total_categories ?> قسم</div>
        <div class="small" style="color:var(--text-muted);">أقسام المتجر النشطة</div>
        <a href="admin_categories.php" class="btn btn-sm btn-outline-success mt-1 fw-bold">إدارة الأقسام</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="glass-card p-3 d-flex align-items-center gap-3">
      <div style="font-size:2.5rem;">👥</div>
      <div>
        <div class="fw-bold" style="color:var(--text-main);"><?= $total_users ?> عميل</div>
        <div class="small" style="color:var(--text-muted);">عملاء مسجلون</div>
        <a href="admin_users.php" class="btn btn-sm btn-outline-success mt-1 fw-bold">إدارة العملاء</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="glass-card p-3 d-flex align-items-center gap-3">
      <div style="font-size:2.5rem;">➕</div>
      <div>
        <div class="fw-bold" style="color:var(--text-main);">إضافة منتج</div>
        <div class="small" style="color:var(--text-muted);">أضف مادة جديدة للمخزن</div>
        <a href="admin_products.php" class="btn btn-sm btn-success mt-1 fw-bold" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));border:none;">إضافة الآن</a>
      </div>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer class="main-footer mt-5">
  <p class="mb-0">© 2024 هايبر ماركت رضا أبو لحمة — جميع الحقوق محفوظة | PR122-3</p>
</footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const isDark = document.body.classList.contains('dark-mode');
const textColor = isDark ? '#94a3b8' : '#475569';
const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)';
const fontFamily = 'Cairo, sans-serif';

// ====== مخطط المبيعات اليومية ======
new Chart(document.getElementById('salesChart').getContext('2d'), {
  type: 'line',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [{
      label: 'الإيرادات اليومية (د.ع)',
      data: <?= json_encode($chart_data) ?>,
      borderColor: '#10b981',
      backgroundColor: 'rgba(16,185,129,0.12)',
      borderWidth: 3, fill: true, tension: 0.4,
      pointBackgroundColor: '#10b981',
      pointRadius: 4
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: textColor, font: { family: fontFamily, size: 12 } } } },
    scales: {
      x: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: fontFamily } } },
      y: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: fontFamily } } }
    }
  }
});

// ====== مخطط حالات الطلبات ======
new Chart(document.getElementById('statusChart').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: ['⏳ انتظار', '⚙️ تجهيز', '✅ مكتمل', '❌ ملغى'],
    datasets: [{
      data: [
        <?= $status_counts['pending'] ?>,
        <?= $status_counts['processing'] ?>,
        <?= $status_counts['completed'] ?>,
        <?= $status_counts['cancelled'] ?>
      ],
      backgroundColor: [
        'rgba(245,158,11,0.7)',
        'rgba(59,130,246,0.7)',
        'rgba(16,185,129,0.8)',
        'rgba(220,38,38,0.7)'
      ],
      borderWidth: 2,
      borderColor: isDark ? '#0f172a' : '#ffffff'
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw} طلب` } }
    },
    cutout: '65%'
  }
});

// ====== مخطط الأقسام ======
new Chart(document.getElementById('catChart').getContext('2d'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($cat_labels) ?>,
    datasets: [{
      label: 'الإيرادات (د.ع)',
      data: <?= json_encode($cat_data) ?>,
      backgroundColor: [
        'rgba(16,185,129,0.75)', 'rgba(59,130,246,0.75)', 'rgba(245,158,11,0.75)',
        'rgba(99,102,241,0.75)', 'rgba(236,72,153,0.75)', 'rgba(234,179,8,0.75)',
        'rgba(20,184,166,0.75)'
      ],
      borderRadius: 8,
      borderSkipped: false
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false, indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: fontFamily, size: 11 } } },
      y: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: fontFamily, size: 11 } } }
    }
  }
});
</script>
<?php include 'footer.php'; ?>
