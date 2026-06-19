<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="هايبر ماركت رضا أبو لحمة - تسوق بسهولة وأمان">
    <title>هايبر ماركت رضا أبو لحمة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <!-- Leaflet.js Maps Library -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        :root {
            /* Brand Palette (Fresh Green & Emerald Accents) */
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #ecfdf5;
            --primary-border: rgba(16, 185, 129, 0.15);
            --accent: #f59e0b;
            --accent-dark: #d97706;

            /* Base Theme (Default Light Mode - Clean White & Vibrant Green) */
            --bg-body: linear-gradient(135deg, #f8fafc 0%, #f0fdf4 50%, #f8fafc 100%);
            --text-main: #0c111d;
            --text-muted: #2d3748;
            --navbar-bg: rgba(255, 255, 255, 0.92);
            --navbar-border: rgba(16, 185, 129, 0.15);
            --card-bg: rgba(255, 255, 255, 0.85);
            --card-bg-secondary: #f8fafc;
            --card-border: rgba(16, 185, 129, 0.12);
            --card-border-hover: rgba(16, 185, 129, 0.35);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 10px 30px rgba(16, 185, 129, 0.06);
            --shadow-lg: 0 20px 40px rgba(16, 185, 129, 0.1);
            --btn-details-bg: #ecfdf5;
            --btn-details-border: rgba(16, 185, 129, 0.25);
            --btn-details-color: #065f46;
            --btn-details-hover-bg: #d1fae5;
            --input-bg: #ffffff;
            --input-border: #cbd5e1;
            --table-header-bg: rgba(16, 185, 129, 0.08);
            --table-header-color: #047857;
            --table-row-hover: rgba(16, 185, 129, 0.03);
            --scrollbar-track: #f8fafc;
            --scrollbar-thumb: #10b981;
            --toast-bg: rgba(255, 255, 255, 0.95);
            --toast-text: #0f172a;
        }

        /* Dark Mode Overrides (Deep Slate Blue & Vibrant Mint) */
        body.dark-mode {
            --bg-body: linear-gradient(135deg, #090d16 0%, #0f172a 50%, #090d16 100%);
            --text-main: #ffffff;
            --text-muted: #cbd5e1;
            --navbar-bg: rgba(15, 23, 42, 0.95);
            --navbar-border: rgba(16, 185, 129, 0.3);
            --card-bg: rgba(30, 41, 59, 0.85);
            --card-bg-secondary: rgba(15, 23, 42, 0.6);
            --card-border: rgba(16, 185, 129, 0.2);
            --card-border-hover: rgba(16, 185, 129, 0.5);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 10px 30px rgba(0, 0, 0, 0.35);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.55);
            --btn-details-bg: rgba(255, 255, 255, 0.05);
            --btn-details-border: rgba(255, 255, 255, 0.15);
            --btn-details-color: #cbd5e1;
            --btn-details-hover-bg: rgba(255, 255, 255, 0.1);
            --input-bg: rgba(15, 23, 42, 0.8);
            --input-border: rgba(255, 255, 255, 0.1);
            --table-header-bg: rgba(16, 185, 129, 0.2);
            --table-header-color: #4ade80;
            --table-row-hover: rgba(16, 185, 129, 0.05);
            --scrollbar-track: #0f172a;
            --scrollbar-thumb: #10b981;
            --toast-bg: rgba(15, 23, 42, 0.95);
            --toast-text: #f8fafc;
            /* Bootstrap 5.3 overrides */
            --bs-secondary-color: #ffffff;
            --bs-body-color: #ffffff;
        }

        /* Dark mode: override Bootstrap text-muted to pure white */
        body.dark-mode .text-muted,
        body.dark-mode .text-muted *,
        body.dark-mode p.text-muted,
        body.dark-mode small.text-muted,
        body.dark-mode span.text-muted,
        body.dark-mode li.text-muted,
        body.dark-mode ul.text-muted,
        body.dark-mode [class*="text-muted"] {
            color: #ffffff !important;
            --bs-secondary-color: #ffffff !important;
            opacity: 1 !important;
        }

        * { font-family: 'Cairo', sans-serif; }

        body {
            background: var(--bg-body) !important;
            min-height: 100vh;
            color: var(--text-main) !important;
            font-weight: 500;
            line-height: 1.6;
            transition: background 0.3s, color 0.3s;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background: var(--navbar-bg) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--navbar-border) !important;
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
            transition: background 0.3s, border-bottom 0.3s;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--primary) !important;
            letter-spacing: -0.5px;
        }

        .navbar-brand span {
            color: var(--accent);
        }

        .nav-link {
            color: var(--text-muted) !important;
            font-weight: 600;
            font-size: 0.95rem;
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

        .nav-link:hover { color: var(--primary) !important; }
        .nav-link:hover::after { width: 100%; }

        .btn-cart {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white !important;
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-weight: 700;
            transition: all 0.3s;
            position: relative;
        }

        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            color: white !important;
        }

        .btn-admin {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: white !important;
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
            color: white !important;
        }

        .btn-logout {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white !important;
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.4);
            color: white !important;
        }

        .btn-login-outline {
            border: 2px solid var(--primary) !important;
            color: var(--primary) !important;
            border-radius: 10px;
            padding: 8px 18px;
            font-weight: 700;
            background: transparent !important;
            transition: all 0.3s;
        }

        .btn-login-outline:hover {
            background: var(--primary) !important;
            color: white !important;
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
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(245, 158, 11, 0.08)) !important;
            border: 1px solid var(--primary-border) !important;
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
            background: radial-gradient(circle, rgba(16, 185, 129, 0.04) 0%, transparent 60%);
            animation: pulse-bg 4s ease-in-out infinite;
        }

        @keyframes pulse-bg {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.7; }
        }

        /* ===== CARDS ===== */
        .glass-card {
            background: var(--card-bg) !important;
            border: 1px solid var(--card-border) !important;
            border-radius: 16px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            color: var(--text-main) !important;
        }

        .glass-card:hover {
            border-color: var(--card-border-hover) !important;
            transform: translateY(-4px);
            box-shadow: var(--shadow-md) !important;
        }

        /* ===== PRODUCT CARD ===== */
        .product-card {
            background: var(--card-bg) !important;
            border: 1px solid var(--card-border) !important;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
        }

        .product-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-lg) !important;
            border-color: var(--card-border-hover) !important;
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
            color: var(--text-main) !important;
            font-weight: 700;
            font-size: 1rem;
        }

        .product-card .card-text {
            color: var(--text-muted) !important;
            font-size: 0.85rem;
        }

        .price-badge {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            color: white !important;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .stock-badge {
            background: rgba(100, 116, 139, 0.15) !important;
            color: var(--text-muted) !important;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .btn-details {
            background: var(--btn-details-bg) !important;
            border: 1px solid var(--btn-details-border) !important;
            color: var(--btn-details-color) !important;
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
            background: var(--btn-details-hover-bg) !important;
            color: var(--btn-details-color) !important;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            border: none !important;
            color: white !important;
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
            background: linear-gradient(135deg, var(--primary-dark), #047857) !important;
            color: white !important;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35) !important;
            transform: translateY(-1px);
        }

        /* ===== SIDEBAR CATEGORIES ===== */
        .sidebar-card {
            background: var(--card-bg) !important;
            border: 1px solid var(--card-border) !important;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 100px;
            z-index: 10;
        }

        .sidebar-title {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            color: white !important;
            padding: 15px 20px;
            font-weight: 700;
            font-size: 1rem;
            margin: 0;
        }

        .sidebar-link {
            display: block;
            padding: 12px 20px;
            color: var(--text-muted) !important;
            text-decoration: none;
            border-bottom: 1px solid var(--card-border) !important;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background: rgba(16, 185, 129, 0.1) !important;
            color: var(--primary) !important;
            padding-right: 28px;
            border-right: 3px solid var(--primary) !important;
        }

        /* ===== TABLES ===== */
        .modern-table {
            background: transparent !important;
            color: var(--text-main) !important;
        }

        .modern-table thead th {
            background: var(--table-header-bg) !important;
            color: var(--table-header-color) !important;
            border: none;
            font-weight: 700;
            padding: 14px 16px;
        }

        .modern-table tbody tr {
            border-bottom: 1px solid var(--card-border) !important;
            transition: background 0.2s;
        }

        .modern-table tbody tr:hover {
            background: var(--table-row-hover) !important;
        }

        .modern-table td {
            background: transparent !important;
            border: none;
            padding: 14px 16px;
            vertical-align: middle;
            color: var(--text-main) !important;
        }

        /* ===== FORMS ===== */
        .form-control, .form-select {
            background: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            color: var(--text-main) !important;
            border-radius: 10px !important;
            padding: 10px 14px !important;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
            outline: none !important;
        }

        .form-control::placeholder { color: var(--text-muted) !important; opacity: 0.6; }

        .form-label {
            color: var(--text-muted) !important;
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
            background: rgba(16, 185, 129, 0.15) !important;
            border: 1px solid rgba(16, 185, 129, 0.3) !important;
            color: var(--primary-dark) !important;
        }

        .alert-warning-modern {
            background: rgba(245, 158, 11, 0.15) !important;
            border: 1px solid rgba(245, 158, 11, 0.3) !important;
            color: var(--accent-dark) !important;
        }

        .alert-danger-modern {
            background: rgba(220, 38, 38, 0.15) !important;
            border: 1px solid rgba(220, 38, 38, 0.3) !important;
            color: #dc2626 !important;
        }

        .alert-info-modern {
            background: rgba(59, 130, 246, 0.15) !important;
            border: 1px solid rgba(59, 130, 246, 0.3) !important;
            color: #2563eb !important;
        }

        /* ===== STATS CARDS ===== */
        .stat-card {
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
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
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.05)) !important;
            border: 1px solid var(--primary-border) !important;
            color: var(--text-main) !important;
        }

        .stat-card-green::before { background: var(--primary); }

        .stat-card-dark {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(217, 119, 6, 0.05)) !important;
            border: 1px solid rgba(245, 158, 11, 0.2) !important;
            color: var(--text-main) !important;
        }

        .stat-card-dark::before { background: var(--accent); }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1;
            color: var(--primary) !important;
        }

        /* ===== FOOTER ===== */
        .main-footer {
            background: var(--navbar-bg) !important;
            border-top: 1px solid var(--navbar-border) !important;
            padding: 20px 0;
            margin-top: 50px;
            text-align: center;
            color: var(--text-muted) !important;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--scrollbar-track) !important; }
        ::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb) !important; border-radius: 3px; }

        /* ===== PAGE TITLE ===== */
        .page-title {
            color: var(--text-main) !important;
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
            background: linear-gradient(90deg, var(--primary), var(--primary-light)) !important;
            border-radius: 2px;
        }

        /* ===== CHECKOUT ===== */
        .checkout-card {
            background: var(--card-bg) !important;
            border: 1px solid var(--card-border) !important;
            border-radius: 20px;
            padding: 35px;
            box-shadow: var(--shadow-sm);
        }

        /* ===== BADGE ===== */
        .badge-stock-low {
            background: rgba(220, 38, 38, 0.15) !important;
            color: #dc2626 !important;
            border: 1px solid rgba(220, 38, 38, 0.25) !important;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .badge-stock-ok {
            background: rgba(16, 185, 129, 0.15) !important;
            color: var(--primary-dark) !important;
            border: 1px solid rgba(16, 185, 129, 0.25) !important;
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
            background: var(--toast-bg) !important;
            border: 1px solid var(--primary-border) !important;
            color: var(--toast-text) !important;
            border-radius: 12px;
            padding: 14px 22px;
            margin-bottom: 12px;
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-md);
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
            background: rgba(16, 185, 129, 0.1) !important;
            border: 1px solid var(--primary-border) !important;
            color: var(--primary) !important;
            border-radius: 10px;
            padding: 8px 14px;
            font-weight: 700;
            transition: all 0.3s;
            cursor: pointer;
            font-size: 1rem;
        }
        .dark-mode-btn:hover {
            background: rgba(16, 185, 129, 0.2) !important;
            transform: translateY(-1px);
        }

        /* ===== NOTIFICATION BELL ===== */
        .notif-btn {
            background: rgba(16, 185, 129, 0.1) !important;
            border: 1px solid var(--primary-border) !important;
            color: var(--primary) !important;
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
            background: rgba(16, 185, 129, 0.2) !important;
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

        /* Global element level styling to override inline styles */
        h1, h2, h3, h4, h5, h6 {
            color: var(--text-main);
        }
        .text-white {
            color: var(--text-main) !important;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      رضا<span> أبو لحمة</span>
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
        <li class="nav-item">
            <a class="nav-link" href="about.php">ℹ️ من نحن</a>
        </li>
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
                    <li><a class="dropdown-item fw-bold" href="support.php">💬 الدعم والمراسلة</a></li>
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
    body.classList.toggle('dark-mode');
    const isDark = body.classList.contains('dark-mode');
    icon.textContent = isDark ? '🌙' : '☀️';
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

// تطبيق الثيم المحفوظ عند التحميل
(function() {
    const saved = localStorage.getItem('theme');
    if (saved === 'dark') {
        document.body.classList.add('dark-mode');
        const icon = document.getElementById('darkModeIcon');
        if (icon) icon.textContent = '🌙';
    } else {
        const icon = document.getElementById('darkModeIcon');
        if (icon) icon.textContent = '☀️';
    }
})();

// ===== جافاسكريبت مستقل (Vanilla JS Fallback) للقوائم المنسدلة والنوافذ المنبثقة =====
document.addEventListener("DOMContentLoaded", function() {
    // التحقق مما إذا كانت مكتبة Bootstrap JS محملة لتفادي التداخل البرمجي
    if (typeof bootstrap !== 'undefined') {
        return;
    }

    // 1. فتح القوائم المنسدلة (Dropdowns) عند النقر يدوياً
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const parent = this.parentElement;
            const menu = parent.querySelector('.dropdown-menu');
            if (menu) {
                const isOpen = menu.classList.contains('show');
                // إغلاق أي قائمة مفتوحة أخرى
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
                document.querySelectorAll('.dropdown-toggle').forEach(t => t.setAttribute('aria-expanded', 'false'));
                
                if (!isOpen) {
                    menu.classList.add('show');
                    this.setAttribute('aria-expanded', 'true');
                }
            }
        });
    });
    
    // إغلاق القوائم المنسدلة عند النقر خارجها
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
        document.querySelectorAll('.dropdown-toggle').forEach(t => t.setAttribute('aria-expanded', 'false'));
    });

    // 2. تفعيل فتح النوافذ المنبثقة (Modals) يدوياً عند تعذر تحميل Bootstrap JS
    const modalTriggers = document.querySelectorAll('[data-bs-toggle="modal"]');
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-bs-target');
            const modal = document.querySelector(targetId);
            if (modal) {
                modal.classList.add('show');
                modal.style.display = 'block';
                modal.style.background = 'rgba(0,0,0,0.5)';
                
                // تمرير رقم الطلب للمودال إن وجد
                const orderId = this.getAttribute('data-order-id');
                if (orderId) {
                    const orderInput = modal.querySelector('#modal_order_id');
                    if (orderInput) orderInput.value = orderId;
                }
            }
        });
    });

    // إغلاق النوافذ المنبثقة عند النقر على أزرار الإغلاق
    const modalCloseBtns = document.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
    modalCloseBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
            }
        });
    });
});
</script>
