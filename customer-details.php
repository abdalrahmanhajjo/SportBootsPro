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

// Get customer ID from URL
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($customer_id <= 0) {
    header("Location: customers.php");
    exit();
}

// Fetch customer details
$customer_stmt = $conn->prepare("
    SELECT * FROM users 
    WHERE id = ?
");
$customer_stmt->execute([$customer_id]);
$customer = $customer_stmt->fetch();

if (!$customer) {
    header("Location: customers.php");
    exit();
}

// Fetch customer's orders
$orders_stmt = $conn->prepare("
    SELECT o.*, 
           COUNT(oi.id) AS item_count,
           SUM(oi.price * oi.quantity) AS items_total,
           DATE_FORMAT(o.created_at, '%b %e, %Y') AS formatted_date
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$orders_stmt->execute([$customer_id]);
$orders = $orders_stmt->fetchAll();

// Calculate customer stats
$total_orders = count($orders);
$total_spent = array_reduce($orders, function($carry, $order) {
    return $carry + $order['total_amount'];
}, 0);
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details - SportBoots Pro Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            600: '#0284c7',
                            700: '#0369a1',
                        },
                        success: {
                            50: '#f0fdf4',
                            600: '#16a34a',
                        },
                        warning: {
                            50: '#fffbeb',
                            600: '#d97706',
                        },
                        danger: {
                            50: '#fef2f2',
                            600: '#dc2626',
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
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .order-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="h-full bg-gray-50">
    <!-- Mobile sidebar toggle -->
    <div x-data="{ mobileSidebarOpen: false }" class="lg:hidden">
        <!-- Mobile sidebar backdrop -->
        <div x-show="mobileSidebarOpen" @click="mobileSidebarOpen = false" 
             class="fixed inset-0 z-40 bg-gray-900 bg-opacity-50 transition-opacity ease-linear duration-300"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
        </div>

        <!-- Mobile sidebar -->
        <div x-show="mobileSidebarOpen" @click.away="mobileSidebarOpen = false"
             class="fixed inset-y-0 left-0 z-50 w-64 transform transition ease-in-out duration-300"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">
            <div class="flex flex-col h-full bg-white shadow-xl">
                <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
                    <span class="text-xl font-bold text-gray-800">SportBoots Pro</span>
                    <button @click="mobileSidebarOpen = false" class="p-1 rounded-md text-gray-500 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
                    <a href="dashboard.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md group">
                        <i class="flex-shrink-0 w-6 mr-3 text-gray-400 fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="orders.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md group">
                        <i class="flex-shrink-0 w-6 mr-3 text-gray-400 fas fa-shopping-bag"></i>
                        Orders
                    </a>
                    <a href="products.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md group">
                        <i class="flex-shrink-0 w-6 mr-3 text-gray-400 fas fa-tshirt"></i>
                        Products
                    </a>
                    <a href="customers.php" class="flex items-center px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-md group">
                        <i class="flex-shrink-0 w-6 mr-3 text-white fas fa-users"></i>
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
        <nav class="mt-6 flex-1 flex flex-col overflow-y-auto">
            <div class="px-2 space-y-1">
                <a href="dashboard.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                    <i class="flex-shrink-0 w-6 mr-3 text-gray-400 fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="orders.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                    <i class="flex-shrink-0 w-6 mr-3 text-gray-400 fas fa-shopping-bag"></i>
                    Orders
                </a>
                <a href="products.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                    <i class="flex-shrink-0 w-6 mr-3 text-gray-400 fas fa-tshirt"></i>
                    Products
                </a>
                <a href="customers.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white bg-primary-600">
                    <i class="flex-shrink-0 w-6 mr-3 text-white fas fa-users"></i>
                    Customers
                </a>
            </div>
        </nav>
    </div>

    <!-- Main content -->
    <div class="lg:pl-64 flex flex-col flex-1">
        <!-- Mobile top header -->
        <div class="lg:hidden sticky top-0 z-10 flex items-center justify-between h-16 px-4 bg-white border-b border-gray-200">
            <button @click="mobileSidebarOpen = true" class="p-1 rounded-md text-gray-500 hover:text-gray-600">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="text-lg font-medium text-gray-900">Customer Details</h1>
            <div class="w-8"></div> <!-- Spacer for balance -->
        </div>

        <!-- Main content area -->
        <main class="flex-1 pb-8">
            <!-- Page header -->
            <div class="bg-white shadow">
                <div class="px-4 py-6 sm:px-6 lg:px-8">
                    <div class="flex flex-col justify-between space-y-4 sm:space-y-0 sm:flex-row sm:items-center">
                        <div class="flex items-center">
                            <h1 class="text-2xl font-bold text-gray-900">Customer Details</h1>
                            <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                Customer #<?= $customer_id ?>
                            </span>
                        </div>
                        <div class="flex space-x-3">
                            <a href="customer-edit.php?id=<?= $customer_id ?>" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <i class="mr-2 fas fa-edit"></i> Edit Customer
                            </a>
                            <a href="customers.php" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <i class="mr-2 fas fa-arrow-left"></i> Back to Customers
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer details -->
            <div class="px-4 mt-6 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Customer information -->
                    <div class="lg:col-span-2">
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Customer Information</h3>
                            </div>
                            <div class="px-4 py-5 sm:p-6">
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Full Name</label>
                                        <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($customer['full_name']) ?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Email</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <a href="mailto:<?= htmlspecialchars($customer['email']) ?>" class="text-primary-600 hover:text-primary-500">
                                                <?= htmlspecialchars($customer['email']) ?>
                                            </a>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Phone</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php if ($customer['phone']): ?>
                                            <a href="tel:<?= htmlspecialchars($customer['phone']) ?>" class="text-primary-600 hover:text-primary-500">
                                                <?= htmlspecialchars($customer['phone']) ?>
                                            </a>
                                            <?php else: ?>
                                            <span class="text-gray-500">Not provided</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Member Since</label>
                                        <p class="mt-1 text-sm text-gray-900"><?= date('M j, Y', strtotime($customer['created_at'])) ?></p>
                                    </div>
                                </div>
                                
                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-500">Address</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <?php if ($customer['address']): ?>
                                        <?= nl2br(htmlspecialchars($customer['address'])) ?>
                                        <?php else: ?>
                                        <span class="text-gray-500">Not provided</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer stats -->
                    <div>
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Customer Stats</h3>
                            </div>
                            <div class="px-4 py-5 sm:p-6">
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <label class="block text-sm font-medium text-gray-500">Total Orders</label>
                                        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= $total_orders ?></p>
                                    </div>
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <label class="block text-sm font-medium text-gray-500">Total Spent</label>
                                        <p class="mt-1 text-2xl font-semibold text-gray-900">$<?= number_format($total_spent, 2) ?></p>
                                    </div>
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <label class="block text-sm font-medium text-gray-500">Last Order</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php if ($total_orders > 0): ?>
                                            <?= $orders[0]['formatted_date'] ?>
                                            <?php else: ?>
                                            <span class="text-gray-500">No orders yet</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer orders -->
                <div class="mt-6">
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Order History</h3>
                        </div>
                        <div class="px-4 py-5 sm:p-6">
                            <?php if (empty($orders)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-shopping-bag text-4xl text-gray-400 mb-3"></i>
                                <p class="text-gray-500">This customer hasn't placed any orders yet</p>
                            </div>
                            <?php else: ?>
                            <!-- Mobile order cards -->
                            <div class="lg:hidden space-y-4">
                                <?php foreach ($orders as $order): ?>
                                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden transition-all duration-200 order-hover">
                                    <div class="px-4 py-5 sm:p-6">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-lg font-medium text-gray-900">Order #<?= $order['id'] ?></h4>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= 
                                                $order['status'] === 'pending' ? 'bg-warning-100 text-warning-800' : 
                                                ($order['status'] === 'processing' ? 'bg-blue-100 text-blue-800' : 
                                                ($order['status'] === 'shipped' ? 'bg-purple-100 text-purple-800' : 
                                                ($order['status'] === 'delivered' ? 'bg-success-100 text-success-800' : 
                                                'bg-danger-100 text-danger-800'))) 
                                            ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </div>
                                        <div class="mt-4 grid grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-sm text-gray-500">Date</p>
                                                <p class="text-sm font-medium text-gray-900"><?= $order['formatted_date'] ?></p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Items</p>
                                                <p class="text-sm font-medium text-gray-900"><?= $order['item_count'] ?></p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Total</p>
                                                <p class="text-sm font-medium text-gray-900">$<?= number_format($order['total_amount'], 2) ?></p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Payment</p>
                                                <p class="text-sm font-medium text-gray-900 <?= $order['payment_status'] === 'paid' ? 'text-success-600' : 'text-warning-600' ?>">
                                                    <?= ucfirst($order['payment_status']) ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex justify-between items-center">
                                            <a href="order-details.php?id=<?= $order['id'] ?>" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                                                View details
                                            </a>
                                            <?php if ($order['tracking_number']): ?>
                                            <a href="#" class="text-sm font-medium text-gray-600 hover:text-gray-500">
                                                Track #<?= $order['tracking_number'] ?>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Desktop order table -->
                            <div class="hidden lg:block">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                                            <th scope="col" class="relative px-6 py-3">
                                                <span class="sr-only">Actions</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($orders as $order): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                #<?= $order['id'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= $order['formatted_date'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= $order['item_count'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                $<?= number_format($order['total_amount'], 2) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= 
                                                    $order['status'] === 'pending' ? 'bg-warning-100 text-warning-800' : 
                                                    ($order['status'] === 'processing' ? 'bg-blue-100 text-blue-800' : 
                                                    ($order['status'] === 'shipped' ? 'bg-purple-100 text-purple-800' : 
                                                    ($order['status'] === 'delivered' ? 'bg-success-100 text-success-800' : 
                                                    'bg-danger-100 text-danger-800'))) 
                                                ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <span class="<?= $order['payment_status'] === 'paid' ? 'text-success-600' : 'text-warning-600' ?>">
                                                    <?= ucfirst($order['payment_status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="order-details.php?id=<?= $order['id'] ?>" class="text-primary-600 hover:text-primary-900">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Alpine.js for interactivity -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>customer-edit.php