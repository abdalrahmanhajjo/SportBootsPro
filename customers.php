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

// Handle customer deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        $conn->beginTransaction();
        
        // First check if customer has orders
        $order_check = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
        $order_check->execute([$delete_id]);
        $order_count = $order_check->fetchColumn();
        
        if ($order_count > 0) {
            $_SESSION['error_message'] = "Cannot delete customer with existing orders";
        } else {
            // Delete from cart and then the user
            $conn->exec("DELETE FROM cart WHERE user_id = $delete_id");
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$delete_id]);
            $_SESSION['success_message'] = "Customer deleted successfully!";
        }
        
        $conn->commit();
        header("Location: customers.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Error deleting customer: " . $e->getMessage();
        header("Location: customers.php");
        exit();
    }
}

// Get filters from URL
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$order_filter = isset($_GET['orders']) ? $_GET['orders'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Base query with order count
$query = "SELECT u.*, 
                 COUNT(o.id) AS order_count,
                 MAX(o.created_at) AS last_order_date,
                 SUM(o.total_amount) AS total_spent
          FROM users u
          LEFT JOIN orders o ON u.id = o.user_id";

$where_clauses = [];
$params = [];

// Add search filter
if (!empty($search_query)) {
    $where_clauses[] = "(u.full_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    $params[':search'] = "%$search_query%";
}

// Add order count filter
if ($order_filter !== 'all') {
    if ($order_filter === 'with') {
        $where_clauses[] = "o.id IS NOT NULL";
    } elseif ($order_filter === 'without') {
        $where_clauses[] = "o.id IS NULL";
    } elseif ($order_filter === 'recent') {
        $where_clauses[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }
}

// Add date range filter
if (!empty($date_from)) {
    $where_clauses[] = "u.created_at >= :date_from";
    $params[':date_from'] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $where_clauses[] = "u.created_at <= :date_to";
    $params[':date_to'] = $date_to . ' 23:59:59';
}

// Combine where clauses
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Group by user ID
$query .= " GROUP BY u.id";

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
$customers = $stmt->fetchAll();

// Get customer counts for filters
$counts = [
    'all' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'with' => $conn->query("SELECT COUNT(DISTINCT user_id) FROM orders")->fetchColumn(),
    'without' => $conn->query("SELECT COUNT(*) FROM users WHERE id NOT IN (SELECT DISTINCT user_id FROM orders)")->fetchColumn(),
    'recent' => $conn->query("SELECT COUNT(DISTINCT user_id) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn()
];

// Get new customers today
$new_today = $conn->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Customers - SportBoots Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reuse all the CSS from your dashboard/orders pages */
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

        /* Customer-specific additions */
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--bg-tertiary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 16px;
            flex-shrink: 0;
        }

        .customer-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .order-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--accent);
            color: white;
            font-size: 12px;
            font-weight: 600;
        }

        .last-order {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .customer-actions {
            display: flex;
            gap: 8px;
        }

        .action-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-secondary);
            color: var(--text-secondary);
            transition: all var(--transition-fast);
        }

        .action-icon:active {
            transform: scale(0.9);
        }

        .action-icon.view {
            color: var(--accent);
        }

        .action-icon.edit {
            color: var(--info);
        }

        .action-icon.delete {
            color: var(--danger);
        }

        /* Filter Sheet */
        .filter-sheet {
            position: fixed;
            bottom: -100%;
            left: 0;
            right: 0;
            background: var(--bg-primary);
            border-top-left-radius: var(--radius-lg);
            border-top-right-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            z-index: 200;
            transition: bottom var(--transition-slow) var(--spring-smooth);
            padding: 20px;
            padding-bottom: calc(20px + var(--safe-area-inset-bottom));
            max-height: 80vh;
            overflow-y: auto;
        }

        .filter-sheet.active {
            bottom: 0;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .filter-title {
            font-size: 18px;
            font-weight: 600;
        }

        .filter-close {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-secondary);
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-group-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--text-secondary);
        }

        .filter-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .filter-option {
            padding: 8px 16px;
            border-radius: var(--radius-full);
            background: var(--bg-secondary);
            border: 1px solid var(--border-light);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .filter-option.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .date-inputs {
            display: flex;
            gap: 12px;
        }

        .date-input {
            flex: 1;
        }

        .date-input label {
            display: block;
            font-size: 12px;
            margin-bottom: 4px;
            color: var(--text-secondary);
        }

        .date-input input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: 14px;
        }

        .filter-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .filter-btn {
            flex: 1;
            padding: 14px;
            border-radius: var(--radius-md);
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .filter-btn.apply {
            background: var(--accent);
            color: white;
        }

        .filter-btn.reset {
            background: var(--bg-tertiary);
            color: var(--text-primary);
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
                    <i class="fas fa-users"></i>
                    Customers
                </h1>
                <div class="header-actions">
                    <button class="header-btn" id="searchBtn">
                        <i class="fas fa-search"></i>
                    </button>
                    <button class="header-btn" id="filterBtn">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main" id="mainContent">
            <div class="main-content">
                <!-- Search Bar -->
                <div class="search-container" id="searchContainer">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" 
                               class="search-input" 
                               id="searchInput"
                               placeholder="Search customers..." 
                               value="<?= htmlspecialchars($search_query) ?>">
                        <button class="header-btn" id="closeSearch" style="position: absolute; right: 10px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Stats Scroll -->
                <div class="stats-scroll">
                    <div class="stats-container">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value"><?= number_format($counts['all']) ?></div>
                            <div class="stat-label">Total Customers</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-value"><?= number_format($counts['with']) ?></div>
                            <div class="stat-label">With Orders</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="stat-value"><?= number_format($new_today) ?></div>
                            <div class="stat-label">New Today</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-value"><?= $counts['all'] > 0 ? round(($counts['with'] / $counts['all']) * 100) : 0 ?>%</div>
                            <div class="stat-label">Conversion</div>
                        </div>
                    </div>
                </div>

                <!-- Filter Pills -->
                <div class="filter-section">
                    <div class="filter-header">
                        <span class="filter-title">Filter by Orders</span>
                        <a href="customers.php" class="filter-action">Clear All</a>
                    </div>
                    <div class="status-pills">
                        <a href="?orders=all<?= $search_query ? '&search='.urlencode($search_query) : '' ?>" class="status-pill <?= $order_filter === 'all' ? 'active' : '' ?>">
                            All <span class="status-count"><?= $counts['all'] ?></span>
                        </a>
                        <a href="?orders=with<?= $search_query ? '&search='.urlencode($search_query) : '' ?>" class="status-pill <?= $order_filter === 'with' ? 'active' : '' ?>">
                            With Orders <span class="status-count"><?= $counts['with'] ?></span>
                        </a>
                        <a href="?orders=without<?= $search_query ? '&search='.urlencode($search_query) : '' ?>" class="status-pill <?= $order_filter === 'without' ? 'active' : '' ?>">
                            Without Orders <span class="status-count"><?= $counts['without'] ?></span>
                        </a>
                        <a href="?orders=recent<?= $search_query ? '&search='.urlencode($search_query) : '' ?>" class="status-pill <?= $order_filter === 'recent' ? 'active' : '' ?>">
                            Recent Buyers <span class="status-count"><?= $counts['recent'] ?></span>
                        </a>
                    </div>
                </div>

                <!-- Customers List -->
                <div class="order-list">
                    <?php if (count($customers) > 0): ?>
                        <?php foreach ($customers as $customer): ?>
                        <a href="customer-details.php?id=<?= $customer['id'] ?>" class="order-card">
                            <div class="order-header">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="customer-avatar">
                                        <?php if (!empty($customer['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars($customer['avatar_url']) ?>" alt="<?= htmlspecialchars($customer['full_name']) ?>">
                                        <?php else: ?>
                                        <i class="fas fa-user"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="order-id"><?= htmlspecialchars($customer['full_name']) ?></div>
                                        <div class="order-time">Joined <?= date('M j, Y', strtotime($customer['created_at'])) ?></div>
                                    </div>
                                </div>
                                <div class="order-status">
                                    <?php if ($customer['order_count'] > 0): ?>
                                    <span class="order-count"><?= $customer['order_count'] ?></span>
                                    <?php else: ?>
                                    <span style="color: var(--text-light)">No orders</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="order-details">
                                <div>
                                    <div class="order-customer"><?= htmlspecialchars($customer['email']) ?></div>
                                    <div class="last-order">
                                        <?php if ($customer['last_order_date']): ?>
                                        Last order: <?= date('M j, Y', strtotime($customer['last_order_date'])) ?>
                                        <?php if ($customer['total_spent']): ?>
                                        • Total spent: $<?= number_format($customer['total_spent'], 2) ?>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        Never ordered
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="customer-actions">
                                    <a href="mailto:<?= htmlspecialchars($customer['email']) ?>" class="action-icon" onclick="event.stopPropagation()">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                    <a href="customer-edit.php?id=<?= $customer['id'] ?>" class="action-icon edit" onclick="event.stopPropagation()">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="customers.php?delete_id=<?= $customer['id'] ?>" class="action-icon delete" onclick="event.stopPropagation(); return confirm('Are you sure you want to delete this customer?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-user-slash"></i>
                            </div>
                            <div class="empty-title">No Customers Found</div>
                            <div class="empty-text">
                                <?php if (!empty($search_query) || $order_filter !== 'all' || !empty($date_from) || !empty($date_to)): ?>
                                    Try adjusting your filters or search terms
                                <?php else: ?>
                                    Customer accounts will appear here
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <!-- Filter Sheet -->
        <div class="filter-sheet" id="filterSheet">
            <div class="filter-header">
                <h2 class="filter-title">Filter Customers</h2>
                <button class="filter-close" id="closeFilter">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="filterForm" method="get">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                
                <div class="filter-group">
                    <h3 class="filter-group-title">Order Status</h3>
                    <div class="filter-options">
                        <div class="filter-option <?= $order_filter === 'all' ? 'active' : '' ?>" data-value="all">
                            All Customers
                        </div>
                        <div class="filter-option <?= $order_filter === 'with' ? 'active' : '' ?>" data-value="with">
                            With Orders
                        </div>
                        <div class="filter-option <?= $order_filter === 'without' ? 'active' : '' ?>" data-value="without">
                            Without Orders
                        </div>
                        <div class="filter-option <?= $order_filter === 'recent' ? 'active' : '' ?>" data-value="recent">
                            Recent Buyers
                        </div>
                    </div>
                    <input type="hidden" name="orders" id="orderFilter" value="<?= $order_filter ?>">
                </div>
                
                <div class="filter-group">
                    <h3 class="filter-group-title">Date Range</h3>
                    <div class="date-inputs">
                        <div class="date-input">
                            <label for="date_from">From</label>
                            <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="date-input">
                            <label for="date_to">To</label>
                            <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="button" class="filter-btn reset" id="resetFilters">
                        Reset
                    </button>
                    <button type="submit" class="filter-btn apply">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Overlay -->
        <div class="overlay" id="overlay"></div>
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
                
                <a href="inventory.php" class="nav-item ">
                    <div class="nav-icon">
                        <i class="fas fa-boxes"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Inventory</span>
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
                
                <a href="reports.php" class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <span>Reports</span>
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
        const searchBtn = document.getElementById('searchBtn');
        const searchContainer = document.getElementById('searchContainer');
        const closeSearch = document.getElementById('closeSearch');
        const searchInput = document.getElementById('searchInput');
        const filterBtn = document.getElementById('filterBtn');
        const filterSheet = document.getElementById('filterSheet');
        const closeFilter = document.getElementById('closeFilter');
        const overlay = document.getElementById('overlay');
        const filterForm = document.getElementById('filterForm');
        const resetFilters = document.getElementById('resetFilters');
        const orderFilter = document.getElementById('orderFilter');
        const filterOptions = document.querySelectorAll('.filter-option');

        // Search functionality
        searchBtn.addEventListener('click', () => {
            searchContainer.style.display = 'block';
            searchInput.focus();
        });

        closeSearch.addEventListener('click', () => {
            searchContainer.style.display = 'none';
        });

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

        // Filter functionality
        filterBtn.addEventListener('click', () => {
            filterSheet.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        closeFilter.addEventListener('click', () => {
            filterSheet.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });

        overlay.addEventListener('click', () => {
            filterSheet.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });

        // Filter option selection
        filterOptions.forEach(option => {
            option.addEventListener('click', function() {
                filterOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                orderFilter.value = this.dataset.value;
            });
        });

        // Reset filters
        resetFilters.addEventListener('click', () => {
            filterOptions.forEach(opt => opt.classList.remove('active'));
            document.querySelector('.filter-option[data-value="all"]').classList.add('active');
            orderFilter.value = 'all';
            document.getElementById('date_from').value = '';
            document.getElementById('date_to').value = '';
            
            // Submit the form immediately when resetting
            const url = new URL(window.location.href);
            url.searchParams.delete('orders');
            url.searchParams.delete('date_from');
            url.searchParams.delete('date_to');
            window.location.href = url.toString();
        });

        // Add haptic feedback to buttons
        document.querySelectorAll('button, a').forEach(element => {
            element.addEventListener('click', () => {
                if ('vibrate' in navigator) {
                    navigator.vibrate(5);
                }
            });
        });

        // Auto-scroll to top when searching/filtering
        if (window.location.search) {
            window.scrollTo(0, 0);
        }
    </script>
</body>
</html>