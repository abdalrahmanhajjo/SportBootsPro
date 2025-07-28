<?php 
ob_start(); // Add this at the very top
require_once 'header.php';
// ... rest of your code

// Handle cart actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'add':
            if (isset($_POST['product_id']) && isset($_POST['quantity']) && isset($_POST['size'])) {
                $product_id = intval($_POST['product_id']);
                $quantity = intval($_POST['quantity']);
                $size = $_POST['size'];
                
                // Check if product exists and has stock
                $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND stock_quantity >= ?");
                $stmt->bind_param("ii", $product_id, $quantity);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    
                    // Create unique cart key with product ID and size
                    $cart_key = $product_id . '_' . $size;
                    
                    // Add to cart
                    if (isset($_SESSION['cart'][$cart_key])) {
                        $_SESSION['cart'][$cart_key]['quantity'] += $quantity;
                    } else {
                        $_SESSION['cart'][$cart_key] = [
                            'id' => $product_id,
                            'name' => $product['name'],
                            'price' => $product['price'],
                            'quantity' => $quantity,
                            'size' => $size,
                            'image' => $product['icon'] ?? $product['image_url'] ?? '👟'
                        ];
                    }
                    
                    $_SESSION['message'] = [
                        'type' => 'success',
                        'text' => 'Product added to cart!'
                    ];
                } else {
                    $_SESSION['message'] = [
                        'type' => 'error',
                        'text' => 'Product not available in requested quantity'
                    ];
                }
            } else {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'Please select a size'
                ];
            }
            // Clear POST data and redirect to prevent form resubmission
            header("Location: cart.php");
            exit();
            
        case 'remove':
            if (isset($_GET['id'])) {
                $cart_key = $_GET['id'];
                if (isset($_SESSION['cart'][$cart_key])) {
                    unset($_SESSION['cart'][$cart_key]);
                    $_SESSION['message'] = [
                        'type' => 'success',
                        'text' => 'Product removed from cart'
                    ];
                }
            }
            header("Location: cart.php");
            exit();
            
        case 'update':
            if (isset($_POST['quantities'])) {
                foreach ($_POST['quantities'] as $cart_key => $quantity) {
                    $quantity = intval($quantity);
                    
                    if (isset($_SESSION['cart'][$cart_key])) {
                        // Extract product_id from cart_key
                        $product_id = $_SESSION['cart'][$cart_key]['id'];
                        
                        // Check stock before updating
                        $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
                        $stmt->bind_param("i", $product_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $product = $result->fetch_assoc();
                            if ($quantity <= $product['stock_quantity'] && $quantity > 0) {
                                $_SESSION['cart'][$cart_key]['quantity'] = $quantity;
                            } else {
                                $_SESSION['message'] = [
                                    'type' => 'error',
                                    'text' => 'Not enough stock for some items'
                                ];
                            }
                        }
                    }
                }
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Cart updated successfully'
                ];
            }
            header("Location: cart.php");
            exit();
    }
}

// Calculate totals
$subtotal = 0;
$total_items = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
        $total_items += $item['quantity'];
    }
}

$shipping = $subtotal > 100 ? 0 : 10; // Free shipping over $100
$total = $subtotal + $shipping;
?>

<style>
/* Mobile-First Responsive Styles */
.cart-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    margin-top: 2rem;
}

.cart-items {
    width: 100%;
}

.cart-summary {
    width: 100%;
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    position: sticky;
    top: 2rem;
}

/* Mobile Cart Table - Hidden on small screens */
.cart-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
    display: none;
}

.cart-table th,
.cart-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.cart-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.product-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.product-image {
    font-size: 2rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 8px;
}

.product-details {
    display: flex;
    flex-direction: column;
}

.product-name {
    font-weight: 500;
    color: #212529;
}

.product-size {
    font-size: 0.875rem;
    color: #6c757d;
}

.quantity-input {
    width: 60px;
    padding: 0.5rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    text-align: center;
}

.remove-btn {
    color: #dc3545;
    text-decoration: none;
    font-size: 1.5rem;
    font-weight: bold;
    padding: 0.5rem;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.remove-btn:hover {
    background-color: #f8d7da;
}

/* Mobile Cart Items */
.mobile-cart-items {
    display: block;
}

.mobile-cart-item {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mobile-item-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.mobile-product-image {
    font-size: 2.5rem;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 8px;
    flex-shrink: 0;
}

.mobile-product-details {
    flex: 1;
}

.mobile-product-name {
    font-weight: 600;
    font-size: 1.1rem;
    color: #212529;
    margin-bottom: 0.25rem;
}

.mobile-product-size {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.mobile-product-price {
    color: #007bff;
    font-weight: 600;
    font-size: 1.1rem;
}

.mobile-item-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.mobile-quantity-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mobile-quantity-control label {
    font-weight: 500;
    color: #495057;
}

.mobile-quantity-control input {
    width: 60px;
    padding: 0.5rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    text-align: center;
}

.mobile-item-total {
    font-weight: 600;
    color: #28a745;
    font-size: 1.1rem;
}

.mobile-remove-btn {
    color: #dc3545;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border: 1px solid #dc3545;
    border-radius: 4px;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.mobile-remove-btn:hover {
    background-color: #dc3545;
    color: white;
}

/* Cart Actions */
.cart-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.cart-actions .btn {
    flex: 1;
    min-width: 140px;
    text-align: center;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-primary {
    background-color: #007bff;
    color: white;
    border: 1px solid #007bff;
}

.btn-primary:hover {
    background-color: #0056b3;
    border-color: #0056b3;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    border: 1px solid #6c757d;
}

.btn-secondary:hover {
    background-color: #545b62;
    border-color: #545b62;
}

/* Cart Summary */
.cart-summary h3 {
    margin-bottom: 1rem;
    color: #212529;
    font-size: 1.25rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.summary-row.total {
    font-weight: 600;
    font-size: 1.1rem;
    color: #212529;
    border-bottom: none;
    padding-top: 1rem;
    margin-top: 0.5rem;
    border-top: 2px solid #dee2e6;
}

.btn-large {
    width: 100%;
    padding: 1rem;
    font-size: 1.1rem;
    margin-top: 1rem;
    border-radius: 6px;
    text-decoration: none;
    display: block;
    text-align: center;
}

/* Empty Cart */
.empty-cart {
    text-align: center;
    padding: 3rem 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 2rem;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-cart h3 {
    color: #495057;
    margin-bottom: 1rem;
}

.empty-cart p {
    color: #6c757d;
    margin-bottom: 2rem;
}

/* Alert Messages */
.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Responsive Design */
@media (min-width: 768px) {
    .cart-container {
        flex-direction: row;
        align-items: flex-start;
    }
    
    .cart-items {
        flex: 2;
    }
    
    .cart-summary {
        flex: 1;
        max-width: 400px;
    }
    
    .cart-table {
        display: table;
    }
    
    .mobile-cart-items {
        display: none;
    }
    
    .cart-actions {
        justify-content: flex-start;
    }
    
    .cart-actions .btn {
        flex: none;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 0 1rem;
    }
    
    .mobile-item-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .mobile-quantity-control {
        justify-content: space-between;
    }
    
    .cart-actions {
        flex-direction: column;
    }
    
    .cart-actions .btn {
        width: 100%;
    }
}
</style>

<!-- Page Content -->
<div class="page-content">
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h1 class="section-title">Your Cart</h1>
                <p class="section-subtitle">
                    Review your selected products
                </p>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message']['type'] ?>">
                    <?= $_SESSION['message']['text'] ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if ($total_items > 0): ?>
                <div class="cart-container">
                    <div class="cart-items">
                        <form method="post" action="cart.php?action=update">
                            <!-- Desktop Table View -->
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['cart'] as $cart_key => $item): ?>
                                        <tr>
                                            <td class="product-info">
                                                <div class="product-image"><?= htmlspecialchars($item['image']) ?></div>
                                                <div class="product-details">
                                                    <div class="product-name"><?= htmlspecialchars($item['name']) ?></div>
                                                    <div class="product-size">Size: <?= htmlspecialchars($item['size']) ?></div>
                                                </div>
                                            </td>
                                            <td class="product-price">$<?= number_format($item['price'], 2) ?></td>
                                            <td class="product-quantity">
                                                <input type="number" name="quantities[<?= htmlspecialchars($cart_key) ?>]" 
                                                       value="<?= $item['quantity'] ?>" min="1" class="quantity-input">
                                            </td>
                                            <td class="product-total">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                            <td class="product-remove">
                                                <a href="cart.php?action=remove&id=<?= urlencode($cart_key) ?>" class="remove-btn">×</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <!-- Mobile Card View -->
                            <div class="mobile-cart-items">
                                <?php foreach ($_SESSION['cart'] as $cart_key => $item): ?>
                                    <div class="mobile-cart-item">
                                        <div class="mobile-item-header">
                                            <div class="mobile-product-image"><?= htmlspecialchars($item['image']) ?></div>
                                            <div class="mobile-product-details">
                                                <div class="mobile-product-name"><?= htmlspecialchars($item['name']) ?></div>
                                                <div class="mobile-product-size">Size: <?= htmlspecialchars($item['size']) ?></div>
                                                <div class="mobile-product-price">$<?= number_format($item['price'], 2) ?></div>
                                            </div>
                                        </div>
                                        <div class="mobile-item-controls">
                                            <div class="mobile-quantity-control">
                                                <label>Qty:</label>
                                                <input type="number" name="quantities[<?= htmlspecialchars($cart_key) ?>]" 
                                                       value="<?= $item['quantity'] ?>" min="1">
                                            </div>
                                            <div class="mobile-item-total">$<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                                            <a href="cart.php?action=remove&id=<?= urlencode($cart_key) ?>" class="mobile-remove-btn">Remove</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="cart-actions">
                                <a href="shop.php" class="btn btn-secondary">Continue Shopping</a>
                                <button type="submit" class="btn btn-primary">Update Cart</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="cart-summary">
                        <h3>Order Summary</h3>
                        <div class="summary-row">
                            <span>Items (<?= $total_items ?>)</span>
                            <span>$<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span><?= $shipping == 0 ? 'FREE' : '$' . number_format($shipping, 2) ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                        <a href="checkout.php" class="btn btn-primary btn-large">Proceed to Checkout</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <div class="empty-icon">🛒</div>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added any items to your cart yet</p>
                    <a href="shop.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once 'footer.php'; ?>