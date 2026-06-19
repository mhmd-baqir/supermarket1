<?php
ob_start();
include 'config.php';

// فرض تسجيل الدخول للعميل
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// معالجة تأكيد الاستلام والتقييم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_received'])) {
    $order_id = intval($_POST['order_id']);
    $rating = intval($_POST['rating'] ?? 5);
    $feedback = trim($_POST['feedback'] ?? '');

    // تحقق من أن الطلب يخص العميل
    $check_stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $check_stmt->execute([$order_id, $user_id]);
    $order_data = $check_stmt->fetch();

    if ($order_data) {
        // تحديث الطلب
        $update_stmt = $pdo->prepare("UPDATE orders SET customer_received = 1, delivery_rating = ?, delivery_feedback = ? WHERE id = ?");
        $update_stmt->execute([$rating, $feedback, $order_id]);

        // إرسال إشعار للادمن
        $admin_msg = "العميل " . ($_SESSION['full_name'] ?? $_SESSION['username']) . " أكد استلام الطلب رقم #{$order_id} وقام بتقييم التوصيل بـ {$rating} نجوم.";
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (NULL, ?, ?, 'order', ?)");
        $notif_stmt->execute([
            "✅ تأكيد استلام طلب #{$order_id}",
            $admin_msg,
            "admin_orders.php?id={$order_id}"
        ]);

        header("Location: my_orders.php?success=1");
        exit;
    }
}

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
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($order['customer_received'] == 1): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-3 py-2 fw-bold" style="font-size:0.85rem;">📢 تم الاستلام والتقييم</span>
                                <?php elseif ($order['status'] !== 'cancelled'): ?>
                                    <button type="button" class="btn btn-sm btn-success fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#receiveModal" data-order-id="<?php echo $order['id']; ?>">
                                        ✅ تأكيد الاستلام والتقييم
                                    </button>
                                <?php endif; ?>

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

                            <!-- تقييم التوصيل والمراسلة -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top border-secondary border-opacity-10 gap-3">
                                <div>
                                    <?php if ($order['customer_received'] == 1): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-success fw-bold small">⭐ تقييمك للتوصيل:</span>
                                            <div>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span style="color: <?php echo $i <= $order['delivery_rating'] ? '#f59e0b' : 'rgba(255,255,255,0.15)'; ?>;">★</span>
                                                <?php endfor; ?>
                                            </div>
                                            <?php if (!empty($order['delivery_feedback'])): ?>
                                                <span class="text-muted small ms-2">| 💬 <?php echo htmlspecialchars($order['delivery_feedback']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <a href="support.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-danger fw-bold">
                                        💬 أواجه مشكلة في هذا الطلب (مراسلة الإدارة)
                                    </a>
                                </div>
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
<?php include 'footer.php'; ?>

<!-- Modal تأكيد الاستلام والتقييم -->
<div class="modal fade" id="receiveModal" tabindex="-1" aria-labelledby="receiveModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card" style="border: 1px solid rgba(16, 185, 129, 0.3);">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold text-success" id="receiveModalLabel">✅ تأكيد الاستلام وتقييم التوصيل</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="my_orders.php">
        <div class="modal-body py-4">
          <input type="hidden" name="order_id" id="modal_order_id">
          
          <div class="text-center mb-4">
            <label class="form-label d-block fw-bold fs-6 mb-2">كيف تقيم خدمة التوصيل والدلفري؟ ⭐</label>
            <div class="star-rating d-flex justify-content-center gap-2">
              <span class="fs-2 star-btn" data-value="1" style="cursor:pointer; color:#f59e0b; transition: transform 0.2s;">★</span>
              <span class="fs-2 star-btn" data-value="2" style="cursor:pointer; color:#f59e0b; transition: transform 0.2s;">★</span>
              <span class="fs-2 star-btn" data-value="3" style="cursor:pointer; color:#f59e0b; transition: transform 0.2s;">★</span>
              <span class="fs-2 star-btn" data-value="4" style="cursor:pointer; color:#f59e0b; transition: transform 0.2s;">★</span>
              <span class="fs-2 star-btn" data-value="5" style="cursor:pointer; color:#f59e0b; transition: transform 0.2s;">★</span>
            </div>
            <input type="hidden" name="rating" id="rating_input" value="5">
          </div>

          <div class="mb-3">
            <label for="feedback" class="form-label fw-bold">ملاحظاتك حول التوصيل (اختياري)</label>
            <textarea name="feedback" id="feedback" class="form-control" rows="3" placeholder="اكتب ملاحظة أو تعليق بخصوص السائق أو التوصيل..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">إلغاء</button>
          <button type="submit" name="submit_received" class="btn btn-success fw-bold px-4">حفظ وإرسال التقييم ✅</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // تمرير رقم الطلب إلى المودال عند النقر
    const receiveModal = document.getElementById('receiveModal');
    if (receiveModal) {
        receiveModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const orderId = button.getAttribute('data-order-id');
            const modalOrderIdInput = receiveModal.querySelector('#modal_order_id');
            modalOrderIdInput.value = orderId;
        });
    }

    // إدارة اختيار النجوم التفاعلي
    const starBtns = document.querySelectorAll('.star-btn');
    const ratingInput = document.getElementById('rating_input');
    
    starBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const val = parseInt(this.getAttribute('data-value'));
            ratingInput.value = val;
            
            // تلوين النجوم
            starBtns.forEach(b => {
                const bVal = parseInt(b.getAttribute('data-value'));
                if (bVal <= val) {
                    b.style.color = '#f59e0b';
                    b.style.transform = 'scale(1.2)';
                } else {
                    b.style.color = 'rgba(255,255,255,0.15)';
                    b.style.transform = 'scale(1)';
                }
                setTimeout(() => { b.style.transform = 'scale(1)'; }, 150);
            });
        });
    });
});
</script>
