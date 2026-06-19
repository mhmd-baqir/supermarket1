</div> <!-- إغلاق container المفتوح في header.php -->

<!-- FOOTER -->
<footer class="main-footer mt-5" style="background: var(--navbar-bg) !important; border-top: 2px solid var(--navbar-border) !important; padding: 40px 0 20px 0; color: var(--text-main) !important; transition: all 0.3s; margin-top: 80px !important;">
  <div class="container">
    <div class="row g-4 text-start" dir="rtl">
      
      <!-- العمود الأول: من نحن -->
      <div class="col-lg-4 col-md-6">
        <h5 class="fw-bold mb-3" style="color: var(--primary);">🥩 هايبر ماركت رضا أبو لحمة</h5>
        <p class="text-muted small lh-lg">
          وجهتكم الأولى للتسوق المتكامل في كربلاء المقدسة. نوفر لكم أجود أنواع اللحوم الطازجة والمشويات، الفواكه والخضروات، مشتقات الألبان، والمواد الغذائية والمنزلية بأعلى معايير الجودة وأفضل الأسعار.
        </p>
        <div class="d-flex gap-2 mt-3">
          <a href="https://wa.me/9647801234567" target="_blank" class="btn btn-sm btn-outline-success rounded-circle" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-color: rgba(16, 185, 129, 0.3); color: var(--primary);">
            💬
          </a>
          <a href="#" class="btn btn-sm btn-outline-success rounded-circle" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-color: rgba(16, 185, 129, 0.3); color: var(--primary);">
            📸
          </a>
          <a href="#" class="btn btn-sm btn-outline-success rounded-circle" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-color: rgba(16, 185, 129, 0.3); color: var(--primary);">
            👍
          </a>
        </div>
      </div>

      <!-- العمود الثاني: روابط سريعة -->
      <div class="col-lg-4 col-md-6 px-lg-5">
        <h5 class="fw-bold mb-3" style="color: var(--primary);">🔗 روابط سريعة</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="index.php" class="text-decoration-none text-muted small hover-primary">🏠 الصفحة الرئيسية</a></li>
          <li class="mb-2"><a href="about.php" class="text-decoration-none text-muted small hover-primary">ℹ️ من نحن (About Us)</a></li>
          <li class="mb-2"><a href="cart.php" class="text-decoration-none text-muted small hover-primary">🛒 سلة التسوق</a></li>
          <?php if(isset($_SESSION['user_id'])): ?>
            <li class="mb-2"><a href="my_orders.php" class="text-decoration-none text-muted small hover-primary">📦 تتبع طلباتي</a></li>
          <?php else: ?>
            <li class="mb-2"><a href="login.php" class="text-decoration-none text-muted small hover-primary">🔐 تسجيل الدخول</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- العمود الثالث: معلومات الاتصال والموقع -->
      <div class="col-lg-4 col-md-12">
        <h5 class="fw-bold mb-3" style="color: var(--primary);">📍 تواصل معنا</h5>
        <ul class="list-unstyled text-muted small">
          <li class="mb-3 d-flex align-items-start gap-2">
            <span>📍</span>
            <div>
              <strong>العنوان:</strong><br>
              العراق، كربلاء المقدسة، حي الحسين
            </div>
          </li>
          <li class="mb-3 d-flex align-items-center gap-2">
            <span>📞</span>
            <div>
              <strong>الهاتف:</strong> <a href="tel:07801234567" class="text-decoration-none text-muted">0780 123 4567</a> (رقم افتراضي)
            </div>
          </li>
          <li class="mb-3 d-flex align-items-center gap-2">
            <span>💬</span>
            <div>
              <strong>واتساب الخدمة:</strong> <a href="https://wa.me/9647801234567" class="text-decoration-none text-muted">0780 123 4567</a>
            </div>
          </li>
        </ul>
      </div>

    </div>

    <hr style="border-color: var(--navbar-border); margin: 30px 0 20px 0;">

    <div class="row align-items-center">
      <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
        <p class="mb-0 text-muted small">
          © 2026 <strong>هايبر ماركت رضا أبو لحمة</strong>. جميع الحقوق محفوظة.
        </p>
      </div>
      <div class="col-md-6 text-center text-md-end">
        <span class="text-muted" style="font-size: 0.75rem;">تطوير احترافي فئة ممتازة | كربلاء حي الحسين</span>
      </div>
    </div>
  </div>
</footer>

<!-- تنسيق تأثيرات الحوم والروابط في الفوتر -->
<style>
.hover-primary {
  transition: color 0.2s ease-in-out, padding-right 0.2s ease-in-out;
}
.hover-primary:hover {
  color: var(--primary) !important;
  padding-right: 5px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
