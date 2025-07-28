<?php
session_start();

// Authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Database connection
try {
    $conn = new PDO("mysql:host=sql209.infinityfree.com;dbname=if0_39222248_sportbootspro;charset=utf8mb4", "if0_39222248", "76536462Ah");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get dashboard statistics
$stats = [];
$stmt = $conn->query("SELECT COUNT(*) as total, SUM(total_amount) as revenue FROM orders");
$orderStats = $stmt->fetch();
$stats['total_orders'] = $orderStats['total'];
$stats['total_revenue'] = $orderStats['revenue'] ?? 0;

$stmt = $conn->query("SELECT COUNT(*) as total, SUM(total_amount) as revenue FROM orders WHERE DATE(created_at) = CURDATE()");
$todayStats = $stmt->fetch();
$stats['today_orders'] = $todayStats['total'];
$stats['today_revenue'] = $todayStats['revenue'] ?? 0;

$stmt = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total_customers'] = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) as total FROM products");
$stats['total_products'] = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity < 10");
$stats['low_stock_products'] = $stmt->fetchColumn();

// Recent orders
$recentOrders = $conn->query("SELECT o.id, o.created_at, o.total_amount, o.status, u.username 
                             FROM orders o JOIN users u ON o.user_id = u.id 
                             ORDER BY o.created_at DESC LIMIT 5")->fetchAll();

// Low stock products
$lowStockProducts = $conn->query("SELECT id, name, stock_quantity FROM products 
                                 WHERE stock_quantity < 10 ORDER BY stock_quantity ASC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>SportBoots Pro - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #000000;
            --primary-dark: #0a0a0a;
            --secondary: #ffffff;
            --accent: #FF385C;
            --accent-light: #FF5A5F;
            --accent-dark: #E31C5F;
            --success: #00A699;
            --warning: #FFB400;
            --danger: #E74C3C;
            --info: #3498db;
            
            --text-primary: #222222;
            --text-secondary: #717171;
            --text-light: #B0B0B0;
            
            --bg-primary: #FFFFFF;
            --bg-secondary: #F7F7F7;
            --bg-tertiary: #EBEBEB;
            
            --border-light: #DDDDDD;
            --border-medium: #C4C4C4;
            
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.08);
            --shadow-md: 0 3px 10px rgba(0,0,0,0.12);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.16);
            --shadow-xl: 0 20px 40px rgba(0,0,0,0.2);
            
            --radius-xs: 8px;
            --radius-sm: 12px;
            --radius-md: 16px;
            --radius-lg: 24px;
            --radius-full: 9999px;
            
            --header-height: 56px;
            --bottom-nav-height: 65px;
            --safe-area-inset-top: env(safe-area-inset-top);
            --safe-area-inset-bottom: env(safe-area-inset-bottom);
            
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 250ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 350ms cubic-bezier(0.4, 0, 0.2, 1);
            
            --spring-bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
            --spring-smooth: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-primary: #1a1a1a;
                --bg-secondary: #2a2a2a;
                --bg-tertiary: #3a3a3a;
                --text-primary: #ffffff;
                --text-secondary: #b0b0b0;
                --border-light: #3a3a3a;
                --border-medium: #4a4a4a;
            }
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
        }

        html {
            height: 100%;
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.5;
            height: 100%;
            overflow: hidden;
            position: fixed;
            width: 100%;
            overscroll-behavior: none;
        }

        /* App Container */
        .app {
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: calc(var(--vh, 1vh) * 100);
            position: relative;
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: calc(var(--header-height) + var(--safe-area-inset-top));
            padding-top: var(--safe-area-inset-top);
            background: var(--bg-primary);
            z-index: 100;
            border-bottom: 1px solid var(--border-light);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .header-content {
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
        }

        .header-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-actions {
            display: flex;
            gap: 8px;
        }

        .header-btn {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-full);
            border: none;
            background: transparent;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-fast);
            position: relative;
        }

        .header-btn:active {
            transform: scale(0.92);
            background: var(--bg-tertiary);
        }

        .notification-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            border: 2px solid var(--bg-primary);
        }

        /* Main Content */
        .main {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            padding-top: calc(var(--header-height) + var(--safe-area-inset-top));
            padding-bottom: calc(var(--bottom-nav-height) + var(--safe-area-inset-bottom) + 20px);
            scroll-behavior: smooth;
        }

        .main-content {
            padding: 20px 16px;
        }

        /* Pull to Refresh */
        .pull-to-refresh {
            position: absolute;
            top: calc(var(--header-height) + var(--safe-area-inset-top));
            left: 50%;
            transform: translateX(-50%) translateY(-60px);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform var(--transition-base);
        }

        .pull-to-refresh.active {
            transform: translateX(-50%) translateY(10px);
        }

        .pull-to-refresh-spinner {
            width: 24px;
            height: 24px;
            border: 3px solid var(--bg-tertiary);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        /* Stats Cards */
        .stats-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -16px;
            padding: 0 16px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .stats-scroll::-webkit-scrollbar {
            display: none;
        }

        .stats-container {
            display: flex;
            gap: 12px;
            padding-bottom: 4px;
            min-width: min-content;
        }

        .stat-card {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: 16px;
            min-width: 140px;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-base);
            border: 1px solid var(--border-light);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .stat-card:active {
            transform: scale(0.98);
            box-shadow: var(--shadow-md);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent);
            transform: scaleX(0);
            transition: transform var(--transition-base);
        }

        .stat-card.primary::before {
            transform: scaleX(1);
        }

        .stat-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background: rgba(255, 56, 92, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            margin-bottom: 12px;
            font-size: 16px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 4px;
            color: var(--text-primary);
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .stat-trend {
            position: absolute;
            top: 16px;
            right: 16px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .stat-trend.up {
            color: var(--success);
        }

        .stat-trend.down {
            color: var(--danger);
        }

        /* Alert Banner */
        .alert-banner {
            background: var(--bg-primary);
            margin: 16px 0;
            padding: 16px;
            border-radius: var(--radius-md);
            border-left: 4px solid var(--accent);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all var(--transition-base);
        }

        .alert-banner:active {
            transform: scale(0.98);
        }

        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            background: rgba(255, 56, 92, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .alert-text {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .alert-arrow {
            color: var(--text-light);
            font-size: 14px;
        }

        /* Quick Actions Grid */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 20px 0;
        }

        .action-card {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            cursor: pointer;
            transition: all var(--transition-base);
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
        }

        .action-card:active {
            transform: scale(0.96);
            box-shadow: var(--shadow-md);
        }

        .action-card::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 56, 92, 0.1);
            transform: translate(-50%, -50%);
            transition: width var(--transition-slow), height var(--transition-slow);
        }

        .action-card:active::after {
            width: 200px;
            height: 200px;
        }

        .action-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            border-radius: var(--radius-sm);
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            position: relative;
            z-index: 1;
        }

        .action-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
            position: relative;
            z-index: 1;
        }

        .action-desc {
            font-size: 11px;
            color: var(--text-secondary);
            position: relative;
            z-index: 1;
        }

        /* Section Headers */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 24px 0 16px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
        }

        .section-link {
            font-size: 14px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Order Cards */
        .order-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .order-card {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            cursor: pointer;
            transition: all var(--transition-base);
            text-decoration: none;
            color: inherit;
        }

        .order-card:active {
            transform: scale(0.98);
            box-shadow: var(--shadow-md);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .order-id {
            font-size: 16px;
            font-weight: 600;
            color: var(--accent);
        }

        .order-status {
            padding: 4px 12px;
            border-radius: var(--radius-full);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: rgba(255, 180, 0, 0.1);
            color: var(--warning);
        }

        .status-processing {
            background: rgba(52, 152, 219, 0.1);
            color: var(--info);
        }

        .status-delivered {
            background: rgba(0, 166, 153, 0.1);
            color: var(--success);
        }

        .status-cancelled {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .order-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-customer {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .order-amount {
            font-size: 18px;
            font-weight: 600;
        }

        .order-time {
            font-size: 11px;
            color: var(--text-light);
            margin-top: 8px;
        }

        /* Product List */
        .product-list {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            overflow: hidden;
        }

        .product-item {
            padding: 16px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: background var(--transition-fast);
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-item:active {
            background: var(--bg-secondary);
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 2px;
        }

        .product-stock {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .product-stock.low {
            color: var(--danger);
            font-weight: 600;
        }

        .product-action {
            padding: 8px 16px;
            background: var(--accent);
            color: white;
            border-radius: var(--radius-full);
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .product-action:active {
            transform: scale(0.95);
            background: var(--accent-dark);
        }

        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: calc(var(--bottom-nav-height) + var(--safe-area-inset-bottom));
            padding-bottom: var(--safe-area-inset-bottom);
            background: var(--bg-primary);
            border-top: 1px solid var(--border-light);
            z-index: 100;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .bottom-nav-content {
            height: var(--bottom-nav-height);
            display: flex;
            align-items: center;
            justify-content: space-around;
        }

        .nav-item {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            text-decoration: none;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-fast);
            position: relative;
        }

        .nav-item.active {
            color: var(--accent);
        }

        .nav-item:active {
            transform: scale(0.95);
        }

        .nav-icon {
            font-size: 22px;
            position: relative;
        }

        .nav-label {
            font-size: 10px;
            font-weight: 600;
        }

        .nav-indicator {
            position: absolute;
            top: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            background: var(--accent);
            border-radius: 50%;
            opacity: 0;
            transition: opacity var(--transition-fast);
        }

        .nav-item.active .nav-indicator {
            opacity: 1;
        }

        /* Side Sheet */
        .side-sheet {
            position: fixed;
            top: 0;
            right: -100%;
            width: 85%;
            max-width: 320px;
            height: 100%;
            background: var(--bg-primary);
            z-index: 200;
            transition: right var(--transition-slow) var(--spring-smooth);
            box-shadow: -10px 0 30px rgba(0,0,0,0.1);
        }

        .side-sheet.active {
            right: 0;
        }

        .side-sheet-header {
            padding: var(--safe-area-inset-top) 20px 20px;
            padding-top: calc(var(--safe-area-inset-top) + 20px);
            border-bottom: 1px solid var(--border-light);
        }

        .side-sheet-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .side-sheet-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .side-sheet-content {
            padding: 20px;
            overflow-y: auto;
            height: calc(100% - 100px);
            -webkit-overflow-scrolling: touch;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 0;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            border-bottom: 1px solid var(--border-light);
            transition: all var(--transition-fast);
        }

        .menu-item:active {
            opacity: 0.7;
        }

        .menu-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            background: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
        }

        .menu-item.danger {
            color: var(--danger);
        }

        .menu-item.danger .menu-icon {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        /* Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            opacity: 0;
            visibility: hidden;
            z-index: 150;
            transition: all var(--transition-base);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Loading States */
        .skeleton {
            background: linear-gradient(90deg, var(--bg-tertiary) 25%, var(--bg-secondary) 50%, var(--bg-tertiary) 75%);
            background-size: 200% 100%;
            animation: skeleton 1.5s ease-in-out infinite;
            border-radius: var(--radius-xs);
        }

        @keyframes skeleton {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Animations */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Safe Area Adjustments */
        @supports (padding: max(0px)) {
            .header {
                padding-top: max(var(--safe-area-inset-top), 0px);
            }
            
            .bottom-nav {
                padding-bottom: max(var(--safe-area-inset-bottom), 0px);
            }
        }

        /* Haptic Feedback Support */
        @supports (animation: vibrate 0s) {
            .haptic {
                animation: vibrate 0.1s linear;
            }
        }

        @keyframes vibrate {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-1px); }
            75% { transform: translateX(1px); }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 48px;
            color: var(--text-light);
            margin-bottom: 16px;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .empty-text {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Responsive Adjustments */
        @media (max-width: 320px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                gap: 8px;
            }
            
            .stat-card {
                min-width: 120px;
                padding: 12px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1 class="header-title">
                    <i class="fas fa-shoe-prints"></i>
                    SportBoots Pro
                </h1>
                <div class="header-actions">
                    <button class="header-btn" id="notificationBtn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge"></span>
                    </button>
                    <button class="header-btn" id="menuBtn">
                        <i class="fas fa-user-circle"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Pull to Refresh -->
        <div class="pull-to-refresh" id="pullToRefresh">
            <div class="pull-to-refresh-spinner"></div>
        </div>

        <!-- Main Content -->
        <main class="main" id="mainContent">
            <div class="main-content">
                <!-- Stats Scroll -->
                <div class="stats-scroll">
                    <div class="stats-container">
                        <div class="stat-card primary">
                            <div class="stat-trend up">
                                <i class="fas fa-arrow-up"></i> 12%
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-value">$<?= number_format($stats['total_revenue'], 0) ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-trend up">
                                <i class="fas fa-arrow-up"></i> 8%
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value"><?= number_format($stats['total_customers']) ?></div>
                            <div class="stat-label">Customers</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stat-value"><?= number_format($stats['total_products']) ?></div>
                            <div class="stat-label">Products</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-trend down">
                                <i class="fas fa-arrow-down"></i> 5%
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-value">$<?= number_format($stats['today_revenue'], 0) ?></div>
                            <div class="stat-label">Today's Revenue</div>
                        </div>
                    </div>
                </div>

                <!-- Alert Banner -->
                <?php if ($stats['low_stock_products'] > 0): ?>
                <div class="alert-banner" onclick="window.location.href='inventory.php'">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-content">
                        <div class="alert-title">Low Stock Alert</div>
                        <div class="alert-text"><?= $stats['low_stock_products'] ?> products need restocking</div>
                    </div>
                    <div class="alert-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="products.php?action=add" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="action-title">Add Product</div>
                        <div class="action-desc">Create new listing</div>
                    </a>
                    
                    <a href="orders.php?status=pending" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="action-title">Process Orders</div>
                        <div class="action-desc">Manage pending</div>
                    </a>
                    
                    <a href="inventory.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="action-title">Inventory</div>
                        <div class="action-desc">Update stock</div>
                    </a>
                    
                    <a href="reports.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="action-title">Reports</div>
                        <div class="action-desc">View analytics</div>
                    </a>
                </div>

                <!-- Recent Orders -->
                <div class="section-header">
                    <h2 class="section-title">Recent Orders</h2>
                    <a href="orders.php" class="section-link">
                        View all <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                
                <div class="order-list">
                    <?php foreach ($recentOrders as $order): ?>
                    <a href="order-details.php?id=<?= $order['id'] ?>" class="order-card">
                        <div class="order-header">
                            <div class="order-id">#<?= $order['id'] ?></div>
                            <div class="order-status status-<?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </div>
                        </div>
                        <div class="order-details">
                            <div>
                                <div class="order-customer"><?= htmlspecialchars($order['username']) ?></div>
                                <div class="order-time"><?= date('M j, g:i a', strtotime($order['created_at'])) ?></div>
                            </div>
                            <div class="order-amount">$<?= number_format($order['total_amount'], 2) ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Low Stock Products -->
                <div class="section-header">
                    <h2 class="section-title">Low Stock Products</h2>
                    <a href="inventory.php" class="section-link">
                        View all <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                
                <?php if (count($lowStockProducts) > 0): ?>
                <div class="product-list">
                    <?php foreach ($lowStockProducts as $product): ?>
                    <div class="product-item" onclick="window.location.href='product.php?id=<?= $product['id'] ?>&action=edit'">
                        <div class="product-info">
                            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="product-stock low">Only <?= $product['stock_quantity'] ?> left</div>
                        </div>
                        <button class="product-action" onclick="event.stopPropagation(); window.location.href='product.php?id=<?= $product['id'] ?>&action=restock'">
                            Restock
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="empty-title">All Stocked Up!</div>
                    <div class="empty-text">All products have sufficient inventory</div>
                </div>
                <?php endif; ?>
            </div>
        </main>

          <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <div class="bottom-nav-content">
                <a href="dashboard.php" class="nav-item active">
                    <div class="nav-icon">
                        <i class="fas fa-home"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Home</span>
                </a>
                
                <a href="orders.php" class="nav-item">
                    <div class="nav-icon">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Orders</span>
                </a>
                
                <a href="products.php" class="nav-item">
                    <div class="nav-icon">
                        <i class="fas fa-shoe-prints"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Products</span>
                </a>
                
                <a href="inventory.php" class="nav-item ">
                    <div class="nav-icon">
                        <i class="fas fa-boxes"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Inventory</span>
                </a>
             <a href="#" class="nav-item" id="moreBtn">
                    <div class="nav-icon">
                        <i class="fas fa-ellipsis-h"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">More</span>
                </a>
            </div>
        </nav>

        <!-- Side Sheet -->
        <div class="side-sheet" id="sideSheet">
            <div class="side-sheet-header">
                <div class="side-sheet-title">Menu</div>
                <div class="side-sheet-subtitle">SportBoots Pro Admin</div>
            </div>
            <div class="side-sheet-content">
                <a href="customers.php" class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <span>Customers</span>
                </a>
                
                <a href="inventory.php" class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <span>Inventory</span>
                </a>
                
                <a href="settings.php" class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <span>Settings</span>
                </a>
                
                <a href="profile.php" class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <span>Profile</span>
                </a>
                
                <a href="help.php" class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <span>Help & Support</span>
                </a>
                
                <a href="logout.php" class="menu-item danger">
                    <div class="menu-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Overlay -->
        <div class="overlay" id="overlay"></div>
    </div> 

    <script>
        // Set viewport height for mobile browsers
        function setViewportHeight() {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);

        // DOM Elements
        const menuBtn = document.getElementById('menuBtn');
        const moreBtn = document.getElementById('moreBtn');
        const notificationBtn = document.getElementById('notificationBtn');
        const sideSheet = document.getElementById('sideSheet');
        const overlay = document.getElementById('overlay');
        const mainContent = document.getElementById('mainContent');
        const pullToRefresh = document.getElementById('pullToRefresh');

        // Touch handling
        let touchStartY = 0;
        let touchEndY = 0;
        let isPulling = false;

        // Side Sheet
        function openSideSheet() {
            sideSheet.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSideSheet() {
            sideSheet.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        menuBtn.addEventListener('click', openSideSheet);
        moreBtn.addEventListener('click', openSideSheet);
        overlay.addEventListener('click', closeSideSheet);

        // Pull to Refresh
        mainContent.addEventListener('touchstart', (e) => {
            touchStartY = e.touches[0].clientY;
        });

        mainContent.addEventListener('touchmove', (e) => {
            if (mainContent.scrollTop === 0) {
                touchEndY = e.touches[0].clientY;
                const pullDistance = touchEndY - touchStartY;
                
                if (pullDistance > 0 && pullDistance < 150) {
                    e.preventDefault();
                    isPulling = true;
                    pullToRefresh.style.transform = `translateX(-50%) translateY(${Math.min(pullDistance - 60, 10)}px)`;
                    
                    if (pullDistance > 80) {
                        pullToRefresh.classList.add('active');
                    }
                }
            }
        });

        mainContent.addEventListener('touchend', () => {
            if (isPulling) {
                const pullDistance = touchEndY - touchStartY;
                
                if (pullDistance > 80) {
                    // Trigger refresh
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    pullToRefresh.classList.remove('active');
                    pullToRefresh.style.transform = 'translateX(-50%) translateY(-60px)';
                }
                
                isPulling = false;
            }
        });

        // Notification button
        notificationBtn.addEventListener('click', () => {
            // Add haptic feedback if available
            if ('vibrate' in navigator) {
                navigator.vibrate(10);
            }
            alert('No new notifications');
        });

        // Add haptic feedback to buttons
        document.querySelectorAll('button, a').forEach(element => {
            element.addEventListener('click', () => {
                if ('vibrate' in navigator) {
                    navigator.vibrate(5);
                }
            });
        });

        // Smooth scrolling for stats
        const statsScroll = document.querySelector('.stats-scroll');
        let isScrolling = false;
        let startX;
        let scrollLeft;

        statsScroll.addEventListener('touchstart', (e) => {
            isScrolling = true;
            startX = e.touches[0].pageX - statsScroll.offsetLeft;
            scrollLeft = statsScroll.scrollLeft;
        });

        statsScroll.addEventListener('touchmove', (e) => {
            if (!isScrolling) return;
            e.preventDefault();
            const x = e.touches[0].pageX - statsScroll.offsetLeft;
            const walk = (x - startX) * 2;
            statsScroll.scrollLeft = scrollLeft - walk;
        });

        statsScroll.addEventListener('touchend', () => {
            isScrolling = false;
        });

        // Prevent overscroll
        document.body.addEventListener('touchmove', (e) => {
            if (e.target.closest('.main')) return;
            e.preventDefault();
        }, { passive: false });

        // Auto refresh every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000);

        // Service Worker for offline support (optional)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    </script>
</body>
</html>