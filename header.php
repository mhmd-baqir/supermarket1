<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="هايبر ماركت المتكامل - تسوق بسهولة وأمان">
    <title>هايبر ماركت المتكامل</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <!-- Leaflet.js Maps Library -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        :root {
            --primary: #16a34a;
            --primary-dark: #15803d;
            --primary-light: #bbf7d0;
            --accent: #f59e0b;
            --dark: #0f172a;
            --dark-card: #1e293b;
            --text-muted: #94a3b8;
        }

        * { font-family: 'Cairo', sans-serif; }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            color: #e2e8f0;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background: rgba(15, 23, 42, 0.95) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(22, 163, 74, 0.3);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0,0,0,0.5);
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 900;
            color: #16a34a !important;
            letter-spacing: -0.5px;
        }

        .navbar-brand span {
            color: #f59e0b;
        }

        .nav-link {
            color: #cbd5e1 !important;
            font-weight: 600;
            transition: color 0.3s;
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            right: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s;
        }

        .nav-link:hover { color: #16a34a !important; }
        .nav-link:hover::after { width: 100%; }

        .btn-cart {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-weight: 700;
            transition: all 0.3s;
            position: relative;
        }

        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(22, 163, 74, 0.5);
            color: white;
        }

        .btn-admin {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.5);
            color: white;
        }

        .btn-logout {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.5);
            color: white;
        }

        .btn-login-outline {
            border: 2px solid #16a34a;
            color: #16a34a;
            border-radius: 10px;
            padding: 8px 18px;
            font-weight: 700;
            background: transparent;
            transition: all 0.3s;
        }

        .btn-login-outline:hover {
            background: #16a34a;
            color: white;
            transform: translateY(-2px);
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            left: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* ===== MAIN CONTAINER ===== */
        .main-container {
            padding: 30px 0;
        }

        /* ===== HERO BANNER ===== */
        .hero-banner {
            background: linear-gradient(135deg, rgba(22,163,74,0.15), rgba(245,158,11,0.1));
            border: 1px solid rgba(22,163,74,0.2);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(22,163,74,0.05) 0%, transparent 60%);
            animation: pulse-bg 4s ease-in-out infinite;
        }

        @keyframes pulse-bg {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.7; }
        }

        /* ===== CARDS ===== */
        .glass-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(22, 163, 74, 0.2);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            border-color: rgba(22, 163, 74, 0.5);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        /* ===== PRODUCT CARD ===== */
        .product-card {
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .product-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            border-color: rgba(22, 163, 74, 0.4);
        }

        .product-card .card-img-top {
            height: 200px;
            object-fit: cover;
            transition: transform 0.4s;
        }

        .product-card:hover .card-img-top {
            transform: scale(1.08);
        }

        .product-card .card-body {
            padding: 18px;
        }

        .product-card .card-title {
            color: #f1f5f9;
            font-weight: 700;
            font-size: 1rem;
        }

        .product-card .card-text {
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .price-badge {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .stock-badge {
            background: rgba(100, 116, 139, 0.3);
            color: #94a3b8;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .btn-details {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.15);
            color: #cbd5e1;
            border-radius: 10px;
            padding: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            text-align: center;
            text-decoration: none;
            display: block;
        }

        .btn-details:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, #16a34a, #15803d);
            border: none;
            color: white;
            border-radius: 10px;
            padding: 9px;
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.3s;
            width: 100%;
            text-align: center;
            text-decoration: none;
            display: block;
        }

        .btn-add-cart:hover {
            background: linear-gradient(135deg, #15803d, #166534);
            color: white;
            box-shadow: 0 8px 20px rgba(22, 163, 74, 0.5);
            transform: translateY(-1px);
        }

        /* ===== SIDEBAR CATEGORIES ===== */
        .sidebar-card {
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            overflow: hidden;
        }

        .sidebar-title {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: white;
            padding: 15px 20px;
            font-weight: 700;
            font-size: 1rem;
            margin: 0;
        }

        .sidebar-link {
            display: block;
            padding: 12px 20px;
            color: #94a3b8;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background: rgba(22, 163, 74, 0.15);
            color: #4ade80;
            padding-right: 28px;
            border-right: 3px solid #16a34a;
        }

        /* ===== TABLES ===== */
        .modern-table {
            background: transparent;
            color: #e2e8f0;
        }

        .modern-table thead th {
            background: rgba(22, 163, 74, 0.2);
            color: #4ade80;
            border: none;
            font-weight: 700;
            padding: 14px 16px;
        }

        .modern-table tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: background 0.2s;
        }

        .modern-table tbody tr:hover {
            background: rgba(22, 163, 74, 0.05);
        }

        .modern-table td {
            border: none;
            padding: 14px 16px;
            vertical-align: middle;
            color: #cbd5e1;
        }

        /* ===== FORMS ===== */
        .form-control, .form-select {
            background: rgba(15, 23, 42, 0.8) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: #e2e8f0 !important;
            border-radius: 10px !important;
            padding: 10px 14px !important;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: rgba(22, 163, 74, 0.5) !important;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15) !important;
            outline: none !important;
        }

        .form-control::placeholder { color: #475569 !important; }

        .form-label {
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.88rem;
            margin-bottom: 6px;
        }

        /* ===== ALERTS ===== */
        .alert-modern {
            border-radius: 12px;
            border: none;
            padding: 16px 20px;
            font-weight: 600;
        }

        .alert-success-modern {
            background: rgba(22, 163, 74, 0.15);
            border: 1px solid rgba(22, 163, 74, 0.3);
            color: #4ade80;
        }

        .alert-warning-modern {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fbbf24;
        }

        .alert-danger-modern {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #f87171;
        }

        .alert-info-modern {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
        }

        /* ===== STATS CARDS ===== */
        .stat-card {
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            opacity: 0.1;
        }

        .stat-card-green {
            background: linear-gradient(135deg, rgba(22,163,74,0.2), rgba(21,128,61,0.1));
            border: 1px solid rgba(22,163,74,0.3);
        }

        .stat-card-green::before { background: #16a34a; }

        .stat-card-dark {
            background: linear-gradient(135deg, rgba(245,158,11,0.2), rgba(217,119,6,0.1));
            border: 1px solid rgba(245,158,11,0.3);
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1;
        }

        /* ===== FOOTER ===== */
        .main-footer {
            background: rgba(15, 23, 42, 0.8);
            border-top: 1px solid rgba(22, 163, 74, 0.2);
            padding: 20px 0;
            margin-top: 50px;
            text-align: center;
            color: #475569;
            font-size: 0.9rem;
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #16a34a; border-radius: 3px; }

        /* ===== PAGE TITLE ===== */
        .page-title {
            color: #f1f5f9;
            font-weight: 900;
            font-size: 1.8rem;
            position: relative;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #16a34a, #4ade80);
            border-radius: 2px;
        }

        /* ===== CHECKOUT ===== */
        .checkout-card {
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 35px;
        }

        /* ===== BADGE ===== */
        .badge-stock-low {
            background: rgba(220, 38, 38, 0.2);
            color: #f87171;
            border: 1px solid rgba(220, 38, 38, 0.3);
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .badge-stock-ok {
            background: rgba(22, 163, 74, 0.2);
            color: #4ade80;
            border: 1px solid rgba(22, 163, 74, 0.3);
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in-up {
            animation: fadeInUp 0.5s ease forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }

        /* ===== CUSTOM TOAST ===== */
        .custom-toast {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(22, 163, 74, 0.5);
            color: #f1f5f9;
            border-radius: 12px;
            padding: 14px 22px;
            margin-bottom: 12px;
            backdrop-filter: blur(20px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: toastIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards, toastOut 0.35s ease 2.65s forwards;
            pointer-events: auto;
            direction: rtl;
            font-weight: 700;
        }
        @keyframes toastIn {
            from { transform: translateX(-120%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes toastOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(-120%); opacity: 0; }
        }
        /* ===== DARK MODE TOGGLE ===== */
        .dark-mode-btn {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            color: #cbd5e1;
            border-radius: 10px;
            padding: 8px 14px;
            font-weight: 700;
            transition: all 0.3s;
            cursor: pointer;
            font-size: 1rem;
        }
        .dark-mode-btn:hover {
            background: rgba(255,255,255,0.12);
            color: white;
            transform: translateY(-1px);
        }

        /* ===== LIGHT MODE ===== */
        body.light-mode {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #f0fdf4 100%) !important;
            color: #0f172a !important;
        }
        body.light-mode .navbar {
            background: rgba(255,255,255,0.92) !important;
            border-bottom: 1px solid rgba(22,163,74,0.3) !important;
        }
        body.light-mode .glass-card,
        body.light-mode .product-card,
        body.light-mode .sidebar-card,
        body.light-mode .checkout-card {
            background: rgba(255,255,255,0.9) !important;
            border-color: rgba(22,163,74,0.2) !important;
            color: #0f172a !important;
        }
        body.light-mode .card-title,
        body.light-mode .product-card h5,
        body.light-mode h1, body.light-mode h2,
        body.light-mode h3, body.light-mode h4,
        body.light-mode h5, body.light-mode h6 { color: #0f172a !important; }
        body.light-mode .card-text, body.light-mode p,
        body.light-mode .text-muted { color: #475569 !important; }
        body.light-mode .nav-link { color: #334155 !important; }
        body.light-mode .modern-table td { color: #334155 !important; }
        body.light-mode .modern-table thead th { background: rgba(22,163,74,0.15) !important; }
        body.light-mode .sidebar-link { color: #334155 !important; }
        body.light-mode .form-control, body.light-mode .form-select {
            background: rgba(255,255,255,0.95) !important;
            color: #0f172a !important;
            border-color: rgba(22,163,74,0.3) !important;
        }
        body.light-mode .main-footer {
            background: rgba(255,255,255,0.7) !important;
            color: #475569 !important;
        }
        body.light-mode ::-webkit-scrollbar-track { background: #f0fdf4; }

        /* ===== NOTIFICATION BELL ===== */
        .notif-btn {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            color: #cbd5e1;
            border-radius: 10px;
            padding: 8px 14px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s;
            position: relative;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .notif-btn:hover {
            background: rgba(255,255,255,0.12);
            color: white;
            transform: translateY(-1px);
        }
        .notif-badge {
            position: absolute;
            top: -6px;
            left: -6px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            animation: pulse-bell 1.5s ease-in-out infinite;
        }
        @keyframes pulse-bell {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      🛒 الهايبر<span>ماركت</span>
    </a>
    <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
            <a class="nav-link" href="index.php">🏠 الرئيسية</a>
        </li>
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'customer'): ?>
            <li class="nav-item">
                <a class="nav-link" href="wishlist.php">❤️ المفضلة</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="my_orders.php">📦 طلباتي</a>
            </li>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <?php
        // حساب عدد الإشعارات غير المقروءة
        $notif_count = 0;
        if (isset($_SESSION['user_id'])) {
            $nc_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id=? OR user_id IS NULL) AND is_read=0");
            $nc_stmt->execute([$_SESSION['user_id']]);
            $notif_count = $nc_stmt->fetchColumn();
        }
        ?>

        <!-- زر الوضع الليلي/النهاري -->
        <button class="dark-mode-btn" id="darkModeToggle" onclick="toggleDarkMode()" title="تبديل الوضع">
            <span id="darkModeIcon">🌙</span>
        </button>

        <!-- زر الإشعارات -->
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="notifications.php" class="notif-btn" title="الإشعارات">
                🔔
                <?php if($notif_count > 0): ?>
                    <span class="notif-badge"><?= $notif_count ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <a href="cart.php" class="btn-cart btn position-relative me-2">
          🛒 السلة
          <span class="cart-badge">
            <?php echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?>
          </span>
        </a>
        <?php if(isset($_SESSION['admin_logged_in'])): ?>
            <a href="admin_dashboard.php" class="btn-admin btn">⚙️ لوحة التحكم</a>
            <a href="logout.php" class="btn-logout btn">خروج</a>
        <?php elseif(isset($_SESSION['user_id'])): ?>
            <div class="dropdown">
                <button class="btn btn-outline-success dropdown-toggle fw-bold" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 10px; border-color: #16a34a; color: #4ade80;">
                    👤 أهلاً، <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="userMenu" style="background: rgba(15,23,42,0.95); backdrop-filter: blur(20px); border: 1px solid rgba(22, 163, 74, 0.3); border-radius: 10px;">
                    <li><a class="dropdown-item fw-bold" href="my_account.php">⚙️ ملفي الشخصي</a></li>
                    <li><a class="dropdown-item fw-bold" href="my_orders.php">📦 تتبع طلباتي</a></li>
                    <li><a class="dropdown-item fw-bold" href="wishlist.php">❤️ المفضلة</a></li>
                    <li><hr class="dropdown-divider" style="border-color: rgba(22, 163, 74, 0.2);"></li>
                    <li><a class="dropdown-item fw-bold text-danger" href="logout.php">🚪 تسجيل الخروج</a></li>
                </ul>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-login-outline btn">🔐 دخول</a>
            <a href="register.php" class="btn btn-success fw-bold" style="background: linear-gradient(135deg, #16a34a, #15803d); border: none; border-radius:10px; padding: 8px 18px;">📝 تسجيل حساب</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div class="container main-container">

<!-- Toast Notification Container -->
<div id="toast-container" class="position-fixed bottom-0 start-0 m-4" style="z-index: 9999; pointer-events: none;"></div>

<script>
function addToCartAJAX(event, productId) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    fetch('cart_ajax.php?id=' + productId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badges = document.querySelectorAll('.cart-badge');
                badges.forEach(b => {
                    b.textContent = data.cart_count;
                    b.style.transform = 'scale(1.4)';
                    setTimeout(() => { b.style.transform = 'scale(1)'; }, 250);
                });
                showToast('🛒 ' + data.message, 'success');
            } else {
                showToast('⚠️ ' + data.message, 'warning');
            }
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            showToast('⚠️ حدث خطأ أثناء إضافة المنتج للسلة.', 'error');
        });
}

function showToast(message, type) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const colors = {
        success: { bg: 'rgba(22,163,74,0.95)', border: 'rgba(22,163,74,0.6)' },
        warning: { bg: 'rgba(245,158,11,0.95)', border: 'rgba(245,158,11,0.6)' },
        error:   { bg: 'rgba(220,38,38,0.95)',  border: 'rgba(220,38,38,0.6)' }
    };
    const c = colors[type] || colors.success;
    const toast = document.createElement('div');
    toast.className = 'custom-toast';
    toast.style.background = c.bg;
    toast.style.borderColor = c.border;
    toast.innerHTML = '<span>' + message + '</span>';
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => { toast.remove(); }, 400);
    }, 3000);
}

// ===== الوضع الليلي / النهاري =====
function toggleDarkMode() {
    const body = document.body;
    const icon = document.getElementById('darkModeIcon');
    body.classList.toggle('light-mode');
    const isLight = body.classList.contains('light-mode');
    icon.textContent = isLight ? '☀️' : '🌙';
    localStorage.setItem('theme', isLight ? 'light' : 'dark');
}

// تطبيق الثيم المحفوظ عند التحميل
(function() {
    const saved = localStorage.getItem('theme');
    if (saved === 'light') {
        document.body.classList.add('light-mode');
        const icon = document.getElementById('darkModeIcon');
        if (icon) icon.textContent = '☀️';
    }
})();
</script>
