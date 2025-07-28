<?php 
require_once 'header.php';

// Load categories
$categories = [];
$cat_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($cat_result && $cat_result->num_rows > 0) {
    while($cat = $cat_result->fetch_assoc()) {
        $categories[] = $cat;
    }
}

// Filters
$products = [];
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$price_filter = $_GET['price'] ?? 'all';
$sort_by = $_GET['sort'] ?? 'featured';
$search = trim($_GET['search'] ?? '');

$query = "SELECT p.*, c.name AS category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.stock_quantity > 0";

// Apply filters
if ($category_filter > 0) {
    $query .= " AND p.category_id = $category_filter";
}
if ($price_filter !== 'all') {
    switch ($price_filter) {
        case 'under100': $query .= " AND p.price < 100"; break;
        case '100-200':  $query .= " AND p.price BETWEEN 100 AND 200"; break;
        case '200-300':  $query .= " AND p.price BETWEEN 200 AND 300"; break;
        case 'over300':  $query .= " AND p.price > 300"; break;
    }
}
if (!empty($search)) {
    $safe_search = $conn->real_escape_string($search);
    $query .= " AND p.name LIKE '%$safe_search%'";
}
switch ($sort_by) {
    case 'price_low': $query .= " ORDER BY p.price ASC"; break;
    case 'price_high': $query .= " ORDER BY p.price DESC"; break;
    case 'newest': $query .= " ORDER BY p.created_at DESC"; break;
    default: $query .= " ORDER BY p.is_featured DESC, p.created_at DESC"; break;
}

$result = $conn->query($query);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<div class="page-content">
    <section class="section">
        <div class="container">
            <div class="section-header text-center">
                <h1 class="section-title">Shop Collection</h1>
                <p class="section-subtitle">Find the perfect athletic footwear for your sport</p>
            </div>

            <!-- Enhanced Filters + Search -->
            <form method="get" action="shop.php" class="shop-filters">
                <div class="filter-container">
                    <!-- Mobile Filter Toggle -->
                    <button type="button" class="mobile-filter-toggle" id="mobileFilterToggle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="4" y1="21" x2="4" y2="14"></line>
                            <line x1="4" y1="10" x2="4" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12" y2="3"></line>
                            <line x1="20" y1="21" x2="20" y2="16"></line>
                            <line x1="20" y1="12" x2="20" y2="3"></line>
                            <line x1="1" y1="14" x2="7" y2="14"></line>
                            <line x1="9" y1="8" x2="15" y2="8"></line>
                            <line x1="17" y1="16" x2="23" y2="16"></line>
                        </svg>
                        Filters
                    </button>
                    
                    <div class="filter-content" id="filterContent">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label class="filter-label">Sport</label>
                                <div class="select-wrapper">
                                    <select name="category" class="filter-select">
                                        <option value="0">All Sports</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $category_filter ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="select-arrow">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">Price</label>
                                <div class="select-wrapper">
                                    <select name="price" class="filter-select">
                                        <option value="all" <?= $price_filter === 'all' ? 'selected' : '' ?>>All</option>
                                        <option value="under100" <?= $price_filter === 'under100' ? 'selected' : '' ?>>Under $100</option>
                                        <option value="100-200" <?= $price_filter === '100-200' ? 'selected' : '' ?>>$100–$200</option>
                                        <option value="200-300" <?= $price_filter === '200-300' ? 'selected' : '' ?>>$200–$300</option>
                                        <option value="over300" <?= $price_filter === 'over300' ? 'selected' : '' ?>>Over $300</option>
                                    </select>
                                    <div class="select-arrow">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">Sort</label>
                                <div class="select-wrapper">
                                    <select name="sort" class="filter-select">
                                        <option value="featured" <?= $sort_by === 'featured' ? 'selected' : '' ?>>Featured</option>
                                        <option value="price_low" <?= $sort_by === 'price_low' ? 'selected' : '' ?>>Price: Low–High</option>
                                        <option value="price_high" <?= $sort_by === 'price_high' ? 'selected' : '' ?>>Price: High–Low</option>
                                        <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Newest</option>
                                    </select>
                                    <div class="select-arrow">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-group search-group">
                                <label class="filter-label">Search</label>
                                <div class="search-wrapper">
                                    <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>" class="search-input">
                                    <button type="submit" class="search-button">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="filter-actions">
                                <button type="submit" class="btn-filter">Apply Filters</button>
                                <?php if ($category_filter > 0 || $price_filter !== 'all' || $sort_by !== 'featured' || !empty($search)): ?>
                                    <a href="shop.php" class="btn-reset">Reset</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Product Grid (unchanged from original) -->
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" onclick="window.location.href='product.php?id=<?= $product['id'] ?>'">
                        <div class="product-img-container">
                            <?php if (!empty($product['badge'])): ?>
                                <div class="product-badge"><?= htmlspecialchars($product['badge']) ?></div>
                            <?php endif; ?>
                            <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='assets/images/placeholder.png';">
                        </div>
                        <div class="product-details">
                            <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="product-category"><?= htmlspecialchars($product['category_name']) ?></p>
                            <p class="product-price">$<?= number_format($product['price'], 2) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>

<!-- Styles -->
<style>
body {
    font-family: 'Inter', sans-serif;
    color: #333;
}
.container {
    max-width: 1200px;
    margin: auto;
    padding: 0 1rem;
}
.section-header {
    margin-bottom: 2rem;
    text-align: center;
}
.section-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #1a1a1a;
}
.section-subtitle {
    font-size: 1rem;
    color: #666;
}

/* Enhanced Filter Styles */
.filter-container {
    margin-bottom: 2rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    padding: 1rem;
}

.mobile-filter-toggle {
    display: none;
    width: 100%;
    padding: 0.75rem;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-weight: 600;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    cursor: pointer;
}

.filter-content {
    transition: all 0.3s ease;
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.filter-group {
    flex: 1 1 180px;
    min-width: 0;
}

.filter-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #555;
}

.select-wrapper {
    position: relative;
}

.filter-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background-color: #fff;
    font-size: 0.9rem;
    appearance: none;
    transition: border-color 0.2s;
}

.filter-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.select-arrow {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #666;
}

.search-group {
    flex: 2 1 240px;
}

.search-wrapper {
    position: relative;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.search-button {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    padding: 0;
    color: #666;
    cursor: pointer;
}

.filter-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    margin-left: auto;
}

.btn-filter {
    padding: 0.75rem 1.5rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    white-space: nowrap;
}

.btn-filter:hover {
    background: #2563eb;
}

.btn-reset {
    padding: 0.75rem 1rem;
    color: #666;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
    white-space: nowrap;
}

.btn-reset:hover {
    color: #3b82f6;
}

/* Product Grid (unchanged from original) */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}
.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
    cursor: pointer;
}
.product-card:hover {
    transform: translateY(-4px);
}
.product-img-container {
    position: relative;
    height: 220px;
    overflow: hidden;
    background: #f9fafb;
}
.product-img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.product-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #ef4444;
    color: white;
    padding: 0.3rem 0.75rem;
    font-size: 0.75rem;
    border-radius: 20px;
    text-transform: uppercase;
    font-weight: 600;
}
.product-details {
    padding: 1rem;
    text-align: center;
}
.product-name {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 0.25rem;
}
.product-category {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
}
.product-price {
    font-size: 1.1rem;
    color: #1e3a8a;
    font-weight: bold;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .mobile-filter-toggle {
        display: flex;
    }
    
    .filter-content {
        display: none;
        padding-top: 0;
    }
    
    .filter-content.active {
        display: block;
    }
    
    .filter-row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .filter-group {
        flex: 1 1 auto;
        width: 100%;
    }
    
    .filter-actions {
        margin-left: 0;
        width: 100%;
        justify-content: flex-end;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn-filter, .btn-reset {
        width: 100%;
        text-align: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileFilterToggle = document.getElementById('mobileFilterToggle');
    const filterContent = document.getElementById('filterContent');
    
    if (mobileFilterToggle && filterContent) {
        mobileFilterToggle.addEventListener('click', function() {
            filterContent.classList.toggle('active');
        });
    }
});
</script>

<?php require_once 'footer.php'; ?>