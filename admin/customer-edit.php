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
$customer_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$customer_stmt->execute([$customer_id]);
$customer = $customer_stmt->fetch();

if (!$customer) {
    header("Location: customers.php");
    exit();
}

// Fetch customer orders
$orders_stmt = $conn->prepare("
    SELECT id, total_amount, status, created_at 
    FROM orders 
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$orders_stmt->execute([$customer_id]);
$recent_orders = $orders_stmt->fetchAll();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Update customer
        $update_stmt = $conn->prepare("
            UPDATE users 
            SET full_name = ?, 
                email = ?, 
                phone = ?, 
                address = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $update_stmt->execute([
            $full_name,
            $email,
            $phone,
            $address,
            $customer_id
        ]);
        
        $_SESSION['success_message'] = 'Customer updated successfully!';
        header("Location: customer-edit.php?id=$customer_id");
        exit();
    } catch (Exception $e) {
        $error_message = "Error updating customer: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - SportBoots Pro Admin</title>
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
        .badge-pending { background-color: #fef3c7; color: #d97706; }
        .badge-processing { background-color: #dbeafe; color: #1d4ed8; }
        .badge-shipped { background-color: #e0e7ff; color: #4f46e5; }
        .badge-delivered { background-color: #dcfce7; color: #16a34a; }
        .badge-cancelled { background-color: #fee2e2; color: #dc2626; }
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
            <h1 class="text-lg font-medium text-gray-900">Edit Customer</h1>
            <div class="w-8"></div> <!-- Spacer for balance -->
        </div>

        <!-- Main content area -->
        <main class="flex-1 pb-8">
            <!-- Page header -->
            <div class="bg-white shadow">
                <div class="px-4 py-6 sm:px-6 lg:px-8">
                    <div class="flex flex-col justify-between space-y-4 sm:space-y-0 sm:flex-row sm:items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Edit Customer</h1>
                            <p class="mt-1 text-sm text-gray-500">Update customer details and information</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="customers.php" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <i class="mr-2 fas fa-arrow-left"></i> Back to Customers
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="px-4 pt-6 sm:px-6 lg:px-8 animate-fade-in">
                <div class="p-4 rounded-md bg-success-50">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="text-success-600 fas fa-check-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-success-800"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
                        </div>
                        <div class="ml-auto pl-3">
                            <div class="-mx-1.5 -my-1.5">
                                <button type="button" onclick="this.parentElement.parentElement.parentElement.parentElement.remove()" class="inline-flex rounded-md p-1.5 text-success-500 hover:bg-success-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-success-500">
                                    <span class="sr-only">Dismiss</span>
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['success_message']); endif; ?>

            <?php if ($error_message): ?>
            <div class="px-4 pt-6 sm:px-6 lg:px-8 animate-fade-in">
                <div class="p-4 rounded-md bg-danger-50">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="text-danger-600 fas fa-exclamation-circle"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-danger-800">Error</h3>
                            <div class="mt-2 text-sm text-danger-700">
                                <p><?= htmlspecialchars($error_message) ?></p>
                            </div>
                        </div>
                        <div class="ml-auto pl-3">
                            <div class="-mx-1.5 -my-1.5">
                                <button type="button" onclick="this.parentElement.parentElement.parentElement.parentElement.remove()" class="inline-flex rounded-md p-1.5 text-danger-500 hover:bg-danger-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-danger-500">
                                    <span class="sr-only">Dismiss</span>
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Customer edit form -->
            <div class="px-4 mt-6 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Customer details -->
                    <div class="lg:col-span-2">
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Customer Information</h3>
                            </div>
                            <form method="post" class="divide-y divide-gray-200">
                                <div class="px-4 py-5 sm:p-6 space-y-6">
                                    <!-- Full Name -->
                                    <div>
                                        <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($customer['full_name']) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    </div>

                                    <!-- Phone -->
                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    </div>

                                    <!-- Address -->
                                    <div>
                                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                                        <textarea id="address" name="address" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"><?= htmlspecialchars($customer['address']) ?></textarea>
                                    </div>
                                </div>
                                <div class="px-4 py-4 bg-gray-50 text-right sm:px-6">
                                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                        <i class="fas fa-save mr-2"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Recent orders -->
                    <div>
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Recent Orders</h3>
                            </div>
                            <div class="divide-y divide-gray-200">
                                <?php if (empty($recent_orders)): ?>
                                <div class="px-4 py-5 sm:p-6 text-center">
                                    <p class="text-sm text-gray-500">No recent orders found</p>
                                </div>
                                <?php else: ?>
                                <?php foreach ($recent_orders as $order): ?>
                                <div class="px-4 py-4 sm:px-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-primary-600">
                                                <a href="order-details.php?id=<?= $order['id'] ?>">Order #<?= $order['id'] ?></a>
                                            </p>
                                            <p class="text-sm text-gray-500"><?= date('M j, Y', strtotime($order['created_at'])) ?></p>
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <p class="text-sm font-medium text-gray-900">$<?= number_format($order['total_amount'], 2) ?></p>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full badge-<?= $order['status'] ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <div class="px-4 py-4 sm:px-6 text-center border-t border-gray-200">
                                    <a href="orders.php?customer_id=<?= $customer_id ?>" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                                        View all orders
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Account details -->
                        <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Account Details</h3>
                            </div>
                            <div class="px-4 py-5 sm:p-6">
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Member since</dt>
                                        <dd class="mt-1 text-sm text-gray-900"><?= date('M j, Y', strtotime($customer['created_at'])) ?></dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Last updated</dt>
                                        <dd class="mt-1 text-sm text-gray-900"><?= date('M j, Y', strtotime($customer['updated_at'])) ?></dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Alpine.js for interactivity -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
