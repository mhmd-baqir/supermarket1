<?php
ob_start();
include 'config.php';

// التحقق من صلاحية المدير
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// 1. الإحصائيات السريعة
$total_products   = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_users      = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$total_orders     = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue    = $pdo->query("SELECT SUM(total) FROM orders WHERE status = 'completed'")->fetchColumn() ?: 0;
$low_stock_count  = $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 10")->fetchColumn();

// 2. جلب تنبيهات المخزن منخفض الكمية
$low_stock_stmt = $pdo->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.stock < 10 ORDER BY p.stock ASC LIMIT 5");
$low_stock_products = $low_stock_stmt->fetchAll();

// 3. أحدث 5 طلبات
$recent_orders_stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
$recent_orders = $recent_orders_stmt->fetchAll();

// 4. جلب مبيعات آخر 7 أيام للرسم البياني
$chart_stmt = $pdo->query("
    SELECT DATE(created_at) as sale_date, SUM(total) as total_sales 
    FROM orders 
    WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at) 
    ORDER BY sale_date ASC
");
$chart_data = $chart_stmt->fetchAll();

// تحضير بيانات الرسم البياني
$labels = [];
$data = [];
if (count($chart_data) > 0) {
    foreach ($chart_data as $row) {
        $labels[] = date('m-d', strtotime($row['sale_date']));
        $data[] = floatval($row['total_sales']);
    }
} else {
    // بيانات افتراضية في حال عدم وجود مبيعات حقيقية بعد
    $labels = [date('m-d', strtotime('-4 days')), date('m-d', strtotime('-3 days')), date('m-d', strtotime('-2 days')), date('m-d', strtotime('-1 days')), date('m-d')];
    $data = [0, 0, 0, 0, 0];
}

include 'header.php';
?>

<!-- شريط التنقل الفرعي للوحة التحكم -->
<div class="row mb-4 fade-in-up">
    <div class="col-12">
        <div class="glass-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h4 class="fw-bold text-success m-0">⚙️ لوحة تحكم المسؤول</h4>
            <div class="d-flex flex-wrap gap-2">
                <a href="admin_dashboard.php" class="btn btn-success fw-bold btn-sm active px-3">📊 الرئيسية</a>
                <a href="admin_products.php" class="btn btn-outline-success fw-bold btn-sm px-3">📦 المنتجات</a>
                <a href="admin_categories.php" class="btn btn-outline-success fw-bold btn-sm px-3">🏷️ الأقسام</a>
                <a href="admin_orders.php" class="btn btn-outline-success fw-bold btn-sm px-3">🧾 الطلبات (<?php echo $total_orders; ?>)</a>
                <a href="admin_users.php" class="btn btn-outline-success fw-bold btn-sm px-3">👥 المستخدمين</a>
                <a href="admin_coupons.php" class="btn btn-outline-warning fw-bold btn-sm px-3">🎟️ الكوبونات</a>
                <a href="logout.php" class="btn btn-danger fw-bold btn-sm px-3">🚪 خروج</a>
            </div>
        </div>
    </div>
</div>

<!-- بطاقات الإحصائيات (Stat Cards) -->
<div class="row g-3 mb-4 fade-in-up delay-1">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card stat-card-green">
            <div style="font-size: 2rem; margin-bottom: 5px;">💰</div>
            <div class="stat-number fs-3" style="color: #4ade80;"><?php echo number_format($total_revenue, 0); ?></div>
            <div class="small text-muted" style="margin-top: 4px; font-weight: 600;">إجمالي الأرباح (د.ع)</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(37,99,235,0.1)); border: 1px solid rgba(59,130,246,0.3);">
            <div style="font-size: 2rem; margin-bottom: 5px;">🧾</div>
            <div class="stat-number fs-3" style="color: #60a5fa;"><?php echo $total_orders; ?></div>
            <div class="small text-muted" style="margin-top: 4px; font-weight: 600;">عدد الطلبات الكلي</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card stat-card-dark">
            <div style="font-size: 2rem; margin-bottom: 5px;">👥</div>
            <div class="stat-number fs-3" style="color: #fbbf24;"><?php echo $total_users; ?></div>
            <div class="small text-muted" style="margin-top: 4px; font-weight: 600;">العملاء المسجلين</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(220,38,38,0.2), rgba(185,28,28,0.1)); border: 1px solid rgba(220,38,38,0.3);">
            <div style="font-size: 2rem; margin-bottom: 5px;">⚠️</div>
            <div class="stat-number fs-3" style="color: #f87171;"><?php echo $low_stock_count; ?></div>
            <div class="small text-muted" style="margin-top: 4px; font-weight: 600;">مواد أوشكت على النفاد</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- رسم بياني للمبيعات -->
    <div class="col-lg-8 fade-in-up delay-2">
        <div class="glass-card p-4">
            <h5 class="fw-bold text-white mb-3">📈 إيرادات المبيعات المؤكدة (آخر 7 أيام)</h5>
            <div style="position: relative; height: 300px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- معلومات سريعة عن المنتجات -->
    <div class="col-lg-4 fade-in-up delay-2">
        <div class="glass-card p-4">
            <h5 class="fw-bold text-white mb-3">📁 ملخص المنتجات والأقسام</h5>
            <div class="d-flex flex-column gap-3">
                <div class="p-3 rounded bg-secondary bg-opacity-10 d-flex justify-content-between align-items-center">
                    <span class="text-muted small">إجمالي الأقسام</span>
                    <span class="badge bg-success fs-6 fw-bold"><?php echo $total_categories; ?></span>
                </div>
                <div class="p-3 rounded bg-secondary bg-opacity-10 d-flex justify-content-between align-items-center">
                    <span class="text-muted small">إجمالي أصناف المنتجات</span>
                    <span class="badge bg-primary fs-6 fw-bold"><?php echo $total_products; ?></span>
                </div>
                <div class="p-3 rounded bg-secondary bg-opacity-10 text-center">
                    <a href="admin_products.php?action=add" class="btn btn-success fw-bold w-100" style="background: linear-gradient(135deg, #16a34a, #15803d); border:none;">
                        ➕ إضافة منتج جديد
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- الطلبات الأخيرة -->
    <div class="col-lg-7 fade-in-up delay-3">
        <div class="glass-card overflow-hidden">
            <div class="p-3 d-flex justify-content-between align-items-center" style="background: rgba(22, 163, 74, 0.1); border-bottom: 1px solid rgba(22, 163, 74, 0.2);">
                <h6 class="fw-bold text-white m-0">🧾 أحدث 5 طلبات مستلمة</h6>
                <a href="admin_orders.php" class="btn btn-sm btn-outline-success fw-bold">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table modern-table mb-0 align-middle text-center">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>العميل</th>
                            <th>المجموع</th>
                            <th>الحالة</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_orders) > 0): ?>
                            <?php foreach ($recent_orders as $ord): ?>
                                <tr>
                                    <td class="fw-bold text-success">#<?php echo $ord['id']; ?></td>
                                    <td class="text-start text-white"><?php echo htmlspecialchars($ord['full_name']); ?></td>
                                    <td class="text-white fw-bold"><?php echo number_format($ord['total'], 0); ?> د.ع</td>
                                    <td>
                                        <?php 
                                        $s = $ord['status'];
                                        if ($s === 'pending') echo '<span class="badge bg-warning text-dark">قيد الانتظار</span>';
                                        elseif ($s === 'processing') echo '<span class="badge bg-info text-dark">قيد التجهيز</span>';
                                        elseif ($s === 'completed') echo '<span class="badge bg-success">تم التوصيل</span>';
                                        elseif ($s === 'cancelled') echo '<span class="badge bg-danger">ملغى</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <a href="admin_orders.php?id=<?php echo $ord['id']; ?>" class="btn btn-sm btn-outline-light py-1 px-3">تعديل</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-muted p-4">لا توجد طلبات مسجلة بعد.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- تنبيهات المخزون -->
    <div class="col-lg-5 fade-in-up delay-3">
        <div class="glass-card overflow-hidden">
            <div class="p-3" style="background: rgba(220, 38, 38, 0.1); border-bottom: 1px solid rgba(220, 38, 38, 0.2);">
                <h6 class="fw-bold text-danger m-0">⚠️ نقص المخزون المتبقي (< 10 قطع)</h6>
            </div>
            <div class="p-3">
                <?php if (count($low_stock_products) > 0): ?>
                    <?php foreach ($low_stock_products as $p): ?>
                        <div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded bg-dark bg-opacity-50 border border-secondary border-opacity-10">
                            <div>
                                <span class="fw-bold text-white small d-block"><?php echo htmlspecialchars($p['name']); ?></span>
                                <span class="text-muted" style="font-size:0.75rem;">القسم: <?php echo htmlspecialchars($p['cat_name']); ?></span>
                            </div>
                            <span class="badge bg-danger bg-opacity-25 text-danger px-3 py-1 fw-bold"><?php echo $p['stock']; ?> قطعة</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-success">
                        <h5>✅ جميع المنتجات تملك مخزوناً كافياً!</h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="main-footer mt-5">
    <p class="mb-0">© 2024 الهايبر ماركت المتكامل — جميع الحقوق محفوظة | PR122-3</p>
</footer>

</div>

<!-- تضمين Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'الإيرادات اليومية بالدينار العراقي',
                data: <?php echo json_encode($data); ?>,
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22, 163, 74, 0.15)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: '#cbd5e1',
                        font: { family: 'Cairo', size: 12 }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#94a3b8', font: { family: 'Cairo' } }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#94a3b8', font: { family: 'Cairo' } }
                }
            }
        }
    });
});
</script>
</body>
</html>
