<?php
require_once 'config.php';

// Set CORS headers
setCORSHeaders();

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request = $_GET['action'] ?? '';

// Get database connection
$db = getDBConnection();

// Handle different API endpoints
switch($request) {
    
    // Get all products
    case 'get_products':
        if ($method === 'GET') {
            try {
                $category = $_GET['category'] ?? 'all';
                $sort = $_GET['sort'] ?? 'featured';
                $minPrice = $_GET['min_price'] ?? 0;
                $maxPrice = $_GET['max_price'] ?? 9999;
                
                $query = "SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.price BETWEEN :min_price AND :max_price
                          AND p.stock_quantity > 0";
                
                if ($category !== 'all') {
                    $query .= " AND c.name = :category";
                }
                
                // Add sorting
                switch($sort) {
                    case 'price_low':
                        $query .= " ORDER BY p.price ASC";
                        break;
                    case 'price_high':
                        $query .= " ORDER BY p.price DESC";
                        break;
                    case 'newest':
                        $query .= " ORDER BY p.created_at DESC";
                        break;
                    default:
                        $query .= " ORDER BY p.is_featured DESC, p.created_at DESC";
                }
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':min_price', $minPrice);
                $stmt->bindParam(':max_price', $maxPrice);
                
                if ($category !== 'all') {
                    $stmt->bindParam(':category', $category);
                }
                
                $stmt->execute();
                $products = $stmt->fetchAll();
                
                jsonResponse(true, 'Products fetched successfully', $products);
            } catch(Exception $e) {
                jsonResponse(false, 'Error fetching products: ' . $e->getMessage());
            }
        }
        break;
    
    // Get cart items
    case 'get_cart':
        if ($method === 'GET') {
            try {
                $cartItems = $_SESSION['cart'] ?? [];
                $detailedCart = [];
                $total = 0;
                
                if (!empty($cartItems)) {
                    $productIds = array_column($cartItems, 'product_id');
                    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
                    
                    $stmt = $db->prepare("SELECT p.*, c.name as category_name 
                                          FROM products p 
                                          LEFT JOIN categories c ON p.category_id = c.id 
                                          WHERE p.id IN ($placeholders)");
                    $stmt->execute($productIds);
                    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Create a map of products
                    $productMap = [];
                    foreach ($products as $product) {
                        $productMap[$product['id']] = $product;
                    }
                    
                    // Build detailed cart
                    foreach ($cartItems as $item) {
                        if (isset($productMap[$item['product_id']])) {
                            $product = $productMap[$item['product_id']];
                            $subtotal = $product['price'] * $item['quantity'];
                            $total += $subtotal;
                            
                            $detailedCart[] = [
                                'id' => $product['id'],
                                'name' => $product['name'],
                                'price' => $product['price'],
                                'quantity' => $item['quantity'],
                                'subtotal' => $subtotal,
                                'icon' => $product['icon'],
                                'category' => $product['category_name'],
                                'stock' => $product['stock_quantity']
                            ];
                        }
                    }
                }
                
                jsonResponse(true, 'Cart fetched successfully', [
                    'items' => $detailedCart,
                    'total' => $total,
                    'count' => count($detailedCart)
                ]);
            } catch(Exception $e) {
                jsonResponse(false, 'Error fetching cart: ' . $e->getMessage());
            }
        }
        break;
    
    // Add to cart
    case 'add_to_cart':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $productId = $data['product_id'] ?? null;
            $quantity = $data['quantity'] ?? 1;
            
            try {
                // Check product stock
                $stmt = $db->prepare("SELECT stock_quantity, name FROM products WHERE id = :id");
                $stmt->execute([':id' => $productId]);
                $product = $stmt->fetch();
                
                if (!$product) {
                    jsonResponse(false, 'Product not found');
                    return;
                }
                
                if ($product['stock_quantity'] < $quantity) {
                    jsonResponse(false, 'Not enough stock available. Only ' . $product['stock_quantity'] . ' items left.');
                    return;
                }
                
                // Initialize cart if not exists
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                // Check if product already in cart
                $found = false;
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['product_id'] == $productId) {
                        // Check if new quantity exceeds stock
                        if (($item['quantity'] + $quantity) > $product['stock_quantity']) {
                            jsonResponse(false, 'Cannot add more. Stock limit reached.');
                            return;
                        }
                        $item['quantity'] += $quantity;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $_SESSION['cart'][] = [
                        'product_id' => $productId,
                        'quantity' => $quantity
                    ];
                }
                
                jsonResponse(true, 'Product added to cart', [
                    'cart_count' => count($_SESSION['cart']),
                    'product_name' => $product['name']
                ]);
            } catch(Exception $e) {
                jsonResponse(false, 'Error adding to cart: ' . $e->getMessage());
            }
        }
        break;
    
    // Update cart item
    case 'update_cart':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $productId = $data['product_id'] ?? null;
            $quantity = $data['quantity'] ?? 1;
            
            try {
                // Check stock
                $stmt = $db->prepare("SELECT stock_quantity FROM products WHERE id = :id");
                $stmt->execute([':id' => $productId]);
                $product = $stmt->fetch();
                
                if ($product && $product['stock_quantity'] < $quantity) {
                    jsonResponse(false, 'Not enough stock available');
                    return;
                }
                
                if (isset($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as &$item) {
                        if ($item['product_id'] == $productId) {
                            if ($quantity <= 0) {
                                // Remove item
                                $_SESSION['cart'] = array_filter($_SESSION['cart'], function($i) use ($productId) {
                                    return $i['product_id'] != $productId;
                                });
                                $_SESSION['cart'] = array_values($_SESSION['cart']);
                            } else {
                                $item['quantity'] = $quantity;
                            }
                            break;
                        }
                    }
                }
                
                jsonResponse(true, 'Cart updated successfully');
            } catch(Exception $e) {
                jsonResponse(false, 'Error updating cart: ' . $e->getMessage());
            }
        }
        break;
    
    // Remove from cart
    case 'remove_from_cart':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $productId = $data['product_id'] ?? null;
            
            if (isset($_SESSION['cart'])) {
                $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($productId) {
                    return $item['product_id'] != $productId;
                });
                $_SESSION['cart'] = array_values($_SESSION['cart']);
            }
            
            jsonResponse(true, 'Item removed from cart');
        }
        break;
    
    // Clear cart
    case 'clear_cart':
        if ($method === 'POST') {
            $_SESSION['cart'] = [];
            jsonResponse(true, 'Cart cleared');
        }
        break;
    
    // Checkout
    case 'checkout':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $name = sanitizeInput($data['name'] ?? '');
            $email = sanitizeInput($data['email'] ?? '');
            $phone = sanitizeInput($data['phone'] ?? '');
            $address = sanitizeInput($data['address'] ?? '');
            
            if (empty($name) || empty($email) || empty($phone) || empty($address)) {
                jsonResponse(false, 'Please fill in all required fields');
                return;
            }
            
            try {
                $db->beginTransaction();
                
                // Get cart details
                $cartItems = $_SESSION['cart'] ?? [];
                if (empty($cartItems)) {
                    jsonResponse(false, 'Cart is empty');
                    return;
                }
                
                // Calculate total and check stock
                $total = 0;
                $orderDetails = [];
                
                foreach ($cartItems as $item) {
                    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
                    $stmt->execute([':id' => $item['product_id']]);
                    $product = $stmt->fetch();
                    
                    if (!$product || $product['stock_quantity'] < $item['quantity']) {
                        $db->rollBack();
                        jsonResponse(false, 'Product ' . $product['name'] . ' is out of stock');
                        return;
                    }
                    
                    $subtotal = $product['price'] * $item['quantity'];
                    $total += $subtotal;
                    
                    $orderDetails[] = [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                        'subtotal' => $subtotal
                    ];
                }
                
                // Create order
                $stmt = $db->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address) 
                                      VALUES (:user_id, :total, 'pending', :address)");
                $stmt->execute([
                    ':user_id' => $_SESSION['user_id'] ?? null,
                    ':total' => $total,
                    ':address' => $address
                ]);
                
                $orderId = $db->lastInsertId();
                
                // Insert order items and update stock
                $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                      VALUES (:order_id, :product_id, :quantity, :price)");
                
                $updateStock = $db->prepare("UPDATE products SET stock_quantity = stock_quantity - :quantity 
                                             WHERE id = :id");
                
                foreach ($orderDetails as $detail) {
                    // Insert order item
                    $stmt->execute([
                        ':order_id' => $orderId,
                        ':product_id' => $detail['product']['id'],
                        ':quantity' => $detail['quantity'],
                        ':price' => $detail['product']['price']
                    ]);
                    
                    // Update stock
                    $updateStock->execute([
                        ':quantity' => $detail['quantity'],
                        ':id' => $detail['product']['id']
                    ]);
                }
                
                $db->commit();
                
                // Clear cart
                $_SESSION['cart'] = [];
                
                // Prepare WhatsApp message
                $message = "🛍️ *New Order #$orderId*\n\n";
                $message .= "👤 *Customer Details:*\n";
                $message .= "Name: $name\n";
                $message .= "Email: $email\n";
                $message .= "Phone: $phone\n";
                $message .= "Address: $address\n\n";
                $message .= "📦 *Order Items:*\n";
                
                foreach ($orderDetails as $detail) {
                    $message .= "• " . $detail['product']['name'] . " x" . $detail['quantity'] . 
                               " - $" . number_format($detail['subtotal'], 2) . "\n";
                }
                
                $message .= "\n💰 *Total: $" . number_format($total, 2) . "*\n";
                $message .= "💵 *Payment: Cash on Delivery*\n\n";
                $message .= "Please confirm this order!";
                
                // URL encode the message
                $whatsappUrl = "https://wa.me/76176536462?text=" . urlencode($message);
                
                jsonResponse(true, 'Order placed successfully!', [
                    'order_id' => $orderId,
                    'total' => $total,
                    'whatsapp_url' => $whatsappUrl
                ]);
                
            } catch(Exception $e) {
                $db->rollBack();
                jsonResponse(false, 'Error processing order: ' . $e->getMessage());
            }
        }
        break;
    
    // Get cart count
    case 'get_cart_count':
        if ($method === 'GET') {
            $count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
            jsonResponse(true, 'Cart count fetched', ['count' => $count]);
        }
        break;
    
    // Submit contact form
    case 'submit_contact':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $name = sanitizeInput($data['name'] ?? '');
            $email = sanitizeInput($data['email'] ?? '');
            $subject = sanitizeInput($data['subject'] ?? '');
            $message = sanitizeInput($data['message'] ?? '');
            
            if (empty($name) || empty($email) || empty($message)) {
                jsonResponse(false, 'Please fill in all required fields');
            }
            
            try {
                $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':subject' => $subject,
                    ':message' => $message
                ]);
                
                jsonResponse(true, 'Message sent successfully! We\'ll get back to you within 24 hours.');
            } catch(Exception $e) {
                jsonResponse(false, 'Error sending message: ' . $e->getMessage());
            }
        }
        break;
    
    // Get categories
    case 'get_categories':
        if ($method === 'GET') {
            try {
                $stmt = $db->query("SELECT * FROM categories ORDER BY name");
                $categories = $stmt->fetchAll();
                jsonResponse(true, 'Categories fetched successfully', $categories);
            } catch(Exception $e) {
                jsonResponse(false, 'Error fetching categories: ' . $e->getMessage());
            }
        }
        break;
    
    default:
        jsonResponse(false, 'Invalid API endpoint');
}
?>