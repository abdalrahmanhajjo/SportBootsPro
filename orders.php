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

// Get filters from URL
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Base query with joins for better data retrieval
$query = "SELECT o.*, 
                 COUNT(oi.id) AS item_count,
                 SUM(oi.quantity) AS total_quantity
          FROM orders o
          LEFT JOIN order_items oi ON o.id = oi.order_id";

$where_clauses = [];
$params = [];

// Add status filter if not 'all'
if ($status_filter !== 'all') {
    $where_clauses[] = "o.status = :status";
    $params[':status'] = $status_filter;
}

// Add search filter
if (!empty($search_query)) {
    $where_clauses[] = "(o.id LIKE :search OR 
                        o.customer_name LIKE :search OR 
                        o.customer_email LIKE :search OR 
                        o.customer_phone LIKE :search)";
    $params[':search'] = "%$search_query%";
}

// Add date range filter
if (!empty($date_from)) {
    $where_clauses[] = "o.created_at >= :date_from";
    $params[':date_from'] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $where_clauses[] = "o.created_at <= :date_to";
    $params[':date_to'] = $date_to . ' 23:59:59';
}

// Combine where clauses
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Group by order ID
$query .= " GROUP BY o.id";

// Sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';
$query .= " ORDER BY $sort $order";

// Prepare and execute query
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$orders = $stmt->fetchAll();

// Get counts for each status
$status_counts = [];
$statuses = ['all', 'pending', 'processing', 'shipped', 'delivered', 'cancelled'];
foreach ($statuses as $status) {
    $count_query = "SELECT COUNT(*) FROM orders";
    $count_params = [];
    
    if ($status !== 'all') {
        $count_query .= " WHERE status = ?";
        $count_params[] = $status;
    }
    
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute($count_params);
    $status_counts[$status] = $count_stmt->fetchColumn();
}

// Get total revenue for the current filter set
$revenue_query = "SELECT SUM(total_amount) FROM orders";
$revenue_params = [];
$revenue_where = [];

if ($status_filter !== 'all') {
    $revenue_where[] = "status = ?";
    $revenue_params[] = $status_filter;
}

if (!empty($search_query)) {
    $revenue_where[] = "(id LIKE ? OR customer_name LIKE ? OR customer_email LIKE ? OR customer_phone LIKE ?)";
    array_push($revenue_params, 
        "%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%");
}

if (!empty($date_from)) {
    $revenue_where[] = "created_at >= ?";
    $revenue_params[] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $revenue_where[] = "created_at <= ?";
    $revenue_params[] = $date_to . ' 23:59:59';
}

if (!empty($revenue_where)) {
    $revenue_query .= " WHERE " . implode(" AND ", $revenue_where);
}

$revenue_stmt = $conn->prepare($revenue_query);
$revenue_stmt->execute($revenue_params);
$total_revenue = $revenue_stmt->fetchColumn() ?? 0;

// Calculate average order value
$avg_order_value = count($orders) > 0 ? $total_revenue / count($orders) : 0;

// Get today's orders count
$today_count_stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
$today_count_stmt->execute();
$today_orders = $today_count_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Orders - SportBoots Pro</title>
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

        /* Search Bar */
        .search-container {
            margin-bottom: 20px;
        }

        .search-wrapper {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-full);
            font-size: 14px;
            background: var(--bg-primary);
            transition: all var(--transition-fast);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255, 56, 92, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 16px;
        }

        /* Stats Cards */
        .stats-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -16px;
            padding: 0 16px;
            scrollbar-width: none;
            -ms-overflow-style: none;
            margin-bottom: 20px;
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

        /* Status Filter Pills */
        .filter-section {
            margin-bottom: 20px;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .filter-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .filter-action {
            font-size: 14px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .status-pills {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 4px;
            margin: 0 -16px;
            padding: 0 16px 4px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .status-pills::-webkit-scrollbar {
            display: none;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--bg-primary);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-full);
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            white-space: nowrap;
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
            flex-shrink: 0;
        }

        .status-pill:active {
            transform: scale(0.95);
        }

        .status-pill.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .status-count {
            background: rgba(0, 0, 0, 0.1);
            padding: 2px 8px;
            border-radius: var(--radius-full);
            font-size: 12px;
            font-weight: 600;
        }

        .status-pill.active .status-count {
            background: rgba(255, 255, 255, 0.2);
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
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            overflow: hidden;
            cursor: pointer;
            transition: all var(--transition-base);
            text-decoration: none;
            color: inherit;
        }

        .order-card:active {
            transform: scale(0.98);
            box-shadow: var(--shadow-md);
        }

        .order-card-main {
            padding: 16px;
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

        .status-shipped {
            background: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }

        .status-delivered {
            background: rgba(0, 166, 153, 0.1);
            color: var(--success);
        }

        .status-cancelled {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .order-customer {
            margin-bottom: 12px;
        }

        .customer-name {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .customer-details {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .customer-detail {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .customer-detail i {
            font-size: 12px;
            color: var(--text-light);
        }

        .order-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid var(--border-light);
        }

        .order-details {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .order-amount {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .order-time {
            font-size: 11px;
            color: var(--text-light);
            margin-top: 4px;
        }

        .order-actions {
            display: flex;
            border-top: 1px solid var(--border-light);
        }

        .order-action {
            flex: 1;
            padding: 12px;
            border: none;
            background: none;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .order-action:not(:last-child) {
            border-right: 1px solid var(--border-light);
        }

        .order-action:active {
            background: var(--bg-secondary);
        }

        .order-action.primary {
            color: var(--accent);
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

        /* Loading Spinner */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .spinner {
            animation: spin 0.8s linear infinite;
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

        /* Responsive Adjustments */
        @media (max-width: 320px) {
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
                    <i class="fas fa-shopping-bag"></i>
                    Orders
                </h1>
                <div class="header-actions">
                    <button class="header-btn" id="filterBtn">
                        <i class="fas fa-filter"></i>
                    </button>
                    <button class="header-btn" id="menuBtn">
                        <i class="fas fa-user-circle"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main" id="mainContent">
            <div class="main-content">
                <!-- Search Bar -->
                <div class="search-container">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" 
                               class="search-input" 
                               id="searchInput"
                               placeholder="Search orders, customers..." 
                               value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                </div>

                <!-- Stats Scroll -->
                <div class="stats-scroll">
                    <div class="stats-container">
                        <div class="stat-card primary">
                            <div class="stat-trend up">
                                <i class="fas fa-arrow-up"></i> 12%
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-value"><?= number_format(count($orders)) ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-trend up">
                                <i class="fas fa-arrow-up"></i> 8%
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-value">$<?= number_format($total_revenue, 0) ?></div>
                            <div class="stat-label">Revenue</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-value">$<?= number_format($avg_order_value, 0) ?></div>
                            <div class="stat-label">Avg Order</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-value"><?= number_format($today_orders) ?></div>
                            <div class="stat-label">Today's Orders</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-trend down">
                                <i class="fas fa-arrow-down"></i> 3%
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-ban"></i>
                            </div>
                            <div class="stat-value"><?= $status_counts['cancelled'] ?></div>
                            <div class="stat-label">Cancelled</div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="filter-header">
                        <span class="filter-title">Filter by Status</span>
                        <a href="orders.php" class="filter-action">Clear All</a>
                    </div>
                    <div class="status-pills">
                        <a href="?status=all" class="status-pill <?= $status_filter === 'all' ? 'active' : '' ?>">
                            All <span class="status-count"><?= $status_counts['all'] ?></span>
                        </a>
                        <a href="?status=pending" class="status-pill <?= $status_filter === 'pending' ? 'active' : '' ?>">
                            Pending <span class="status-count"><?= $status_counts['pending'] ?></span>
                        </a>
                        <a href="?status=processing" class="status-pill <?= $status_filter === 'processing' ? 'active' : '' ?>">
                            Processing <span class="status-count"><?= $status_counts['processing'] ?></span>
                        </a>
                        <a href="?status=shipped" class="status-pill <?= $status_filter === 'shipped' ? 'active' : '' ?>">
                            Shipped <span class="status-count"><?= $status_counts['shipped'] ?></span>
                        </a>
                        <a href="?status=delivered" class="status-pill <?= $status_filter === 'delivered' ? 'active' : '' ?>">
                            Delivered <span class="status-count"><?= $status_counts['delivered'] ?></span>
                        </a>
                        <a href="?status=cancelled" class="status-pill <?= $status_filter === 'cancelled' ? 'active' : '' ?>">
                            Cancelled <span class="status-count"><?= $status_counts['cancelled'] ?></span>
                        </a>
                    </div>
                </div>

                <!-- Orders List -->
                <div class="order-list">
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                        <a href="order-details.php?id=<?= $order['id'] ?>" class="order-card">
                            <div class="order-card-main">
                                <div class="order-header">
                                    <div class="order-id">#<?= $order['id'] ?></div>
                                    <div class="order-status status-<?= $order['status'] ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </div>
                                </div>
                                
                                <div class="order-customer">
                                    <div class="customer-name"><?= htmlspecialchars($order['customer_name']) ?></div>
                                    <div class="customer-details">
                                        <div class="customer-detail">
                                            <i class="fas fa-phone"></i>
                                            <?= htmlspecialchars($order['customer_phone']) ?>
                                        </div>
                                        <div class="customer-detail">
                                            <i class="fas fa-envelope"></i>
                                            <?= htmlspecialchars($order['customer_email']) ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="order-summary">
                                    <div>
                                        <div class="order-details">
                                            <?= $order['item_count'] ?> items (<?= $order['total_quantity'] ?> qty)
                                        </div>
                                        <div class="order-time"><?= date('M j, g:i a', strtotime($order['created_at'])) ?></div>
                                    </div>
                                    <div class="order-amount">
                                        $<?= number_format($order['total_amount'], 2) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="order-actions">
                                <button class="order-action primary" onclick="event.preventDefault(); window.location.href='order-details.php?id=<?= $order['id'] ?>'">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                <button class="order-action" onclick="event.preventDefault(); window.location.href='order-edit.php?id=<?= $order['id'] ?>'">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <div class="empty-title">No Orders Found</div>
                            <div class="empty-text">
                                <?php if (!empty($search_query) || $status_filter !== 'all'): ?>
                                    Try adjusting your filters or search terms
                                <?php else: ?>
                                    New orders will appear here
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <div class="bottom-nav-content">
                <a href="dashboard.php" class="nav-item">
                    <div class="nav-icon">
                        <i class="fas fa-home"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Home</span>
                </a>
                
                <a href="orders.php" class="nav-item active">
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
                
                <a href="reports.php" class="nav-item">
                    <div class="nav-icon">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Reports</span>
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
        const filterBtn = document.getElementById('filterBtn');
        const sideSheet = document.getElementById('sideSheet');
        const overlay = document.getElementById('overlay');
        const mainContent = document.getElementById('mainContent');
        const searchInput = document.getElementById('searchInput');

        // Touch handling
        let touchStartY = 0;
        let touchEndY = 0;

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

        // Search functionality
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const url = new URL(window.location.href);
                if (e.target.value) {
                    url.searchParams.set('search', e.target.value);
                } else {
                    url.searchParams.delete('search');
                }
                window.location.href = url.toString();
            }, 500);
        });

        // Filter button (you can implement a filter sheet similar to side sheet)
        filterBtn.addEventListener('click', () => {
            // For now, just export
            const params = new URLSearchParams(window.location.search);
            window.location.href = `order-export.php?${params.toString()}`;
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

        // Auto-scroll to active status pill
        const activeStatusPill = document.querySelector('.status-pill.active');
        if (activeStatusPill) {
            setTimeout(() => {
                activeStatusPill.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            }, 100);
        }

        // Prevent overscroll
        document.body.addEventListener('touchmove', (e) => {
            if (e.target.closest('.main')) return;
            e.preventDefault();
        }, { passive: false });

        // Service Worker for offline support (optional)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    </script>
</body>
</html>