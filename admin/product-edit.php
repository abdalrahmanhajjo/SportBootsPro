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

if ($product_id <= 0) {
    header("Location: products.php");
    exit();
}

// Fetch product details
$product_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$product_stmt->execute([$product_id]);
$product = $product_stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit();
}

// Fetch all categories
$categories_stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'];
        $category_id = $_POST['category_id'];
        $price = floatval($_POST['price']);
        $description = $_POST['description'];
        $features = $_POST['features'];
        $stock_quantity = intval($_POST['stock_quantity']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $sizes = $_POST['sizes'];
        
        // Handle image upload
        $image_url = $product['image_url'];
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "../uploads/products/";
            $target_file = $target_dir . basename($_FILES['image']['name']);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Check if image file is valid
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check === false) {
                throw new Exception("File is not an image.");
            }
            
            // Generate unique filename
            $new_filename = uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $new_filename;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                throw new Exception("Sorry, there was an error uploading your file.");
            }
            
            $image_url = "uploads/products/" . $new_filename;
            
            // Delete old image if it exists
            if (!empty($product['image_url']) && file_exists("../" . $product['image_url'])) {
                unlink("../" . $product['image_url']);
            }
        }
        
        // Update product
        $update_stmt = $conn->prepare("
            UPDATE products 
            SET name = ?, 
                category_id = ?, 
                price = ?, 
                description = ?, 
                features = ?, 
                image_url = ?, 
                stock_quantity = ?, 
                is_featured = ?,
                sizes = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $update_stmt->execute([
            $name,
            $category_id,
            $price,
            $description,
            $features,
            $image_url,
            $stock_quantity,
            $is_featured,
            $sizes,
            $product_id
        ]);
        
        $success_message = 'Product updated successfully!';
    } catch (Exception $e) {
        $error_message = "Error updating product: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - SportBoots Pro Admin</title>
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
        .image-preview-container {
            transition: all 0.3s ease;
        }
        .image-preview-container:hover {
            transform: scale(1.02);
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
                    <a href="products.php" class="flex items-center px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-md group">
                        <i class="flex-shrink-0 w-6 mr-3 text-white fas fa-tshirt"></i>
                        Products
                    </a>
                    <a href="customers.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md group">
                        <i class="flex-shrink-0 w-6 mr-3 text-gray-400 fas fa-users"></i>
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
                <a href="products.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-white bg-primary-600">
                    <i class="flex-shrink-0 w-6 mr-3 text-white fas fa-tshirt"></i>
                    Products
                </a>
                <a href="customers.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                    <i class="flex-shrink-0 w-6 mr-3 text-gray-400 fas fa-users"></i>
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
            <h1 class="text-lg font-medium text-gray-900">Edit Product</h1>
            <div class="w-8"></div> <!-- Spacer for balance -->
        </div>

        <!-- Main content area -->
        <main class="flex-1 pb-8">
            <!-- Page header -->
            <div class="bg-white shadow">
                <div class="px-4 py-6 sm:px-6 lg:px-8">
                    <div class="flex flex-col justify-between space-y-4 sm:space-y-0 sm:flex-row sm:items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Edit Product</h1>
                            <p class="mt-1 text-sm text-gray-500">Update product details and inventory</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="products.php" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <i class="mr-2 fas fa-arrow-left"></i> Back to Products
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($success_message): ?>
            <div class="px-4 pt-6 sm:px-6 lg:px-8 animate-fade-in">
                <div class="p-4 rounded-md bg-success-50">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="text-success-600 fas fa-check-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-success-800"><?= htmlspecialchars($success_message) ?></p>
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
            <?php endif; ?>

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

            <!-- Product edit form -->
            <div class="px-4 mt-6 sm:px-6 lg:px-8">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <form method="post" enctype="multipart/form-data" class="divide-y divide-gray-200">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <!-- Left column -->
                                <div class="space-y-6">
                                    <!-- Product Name -->
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">Product Name</label>
                                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    </div>

                                    <!-- Category -->
                                    <div>
                                        <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                                        <select id="category_id" name="category_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                            <option value="">Select a category</option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" <?= $category['id'] == $product['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Price -->
                                    <div>
                                        <label for="price" class="block text-sm font-medium text-gray-700">Price</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">$</span>
                                            </div>
                                            <input type="number" step="0.01" id="price" name="price" value="<?= number_format($product['price'], 2, '.', '') ?>" required class="focus:ring-primary-500 focus:border-primary-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md">
                                        </div>
                                    </div>

                                    <!-- Stock Quantity -->
                                    <div>
                                        <label for="stock_quantity" class="block text-sm font-medium text-gray-700">Stock Quantity</label>
                                        <input type="number" id="stock_quantity" name="stock_quantity" value="<?= $product['stock_quantity'] ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    </div>

                                    <!-- Sizes -->
                                    <div>
                                        <label for="sizes" class="block text-sm font-medium text-gray-700">Available Sizes (comma separated)</label>
                                        <input type="text" id="sizes" name="sizes" value="<?= htmlspecialchars($product['sizes']) ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="e.g., S,M,L,XL">
                                    </div>

                                    <!-- Featured Product -->
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="is_featured" name="is_featured" type="checkbox" <?= $product['is_featured'] ? 'checked' : '' ?> class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="is_featured" class="font-medium text-gray-700">Featured Product</label>
                                            <p class="text-gray-500">Show this product in featured sections</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right column -->
                                <div class="space-y-6">
                                    <!-- Image Upload -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Product Image</label>
                                        <div class="mt-1 flex items-center">
                                            <div class="image-preview-container relative rounded-md overflow-hidden border border-gray-300 w-full h-64 bg-gray-100 flex items-center justify-center">
                                                <?php if (!empty($product['image_url'])): ?>
                                                <img id="image-preview" src="../<?= htmlspecialchars($product['image_url']) ?>" alt="Product Image" class="absolute inset-0 w-full h-full object-contain">
                                                <?php else: ?>
                                                <div id="image-placeholder" class="text-gray-400">
                                                    <i class="fas fa-camera text-4xl"></i>
                                                    <p class="mt-2 text-sm">No image selected</p>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <input type="file" id="image" name="image" accept="image/*" class="sr-only" onchange="previewImage(this)">
                                            <label for="image" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                                <i class="fas fa-upload mr-2"></i> Upload Image
                                            </label>
                                            <p class="mt-1 text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mt-6">
                                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                <div class="mt-1">
                                    <textarea id="description" name="description" rows="3" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border border-gray-300 rounded-md"><?= htmlspecialchars($product['description']) ?></textarea>
                                </div>
                            </div>

                            <!-- Features -->
                            <div class="mt-6">
                                <label for="features" class="block text-sm font-medium text-gray-700">Features (one per line)</label>
                                <div class="mt-1">
                                    <textarea id="features" name="features" rows="3" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border border-gray-300 rounded-md"><?= htmlspecialchars($product['features']) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Form actions -->
                        <div class="px-4 py-4 bg-gray-50 text-right sm:px-6">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <i class="fas fa-save mr-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Alpine.js for interactivity -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            const placeholder = document.getElementById('image-placeholder');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (!preview) {
                        // Create new image element if it doesn't exist
                        const newPreview = document.createElement('img');
                        newPreview.id = 'image-preview';
                        newPreview.className = 'absolute inset-0 w-full h-full object-contain';
                        newPreview.src = e.target.result;
                        document.querySelector('.image-preview-container').appendChild(newPreview);
                        
                        if (placeholder) {
                            placeholder.style.display = 'none';
                        }
                    } else {
                        preview.src = e.target.result;
                    }
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Initialize any other interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // You can add more initialization code here if needed
        });
    </script>
</body>
</html>
