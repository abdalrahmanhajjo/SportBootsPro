<?php 
require_once 'header.php';

// Redirect if no product ID provided
if (!isset($_GET['id'])) {
    header("Location: shop.php");
    exit();
}

$product_id = intval($_GET['id']);
$product = null;

// Fetch product with category information
$stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
} else {
    header("Location: shop.php");
    exit();
}

// Parse product sizes into array
$product_sizes = array_map('trim', explode(',', $product['sizes'] ?? ''));
$all_sizes = ['38', '39', '40', '41', '42', '43', '44'];

// Check if product has any available sizes
$has_available_sizes = !empty(array_intersect($product_sizes, $all_sizes));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Product Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<!-- Page Content -->
<div class="page-content">
    <!-- Breadcrumb Navigation -->
    <nav class="breadcrumb-nav">
        <div class="container">
            <a href="index.php" class="breadcrumb-link">
                <i class="fas fa-home"></i> Home
            </a>
            <span class="breadcrumb-separator">/</span>
            <a href="shop.php" class="breadcrumb-link">Shop</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= htmlspecialchars($product['category_name']) ?></span>
        </div>
    </nav>

    <section class="product-section">
        <div class="container">
            <div class="product-detail-card">
                <!-- Product Image Section -->
               <!-- Product Image Section -->
<div class="product-gallery">
    <div class="product-main-image">
        <div class="image-container">
            <?php if ($product['image_url']): ?>
                <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='assets/images/placeholder.png';">
            <?php else: ?>
                <div class="image-placeholder">
                    <i class="fas fa-image"></i>
                </div>
            <?php endif; ?>
            
            <?php if ($product['badge']): ?>
                <div class="product-badge-overlay">
                    <span class="badge"><?= htmlspecialchars($product['badge']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
                
                <!-- Product Information Section -->
                <div class="product-info">
                    <div class="product-header">
                        <div class="product-category-tag">
                            <i class="fas fa-tag"></i>
                            <?= htmlspecialchars($product['category_name']) ?>
                        </div>
                        
                        <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
                        
                        <div class="product-meta">
                            <div class="product-price">
                                <span class="price-amount">$<?= number_format($product['price'], 2) ?></span>
                            </div>
                            
                            <div class="product-stock">
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <span class="stock-status in-stock">
                                        <i class="fas fa-check-circle"></i>
                                        In Stock (<?= $product['stock_quantity'] ?> available)
                                    </span>
                                <?php else: ?>
                                    <span class="stock-status out-of-stock">
                                        <i class="fas fa-times-circle"></i>
                                        Out of Stock
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Product Description -->
                    <div class="product-details">
                        <div class="detail-section">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Description
                            </h3>
                            <p class="description-text"><?= htmlspecialchars($product['description']) ?></p>
                        </div>
                        
                        <div class="detail-section">
                            <h3 class="section-title">
                                <i class="fas fa-star"></i>
                                Features
                            </h3>
                            <ul class="features-list">
                                <?php 
                                $features = explode("\n", $product['features']);
                                foreach ($features as $feature) {
                                    $trimmed = trim($feature);
                                    if ($trimmed) {
                                        echo '<li><i class="fas fa-check"></i>' . htmlspecialchars($trimmed) . '</li>';
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Purchase Form -->
                    <div class="purchase-section">
                        <form method="post" action="cart.php?action=add" class="purchase-form">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                            <!-- Size Selection -->
                            <div class="form-group size-selection">
                                <label for="size" class="form-label">
                                    <i class="fas fa-ruler"></i>
                                    Select Size <span class="required">*</span>
                                </label>
                                <div class="size-options">
                                    <?php foreach ($all_sizes as $size): ?>
                                        <?php $available = in_array($size, $product_sizes); ?>
                                        <label class="size-option <?= !$available ? 'disabled' : '' ?>">
                                            <input type="radio" name="size" value="<?= $available ? $size : '' ?>" 
                                                   <?= !$available ? 'disabled' : '' ?> required>
                                            <span class="size-label"><?= $size ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (!$has_available_sizes): ?>
                                    <p class="size-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        No sizes currently available
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Quantity Selection -->
                            <div class="form-group quantity-selection">
                                <label for="quantity" class="form-label">
                                    <i class="fas fa-calculator"></i>
                                    Quantity
                                </label>
                                <div class="quantity-controls">
                                    <button type="button" class="quantity-btn minus" aria-label="Decrease quantity">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" name="quantity" value="1" min="1" 
                                           max="<?= $product['stock_quantity'] ?>" class="quantity-input" readonly>
                                    <button type="button" class="quantity-btn plus" aria-label="Increase quantity">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="action-buttons">
                                <button type="submit" class="btn btn-primary btn-add-to-cart" 
                                        <?= ($product['stock_quantity'] <= 0 || !$has_available_sizes) ? 'disabled' : '' ?>>
                                    <i class="fas fa-shopping-cart"></i>
                                    <span>Add to Cart</span>
                                </button>
                                
                                <button type="button" class="btn btn-secondary btn-wishlist">
                                    <i class="far fa-heart"></i>
                                    <span>Add to Wishlist</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Additional Product Info -->
                    <div class="additional-info">
                        <div class="info-item">
                            <i class="fas fa-truck"></i>
                            <span>Free shipping on orders over $100</span>
                        </div>
                
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Enhanced Styles -->
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: #333;
    background-color: #f8fafc;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Breadcrumb Navigation */
.breadcrumb-nav {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 0;
    margin-bottom: 2rem;
}

.breadcrumb-link {
    color: #64748b;
    text-decoration: none;
    transition: color 0.2s;
}

.breadcrumb-link:hover {
    color: #3b82f6;
}

.breadcrumb-separator {
    margin: 0 0.5rem;
    color: #cbd5e1;
}

.breadcrumb-current {
    color: #1e293b;
    font-weight: 500;
}

/* Product Section */
.product-section {
    padding: 2rem 0;
}

.product-detail-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    min-height: 600px;
}

/* Product Gallery */
.product-gallery {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    position: relative;
}

.image-container {
    position: relative;
    width: 100%;
    max-width: 400px;
    text-align: center;
}

.product-main-image img,
.product-main-image svg {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
    transition: transform 0.3s ease;
}

.product-main-image:hover img,
.product-main-image:hover svg {
    transform: scale(1.05);
}

.product-badge-overlay {
    position: absolute;
    top: 1rem;
    right: 1rem;
}

.badge {
    background: #ef4444;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Product Info */
.product-info {
    padding: 2rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.product-header {
    margin-bottom: 2rem;
}

.product-category-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #f1f5f9;
    color: #475569;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 1rem;
}

.product-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 1rem;
    line-height: 1.2;
}

.product-meta {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.product-price {
    font-size: 2rem;
    font-weight: 700;
    color: #dc2626;
}

.stock-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.stock-status.in-stock {
    color: #059669;
}

.stock-status.out-of-stock {
    color: #dc2626;
}

/* Product Details */
.product-details {
    margin-bottom: 2rem;
}

.detail-section {
    margin-bottom: 1.5rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1rem;
}

.description-text {
    color: #64748b;
    line-height: 1.7;
}

.features-list {
    list-style: none;
}

.features-list li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    color: #475569;
}

.features-list li i {
    color: #059669;
    font-size: 0.875rem;
}

/* Purchase Section */
.purchase-section {
    border-top: 1px solid #e2e8f0;
    padding-top: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.75rem;
}

.required {
    color: #dc2626;
}

/* Size Selection */
.size-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
    gap: 0.5rem;
}

.size-option {
    position: relative;
    cursor: pointer;
}

.size-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.size-label {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 48px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s;
    background: #fff;
}

.size-option:not(.disabled):hover .size-label {
    border-color: #3b82f6;
    background: #eff6ff;
}

.size-option input:checked + .size-label {
    border-color: #3b82f6;
    background: #3b82f6;
    color: white;
}

.size-option.disabled .size-label {
    background: #f1f5f9;
    color: #94a3b8;
    cursor: not-allowed;
}

.size-warning {
    color: #f59e0b;
    font-size: 0.875rem;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Quantity Selection */
.quantity-controls {
    display: flex;
    align-items: center;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    width: fit-content;
}

.quantity-btn {
    background: #f8fafc;
    border: none;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s;
    color: #475569;
}

.quantity-btn:hover {
    background: #e2e8f0;
}

.quantity-input {
    border: none;
    width: 80px;
    height: 48px;
    text-align: center;
    font-weight: 600;
    color: #1e293b;
    background: #fff;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
}

.btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    flex: 1;
    min-height: 56px;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-secondary {
    background: #f8fafc;
    color: #475569;
    border: 2px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Additional Info */
.additional-info {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 2rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    color: #475569;
    font-size: 0.875rem;
}

.info-item i {
    color: #3b82f6;
    width: 16px;
}

/* Mobile Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 0 0.5rem;
    }

    .breadcrumb-nav {
        padding: 0.75rem 0;
        margin-bottom: 1rem;
    }

    .product-section {
        padding: 1rem 0;
    }

    .product-detail-card {
        grid-template-columns: 1fr;
        border-radius: 12px;
        margin: 0 0.5rem;
    }

    .product-gallery {
        padding: 1.5rem;
        min-height: 300px;
    }

    .product-info {
        padding: 1.5rem;
    }

    .product-title {
        font-size: 1.5rem;
    }

    .product-price {
        font-size: 1.5rem;
    }

    .action-buttons {
        flex-direction: column;
    }

    .size-options {
        grid-template-columns: repeat(4, 1fr);
    }

    .additional-info {
        margin-top: 1rem;
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .product-detail-card {
        margin: 0;
        border-radius: 0;
    }

    .product-gallery {
        padding: 1rem;
    }

    .product-info {
        padding: 1rem;
    }

    .product-title {
        font-size: 1.25rem;
    }

    .size-options {
        grid-template-columns: repeat(3, 1fr);
    }

    .btn {
        padding: 0.875rem 1.5rem;
        font-size: 0.875rem;
    }
}

/* Loading and Animation States */
.product-detail-card {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Focus States for Accessibility */
.size-option input:focus-visible + .size-label {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

.btn:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

.quantity-btn:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
</style>

<!-- Enhanced JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quantity controls
    const quantityBtns = document.querySelectorAll('.quantity-btn');
    const quantityInput = document.querySelector('.quantity-input');
    const maxQuantity = parseInt(quantityInput.getAttribute('max'));

    quantityBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value);
            
            if (btn.classList.contains('plus')) {
                if (currentValue < maxQuantity) {
                    quantityInput.value = currentValue + 1;
                }
            } else if (btn.classList.contains('minus')) {
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                }
            }
            
            // Update button states
            updateQuantityButtons();
        });
    });

    function updateQuantityButtons() {
        const currentValue = parseInt(quantityInput.value);
        const minusBtn = document.querySelector('.quantity-btn.minus');
        const plusBtn = document.querySelector('.quantity-btn.plus');
        
        minusBtn.style.opacity = currentValue <= 1 ? '0.5' : '1';
        plusBtn.style.opacity = currentValue >= maxQuantity ? '0.5' : '1';
    }

    // Initialize button states
    updateQuantityButtons();

    // Form validation
    const form = document.querySelector('.purchase-form');
    const sizeInputs = document.querySelectorAll('input[name="size"]');
    const submitBtn = document.querySelector('.btn-add-to-cart');

    form.addEventListener('submit', function(e) {
        const selectedSize = document.querySelector('input[name="size"]:checked');
        
        if (!selectedSize) {
            e.preventDefault();
            alert('Please select a size before adding to cart.');
            return false;
        }
    });

    // Size selection visual feedback
    sizeInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Remove previous selection styling
            document.querySelectorAll('.size-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selection styling to current
            if (this.checked) {
                this.closest('.size-option').classList.add('selected');
            }
        });
    });

    // Wishlist functionality (placeholder)
    const wishlistBtn = document.querySelector('.btn-wishlist');
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            const text = this.querySelector('span');
            
            if (icon.classList.contains('far')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                text.textContent = 'Added to Wishlist';
                this.style.background = '#fef2f2';
                this.style.color = '#dc2626';
                this.style.borderColor = '#fecaca';
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                text.textContent = 'Add to Wishlist';
                this.style.background = '#f8fafc';
                this.style.color = '#475569';
                this.style.borderColor = '#e2e8f0';
            }
        });
    }

    // Image loading animation
    const productImage = document.querySelector('.product-main-image img, .product-main-image svg');
    if (productImage) {
        productImage.style.opacity = '0';
        productImage.style.transform = 'scale(0.9)';
        
        setTimeout(() => {
            productImage.style.transition = 'all 0.5s ease';
            productImage.style.opacity = '1';
            productImage.style.transform = 'scale(1)';
        }, 100);
    }
});
</script>

<?php require_once 'footer.php'; ?>