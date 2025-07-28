<?php
session_start();

// Authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection
try {
    $conn = new PDO("mysql:host=sql209.infinityfree.com;dbname=if0_39222248_sportbootspro;charset=utf8mb4", "if0_39222248", "76536462Ah");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Date range for reports (default: last 30 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Sales Report
$sales_stmt = $conn->prepare("
    SELECT 
        DATE(o.created_at) as order_date,
        COUNT(*) as total_orders,
        SUM(o.total_amount) as total_sales,
        AVG(o.total_amount) as avg_order_value
    FROM orders o
    WHERE o.created_at BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)
    AND o.status != 'cancelled'
    GROUP BY DATE(o.created_at)
    ORDER BY order_date ASC
");
$sales_stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
$sales_data = $sales_stmt->fetchAll();

// Product Performance
$products_stmt = $conn->prepare("
    SELECT 
        p.id,
        p.name,
        p.image_url,
        COUNT(oi.id) as units_sold,
        SUM(oi.price * oi.quantity) as revenue,
        p.stock_quantity
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)
    AND o.status != 'cancelled'
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 10
");
$products_stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
$top_products = $products_stmt->fetchAll();

// Customer Statistics
$customers_stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT o.user_id) as total_customers,
        COUNT(DISTINCT CASE WHEN o.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW() THEN o.user_id END) as new_customers,
        COUNT(DISTINCT CASE WHEN o.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN o.user_id END) as returning_customers
    FROM orders o
    WHERE o.created_at BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)
    AND o.status != 'cancelled'
");
$customers_stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
$customer_stats = $customers_stmt->fetch();

// Calculate totals
$total_sales = 0;
$total_orders = 0;
foreach ($sales_data as $day) {
    $total_sales += $day['total_sales'];
    $total_orders += $day['total_orders'];
}
$avg_order_value = $total_orders > 0 ? $total_sales / $total_orders : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Reports - SportBoots Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Include all CSS from your dashboard/orders pages */
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
        }

        /* Report-specific additions */
        .chart-container {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
        }

        .chart-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--text-primary);
        }

        .date-filter {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .date-input {
            flex: 1;
            min-width: 150px;
            padding: 12px 16px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: 14px;
            background: var(--bg-primary);
        }

        .filter-btn {
            padding: 12px 20px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .reset-btn {
            padding: 12px 20px;
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .export-options {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .export-btn {
            padding: 12px 16px;
            border-radius: var(--radius-md);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .export-csv {
            background: var(--success);
            color: white;
        }

        .export-excel {
            background: var(--info);
            color: white;
        }

        .export-pdf {
            background: var(--danger);
            color: white;
        }

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
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </h1>
                <div class="header-actions">
                    <button class="header-btn" id="exportBtn">
                        <i class="fas fa-file-export"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main" id="mainContent">
            <div class="main-content">
                <!-- Date Filter -->
                <form method="get" class="date-filter">
                    <input type="date" name="start_date" value="<?= $start_date ?>" class="date-input">
                    <input type="date" name="end_date" value="<?= $end_date ?>" class="date-input">
                    <button type="submit" class="filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="reports.php" class="reset-btn">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </form>

                <!-- Stats Cards -->
                <div class="stats-scroll">
                    <div class="stats-container">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-value">$<?= number_format($total_sales, 0) ?></div>
                            <div class="stat-label">Total Sales</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-value"><?= number_format($total_orders) ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div class="stat-value">$<?= number_format($avg_order_value, 0) ?></div>
                            <div class="stat-label">Avg Order Value</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value"><?= number_format($customer_stats['total_customers']) ?></div>
                            <div class="stat-label">Total Customers</div>
                        </div>
                    </div>
                </div>

                <!-- Sales Trend Chart -->
                <div class="chart-container">
                    <div class="chart-title">Sales Trend</div>
                    <canvas id="salesChart" height="250"></canvas>
                </div>

                <!-- Customer Breakdown -->
                <div class="chart-container">
                    <div class="chart-title">Customer Breakdown</div>
                    <canvas id="customersChart" height="250"></canvas>
                </div>

                <!-- Top Products -->
                <div class="section-header">
                    <h2 class="section-title">Top Performing Products</h2>
                </div>
                
                <div class="order-list">
                    <?php foreach ($top_products as $product): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="order-amount">$<?= number_format($product['revenue'], 2) ?></div>
                        </div>
                        
                        <div class="order-details">
                            <div>
                                <div class="order-customer"><?= number_format($product['units_sold']) ?> units sold</div>
                                <div class="order-time">Stock: <?= number_format($product['stock_quantity']) ?></div>
                            </div>
                            <div>
                                <a href="product-details.php?id=<?= $product['id'] ?>" class="order-status status-delivered">
                                    View Product
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Export Options -->
                <div class="section-header">
                    <h2 class="section-title">Export Reports</h2>
                </div>
                
                <div class="export-options">
                    <a href="export-reports.php?type=sales&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="export-btn export-csv">
                        <i class="fas fa-file-csv"></i> Export Sales (CSV)
                    </a>
                    <a href="export-reports.php?type=products&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="export-btn export-excel">
                        <i class="fas fa-file-excel"></i> Export Products (Excel)
                    </a>
                    <a href="export-reports.php?type=customers&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="export-btn export-pdf">
                        <i class="fas fa-file-pdf"></i> Export Customers (PDF)
                    </a>
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
                
                <a href="reports.php" class="nav-item active">
                    <div class="nav-icon">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Reports</span>
                </a>
            </div>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Set viewport height for mobile browsers
        function setViewportHeight() {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Sales Trend Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: [<?= implode(',', array_map(function($day) { return "'" . date('M j', strtotime($day['order_date'])) . "'"; }, $sales_data)) ?>],
                    datasets: [{
                        label: 'Daily Sales',
                        data: [<?= implode(',', array_column($sales_data, 'total_sales')) ?>],
                        backgroundColor: 'rgba(255, 56, 92, 0.1)',
                        borderColor: 'var(--accent)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '$' + context.raw.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });

            // Customers Chart
            const customersCtx = document.getElementById('customersChart').getContext('2d');
            new Chart(customersCtx, {
                type: 'doughnut',
                data: {
                    labels: ['New Customers', 'Returning Customers'],
                    datasets: [{
                        data: [<?= $customer_stats['new_customers'] ?>, <?= $customer_stats['returning_customers'] ?>],
                        backgroundColor: [
                            'rgba(0, 166, 153, 0.7)',
                            'rgba(255, 56, 92, 0.7)'
                        ],
                        borderColor: [
                            'var(--success)',
                            'var(--accent)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        });

        // Export button handler
        document.getElementById('exportBtn').addEventListener('click', function() {
            // Scroll to export options
            document.querySelector('.export-options').scrollIntoView({
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>
