<?php
ob_start();
include 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);

// تحديد الإشعارات كمقروءة
if (isset($_GET['mark_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? OR user_id IS NULL")->execute([$user_id]);
    header('Location: notifications.php');
    exit;
}

// جلب الإشعارات الخاصة بالمستخدم + العامة
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? OR user_id IS NULL ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$unread = array_filter($notifications, fn($n) => !$n['is_read']);
?>

<div class="container-fluid" style="max-width: 800px;">
    <!-- رأس الصفحة -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="page-title mb-0">🔔 الإشعارات</h2>
        <?php if(count($unread) > 0): ?>
            <a href="notifications.php?mark_read=1" class="btn btn-sm fw-bold" 
               style="background: rgba(22,163,74,0.15); border: 1px solid rgba(22,163,74,0.3); color: #4ade80; border-radius: 10px; padding: 8px 16px;">
                ✅ تحديد الكل كمقروء (<?= count($unread) ?>)
            </a>
        <?php endif; ?>
    </div>

    <?php if(count($notifications) === 0): ?>
        <div class="glass-card p-5 text-center">
            <div style="font-size: 4rem; margin-bottom: 16px;">🔕</div>
            <h5 style="color: #94a3b8;">لا توجد إشعارات حالياً</h5>
            <p style="color: #64748b; font-size: 0.9rem;">ستصلك إشعارات عند وجود عروض أو تحديثات على طلباتك</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach($notifications as $n): ?>
                <?php
                $icon = match($n['type']) {
                    'order'  => '📦',
                    'promo'  => '🎉',
                    'alert'  => '⚠️',
                    default  => '🔔'
                };
                $borderColor = match($n['type']) {
                    'order'  => 'rgba(59,130,246,0.4)',
                    'promo'  => 'rgba(245,158,11,0.4)',
                    'alert'  => 'rgba(220,38,38,0.4)',
                    default  => 'rgba(22,163,74,0.3)'
                };
                $isUnread = !$n['is_read'];
                ?>
                <div class="glass-card p-4 position-relative" 
                     style="border-color: <?= $borderColor ?>; <?= $isUnread ? 'background: rgba(30,41,59,0.95);' : 'opacity: 0.7;' ?>">
                    <?php if($isUnread): ?>
                        <span class="position-absolute" style="top: 12px; left: 12px; width: 10px; height: 10px; background: #16a34a; border-radius: 50; display: block; border-radius: 50%;"></span>
                    <?php endif; ?>
                    <div class="d-flex align-items-start gap-3">
                        <div style="font-size: 2rem; flex-shrink: 0;"><?= $icon ?></div>
                        <div class="flex-fill">
                            <h6 class="fw-bold mb-1" style="color: #f1f5f9;"><?= htmlspecialchars($n['title']) ?></h6>
                            <p class="mb-2" style="color: #94a3b8; font-size: 0.9rem;"><?= htmlspecialchars($n['message']) ?></p>
                            <div class="d-flex align-items-center justify-content-between">
                                <small style="color: #475569; font-size: 0.8rem;">
                                    🕐 <?= date('Y/m/d H:i', strtotime($n['created_at'])) ?>
                                </small>
                                <?php if($n['link']): ?>
                                    <a href="<?= htmlspecialchars($n['link']) ?>" class="btn btn-sm fw-bold"
                                       style="background: rgba(22,163,74,0.15); border: 1px solid rgba(22,163,74,0.3); color: #4ade80; border-radius: 8px; font-size: 0.8rem; padding: 4px 12px;">
                                        عرض ←
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer class="main-footer mt-5">
    <p class="mb-0">© 2024 الهايبر ماركت المتكامل — جميع الحقوق محفوظة</p>
</footer>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
