<?php
// Enable error reporting for debugging


// MUST be the very first line - no whitespace before!
ob_start(); // Start output buffering
session_start();

require_once 'header.php';

// Database connection
try {
    // Update these with your database credentials
    $host = 'sql209.infinityfree.com';
    $dbname = 'if0_39222248_sportbootspro';
    $username = 'if0_39222248';
    $password = '76536462Ah';
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    header("Location: cart.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    $required_fields = ['name', 'email', 'phone', 'address'];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = "This field is required";
        }
    }

    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }

    if (empty($errors)) {
        try {
            // Start database transaction
            $conn->beginTransaction();

            // First, validate stock availability for all items
            $stockErrors = [];
            foreach ($_SESSION['cart'] as $item) {
                $checkStockStmt = $conn->prepare("
                    SELECT stock_quantity, name FROM products WHERE id = ?
                ");
                $checkStockStmt->execute([$item['id'] ?? 1]);
                $product = $checkStockStmt->fetch();
                
                if ($product && $product['stock_quantity'] < $item['quantity']) {
                    $stockErrors[] = "Only {$product['stock_quantity']} units of {$product['name']} available (you requested {$item['quantity']})";
                }
            }
            
            if (!empty($stockErrors)) {
                throw new Exception("Stock issues: \n" . implode("\n", $stockErrors));
            }

            // Check if user exists or create guest user
            $user_id = null;
            
            if (isset($_SESSION['user_id'])) {
                // If user is logged in, use their ID
                $user_id = $_SESSION['user_id'];
                
                // Update user information if provided
                $updateUserStmt = $conn->prepare("
                    UPDATE users 
                    SET full_name = ?, phone = ?, address = ? 
                    WHERE id = ?
                ");
                $updateUserStmt->execute([
                    $_POST['name'],
                    $_POST['phone'],
                    $_POST['address'],
                    $user_id
                ]);
            } else {
                // Check if email exists
                $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $checkEmailStmt->execute([$_POST['email']]);
                $existingUser = $checkEmailStmt->fetch();
                
                if ($existingUser) {
                    $user_id = $existingUser['id'];
                    // Update existing user info
                    $updateUserStmt = $conn->prepare("
                        UPDATE users 
                        SET full_name = ?, phone = ?, address = ? 
                        WHERE id = ?
                    ");
                    $updateUserStmt->execute([
                        $_POST['name'],
                        $_POST['phone'],
                        $_POST['address'],
                        $user_id
                    ]);
                } else {
                    // Create guest user account
                    $insertUserStmt = $conn->prepare("
                        INSERT INTO users (username, email, password, full_name, phone, address) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    // Generate a unique username for guest
                    $username = 'guest_' . uniqid();
                    // Set a random password for guest account (they can reset later)
                    $guestPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                    
                    $insertUserStmt->execute([
                        $username,
                        $_POST['email'],
                        $guestPassword,
                        $_POST['name'],
                        $_POST['phone'],
                        $_POST['address']
                    ]);
                    
                    $user_id = $conn->lastInsertId();
                }
            }

            // Calculate totals
            $subtotal = 0;
            $items_list = "";
            $cart_items = [];

            foreach ($_SESSION['cart'] as $item) {
                $subtotal += $item['price'] * $item['quantity'];
                $items_list .= "• " . $item['name'] .
                             " (Size: " . ($item['size'] ?? 'N/A') . 
                             ", Qty: " . $item['quantity'] . 
                             ") - $" . number_format($item['price'] * $item['quantity'], 2) . "\n";
                
                // Store for database insertion
                $cart_items[] = [
                    'product_id' => $item['id'] ?? 1, // Use 1 as default if ID is missing
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'size' => $item['size'] ?? null
                ];
            }

            $total = $subtotal;

            // Insert order into database with customer information
            $insertOrderStmt = $conn->prepare("
                INSERT INTO orders (user_id, total_amount, status, shipping_address, customer_name, customer_phone, customer_email) 
                VALUES (?, ?, 'pending', ?, ?, ?, ?)
            ");
            
            $insertOrderStmt->execute([
                $user_id,
                $total,
                $_POST['address'],
                $_POST['name'],
                $_POST['phone'],
                $_POST['email']
            ]);
            
            $order_id = $conn->lastInsertId();

            // Insert order items
            $insertOrderItemStmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($cart_items as $item) {
                $insertOrderItemStmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);
                
                // Update product stock - check if enough stock is available
                $checkStockStmt = $conn->prepare("
                    SELECT stock_quantity, name FROM products WHERE id = ?
                ");
                $checkStockStmt->execute([$item['product_id']]);
                $product = $checkStockStmt->fetch();
                
                if ($product) {
                    if ($product['stock_quantity'] < $item['quantity']) {
                        // Not enough stock - rollback and show error
                        throw new Exception("Sorry, we only have {$product['stock_quantity']} units of {$product['name']} in stock.");
                    }
                    
                    // Update the stock quantity
                    $updateStockStmt = $conn->prepare("
                        UPDATE products 
                        SET stock_quantity = stock_quantity - ? 
                        WHERE id = ?
                    ");
                    $updateStockStmt->execute([
                        $item['quantity'],
                        $item['product_id']
                    ]);
                    
                    // Log stock update for debugging
                    error_log("Updated stock for product ID {$item['product_id']}: reduced by {$item['quantity']} units");
                } else {
                    // Product not found - this shouldn't happen but handle it
                    throw new Exception("Product with ID {$item['product_id']} not found in database.");
                }
            }

            // Clear cart from database if user is logged in
            if (isset($_SESSION['user_id'])) {
                $clearCartStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $clearCartStmt->execute([$user_id]);
            }

            // Commit transaction
            $conn->commit();

            // Log success for debugging
            error_log("Order successfully created with ID: " . $order_id);

            // Prepare WhatsApp message with order ID
            $whatsapp_message = "🛒 *NEW ORDER #" . $order_id . "* 🛒\n\n";
            $whatsapp_message .= "👤 *Customer Information:*\n";
            $whatsapp_message .= "• Name: " . $_POST['name'] . "\n";
            $whatsapp_message .= "• Email: " . $_POST['email'] . "\n";
            $whatsapp_message .= "• Phone: " . $_POST['phone'] . "\n";
            $whatsapp_message .= "• Address: " . $_POST['address'] . "\n\n";

            $whatsapp_message .= "📦 *Order Items:*\n";
            $whatsapp_message .= $items_list . "\n";

            $whatsapp_message .= "💰 *Order Total:* $" . number_format($total, 2) . "\n\n";
            $whatsapp_message .= "💳 *Payment Method:* Cash on Delivery\n\n";
            $whatsapp_message .= "Thank you!";

            $encoded_message = rawurlencode($whatsapp_message);
            $whatsapp_url = "https://wa.me/96176536462?text=$encoded_message";

            // Clear the output buffer before redirect
            ob_end_clean();
            
            // Clear the session cart
            unset($_SESSION['cart']);
            
            // Store order success message in session
            $_SESSION['order_success'] = true;
            $_SESSION['order_id'] = $order_id;
            
            // Redirect to WhatsApp
            header("Location: $whatsapp_url");
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors['database'] = "An error occurred while processing your order. Please try again.";
            
            // Log the error for debugging
            error_log("Checkout error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            
            // For debugging, show the actual error (remove in production)
            $errors['debug'] = "Debug info: " . $e->getMessage();
        }
    }
}

// Calculate totals for display
$subtotal = 0;
$total_items = 0;
$stock_info = [];

// Get current stock levels for all cart items
foreach ($_SESSION['cart'] as $key => $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
    
    // Get current stock for this product
    if (isset($conn) && isset($item['id'])) {
        try {
            $stockStmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stockStmt->execute([$item['id']]);
            $stockData = $stockStmt->fetch();
            if ($stockData) {
                $stock_info[$key] = $stockData['stock_quantity'];
            }
        } catch (Exception $e) {
            // Silently fail if database not available
        }
    }
}
$shipping = 0;
$total = $subtotal;

// Flush the output buffer before HTML
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - SportBoots Pro</title>
    <style>
    /* Your existing CSS styles here */
    .checkout-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
        margin-top: 2rem;
    }
    
    @media (min-width: 992px) {
        .checkout-container {
            grid-template-columns: 2fr 1fr;
        }
    }
    
    .checkout-form, .order-summary {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    
    .form-input, .form-textarea {
        width: 100%;
        padding: 1rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }
    
    .form-textarea {
        min-height: 100px;
    }
    
    .error-message {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: block;
    }
    
    .has-error .form-input, 
    .has-error .form-textarea {
        border-color: #dc3545;
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 1rem 0;
        border-bottom: 1px solid #eee;
    }
    
    .summary-totals {
        margin-top: 2rem;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
    }
    
    .summary-row.total {
        font-weight: bold;
        font-size: 1.1rem;
        border-top: 1px solid #eee;
        margin-top: 0.5rem;
        padding-top: 1rem;
    }
    
    .btn-large {
        display: block;
        width: 100%;
        padding: 1rem;
        background: #28a745;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 1.1rem;
        cursor: pointer;
        text-align: center;
        margin-top: 1rem;
    }
    
    .form-notice {
        margin: 1rem 0;
        font-size: 0.875rem;
        color: #666;
    }
    
    .alert {
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 4px;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    </style>
</head>
<body>
<!-- Page Content -->
<div class="page-content">
    <section class="section">
        <div class="container">
            <?php if (isset($errors['database'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($errors['database']) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errors['debug'])): ?>
                <div class="alert alert-info">
                    <?= htmlspecialchars($errors['debug']) ?>
                </div>
            <?php endif; ?>
            
            <div class="checkout-container">
                <div class="checkout-form">
                    <h2>Shipping Information</h2>
                    <form method="post" action="checkout.php" id="checkoutForm">
                        <div class="form-group <?= isset($errors['name']) ? 'has-error' : '' ?>">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-input" 
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <span class="error-message"><?= $errors['name'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-input" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <span class="error-message"><?= $errors['email'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" name="phone" class="form-input" 
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                            <?php if (isset($errors['phone'])): ?>
                                <span class="error-message"><?= $errors['phone'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group <?= isset($errors['address']) ? 'has-error' : '' ?>">
                            <label class="form-label">Shipping Address *</label>
                            <textarea name="address" class="form-textarea" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                            <?php if (isset($errors['address'])): ?>
                                <span class="error-message"><?= $errors['address'] ?></span>
                            <?php endif; ?>
                        </div>

                        <input type="hidden" name="payment_method" value="cod">

                        <div class="form-notice">
                            <p>* Required fields</p>
                            <p>After placing the order, you'll be redirected to WhatsApp to confirm it.</p>
                        </div>

                        <button type="submit" class="btn btn-primary btn-large">Place Order & Confirm via WhatsApp</button>
                    </form>
                </div>

                <div class="order-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-items">
                        <?php foreach ($_SESSION['cart'] as $key => $item): ?>
                            <div class="summary-item">
                                <div class="item-details">
                                    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <?php if (!empty($item['size'])): ?>
                                        <div class="item-size">Size: <?= htmlspecialchars($item['size']) ?></div>
                                    <?php endif; ?>
                                    <div class="item-quantity">Qty: <?= $item['quantity'] ?></div>
                                    <?php if (isset($stock_info[$key])): ?>
                                        <div class="item-stock" style="color: <?= $stock_info[$key] < $item['quantity'] ? '#dc3545' : '#28a745' ?>; font-size: 0.875rem;">
                                            Stock: <?= $stock_info[$key] ?> available
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-price">$<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-totals">
                        <div class="summary-row"><span>Subtotal</span><span>$<?= number_format($subtotal, 2) ?></span></div>
                        <div class="summary-row"><span>Shipping</span><span><?= $shipping == 0 ? 'FREE' : '$' . number_format($shipping, 2) ?></span></div>
                        <div class="summary-row total"><span>Total</span><span>$<?= number_format($total, 2) ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php 
// Make sure no whitespace after this closing tag
require_once 'footer.php';