<?php
ob_start();
include 'config.php';
include 'header.php';
?>

<div class="row justify-content-center mt-4 fade-in-up">
    <div class="col-lg-10">
        <!-- قسم العنوان البارز -->
        <div class="text-center mb-5 p-5 rounded-4" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.02) 100%); border: 1px solid var(--primary-border);">
            <div style="font-size: 4rem; animation: float 3s ease-in-out infinite;">🥩</div>
            <h1 class="fw-bold mt-3" style="color: var(--text-main); font-size: 2.8rem; font-weight: 900;">من نحن - رضا أبو لحمة</h1>
            <p class="text-muted mt-2" style="font-size: 1.1rem; max-width: 700px; margin: 0 auto;">
                قصة الجودة، الأمانة، والخدمة الممتازة في قلب كربلاء المقدسة.
            </p>
        </div>

        <!-- القسم الأول: من نحن وقصتنا -->
        <div class="row align-items-center g-5 mb-5">
            <div class="col-md-6">
                <h2 class="fw-bold mb-4" style="color: var(--primary); font-weight: 800; border-bottom: 3px solid var(--primary); display: inline-block; padding-bottom: 8px;">قصتنا ورؤيتنا</h2>
                <p class="text-muted lh-lg" style="text-align: justify; font-size: 1.05rem;">
                    بدأ **هايبر ماركت رضا أبو لحمة** كملحمة صغيرة تقدم أجود أنواع اللحوم العراقية الطازجة. ومع كسب ثقة ومحبة أهالي **كربلاء المقدسة وتحديداً حي الحسين**، توسعنا لنصبح مركز تسوق متكامل يلبي كافة احتياجات العائلة اليومية.
                </p>
                <p class="text-muted lh-lg" style="text-align: justify; font-size: 1.05rem;">
                    رؤيتنا بسيطة ومباشرة: أن نكون الخيار الأول والأنظف لكل بيت كربلائي من خلال تقديم لحوم طازجة يومياً، خضار وفواكه قطاف اليوم، ومواد منزلية وغذائية منتقاة بعناية فائقة، مع خدمة توصيل فائقة السرعة تراعي راحة العميل.
                </p>
            </div>
            <div class="col-md-6">
                <!-- صورة توضيحية من Unsplash عن الماركت/اللحوم -->
                <div class="position-relative" style="border-radius: 20px; overflow: hidden; border: 1px solid var(--primary-border); box-shadow: var(--shadow-lg);">
                    <img src="https://images.unsplash.com/photo-1603048588665-791ca8aea617?w=600&auto=format&fit=crop&q=80" class="img-fluid w-100" alt="لحم عجل طازج" style="height: 350px; object-fit: cover;">
                    <div class="position-absolute bottom-0 start-0 w-100 p-3 text-white fw-bold text-center" style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
                        قسم اللحوم والمشويات الطازجة يومياً
                    </div>
                </div>
            </div>
        </div>

        <!-- القسم الثاني: قيمنا ومميزاتنا -->
        <h3 class="fw-bold text-center mb-4 mt-5" style="color: var(--text-main); font-weight: 800;">لماذا تتسوق من رضا أبو لحمة؟</h3>
        <div class="row g-4 mb-5">
            <!-- كارت 1 -->
            <div class="col-md-4">
                <div class="glass-card p-4 text-center h-100 border border-success border-opacity-25" style="border-radius: 20px; box-shadow: var(--shadow-sm); transition: transform 0.3s;">
                    <div style="font-size: 3rem; margin-bottom: 15px;">🥩</div>
                    <h5 class="fw-bold text-success">لحوم بلدية طازجة</h5>
                    <p class="text-muted small lh-lg">
                        لحوم عجل وأغنام عراقية بلدية تذبح يومياً تحت إشراف صحي كامل، مقطعة ومجهزة بدقة حسب رغبتكم.
                    </p>
                </div>
            </div>
            <!-- كارت 2 -->
            <div class="col-md-4">
                <div class="glass-card p-4 text-center h-100 border border-success border-opacity-25" style="border-radius: 20px; box-shadow: var(--shadow-sm); transition: transform 0.3s;">
                    <div style="font-size: 3rem; margin-bottom: 15px;">🥦</div>
                    <h5 class="fw-bold text-success">خضار وفواكه يومية</h5>
                    <p class="text-muted small lh-lg">
                        نستقبل صباح كل يوم شحنات الخضار والفواكه الطازجة من المزارع مباشرة لضمان أعلى فائدة ونكهة رائعة.
                    </p>
                </div>
            </div>
            <!-- كارت 3 -->
            <div class="col-md-4">
                <div class="glass-card p-4 text-center h-100 border border-success border-opacity-25" style="border-radius: 20px; box-shadow: var(--shadow-sm); transition: transform 0.3s;">
                    <div style="font-size: 3rem; margin-bottom: 15px;">🚚</div>
                    <h5 class="fw-bold text-success">توصيل سريع ودقيق</h5>
                    <p class="text-muted small lh-lg">
                        فريق دليفري مخصص لتغطية جميع مناطق كربلاء المقدسة وحي الحسين لضمان وصول الطلب لباب بيتك بأسرع وقت.
                    </p>
                </div>
            </div>
        </div>

        <!-- القسم الثالث: موقعنا الجغرافي والاتصال -->
        <div class="glass-card p-5 rounded-4 mb-4 border border-success border-opacity-25" style="box-shadow: var(--shadow-md);">
            <div class="row g-4 align-items-center">
                <div class="col-md-7">
                    <h4 class="fw-bold text-success mb-3">📍 فرعنا الرئيسي في كربلاء</h4>
                    <p class="text-muted lh-lg">
                        يقع الهايبر ماركت في **محافظة كربلاء المقدسة، حي الحسين**، وهو مجهز بالكامل بأحدث وسائل العرض والتبريد لضمان سلامة السلع. نرحب بزيارتكم الكريمة يومياً من الساعة **8:00 صباحاً وحتى 12:00 بعد منتصف الليل**.
                    </p>
                    <div class="d-flex align-items-center gap-3 mt-4">
                        <span style="font-size: 1.5rem;">📞</span>
                        <div>
                            <span class="text-muted d-block small">رقم الهاتف للتواصل المباشر:</span>
                            <a href="tel:07801234567" class="fw-bold text-success text-decoration-none" style="font-size: 1.2rem;">0780 123 4567</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 text-center">
                    <div class="p-3 bg-light rounded-4 border border-2 border-dashed border-success border-opacity-50" style="background: rgba(16, 185, 129, 0.05) !important;">
                        <span style="font-size: 4rem;">🏫</span>
                        <h5 class="fw-bold mt-2 text-success">حي الحسين، كربلاء</h5>
                        <p class="text-muted small mb-0">بالقرب من مركز المدينة الرئيسي</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.glass-card:hover {
    transform: translateY(-5px);
}
</style>

<?php
include 'footer.php';
?>
