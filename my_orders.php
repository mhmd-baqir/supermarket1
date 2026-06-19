<?php
ob_start();
include 'config.php';

// فرض تسجيل الدخول للعميل
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// جلب جميع طلبات العميل
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

include 'header.php';
?>

<div class="row">
    <!-- قائمة العميل الجانبية -->
    <div class="col-md-3 mb-4 fade-in-up">
        <div class="sidebar-card">
            <h6 class="sidebar-title">👤 حسابي الشخصي</h6>
            <a href="my_account.php" class="sidebar-link">🛠️ تعديل الملف الشخصي</a>
            <a href="my_orders.php" class="sidebar-link active">📦 طلباتي السابقة</a>
            <a href="wishlist.php" class="sidebar-link">❤️ قائمة المفضلة</a>
            <a href="logout.php" class="sidebar-link text-danger">🚪 تسجيل الخروج</a>
        </div>
    </div>

    <!-- قائمة الطلبات -->
    <div class="col-md-9 fade-in-up delay-1">
        <h2 class="page-title">📦 طلباتي السابقة وتتبعها</h2>

        <?php if (count($orders) > 0): ?>
            <div class="d-flex flex-column gap-4">
                <?php foreach ($orders as $order): ?>
                    <div class="glass-card overflow-hidden">
                        <!-- ترويسة الطلب -->
                        <div class="p-3 d-flex flex-wrap justify-content-between align-items-center gap-2" 
                             style="background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <div>
                                <span class="fw-bold text-success fs-5">طلب رقم #<?php echo $order['id']; ?></span>
                                <span class="text-secondary small ms-3">📅 <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div>
                                <?php 
                                $status = $order['status'];
                                if ($status === 'pending') {
                                    echo '<span class="badge bg-warning text-dark px-3 py-2 fw-bold" style="font-size:0.85rem;">⏳ قيد الانتظار</span>';
                                } elseif ($status === 'processing') {
                                    echo '<span class="badge bg-info text-dark px-3 py-2 fw-bold" style="font-size:0.85rem;">⚙️ قيد التجهيز</span>';
                                } elseif ($status === 'completed') {
                                    echo '<span class="badge bg-success px-3 py-2 fw-bold" style="font-size:0.85rem;">✅ تم التوصيل</span>';
                                } elseif ($status === 'cancelled') {
                                    echo '<span class="badge bg-danger px-3 py-2 fw-bold" style="font-size:0.85rem;">❌ ملغى</span>';
                                }
                                ?>
                            </div>
                        </div>

                        <!-- تفاصيل التوصيل وعناصر الفاتورة -->
                        <div class="p-4">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 text-muted small">
                                    <div class="mb-1">👤 <strong>المستلم:</strong> <?php echo htmlspecialchars($order['full_name']); ?></div>
                                    <div>📱 <strong>الهاتف:</strong> <?php echo htmlspecialchars($order['phone']); ?></div>
                                </div>
                                <div class="col-md-6 text-muted small">
                                    <div>📍 <strong>عنوان الشحن:</strong> <?php echo htmlspecialchars($order['address']); ?></div>
                                </div>
                            </div>

                            <!-- خريطة تتبع الشحنة التفاعلية لكربلاء -->
                            <?php if ($order['lat'] !== null && $order['lng'] !== null): ?>
                                <div class="mb-4">
                                    <div class="fw-bold text-success mb-2 small">🗺️ خريطة تتبع الشحنة الجغرافية في كربلاء:</div>
                                    <div id="map_<?php echo $order['id']; ?>" style="height: 250px; border-radius: 12px; border: 1px solid rgba(22, 163, 74, 0.25); z-index: 1;"></div>
                                </div>
                            <?php endif; ?>

                            <!-- قائمة المنتجات للطلب -->
                            <div class="table-responsive">
                                <table class="table table-sm modern-table align-middle text-center mb-0">
                                    <thead>
                                        <tr class="table-dark">
                                            <th class="text-start">اسم المنتج</th>
                                            <th>سعر الوحدة</th>
                                            <th>الكمية</th>
                                            <th>المجموع الفرعي</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $items_stmt = $pdo->prepare("
                                            SELECT oi.*, p.name, p.unit 
                                            FROM order_items oi 
                                            JOIN products p ON oi.product_id = p.id 
                                            WHERE oi.order_id = ?
                                        ");
                                        $items_stmt->execute([$order['id']]);
                                        $items = $items_stmt->fetchAll();

                                        foreach ($items as $item):
                                            $sub = $item['price'] * $item['qty'];
                                        ?>
                                        <tr>
                                            <td class="text-start text-white fw-bold"><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td class="text-muted"><?php echo number_format($item['price'], 0); ?> د.ع / <?php echo htmlspecialchars($item['unit'] ?? 'قطعة'); ?></td>
                                            <td class="text-white fw-bold">× <?php echo $item['qty']; ?> <?php echo htmlspecialchars($item['unit'] ?? 'قطعة'); ?></td>
                                            <td class="text-success fw-bold"><?php echo number_format($sub, 0); ?> د.ع</td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr style="border-top: 2px solid rgba(22, 163, 74, 0.3);">
                                            <td colspan="3" class="text-start fw-bold text-success fs-6">الإجمالي الكلي:</td>
                                            <td class="fw-black text-success fs-5"><?php echo number_format($order['total'], 0); ?> د.ع</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert-modern alert-warning-modern p-4 text-center rounded-3">
                <div style="font-size: 3rem;">📦</div>
                <h5 class="mt-2">ليس لديك طلبات سابقة</h5>
                <p class="mb-0 opacity-75">قم بإضافة المنتجات إلى السلة وأكمل طلبك الأول الآن!</p>
                <a href="index.php" class="btn btn-success mt-3 fw-bold">🏪 اذهب للمتجر الآن</a>
            </div>
        <?php endif; ?>
    </div>
</div>



<!-- تهيئة خرائط التتبع لطلبات كربلاء -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // إحداثيات المتجر الرئيسي الافتراضي في كربلاء
    const storeLat = 32.6160;
    const storeLng = 44.0249;

    <?php foreach ($orders as $order): ?>
        <?php if ($order['lat'] !== null && $order['lng'] !== null): ?>
            (function() {
                const mapId = 'map_<?php echo $order['id']; ?>';
                const custLat = <?php echo floatval($order['lat']); ?>;
                const custLng = <?php echo floatval($order['lng']); ?>;
                const status = '<?php echo $order['status']; ?>';

                // تهيئة الخريطة لكل فواتير الطلبات
                const map = L.map(mapId, {
                    zoomControl: false // إخفاء أزرار التحكم للتناسق مع الشاشات الصغيرة
                }).setView([storeLat, storeLng], 13);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(map);

                // ماركر المتجر (هايبر ماركت رضا أبو لحمة) بأيقونة خضراء
                const storeIcon = L.icon({
                    iconUrl: 'https://cdn-icons-png.flaticon.com/512/869/869636.png', // أيقونة متجر
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });
                L.marker([storeLat, storeLng], {icon: storeIcon}).addTo(map)
                    .bindPopup('🏫 هايبر ماركت رضا أبو لحمة (المركز الرئيسي)')
                    .openPopup();

                // ماركر موقع العميل
                const homeIcon = L.icon({
                    iconUrl: 'https://cdn-icons-png.flaticon.com/512/25/25694.png', // أيقونة منزل
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });
                L.marker([custLat, custLng], {icon: homeIcon}).addTo(map)
                    .bindPopup('📍 موقع التوصيل المختار الخاص بك');

                // رسم خط المسار المنقط باللون الأخضر المميز للمتجر
                const pathLine = L.polyline([[storeLat, storeLng], [custLat, custLng]], {
                    color: '#16a34a',
                    weight: 3,
                    dashArray: '5, 10'
                }).addTo(map);

                // تعديل نطاق الخريطة التلقائي ليناسب الماركرين
                map.fitBounds(pathLine.getBounds(), { padding: [40, 40] });

                // محاكاة سيارة توصيل متحركة على طول المسار فقط عند انتظار أو تحضير الطلب
                if (status === 'pending' || status === 'processing') {
                    const truckIcon = L.icon({
                        iconUrl: 'https://cdn-icons-png.flaticon.com/512/754/754704.png', // شاحنة توصيل
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    });
                    
                    const truckMarker = L.marker([storeLat, storeLng], { icon: truckIcon }).addTo(map);
                    
                    let progress = 0;
                    setInterval(() => {
                        progress += 0.005;
                        if (progress > 1) {
                            progress = 0; // إعادة التكرار من المتجر للبيت
                        }
                        const currentLat = storeLat + (custLat - storeLat) * progress;
                        const currentLng = storeLng + (custLng - storeLng) * progress;
                        truckMarker.setLatLng([currentLat, currentLng]);
                    }, 80);
                }
            })();
        <?php endif; ?>
    <?php endforeach; ?>
});
</script>
<?php include 'footer.php'; ?>
