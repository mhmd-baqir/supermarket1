<?php
ob_start();
include 'config.php';

// فرض تسجيل الدخول للعملاء لإتمام الشراء وتتبع الطلب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_empty = empty($_SESSION['cart']);
$error = '';
$success = false;
$order_id = 0;

// جلب بيانات العميل المسبقة للملء التلقائي
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// معالجة التحقق من كود الخصم (AJAX)
if (isset($_GET['check_coupon'])) {
    header('Content-Type: application/json');
    $code = strtoupper(trim($_GET['check_coupon']));
    // حساب المجموع الكلي من السلة
    $total_for_coupon = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $pid => $qty) {
            $ps = $pdo->prepare("SELECT price FROM products WHERE id=?");
            $ps->execute([$pid]);
            $pp = $ps->fetch();
            if ($pp) $total_for_coupon += $pp['price'] * $qty;
        }
    }
    $cp_stmt = $pdo->prepare("SELECT * FROM coupons WHERE code=? AND is_active=1");
    $cp_stmt->execute([$code]);
    $coupon = $cp_stmt->fetch();
    if (!$coupon) {
        echo json_encode(['valid' => false, 'msg' => 'كود الخصم غير صحيح أو منتهي الصلاحية.']);
    } elseif ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
        echo json_encode(['valid' => false, 'msg' => 'كود الخصم انتهت صلاحيته.']);
    } elseif ($coupon['used_count'] >= $coupon['max_uses']) {
        echo json_encode(['valid' => false, 'msg' => 'تم استنفاذ هذا الكود، جرب كوداً آخر.']);
    } elseif ($total_for_coupon < $coupon['min_order']) {
        echo json_encode(['valid' => false, 'msg' => 'الحد الأدنى للطلب هو ' . number_format($coupon['min_order'], 0) . ' د.ع']);
    } else {
        $disc = $coupon['discount_type'] === 'percentage'
            ? ($total_for_coupon * $coupon['discount_value'] / 100)
            : $coupon['discount_value'];
        $disc = min($disc, $total_for_coupon);
        echo json_encode(['valid' => true, 'msg' => 'تم تطبيق الخصم بنجاح! 🎉', 'discount' => $disc, 'total' => $total_for_coupon - $disc]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$cart_empty) {
    $full_name     = trim($_POST['full_name'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $lat           = isset($_POST['lat']) && $_POST['lat'] !== '' ? floatval($_POST['lat']) : null;
    $lng           = isset($_POST['lng']) && $_POST['lng'] !== '' ? floatval($_POST['lng']) : null;
    $coupon_code   = strtoupper(trim($_POST['coupon_code'] ?? ''));
    $notes         = trim($_POST['notes'] ?? '');

    if (empty($full_name) || empty($phone) || empty($address)) {
        $error = 'يرجى ملء جميع حقول الشحن والتوصيل.';
    } elseif ($lat === null || $lng === null) {
        $error = 'يرجى تحديد موقع منزلك بدقة على خريطة كربلاء لإتمام الطلب.';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. حساب السعر الإجمالي
            $total_price = 0;
            $items_to_save = [];
            foreach ($_SESSION['cart'] as $prod_id => $qty) {
                $p_stmt = $pdo->prepare("SELECT price, stock FROM products WHERE id = ? FOR UPDATE");
                $p_stmt->execute([$prod_id]);
                $prod = $p_stmt->fetch();
                if (!$prod) throw new Exception("أحد المنتجات غير موجود في المتجر حالياً.");
                if ($prod['stock'] < $qty) throw new Exception("الكمية المطلوبة من المنتج غير متوفرة في المخزن.");
                $total_price += $prod['price'] * $qty;
                $items_to_save[] = ['product_id' => $prod_id, 'qty' => $qty, 'price' => $prod['price']];
            }

            // 2. التحقق من كود الخصم وحساب الخصم
            $discount_amount = 0;
            $applied_coupon = '';
            if ($coupon_code) {
                $cp_stmt = $pdo->prepare("SELECT * FROM coupons WHERE code=? AND is_active=1");
                $cp_stmt->execute([$coupon_code]);
                $coupon = $cp_stmt->fetch();
                if ($coupon && (!$coupon['expires_at'] || strtotime($coupon['expires_at']) >= time())
                    && $coupon['used_count'] < $coupon['max_uses']
                    && $total_price >= $coupon['min_order']) {
                    $discount_amount = $coupon['discount_type'] === 'percentage'
                        ? ($total_price * $coupon['discount_value'] / 100)
                        : $coupon['discount_value'];
                    $discount_amount = min($discount_amount, $total_price);
                    $applied_coupon = $coupon_code;
                    // زيادة عدد الاستخدامات
                    $pdo->prepare("UPDATE coupons SET used_count=used_count+1 WHERE code=?")->execute([$coupon_code]);
                }
            }
            $final_total = $total_price - $discount_amount;

            // 3. إدخال الطلب
            $ins_order = $pdo->prepare("INSERT INTO orders (user_id, full_name, phone, address, total, lat, lng, status, coupon_code, discount_amount, notes) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
            $ins_order->execute([$user_id, $full_name, $phone, $address, $final_total, $lat, $lng, $applied_coupon ?: null, $discount_amount, $notes ?: null]);
            $order_id = $pdo->lastInsertId();

            // 4. إدخال عناصر الطلب وخصم المخزون
            $ins_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
            $up_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            foreach ($items_to_save as $item) {
                $ins_item->execute([$order_id, $item['product_id'], $item['qty'], $item['price']]);
                $up_stock->execute([$item['qty'], $item['product_id']]);
            }

            // 5. إشعار للمستخدم
            $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, 'order', ?)"
            )->execute([$user_id, '📦 تم استلام طلبك #'.$order_id, 'طلبك رقم #'.$order_id.' قيد المعالجة. يمكنك تتبعه من صفحة طلباتي.', 'my_orders.php']);

            $pdo->commit();
            unset($_SESSION['cart']);
            $success = true;
            $discount_saved = $discount_amount;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'فشلت عملية الشراء: ' . htmlspecialchars($e->getMessage());
        }
    }
}

include 'header.php';
?>

<div class="row justify-content-center fade-in-up">
    <div class="col-md-9 col-lg-8">
        <?php if($success): ?>
            <!-- رسالة النجاح -->
            <div class="checkout-card text-center">
                <div style="font-size: 5rem; margin-bottom: 20px; animation: fadeInUp 0.6s ease;">🎉</div>
                <h2 class="fw-bold mb-3" style="color: #4ade80;">تمت عملية الشراء بنجاح!</h2>
                <p style="color: #94a3b8; font-size: 1.05rem; margin-bottom: 30px;">
                    شكراً لتسوقك معنا! تم تسجيل طلبك برقم <strong>#<?php echo $order_id; ?></strong> وتأكيده في النظام بنجاح.
                </p>
                <div class="p-4 mb-4" style="background: rgba(22,163,74,0.1); border: 1px solid rgba(22,163,74,0.2); border-radius: 16px;">
                    <div style="color: #4ade80; font-weight: 700; margin-bottom: 8px;">✅ تم تثبيت موقع التوصيل الجغرافي</div>
                    <div style="color: #94a3b8; font-size: 0.9rem;">يمكنك تتبع حركة مندوب التوصيل والطلب الآن من صفحة طلباتي التفاعلية.</div>
                </div>
                <div class="d-flex gap-3">
                    <a href="my_orders.php" class="btn btn-success fw-bold flex-fill py-3" style="background: linear-gradient(135deg,#16a34a,#15803d); border:none; border-radius: 12px;">
                        📦 تتبع مسار طلبي على الخريطة
                    </a>
                    <a href="index.php" class="btn btn-secondary fw-bold flex-fill py-3" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 12px; color: #cbd5e1;">
                        🏪 مواصلة التسوق
                    </a>
                </div>
            </div>

        <?php elseif($cart_empty): ?>
            <!-- السلة فارغة -->
            <div class="checkout-card text-center">
                <div style="font-size: 4rem; margin-bottom: 20px;">🛒</div>
                <h3 class="fw-bold mb-3" style="color: #f1f5f9;">السلة فارغة!</h3>
                <p style="color: #94a3b8;">لا يمكن إتمام الشراء بدون منتجات في السلة.</p>
                <a href="index.php" class="btn btn-success btn-lg fw-bold w-100 mt-3" style="border-radius: 14px; background: linear-gradient(135deg,#16a34a,#15803d); border:none;">
                    🏪 تصفح المنتجات الآن
                </a>
            </div>

        <?php else: ?>
            <!-- فورم الشراء والخريطة -->
            <div class="checkout-card">
                <div class="text-center mb-4">
                    <div style="font-size: 2.5rem; margin-bottom: 10px;">💳</div>
                    <h3 class="fw-bold text-success">إتمام الشراء والشحن بالتتبع</h3>
                    <p style="color: #94a3b8; font-size: 0.9rem;">يرجى تأكيد العنوان وتحديد موقعك بدقة على الخريطة</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert-modern alert-danger-modern p-3 rounded-3 mb-4">
                        ⚠️ <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="checkoutForm">
                    <!-- حقول الإحداثيات المخفية -->
                    <input type="hidden" name="lat" id="delivery_lat" value="<?php echo isset($_POST['lat']) ? htmlspecialchars($_POST['lat']) : ''; ?>">
                    <input type="hidden" name="lng" id="delivery_lng" value="<?php echo isset($_POST['lng']) ? htmlspecialchars($_POST['lng']) : ''; ?>">

                    <h5 class="fw-bold text-white mb-3 pb-2 border-bottom border-secondary border-opacity-25">📍 تفاصيل الشحن والتوصيل</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">👤 الاسم الكامل للمستلم <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" required 
                                   placeholder="أدخل الاسم الرباعي للمستلم"
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">📱 رقم الهاتف للتواصل <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="phone" required 
                                   placeholder="07XXXXXXXX"
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">🏠 عنوان التوصيل كتابياً <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="address" rows="2" required 
                                      placeholder="مثال: كربلاء، حي الموظفين، قرب مدرسة اليرموك، زقاق 14، دار 5"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- خريطة كربلاء التفاعلية -->
                        <div class="col-12 my-3">
                            <label class="form-label d-block text-success fw-bold">🗺️ حدد موقع منزلك الجغرافي على خريطة كربلاء المقدسة: <span class="text-danger">*</span></label>
                            <div id="checkoutMap" style="height: 350px; border-radius: 12px; border: 2px solid rgba(22, 163, 74, 0.4); z-index: 10;"></div>
                            <small class="text-muted mt-2 d-block">
                                💡 <strong>طريقة التحديد:</strong> تصفح الخريطة وانقر على موقع منزلك بدقة. ستظهر علامة حمراء تشير للموقع. يمكنك سحبها لتعديلها.
                            </small>
                        </div>
                    </div>

                    <!-- قسم كود الخصم -->
                    <h5 class="fw-bold text-white mb-3 pb-2 border-bottom border-secondary border-opacity-25">🎟️ كود الخصم (اختياري)</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label">أدخل كود الخصم إن وجد</label>
                            <input type="text" class="form-control text-uppercase" name="coupon_code" id="coupon_input"
                                   placeholder="مثال: WELCOME10" autocomplete="off"
                                   oninput="this.value=this.value.toUpperCase()">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" onclick="checkCoupon()" class="btn fw-bold w-100"
                                    style="background: linear-gradient(135deg,#f59e0b,#d97706); color:white; border:none; border-radius:10px; padding:10px;">
                                🎟️ تطبيق الكود
                            </button>
                        </div>
                        <div class="col-12" id="coupon_msg" style="display:none;"></div>
                    </div>

                    <!-- ملاحظات إضافية -->
                    <div class="mb-4">
                        <label class="form-label">📝 ملاحظات للتوصيل (اختياري)</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="مثال: اتصل قبل الوصول، الطابق الثاني..."></textarea>
                    </div>

                    <!-- تفاصيل الدفع -->
                    <h5 class="fw-bold text-white mb-3 pb-2 border-bottom border-secondary border-opacity-25">💳 طريقة الدفع</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="p-4 rounded-3 d-flex gap-3 align-items-center" style="background: rgba(22,163,74,0.1); border: 1px solid rgba(22,163,74,0.3); cursor:pointer;" onclick="selectPayment('cod')" id="pay_cod">
                                <div style="font-size:2rem;">💵</div>
                                <div>
                                    <div class="fw-bold" style="color:#4ade80;">الدفع عند الاستلام</div>
                                    <small style="color:#94a3b8;">ادفع نقداً عند وصول المندوب إلى بابك</small>
                                </div>
                                <div class="me-auto"><input type="radio" name="payment_method" value="cod" checked style="width:18px;height:18px;"></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-4 rounded-3 d-flex gap-3 align-items-center" style="background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.08); cursor:pointer;" onclick="selectPayment('card')" id="pay_card">
                                <div style="font-size:2rem;">💳</div>
                                <div>
                                    <div class="fw-bold" style="color:#f1f5f9;">الدفع ببطاقة (تجريبي)</div>
                                    <small style="color:#94a3b8;">أدخل بيانات البطاقة للمحاكاة</small>
                                </div>
                                <div class="me-auto"><input type="radio" name="payment_method" value="card" style="width:18px;height:18px;"></div>
                            </div>
                        </div>
                        <div class="col-12" id="card_fields" style="display:none;">
                            <div class="row g-3 p-3 rounded-3" style="background: rgba(15,23,42,0.5);">
                                <div class="col-12">
                                    <label class="form-label">💳 رقم البطاقة</label>
                                    <input type="text" class="form-control" placeholder="1234 5678 9101 1121" maxlength="19" oninput="formatCard(this)">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">📅 تاريخ الانتهاء</label>
                                    <input type="text" class="form-control" placeholder="MM/YY" maxlength="5">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">🔒 CVV</label>
                                    <input type="text" class="form-control" placeholder="123" maxlength="3">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ملخص الطلب -->
                    <div id="order_summary" class="p-4 mb-4 rounded-3" style="background: rgba(15,23,42,0.7); border: 1px solid rgba(22,163,74,0.2);">
                        <h6 class="fw-bold mb-3" style="color:#4ade80;">📋 ملخص الطلب</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span style="color:#94a3b8;">إجمالي المنتجات</span>
                            <span id="summary_subtotal" class="fw-bold" style="color:#f1f5f9;">—</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2" id="summary_discount_row" style="display:none!important;">
                            <span style="color:#f59e0b;">🎟️ خصم الكوبون</span>
                            <span id="summary_discount" class="fw-bold" style="color:#f59e0b;">—</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span style="color:#94a3b8;">رسوم التوصيل</span>
                            <span class="fw-bold" style="color:#4ade80;">مجاني 🎉</span>
                        </div>
                        <hr style="border-color: rgba(255,255,255,0.1);">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold" style="color:#f1f5f9;">الإجمالي النهائي</span>
                            <span id="summary_total" class="fw-bold" style="color:#4ade80; font-size:1.3rem;">—</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-lg w-100 fw-bold py-3"
                            style="background: linear-gradient(135deg,#16a34a,#15803d); color:white; border:none; border-radius:14px; font-size:1.1rem;">
                        ✅ تأكيد وإرسال الطلب
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function formatCard(input) {
    let value = input.value.replace(/\D/g, '');
    let formatted = value.match(/.{1,4}/g);
    input.value = formatted ? formatted.join(' ') : '';
}

// اختيار طريقة الدفع
function selectPayment(method) {
    document.getElementById('card_fields').style.display = method === 'card' ? 'block' : 'none';
    document.getElementById('pay_cod').style.background = method === 'cod' ? 'rgba(22,163,74,0.1)' : 'rgba(30,41,59,0.5)';
    document.getElementById('pay_card').style.background = method === 'card' ? 'rgba(22,163,74,0.1)' : 'rgba(30,41,59,0.5)';
    document.querySelector(`input[value="${method}"]`).checked = true;
}

// التحقق من كود الخصم
let discountApplied = 0;
let subtotalValue = 0;
function checkCoupon() {
    const code = document.getElementById('coupon_input').value.trim();
    const msgEl = document.getElementById('coupon_msg');
    if (!code) { msgEl.style.display='none'; return; }
    fetch(`checkout.php?check_coupon=${encodeURIComponent(code)}`)
        .then(r => r.json())
        .then(data => {
            msgEl.style.display = 'block';
            if (data.valid) {
                msgEl.innerHTML = `<div class="p-3 rounded-3" style="background:rgba(22,163,74,0.15);border:1px solid rgba(22,163,74,0.3);color:#4ade80;">✅ ${data.msg}</div>`;
                discountApplied = data.discount;
                updateSummary();
            } else {
                msgEl.innerHTML = `<div class="p-3 rounded-3" style="background:rgba(220,38,38,0.15);border:1px solid rgba(220,38,38,0.3);color:#f87171;">❌ ${data.msg}</div>`;
                discountApplied = 0;
                updateSummary();
            }
        });
}

// تحديث ملخص الطلب
function updateSummary() {
    const subEl = document.getElementById('summary_subtotal');
    const totEl = document.getElementById('summary_total');
    const discRow = document.getElementById('summary_discount_row');
    const discEl = document.getElementById('summary_discount');
    if (!subEl) return;
    subEl.textContent = subtotalValue.toLocaleString('ar-IQ') + ' د.ع';
    if (discountApplied > 0) {
        discRow.style.display = 'flex';
        discEl.textContent = '- ' + discountApplied.toLocaleString('ar-IQ') + ' د.ع';
        totEl.textContent = (subtotalValue - discountApplied).toLocaleString('ar-IQ') + ' د.ع';
    } else {
        discRow.style.display = 'none';
        totEl.textContent = subtotalValue.toLocaleString('ar-IQ') + ' د.ع';
    }
}

// جلب المجموع من السلة وعرضه
fetch('cart_ajax.php?get_total=1')
    .then(r => r.json())
    .then(data => {
        if (data && data.subtotal !== undefined) {
            subtotalValue = data.subtotal;
            updateSummary();
        }
    }).catch(() => {});


// تهيئة خريطة كربلاء باستخدام Google Maps
let map;
let marker;
const karbala = { lat: 32.6160, lng: 44.0249 };

function initMap() {
    const prevLat = document.getElementById('delivery_lat').value;
    const prevLng = document.getElementById('delivery_lng').value;
    const initialCenter = (prevLat && prevLng) 
        ? { lat: parseFloat(prevLat), lng: parseFloat(prevLng) }
        : karbala;

    map = new google.maps.Map(document.getElementById("checkoutMap"), {
        zoom: 13,
        center: initialCenter
    });

    if (prevLat && prevLng) {
        placeMarker(initialCenter);
    }

    map.addListener("click", (event) => {
        placeMarker(event.latLng);
    });
}

function placeMarker(latLng) {
    const lat = (typeof latLng.lat === 'function') ? latLng.lat() : latLng.lat;
    const lng = (typeof latLng.lng === 'function') ? latLng.lng() : latLng.lng;

    document.getElementById('delivery_lat').value = lat;
    document.getElementById('delivery_lng').value = lng;

    if (marker) {
        marker.setPosition(latLng);
    } else {
        marker = new google.maps.Marker({
            position: latLng,
            map: map,
            draggable: true
        });

        marker.addListener("dragend", () => {
            const pos = marker.getPosition();
            document.getElementById('delivery_lat').value = pos.lat();
            document.getElementById('delivery_lng').value = pos.lng();
        });
    }
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?callback=initMap" async defer></script>
<script>
<?php include 'footer.php'; ?>
