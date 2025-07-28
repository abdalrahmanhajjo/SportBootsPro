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
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : 'all';

// Base query
$query = "SELECT p.*, c.name AS category_name FROM products p
          LEFT JOIN categories c ON p.category_id = c.id";

$where_clauses = [];
$params = [];

// Add category filter if not 'all'
if ($category_filter !== 'all') {
    $where_clauses[] = "p.category_id = :category";
    $params[':category'] = $category_filter;
}

// Add stock filter
if ($stock_filter === 'low') {
    $where_clauses[] = "p.stock_quantity < 10";
} elseif ($stock_filter === 'out') {
    $where_clauses[] = "p.stock_quantity <= 0";
}

// Add search filter
if (!empty($search_query)) {
    $where_clauses[] = "(p.name LIKE :search OR p.description LIKE :search OR p.sku LIKE :search)";
    $params[':search'] = "%$search_query%";
}

// Combine where clauses
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

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
$products = $stmt->fetchAll();

// Get counts for each category
$category_counts = [];
$categories = $conn->query("SELECT id, name FROM categories")->fetchAll();
$category_counts['all'] = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();

foreach ($categories as $category) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$category['id']]);
    $category_counts[$category['id']] = $stmt->fetchColumn();
}

// Get stock counts
$stock_counts = [
    'all' => $category_counts['all'],
    'low' => $conn->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10")->fetchColumn(),
    'out' => $conn->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 0")->fetchColumn()
];

// Get total inventory value
$inventory_value = $conn->query("SELECT SUM(price * stock_quantity) FROM products")->fetchColumn() ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Products - SportBoots Pro</title>
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

/* Filter Section */
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

/* Product Cards */
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

.order-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid var(--border-light);
}

/* Product Image */
.product-image {
    width: 60px;
    height: 60px;
    border-radius: var(--radius-sm);
    object-fit: cover;
    border: 1px solid var(--border-light);
    background: var(--bg-tertiary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-light);
    font-size: 20px;
}

/* Stock Badges */
.stock-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: var(--radius-full);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stock-in {
    background: rgba(0, 166, 153, 0.1);
    color: var(--success);
}

.stock-low {
    background: rgba(255, 180, 0, 0.1);
    color: var(--warning);
}

.stock-out {
    background: rgba(231, 76, 60, 0.1);
    color: var(--danger);
}

.featured-badge {
    background: rgba(255, 56, 92, 0.1);
    color: var(--accent);
    padding: 4px 8px;
    border-radius: var(--radius-full);
    font-size: 11px;
    font-weight: 600;
    margin-left: 6px;
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
                    <i class="fas fa-shoe-prints"></i>
                    Products
                </h1>
                <div class="header-actions">
                    <button class="header-btn" id="filterBtn">
                        <i class="fas fa-filter"></i>
                    </button>
                    <button class="header-btn" id="addBtn">
                        <i class="fas fa-plus"></i>
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
                               placeholder="Search products..." 
                               value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                </div>

                <!-- Stats Scroll -->
                <div class="stats-scroll">
                    <div class="stats-container">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div class="stat-value"><?= number_format(count($products)) ?></div>
                            <div class="stat-label">Total Products</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-value">$<?= number_format($inventory_value, 0) ?></div>
                            <div class="stat-label">Inventory Value</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="stat-value"><?= $stock_counts['low'] ?></div>
                            <div class="stat-label">Low Stock</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-ban"></i>
                            </div>
                            <div class="stat-value"><?= $stock_counts['out'] ?></div>
                            <div class="stat-label">Out of Stock</div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="filter-header">
                        <span class="filter-title">Filter by Category</span>
                        <a href="products.php" class="filter-action">Clear All</a>
                    </div>
                    <div class="status-pills">
                        <a href="?category=all" class="status-pill <?= $category_filter === 'all' ? 'active' : '' ?>">
                            All <span class="status-count"><?= $category_counts['all'] ?></span>
                        </a>
                        <?php foreach ($categories as $category): ?>
                        <a href="?category=<?= $category['id'] ?>" class="status-pill <?= $category_filter == $category['id'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($category['name']) ?> <span class="status-count"><?= $category_counts[$category['id']] ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Stock Filter -->
                <div class="filter-section">
                    <div class="filter-header">
                        <span class="filter-title">Filter by Stock</span>
                    </div>
                    <div class="status-pills">
                        <a href="?stock=all" class="status-pill <?= $stock_filter === 'all' ? 'active' : '' ?>">
                            All <span class="status-count"><?= $stock_counts['all'] ?></span>
                        </a>
                        <a href="?stock=low" class="status-pill <?= $stock_filter === 'low' ? 'active' : '' ?>">
                            Low Stock <span class="status-count"><?= $stock_counts['low'] ?></span>
                        </a>
                        <a href="?stock=out" class="status-pill <?= $stock_filter === 'out' ? 'active' : '' ?>">
                            Out of Stock <span class="status-count"><?= $stock_counts['out'] ?></span>
                        </a>
                    </div>
                </div>

                <!-- Products List -->
                <div class="order-list">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                        <a href="product-details.php?id=<?= $product['id'] ?>" class="order-card">
                            <div class="order-header">
                                <div class="order-id"><?= htmlspecialchars($product['name']) ?>
                                    <?php if ($product['is_featured']): ?>
                                    <span class="featured-badge">Featured</span>
                                    <?php endif; ?>
                                </div>
                                <div class="order-status stock-<?= $product['stock_quantity'] <= 0 ? 'out' : ($product['stock_quantity'] < 10 ? 'low' : 'in') ?>">
                                    <?= $product['stock_quantity'] <= 0 ? 'Out of Stock' : ($product['stock_quantity'] < 10 ? 'Low Stock' : 'In Stock') ?>
                                </div>
                            </div>
                            
                            <div class="order-details">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($product['image_url']) ?>" class="product-image" alt="<?= htmlspecialchars($product['name']) ?>">
                                    <?php else: ?>
                                    <div class="product-image">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="order-customer"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></div>
                                        <div class="order-time">SKU: <?= $product['id'] ?></div>
                                    </div>
                                </div>
                                <div class="order-amount">$<?= number_format($product['price'], 2) ?></div>
                            </div>
                            
                            <div class="order-summary">
                                <div class="order-details">
                                    Stock: <?= $product['stock_quantity'] ?> units
                                </div>
                                <div class="order-time">Added: <?= date('M j, Y', strtotime($product['created_at'])) ?></div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-box-open"></i>
                            </div>
                            <div class="empty-title">No Products Found</div>
                            <div class="empty-text">
                                <?php if (!empty($search_query) || $category_filter !== 'all' || $stock_filter !== 'all'): ?>
                                    Try adjusting your filters or search terms
                                <?php else: ?>
                                    Add your first product to get started
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
                
                <a href="orders.php" class="nav-item">
                    <div class="nav-icon">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Orders</span>
                </a>
                
                <a href="products.php" class="nav-item active">
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
        const addBtn = document.getElementById('addBtn');
        const filterBtn = document.getElementById('filterBtn');
        const sideSheet = document.getElementById('sideSheet');
        const overlay = document.getElementById('overlay');
        const searchInput = document.getElementById('searchInput');

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

        // Button actions
        addBtn.addEventListener('click', () => {
            window.location.href = 'product-add.php';
        });

        filterBtn.addEventListener('click', () => {
            // Implement filter sheet if needed
            alert('Filter functionality would go here');
        });

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

        // Add haptic feedback to buttons
        document.querySelectorAll('button, a').forEach(element => {
            element.addEventListener('click', () => {
                if ('vibrate' in navigator) {
                    navigator.vibrate(5);
                }
            });
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

        // Service Worker for offline support (optional)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    </script>
</body>
</html>