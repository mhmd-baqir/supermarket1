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

// تحديث حالة الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];

    if (in_array($new_status, ['pending', 'processing', 'completed', 'cancelled'])) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            $message = "تم تحديث حالة الطلب رقم #{$order_id} بنجاح إلى " . ($new_status === 'completed' ? 'تم التوصيل' : $new_status) . ".";
            $message_type = "success";
        } catch (\PDOException $e) {
            $message = "حدث خطأ أثناء تحديث الطلب: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}

// تفاصيل طلب محدد لعرضه
$view_order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$view_order = null;
$order_items = [];

if ($view_order_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$view_order_id]);
    $view_order = $stmt->fetch();

    if ($view_order) {
        $items_stmt = $pdo->prepare("
            SELECT oi.*, p.name, p.unit 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $items_stmt->execute([$view_order_id]);
        $order_items = $items_stmt->fetchAll();
    }
}

// فلترة قائمة الطلبات
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$query = "SELECT * FROM orders";
$params = [];

if (in_array($filter_status, ['pending', 'processing', 'completed', 'cancelled'])) {
    $query .= " WHERE status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY created_at DESC";
$orders_stmt = $pdo->prepare($query);
$orders_stmt->execute($params);
$orders = $orders_stmt->fetchAll();

include 'header.php';
?>

<!-- شريط التنقل للوحة التحكم -->
<div class="row mb-4 fade-in-up">
    <div class="col-12">
        <div class="glass-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h4 class="fw-bold text-success m-0">⚙️ إدارة طلبات المتجر</h4>
            <div class="d-flex flex-wrap gap-2">
                <a href="admin_dashboard.php" class="btn btn-outline-success fw-bold btn-sm px-3">📊 الرئيسية</a>
                <a href="admin_products.php" class="btn btn-outline-success fw-bold btn-sm px-3">📦 المنتجات</a>
                <a href="admin_categories.php" class="btn btn-outline-success fw-bold btn-sm px-3">🏷️ الأقسام</a>
                <a href="admin_orders.php" class="btn btn-success fw-bold btn-sm active px-3">🧾 الطلبات</a>
                <a href="admin_users.php" class="btn btn-outline-success fw-bold btn-sm px-3">👥 المستخدمين</a>
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
    <!-- عرض تفاصيل فاتورة محددة -->
    <?php if ($view_order): ?>
        <div class="col-12 mb-4 fade-in-up">
            <div class="glass-card overflow-hidden border border-success">
                <div class="p-3 d-flex justify-content-between align-items-center bg-success bg-opacity-10 border-bottom border-success border-opacity-25">
                    <h5 class="fw-bold text-success m-0">🔎 تفاصيل الفاتورة والطلب رقم #<?php echo $view_order['id']; ?></h5>
                    <a href="admin_orders.php" class="btn btn-sm btn-outline-light">إغلاق التفاصيل ✕</a>
                </div>
                <div class="p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <h6 class="text-white fw-bold mb-2">👤 بيانات العميل:</h6>
                            <p class="text-muted small mb-1">الاسم: <?php echo htmlspecialchars($view_order['full_name']); ?></p>
                            <p class="text-muted small mb-1">الهاتف: <?php echo htmlspecialchars($view_order['phone']); ?></p>
                            <p class="text-muted small mb-1">العنوان: <?php echo htmlspecialchars($view_order['address']); ?></p>
                            <?php if ($view_order['lat'] !== null && $view_order['lng'] !== null): ?>
                                <span class="badge bg-success mt-1">📍 إحداثيات الموقع الجغرافي متوفرة</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-white fw-bold mb-2">📅 التوقيت والمجموع:</h6>
                            <p class="text-muted small mb-1">تاريخ الطلب: <?php echo date('Y-m-d H:i', strtotime($view_order['created_at'])); ?></p>
                            <p class="text-muted small mb-1">المجموع الكلي: <strong class="text-success"><?php echo number_format($view_order['total'], 0); ?> د.ع</strong></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-white fw-bold mb-2">⚙️ تعديل الحالة:</h6>
                            <form method="POST" action="admin_orders.php?id=<?php echo $view_order['id']; ?>" class="d-flex gap-2">
                                <input type="hidden" name="order_id" value="<?php echo $view_order['id']; ?>">
                                <select name="status" class="form-select form-select-sm" required style="max-width: 150px;">
                                    <option value="pending" <?php echo $view_order['status'] === 'pending' ? 'selected' : ''; ?>>⏳ قيد الانتظار</option>
                                    <option value="processing" <?php echo $view_order['status'] === 'processing' ? 'selected' : ''; ?>>⚙️ قيد التجهيز</option>
                                    <option value="completed" <?php echo $view_order['status'] === 'completed' ? 'selected' : ''; ?>>✅ تم التوصيل</option>
                                    <option value="cancelled" <?php echo $view_order['status'] === 'cancelled' ? 'selected' : ''; ?>>❌ ملغى</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-sm btn-success fw-bold">حفظ</button>
                            </form>
                        </div>
                    </div>

                    <!-- خريطة موقع العميل الجغرافية لتسهيل التوصيل -->
                    <?php if ($view_order['lat'] !== null && $view_order['lng'] !== null): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-success fw-bold mb-2">🗺️ موقع العميل الجغرافي للتوصيل (كربلاء):</h6>
                                <div id="adminOrderMap" style="height: 280px; border-radius: 12px; border: 1px solid rgba(22, 163, 74, 0.3); z-index: 1;"></div>
                            </div>
                        </div>
                        <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            const storeLat = 32.6160;
                            const storeLng = 44.0249;
                            const custLat = <?php echo floatval($view_order['lat']); ?>;
                            const custLng = <?php echo floatval($view_order['lng']); ?>;

                            const map = L.map('adminOrderMap').setView([custLat, custLng], 14);
                            
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '© OpenStreetMap'
                            }).addTo(map);

                            // ماركر المتجر
                            const storeIcon = L.icon({
                                iconUrl: 'https://cdn-icons-png.flaticon.com/512/869/869636.png',
                                iconSize: [28, 28],
                                iconAnchor: [14, 14]
                            });
                            L.marker([storeLat, storeLng], {icon: storeIcon}).addTo(map)
                                .bindPopup('🏫 المتجر الرئيسي');

                            // ماركر العميل
                            const homeIcon = L.icon({
                                iconUrl: 'https://cdn-icons-png.flaticon.com/512/25/25694.png',
                                iconSize: [24, 24],
                                iconAnchor: [12, 12]
                            });
                            L.marker([custLat, custLng], {icon: homeIcon}).addTo(map)
                                .bindPopup('📍 منزل العميل (موقع التوصيل المختار)').openPopup();

                            // خط مسار التوصيل المنقط
                            const pathLine = L.polyline([[storeLat, storeLng], [custLat, custLng]], {
                                color: '#16a34a',
                                weight: 3,
                                dashArray: '5, 10'
                            }).addTo(map);

                            map.fitBounds(pathLine.getBounds(), { padding: [40, 40] });
                        });
                        </script>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table modern-table text-center align-middle mb-0">
                            <thead>
                                <tr class="table-dark">
                                    <th class="text-start">المنتج</th>
                                    <th>السعر الفردي</th>
                                    <th>الكمية المطلوبة</th>
                                    <th>المجموع الفرعي</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td class="text-start text-white"><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td class="text-muted"><?php echo number_format($item['price'], 0); ?> د.ع / <?php echo htmlspecialchars($item['unit'] ?? 'قطعة'); ?></td>
                                        <td class="text-white">× <?php echo $item['qty']; ?> <?php echo htmlspecialchars($item['unit'] ?? 'قطعة'); ?></td>
                                        <td class="text-success fw-bold"><?php echo number_format($item['price'] * $item['qty'], 0); ?> د.ع</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- قائمة الطلبات الكلية -->
    <div class="col-12 fade-in-up delay-1">
        <div class="glass-card">
            <!-- أزرار الفلترة -->
            <div class="p-3 d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom border-secondary border-opacity-10">
                <h6 class="fw-bold text-white m-0">📋 قائمة طلبات المتجر</h6>
                <div class="d-flex gap-2">
                    <a href="admin_orders.php" class="btn btn-sm <?php echo $filter_status === '' ? 'btn-success' : 'btn-outline-success'; ?> fw-bold">الكل</a>
                    <a href="admin_orders.php?status=pending" class="btn btn-sm <?php echo $filter_status === 'pending' ? 'btn-success' : 'btn-outline-success'; ?> fw-bold">⏳ قيد الانتظار</a>
                    <a href="admin_orders.php?status=processing" class="btn btn-sm <?php echo $filter_status === 'processing' ? 'btn-success' : 'btn-outline-success'; ?> fw-bold">⚙️ قيد التجهيز</a>
                    <a href="admin_orders.php?status=completed" class="btn btn-sm <?php echo $filter_status === 'completed' ? 'btn-success' : 'btn-outline-success'; ?> fw-bold">✅ تم التوصيل</a>
                    <a href="admin_orders.php?status=cancelled" class="btn btn-sm <?php echo $filter_status === 'cancelled' ? 'btn-success' : 'btn-outline-success'; ?> fw-bold">❌ ملغى</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table modern-table text-center align-middle mb-0">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>المستلم</th>
                            <th>رقم الهاتف</th>
                            <th>المجموع الكلي</th>
                            <th>تاريخ الطلب</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $ord): ?>
                                <tr>
                                    <td class="fw-bold text-success">#<?php echo $ord['id']; ?></td>
                                    <td class="text-start text-white"><?php echo htmlspecialchars($ord['full_name']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($ord['phone']); ?></td>
                                    <td class="text-white fw-bold"><?php echo number_format($ord['total'], 0); ?> د.ع</td>
                                    <td class="small text-secondary"><?php echo date('Y-m-d H:i', strtotime($ord['created_at'])); ?></td>
                                    <td>
                                        <?php 
                                        $s = $ord['status'];
                                        if ($s === 'pending') echo '<span class="badge bg-warning text-dark">⏳ قيد الانتظار</span>';
                                        elseif ($s === 'processing') echo '<span class="badge bg-info text-dark">⚙️ قيد التجهيز</span>';
                                        elseif ($s === 'completed') echo '<span class="badge bg-success">✅ تم التوصيل</span>';
                                        elseif ($s === 'cancelled') echo '<span class="badge bg-danger">❌ ملغى</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <a href="admin_orders.php?id=<?php echo $ord['id']; ?>" class="btn btn-sm btn-outline-success fw-bold px-3 py-1">🔎 تفاصيل وتحرير</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-muted p-4">لا توجد طلبات في هذه الفئة حالياً.</td>
                            </tr>
                        <?php endif; ?>
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
