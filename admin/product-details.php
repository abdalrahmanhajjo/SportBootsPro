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

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch product details
$product_stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$product_stmt->execute([$product_id]);
$product = $product_stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit();
}

// Use the image_url from products table
$primary_image = !empty($product['image_url']) ? $product['image_url'] : 'placeholder.jpg';

// Check if variants table exists and fetch variants
$variants = [];
$variants_table_exists = $conn->query("SHOW TABLES LIKE 'product_variants'")->rowCount() > 0;
if ($variants_table_exists) {
    $variants_stmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ?");
    $variants_stmt->execute([$product_id]);
    $variants = $variants_stmt->fetchAll();
}

// Fetch similar products
$similar_products = $conn->prepare("
    SELECT p.id, p.name, p.price, p.image_url 
    FROM products p
    WHERE p.category_id = ? AND p.id != ?
    LIMIT 4
");
$similar_products->execute([$product['category_id'], $product_id]);
$similar = $similar_products->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= htmlspecialchars($product['name']) ?> - SportBoots Pro</title>
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

/* Product Gallery */
.product-gallery {
    position: relative;
    margin-bottom: 20px;
    border-radius: var(--radius-md);
    overflow: hidden;
    background: var(--bg-primary);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-light);
}

.primary-image {
    width: 100%;
    height: 300px;
    object-fit: cover;
    display: block;
}

.image-placeholder {
    width: 100%;
    height: 300px;
    background: var(--bg-tertiary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-light);
    font-size: 48px;
}

/* Product Info */
.product-info {
    background: var(--bg-primary);
    border-radius: var(--radius-md);
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-light);
}

.product-header {
    margin-bottom: 16px;
}

.product-title {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 4px;
    color: var(--text-primary);
}

.product-category {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.product-price {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 16px;
}

.product-meta {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: var(--text-secondary);
}

.meta-item i {
    color: var(--text-light);
}

.stock-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: var(--radius-full);
    font-size: 13px;
    font-weight: 600;
}

.in-stock {
    background: rgba(0, 166, 153, 0.1);
    color: var(--success);
}

.out-of-stock {
    background: rgba(231, 76, 60, 0.1);
    color: var(--danger);
}

.featured-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: var(--radius-full);
    font-size: 13px;
    font-weight: 600;
    background: rgba(255, 56, 92, 0.1);
    color: var(--accent);
    margin-left: 8px;
}

.product-description {
    margin-bottom: 20px;
}

.section-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
    color: var(--text-primary);
}

.description-text {
    font-size: 14px;
    color: var(--text-secondary);
    line-height: 1.6;
}

.variant-section {
    margin-bottom: 20px;
}

.variant-options {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.variant-option {
    padding: 8px 16px;
    border: 1px solid var(--border-light);
    border-radius: var(--radius-full);
    font-size: 14px;
    font-weight: 500;
    background: var(--bg-primary);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.variant-option.selected {
    background: var(--accent);
    color: white;
    border-color: var(--accent);
}

.product-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.action-btn {
    flex: 1;
    padding: 14px;
    border-radius: var(--radius-md);
    border: none;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.action-btn:active {
    transform: scale(0.96);
}

.primary-btn {
    background: var(--accent);
    color: white;
}

.secondary-btn {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

/* Similar Products */
.similar-products {
    margin-top: 24px;
}

.similar-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.similar-item {
    background: var(--bg-primary);
    border-radius: var(--radius-md);
    padding: 12px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-light);
    text-decoration: none;
    color: inherit;
}

.similar-image {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: var(--radius-sm);
    margin-bottom: 8px;
    background: var(--bg-tertiary);
}

.similar-name {
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.similar-price {
    font-size: 16px;
    font-weight: 600;
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

/* Animations */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.spinner {
    animation: spin 0.8s linear infinite;
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
    .similar-grid {
        grid-template-columns: 1fr;
    }
    
    .product-meta {
        gap: 8px;
    }
    
    .product-actions {
        flex-direction: column;
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
                <a href="products.php" class="header-title">
                    <i class="fas fa-arrow-left"></i>
                    Product
                </a>
                <div class="header-actions">
                    <button class="header-btn" onclick="window.location.href='product-edit.php?id=<?= $product['id'] ?>'">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main" id="mainContent">
            <div class="main-content">
                <!-- Product Image -->
                <div class="product-gallery">
                    <?php if (!empty($primary_image)): ?>
                        <img src="../<?= htmlspecialchars($primary_image) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="primary-image">
                    <?php else: ?>
                        <div class="image-placeholder">
                            <i class="fas fa-box-open"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="product-info">
                    <div class="product-header">
                        <h1 class="product-title"><?= htmlspecialchars($product['name']) ?>
                            <?php if ($product['is_featured']): ?>
                            <span class="featured-badge">Featured</span>
                            <?php endif; ?>
                        </h1>
                        <div class="product-category"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></div>
                        <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
                        
                        <span class="stock-badge <?= $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                            <?= $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock' ?>
                        </span>
                        
                        <div class="product-meta">
                            <div class="meta-item">
                                <i class="fas fa-barcode"></i>
                                <span>SKU: <?= $product['id'] ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-box"></i>
                                <span>Stock: <?= $product['stock_quantity'] ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($product['description'])): ?>
                    <div class="product-description">
                        <h3 class="section-title">Description</h3>
                        <p class="description-text"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($variants)): ?>
                    <div class="variant-section">
                        <h3 class="section-title">Available Variants</h3>
                        <div class="variant-options">
                            <?php foreach ($variants as $variant): ?>
                            <div class="variant-option <?= $variant['is_default'] ? 'selected' : '' ?>">
                                <?= htmlspecialchars($variant['name']) ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="product-actions">
                        <button class="action-btn secondary-btn" onclick="window.location.href='product-edit.php?id=<?= $product['id'] ?>'">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                </div>

                <!-- Similar Products -->
                <?php if (count($similar) > 0): ?>
                <div class="similar-products">
                    <h3 class="section-title">Similar Products</h3>
                    <div class="similar-grid">
                        <?php foreach ($similar as $item): ?>
                        <a href="product-details.php?id=<?= $item['id'] ?>" class="similar-item">
                            <?php if (!empty($item['image_url'])): ?>
                            <img src="../<?= htmlspecialchars($item['image_url']) ?>" class="similar-image" alt="<?= htmlspecialchars($item['name']) ?>">
                            <?php else: ?>
                            <div class="similar-image" style="display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-box-open" style="font-size: 24px; color: var(--text-light);"></i>
                            </div>
                            <?php endif; ?>
                            <div class="similar-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="similar-price">$<?= number_format($item['price'], 2) ?></div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
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
                
                <a href="reports.php" class="nav-item">
                    <div class="nav-icon">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Reports</span>
                </a>
            </div>
        </nav>
    </div>

    <script>
        // Set viewport height for mobile browsers
        function setViewportHeight() {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);

        // Variant selection (if variants exist)
        const variantOptions = document.querySelectorAll('.variant-option');
        if (variantOptions.length > 0) {
            variantOptions.forEach(option => {
                option.addEventListener('click', function() {
                    variantOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });
        }

        // Add haptic feedback to buttons
        document.querySelectorAll('button, a').forEach(element => {
            element.addEventListener('click', () => {
                if ('vibrate' in navigator) {
                    navigator.vibrate(5);
                }
            });
        });
    </script>
</body>
</html>
