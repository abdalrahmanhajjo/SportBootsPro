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

// Fetch order details with user information
$order_stmt = $conn->prepare("
    SELECT o.*, 
           u.full_name AS customer_name,
           u.email AS customer_email,
           u.phone AS customer_phone,
           u.address AS customer_address,
           DATE_FORMAT(o.created_at, '%b %e, %Y at %l:%i %p') AS formatted_date
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
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
           p.image_url AS product_image,
           p.price AS original_price
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id
");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

// Calculate order totals
$subtotal = 0;
$item_count = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $item_count += $item['quantity'];
}

// Set default values for missing fields
$default_values = [
    'payment_method' => 'Credit Card',
    'payment_status' => 'Paid',
    'shipping_method' => 'Standard Shipping',
    'tracking_number' => 'Not available',
    'shipping_cost' => 9.99,
    'tax_amount' => round($subtotal * 0.08, 2),
    'customer_city' => '',
    'customer_state' => '',
    'customer_zip' => '',
    'customer_country' => 'United States'
];

foreach ($default_values as $key => $value) {
    if (!isset($order[$key]) || empty($order[$key])) {
        $order[$key] = $value;
    }
}

// Calculate total amount if not set
if (!isset($order['total_amount']) || empty($order['total_amount'])) {
    $order['total_amount'] = $subtotal + $order['shipping_cost'] + $order['tax_amount'];
}

// Process status update if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $notes = trim($_POST['notes']);
    
    // Validate status
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        $error = "Invalid status selected";
    } else {
        try {
            $conn->beginTransaction();
            
            // Update order status
            $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $update_stmt->execute([$new_status, $order_id]);
            
            $conn->commit();
            
            // Refresh to show updated status
            header("Location: order-details.php?id=$order_id");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error updating status: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order_id ?> - SportBoots Pro Admin</title>
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
        <!-- Off-canvas menu for mobile -->
        <div class="lg:hidden" x-data="{ mobileMenuOpen: false }">
            <!-- Mobile menu button -->
            <div class="fixed inset-0 z-40 flex lg:hidden" x-show="mobileMenuOpen" x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="mobileMenuOpen = false"></div>
                <div class="relative flex flex-col flex-1 w-full max-w-xs bg-white">
                    <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
                        <div class="text-lg font-semibold text-gray-900">SportBoots Pro</div>
                        <button @click="mobileMenuOpen = false" class="p-2 -mr-2 text-gray-400 rounded-md hover:text-gray-500">
                            <span class="sr-only">Close menu</span>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <nav class="flex-1 px-2 py-4 space-y-1 bg-white">
                        <a href="dashboard.php" class="flex items-center px-2 py-2 text-base font-medium text-gray-900 rounded-md group">
                            <i class="w-6 mr-3 text-gray-400 fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                        <a href="orders.php" class="flex items-center px-2 py-2 text-base font-medium text-white rounded-md bg-primary-600 group">
                            <i class="w-6 mr-3 text-white fas fa-shopping-bag"></i>
                            Orders
                        </a>
                        <a href="products.php" class="flex items-center px-2 py-2 text-base font-medium text-gray-900 rounded-md group">
                            <i class="w-6 mr-3 text-gray-400 fas fa-tshirt"></i>
                            Products
                        </a>
                        <a href="customers.php" class="flex items-center px-2 py-2 text-base font-medium text-gray-900 rounded-md group">
                            <i class="w-6 mr-3 text-gray-400 fas fa-users"></i>
                            Customers
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col lg:border-r lg:border-gray-200 lg:bg-white lg:pb-4 lg:pt-5">
            <div class="flex items-center flex-shrink-0 px-6">
                <h1 class="text-xl font-bold text-gray-900">SportBoots Pro</h1>
            </div>
            <nav class="flex-1 mt-5 overflow-y-auto">
                <div class="px-2 space-y-1">
                    <a href="dashboard.php" class="flex items-center px-2 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-gray-50 hover:text-gray-900 group">
                        <i class="w-6 mr-3 text-gray-400 fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="orders.php" class="flex items-center px-2 py-2 text-sm font-medium text-white rounded-md bg-primary-600 group">
                        <i class="w-6 mr-3 text-white fas fa-shopping-bag"></i>
                        Orders
                    </a>
                    <a href="products.php" class="flex items-center px-2 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-gray-50 hover:text-gray-900 group">
                        <i class="w-6 mr-3 text-gray-400 fas fa-tshirt"></i>
                        Products
                    </a>
                    <a href="customers.php" class="flex items-center px-2 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-gray-50 hover:text-gray-900 group">
                        <i class="w-6 mr-3 text-gray-400 fas fa-users"></i>
                        Customers
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main content -->
        <div class="lg:pl-64">
            <div class="sticky top-0 z-10 flex items-center h-16 px-4 bg-white border-b border-gray-200 lg:hidden">
                <button @click="mobileMenuOpen = true" class="px-4 -ml-2 text-gray-500 border-r border-gray-200 focus:outline-none">
                    <span class="sr-only">Open sidebar</span>
                    <i class="fas fa-bars"></i>
                </button>
                <div class="flex justify-center flex-1">
                    <h1 class="text-lg font-medium text-gray-900">Order #<?= $order_id ?></h1>
                </div>
            </div>

            <main class="flex-1 pb-8">
                <!-- Page header -->
                <div class="px-4 pt-6 bg-white shadow sm:px-6 lg:px-8 lg:pt-8">
                    <div class="max-w-6xl mx-auto">
                        <div class="flex flex-col justify-between sm:flex-row sm:items-center">
                            <div class="flex items-center">
                                <h1 class="text-2xl font-bold leading-tight text-gray-900">Order #<?= $order_id ?></h1>
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
                                <a href="orders.php" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <i class="mr-2 fas fa-arrow-left"></i> Back to Orders
                                </a>
                                <a href="order-edit.php?id=<?= $order_id ?>" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white border border-transparent rounded-md shadow-sm bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <i class="mr-2 fas fa-edit"></i> Edit Order
                                </a>
                            </div>
                        </div>
                        <div class="mt-4 sm:flex sm:items-center sm:justify-between">
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="mr-1.5 fas fa-calendar-alt"></i>
                                <span>Placed on <?= $order['formatted_date'] ?></span>
                            </div>
                            <div class="flex mt-3 sm:mt-0 sm:ml-4">
                                <a href="#" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <i class="mr-2 fas fa-print"></i> Print
                                </a>
                                <a href="order-export.php?id=<?= $order_id ?>" class="inline-flex items-center px-3 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <i class="mr-2 fas fa-file-export"></i> Export
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                <div class="max-w-6xl px-4 mx-auto mt-6 sm:px-6 lg:px-8">
                    <div class="p-4 rounded-md bg-red-50">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="text-red-400 fas fa-exclamation-circle"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Error updating order</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p><?= htmlspecialchars($error) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Order details -->
                <div class="max-w-6xl px-4 mx-auto mt-6 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <!-- Order summary -->
                        <div class="lg:col-span-2">
                            <div class="bg-white shadow sm:rounded-lg">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Order Summary</h3>
                                </div>
                                <div class="border-t border-gray-200">
                                    <dl>
                                        <div class="px-4 py-5 bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt class="text-sm font-medium text-gray-500">Payment method</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                <?= htmlspecialchars($order['payment_method']) ?>
                                                <div class="mt-1 text-sm text-gray-500">
                                                    Status: <span class="font-medium <?= 
                                                        $order['payment_status'] === 'Paid' ? 'text-green-600' : 
                                                        'text-yellow-600'
                                                    ?>">
                                                        <?= htmlspecialchars($order['payment_status']) ?>
                                                    </span>
                                                </div>
                                            </dd>
                                        </div>
                                        <div class="px-4 py-5 bg-white sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt class="text-sm font-medium text-gray-500">Shipping method</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                <?= htmlspecialchars($order['shipping_method']) ?>
                                                <?php if ($order['tracking_number'] !== 'Not available'): ?>
                                                <div class="mt-1">
                                                    <span class="text-sm text-gray-500">Tracking #:</span>
                                                    <a href="#" class="ml-1 text-sm font-medium text-primary-600 hover:text-primary-500">
                                                        <?= htmlspecialchars($order['tracking_number']) ?>
                                                    </a>
                                                </div>
                                                <?php endif; ?>
                                            </dd>
                                        </div>
                                        <div class="px-4 py-5 bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt class="text-sm font-medium text-gray-500">Order date</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                <?= $order['formatted_date'] ?>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Order items -->
                            <div class="mt-6 bg-white shadow sm:rounded-lg">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Order Items (<?= count($items) ?>)</h3>
                                </div>
                                <div class="border-t border-gray-200">
                                    <div class="overflow-hidden">
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
                                                                <div class="text-sm text-gray-500">SKU: <?= $item['product_id'] ?></div>
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
                            </div>
                        </div>

                        <!-- Customer and payment info -->
                        <div>
                            <!-- Customer information -->
                            <div class="bg-white shadow sm:rounded-lg">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Customer Information</h3>
                                </div>
                                <div class="border-t border-gray-200">
                                    <dl>
                                        <div class="px-4 py-5 bg-white sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt class="text-sm font-medium text-gray-500">Name</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                <?= htmlspecialchars($order['customer_name']) ?>
                                            </dd>
                                        </div>
                                        <div class="px-4 py-5 bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                <a href="mailto:<?= htmlspecialchars($order['customer_email']) ?>" class="text-primary-600 hover:text-primary-500">
                                                    <?= htmlspecialchars($order['customer_email']) ?>
                                                </a>
                                            </dd>
                                        </div>
                                        <div class="px-4 py-5 bg-white sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                <a href="tel:<?= htmlspecialchars($order['customer_phone']) ?>" class="text-primary-600 hover:text-primary-500">
                                                    <?= htmlspecialchars($order['customer_phone']) ?>
                                                </a>
                                            </dd>
                                        </div>
                                        <div class="px-4 py-5 bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt class="text-sm font-medium text-gray-500">Address</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                <div><?= htmlspecialchars($order['customer_address']) ?></div>
                                                <div>
                                                    <?= !empty($order['customer_city']) ? htmlspecialchars($order['customer_city']) . ', ' : '' ?>
                                                    <?= htmlspecialchars($order['customer_state']) ?> <?= htmlspecialchars($order['customer_zip']) ?>
                                                </div>
                                                <div><?= htmlspecialchars($order['customer_country']) ?></div>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Order totals -->
                            <div class="mt-6 bg-white shadow sm:rounded-lg">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Order Totals</h3>
                                </div>
                                <div class="border-t border-gray-200">
                                    <dl class="divide-y divide-gray-200">
                                        <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt class="text-sm font-medium text-gray-500">Subtotal</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:text-right sm:col-span-2">
                                                $<?= number_format($subtotal, 2) ?>
                                            </dd>
                                        </div>
                                        <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt class="text-sm font-medium text-gray-500">Shipping</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:text-right sm:col-span-2">
                                                $<?= number_format($order['shipping_cost'], 2) ?>
                                            </dd>
                                        </div>
                                        <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt class="text-sm font-medium text-gray-500">Tax</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:text-right sm:col-span-2">
                                                $<?= number_format($order['tax_amount'], 2) ?>
                                            </dd>
                                        </div>
                                        <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt class="text-base font-medium text-gray-900">Total</dt>
                                            <dd class="mt-1 text-base font-medium text-gray-900 sm:mt-0 sm:text-right sm:col-span-2">
                                                $<?= number_format($order['total_amount'], 2) ?>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Status update -->
                            <div class="mt-6 bg-white shadow sm:rounded-lg">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Update Status</h3>
                                </div>
                                <div class="border-t border-gray-200">
                                    <form method="post" class="px-4 py-5 space-y-4 sm:px-6">
                                        <div>
                                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                            <select id="status" name="status" class="block w-full px-3 py-2 mt-1 text-base border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                            <textarea id="notes" name="notes" rows="3" class="block w-full mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="Add any notes about this status change..."></textarea>
                                        </div>
                                        <div class="pt-2">
                                            <button type="submit" name="update_status" class="flex justify-center w-full px-4 py-2 text-sm font-medium text-white border border-transparent rounded-md shadow-sm bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                                <i class="mr-2 fas fa-save"></i> Update Status
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Alpine.js for interactivity -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add confirmation for status changes to cancelled
            document.querySelector('form')?.addEventListener('submit', function(e) {
                const statusSelect = document.getElementById('status');
                if (statusSelect.value === 'cancelled') {
                    if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                }
            });

            // Print functionality
            document.querySelector('[href="#"]')?.addEventListener('click', function(e) {
                e.preventDefault();
                window.print();
            });
        });
    </script>
</body>
</html>
