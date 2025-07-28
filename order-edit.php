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

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    header("Location: orders.php");
    exit();
}

// Fetch order details
$order_stmt = $conn->prepare("
    SELECT o.*, 
           DATE_FORMAT(o.created_at, '%b %e, %Y at %l:%i %p') AS formatted_date
    FROM orders o
    WHERE o.id = ?
");
$order_stmt->execute([$order_id]);
$order = $order_stmt->fetch();

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Fetch order items with product information
$items_stmt = $conn->prepare("
    SELECT oi.*, 
           p.name AS product_name,
           p.image_url AS product_image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id
");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

// Calculate order subtotal
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Set default values for missing fields
$default_values = [
    'payment_method' => 'Credit Card',
    'payment_status' => 'Paid',
    'shipping_method' => 'Standard Shipping',
    'tracking_number' => '',
    'shipping_cost' => 9.99,
    'tax_amount' => round($subtotal * 0.08, 2),
];

foreach ($default_values as $key => $value) {
    if (!isset($order[$key])) {
        $order[$key] = $value;
    }
}

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // Get form data
        $customer_name = $_POST['customer_name'] ?? $order['customer_name'];
        $customer_email = $_POST['customer_email'] ?? $order['customer_email'];
        $customer_phone = $_POST['customer_phone'] ?? $order['customer_phone'];
        $shipping_address = $_POST['shipping_address'] ?? $order['shipping_address'];
        $payment_method = $_POST['payment_method'] ?? $order['payment_method'];
        $payment_status = $_POST['payment_status'] ?? $order['payment_status'];
        $shipping_method = $_POST['shipping_method'] ?? $order['shipping_method'];
        $tracking_number = $_POST['tracking_number'] ?? $order['tracking_number'];
        $shipping_cost = floatval($_POST['shipping_cost'] ?? $order['shipping_cost']);
        $tax_amount = floatval($_POST['tax_amount'] ?? $order['tax_amount']);
        $status = $_POST['status'] ?? $order['status'];
        $notes = $_POST['notes'] ?? '';
        
        // Update order
        $update_order_stmt = $conn->prepare("
            UPDATE orders 
            SET customer_name = ?,
                customer_email = ?,
                customer_phone = ?,
                shipping_address = ?,
                payment_method = ?, 
                payment_status = ?, 
                shipping_method = ?, 
                tracking_number = ?, 
                shipping_cost = ?, 
                tax_amount = ?, 
                status = ?,
                total_amount = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $new_total = $subtotal + $shipping_cost + $tax_amount;
        $update_order_stmt->execute([
            $customer_name,
            $customer_email,
            $customer_phone,
            $shipping_address,
            $payment_method,
            $payment_status,
            $shipping_method,
            $tracking_number,
            $shipping_cost,
            $tax_amount,
            $status,
            $new_total,
            $order_id
        ]);
        
        // Add order note if provided
        if (!empty($notes)) {
            $add_note_stmt = $conn->prepare("
                INSERT INTO order_notes (order_id, note, created_at)
                VALUES (?, ?, NOW())
            ");
            $add_note_stmt->execute([$order_id, $notes]);
        }
        
        $conn->commit();
        
        // Refresh to show updated data
        $success_message = 'Order updated successfully!';
        header("Refresh:2; url=order-edit.php?id=$order_id");
    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = "Error updating order: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order #<?= $order_id ?> - SportBoots Pro Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        accent: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .scrollbar-hidden::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body class="h-full">
    <div class="min-h-full">
        <!-- Main content -->
        <div class="lg:pl-64">
            <div class="sticky top-0 z-10 flex items-center h-16 px-4 bg-white border-b border-gray-200 lg:hidden">
                <button @click="mobileMenuOpen = true" class="px-4 -ml-2 text-gray-500 border-r border-gray-200 focus:outline-none">
                    <span class="sr-only">Open sidebar</span>
                    <i class="fas fa-bars"></i>
                </button>
                <div class="flex justify-center flex-1">
                    <h1 class="text-lg font-medium text-gray-900">Edit Order #<?= $order_id ?></h1>
                </div>
            </div>

            <main class="flex-1 pb-8">
                <!-- Page header -->
                <div class="px-4 pt-6 bg-white shadow sm:px-6 lg:px-8 lg:pt-8">
                    <div class="max-w-6xl mx-auto">
                        <div class="flex flex-col justify-between sm:flex-row sm:items-center">
                            <div class="flex items-center">
                                <h1 class="text-2xl font-bold leading-tight text-gray-900">Edit Order #<?= $order_id ?></h1>
                                <span class="ml-3 text-sm font-medium <?= 
                                    $order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                    ($order['status'] === 'processing' ? 'bg-blue-100 text-blue-800' : 
                                    ($order['status'] === 'shipped' ? 'bg-purple-100 text-purple-800' : 
                                    ($order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 
                                    'bg-red-100 text-red-800'))) 
                                ?> rounded-full py-1 px-3">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </div>
                            <div class="flex mt-4 space-x-3 sm:mt-0">
                                <a href="order-details.php?id=<?= $order_id ?>" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <i class="mr-2 fas fa-arrow-left"></i> Back to Details
                                </a>
                            </div>
                        </div>
                        <div class="mt-4 sm:flex sm:items-center sm:justify-between">
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="mr-1.5 fas fa-calendar-alt"></i>
                                <span>Placed on <?= $order['formatted_date'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($success_message): ?>
                <div class="max-w-6xl px-4 mx-auto mt-6 sm:px-6 lg:px-8">
                    <div class="p-4 rounded-md bg-green-50">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="text-green-400 fas fa-check-circle"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success_message) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="max-w-6xl px-4 mx-auto mt-6 sm:px-6 lg:px-8">
                    <div class="p-4 rounded-md bg-red-50">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="text-red-400 fas fa-exclamation-circle"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Error updating order</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p><?= htmlspecialchars($error_message) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Order edit form -->
                <div class="max-w-6xl px-4 mx-auto mt-6 sm:px-6 lg:px-8">
                    <form method="post" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <!-- Order summary -->
                        <div class="lg:col-span-2">
                            <div class="bg-white shadow sm:rounded-lg">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Order Information</h3>
                                </div>
                                <div class="border-t border-gray-200">
                                    <div class="px-4 py-5 space-y-6 sm:p-6">
                                        <!-- Status -->
                                        <div>
                                            <label for="status" class="block text-sm font-medium text-gray-700">Order Status</label>
                                            <select id="status" name="status" class="block w-full px-3 py-2 mt-1 text-base border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </div>

                                        <!-- Customer Information -->
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <label for="customer_name" class="block text-sm font-medium text-gray-700">Customer Name</label>
                                                <input type="text" id="customer_name" name="customer_name" value="<?= htmlspecialchars($order['customer_name']) ?>" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label for="customer_email" class="block text-sm font-medium text-gray-700">Email</label>
                                                <input type="email" id="customer_email" name="customer_email" value="<?= htmlspecialchars($order['customer_email']) ?>" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <label for="customer_phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                                <input type="text" id="customer_phone" name="customer_phone" value="<?= htmlspecialchars($order['customer_phone']) ?>" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                            </div>
                                        </div>
                                        <div>
                                            <label for="shipping_address" class="block text-sm font-medium text-gray-700">Shipping Address</label>
                                            <textarea id="shipping_address" name="shipping_address" rows="3" class="block w-full mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"><?= htmlspecialchars($order['shipping_address']) ?></textarea>
                                        </div>

                                        <!-- Payment Information -->
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                                                <select id="payment_method" name="payment_method" class="block w-full px-3 py-2 mt-1 text-base border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                                    <option value="Credit Card" <?= $order['payment_method'] === 'Credit Card' ? 'selected' : '' ?>>Credit Card</option>
                                                    <option value="PayPal" <?= $order['payment_method'] === 'PayPal' ? 'selected' : '' ?>>PayPal</option>
                                                    <option value="Bank Transfer" <?= $order['payment_method'] === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                                    <option value="Cash on Delivery" <?= $order['payment_method'] === 'Cash on Delivery' ? 'selected' : '' ?>>Cash on Delivery</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label for="payment_status" class="block text-sm font-medium text-gray-700">Payment Status</label>
                                                <select id="payment_status" name="payment_status" class="block w-full px-3 py-2 mt-1 text-base border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                                    <option value="Paid" <?= $order['payment_status'] === 'Paid' ? 'selected' : '' ?>>Paid</option>
                                                    <option value="Pending" <?= $order['payment_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="Refunded" <?= $order['payment_status'] === 'Refunded' ? 'selected' : '' ?>>Refunded</option>
                                                    <option value="Failed" <?= $order['payment_status'] === 'Failed' ? 'selected' : '' ?>>Failed</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Shipping Information -->
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <label for="shipping_method" class="block text-sm font-medium text-gray-700">Shipping Method</label>
                                                <select id="shipping_method" name="shipping_method" class="block w-full px-3 py-2 mt-1 text-base border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                                    <option value="Standard Shipping" <?= $order['shipping_method'] === 'Standard Shipping' ? 'selected' : '' ?>>Standard Shipping</option>
                                                    <option value="Express Shipping" <?= $order['shipping_method'] === 'Express Shipping' ? 'selected' : '' ?>>Express Shipping</option>
                                                    <option value="Local Pickup" <?= $order['shipping_method'] === 'Local Pickup' ? 'selected' : '' ?>>Local Pickup</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label for="tracking_number" class="block text-sm font-medium text-gray-700">Tracking Number</label>
                                                <input type="text" id="tracking_number" name="tracking_number" value="<?= htmlspecialchars($order['tracking_number']) ?>" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                            </div>
                                        </div>

                                        <!-- Order Items (readonly) -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Order Items</label>
                                            <div class="mt-1 overflow-hidden border border-gray-200 rounded-md">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Product</th>
                                                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Price</th>
                                                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Qty</th>
                                                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        <?php foreach ($items as $item): ?>
                                                        <tr>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <div class="flex items-center">
                                                                    <div class="flex-shrink-0 w-16 h-16 overflow-hidden border border-gray-200 rounded-md">
                                                                        <?php if (!empty($item['product_image'])): ?>
                                                                        <img src="<?= htmlspecialchars($item['product_image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="object-cover object-center w-full h-full">
                                                                        <?php else: ?>
                                                                        <div class="flex items-center justify-center w-full h-full bg-gray-100 text-gray-400">
                                                                            <i class="fas fa-box text-xl"></i>
                                                                        </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="ml-4">
                                                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['product_name']) ?></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-6 py-4 text-sm text-right text-gray-500 whitespace-nowrap">
                                                                $<?= number_format($item['price'], 2) ?>
                                                            </td>
                                                            <td class="px-6 py-4 text-sm text-right text-gray-500 whitespace-nowrap">
                                                                <?= $item['quantity'] ?>
                                                            </td>
                                                            <td class="px-6 py-4 text-sm font-medium text-right text-gray-900 whitespace-nowrap">
                                                                $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Order Notes -->
                                        <div>
                                            <label for="notes" class="block text-sm font-medium text-gray-700">Add Order Note</label>
                                            <textarea id="notes" name="notes" rows="3" class="block w-full mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="Add any notes about this order..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order totals -->
                        <div>
                            <div class="bg-white shadow sm:rounded-lg">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Order Totals</h3>
                                </div>
                                <div class="border-t border-gray-200">
                                    <div class="px-4 py-5 space-y-4 sm:p-6">
                                        <div>
                                            <label for="subtotal" class="block text-sm font-medium text-gray-700">Subtotal</label>
                                            <div class="mt-1 text-sm text-gray-900">$<?= number_format($subtotal, 2) ?></div>
                                        </div>
                                        <div>
                                            <label for="shipping_cost" class="block text-sm font-medium text-gray-700">Shipping</label>
                                            <input type="number" step="0.01" id="shipping_cost" name="shipping_cost" value="<?= number_format($order['shipping_cost'], 2) ?>" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label for="tax_amount" class="block text-sm font-medium text-gray-700">Tax</label>
                                            <input type="number" step="0.01" id="tax_amount" name="tax_amount" value="<?= number_format($order['tax_amount'], 2) ?>" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                        </div>
                                        <div class="pt-4 border-t border-gray-200">
                                            <div class="flex justify-between">
                                                <label for="total_amount" class="block text-base font-medium text-gray-700">Total</label>
                                                <div class="text-base font-medium text-gray-900">$<?= number_format($order['total_amount'], 2) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Save button -->
                            <div class="mt-6">
                                <button type="submit" class="flex justify-center w-full px-4 py-2 text-sm font-medium text-white border border-transparent rounded-md shadow-sm bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <i class="mr-2 fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <!-- Alpine.js for interactivity -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Recalculate total when shipping or tax changes
            const shippingInput = document.getElementById('shipping_cost');
            const taxInput = document.getElementById('tax_amount');
            const subtotal = <?= $subtotal ?>;
            
            function updateTotal() {
                const shipping = parseFloat(shippingInput.value) || 0;
                const tax = parseFloat(taxInput.value) || 0;
                const total = subtotal + shipping + tax;
                document.querySelector('[for="total_amount"] + div').textContent = '$' + total.toFixed(2);
            }
            
            shippingInput.addEventListener('change', updateTotal);
            taxInput.addEventListener('change', updateTotal);
            
            // Confirm before cancelling order
            const statusSelect = document.getElementById('status');
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                if (statusSelect.value === 'cancelled') {
                    if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>