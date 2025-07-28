<?php
require_once 'config.php';

try {
    $db = getDBConnection();
    
    // Clear existing data
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("TRUNCATE TABLE order_items");
    $db->exec("TRUNCATE TABLE orders");
    $db->exec("TRUNCATE TABLE cart");
    $db->exec("TRUNCATE TABLE products");
    $db->exec("TRUNCATE TABLE categories");
    $db->exec("TRUNCATE TABLE users");
    $db->exec("TRUNCATE TABLE contact_messages");
    $db->exec("TRUNCATE TABLE newsletter_subscribers");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Insert categories with more details
    $categories = [
        ['name' => 'Football', 'description' => 'Professional football boots for grass and turf', 'icon' => '⚽'],
        ['name' => 'Running', 'description' => 'Lightweight running shoes for all distances', 'icon' => '🏃'],
        ['name' => 'Basketball', 'description' => 'High-performance basketball shoes', 'icon' => '🏀'],
        ['name' => 'Tennis', 'description' => 'Court shoes for all surfaces', 'icon' => '🎾'],
        ['name' => 'Training', 'description' => 'Versatile training and gym shoes', 'icon' => '💪'],
        ['name' => 'Trail Running', 'description' => 'Rugged shoes for off-road adventures', 'icon' => '🥾']
    ];
    
    $stmt = $db->prepare("INSERT INTO categories (name, description, icon) VALUES (:name, :description, :icon)");
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
    
    // Insert many more products with realistic stock quantities
    $products = [
        // Football Boots
        ['name' => 'Elite Strike Pro', 'category_id' => 1, 'price' => 299.99, 'description' => 'Professional football boots with advanced stud configuration', 'features' => 'Flyknit upper, Carbon fiber soleplate, ACC technology', 'icon' => '⚽', 'badge' => 'New', 'stock_quantity' => 45, 'is_featured' => true],
        ['name' => 'Predator Edge+', 'category_id' => 1, 'price' => 279.99, 'description' => 'Control the game with precision', 'features' => 'Control zones, Primeknit collar, Boost midsole', 'icon' => '⚽', 'badge' => null, 'stock_quantity' => 38, 'is_featured' => true],
        ['name' => 'Mercurial Vapor', 'category_id' => 1, 'price' => 259.99, 'description' => 'Built for speed on the pitch', 'features' => 'Vaporposite upper, Aerotrak zone, Tri-star studs', 'icon' => '⚽', 'badge' => 'Sale', 'stock_quantity' => 52, 'is_featured' => false],
        ['name' => 'Phantom GT2', 'category_id' => 1, 'price' => 239.99, 'description' => 'Precision control football boots', 'features' => 'Gripknit upper, Asymmetric lacing, AG soleplate', 'icon' => '⚽', 'badge' => null, 'stock_quantity' => 29, 'is_featured' => false],
        
        // Running Shoes
        ['name' => 'Speed Runner X', 'category_id' => 2, 'price' => 189.99, 'description' => 'Lightweight racing shoes for PRs', 'features' => 'Carbon plate, ZoomX foam, Flyknit upper', 'icon' => '🏃', 'badge' => null, 'stock_quantity' => 67, 'is_featured' => true],
        ['name' => 'UltraBoost 23', 'category_id' => 2, 'price' => 179.99, 'description' => 'Maximum cushioning for long runs', 'features' => 'Boost midsole, Primeknit+, Continental rubber', 'icon' => '🏃', 'badge' => 'New', 'stock_quantity' => 89, 'is_featured' => true],
        ['name' => 'Pegasus Turbo', 'category_id' => 2, 'price' => 169.99, 'description' => 'Daily trainer with race-day feel', 'features' => 'React foam, Zoom Air pods, Engineered mesh', 'icon' => '🏃', 'badge' => null, 'stock_quantity' => 76, 'is_featured' => false],
        ['name' => 'Endorphin Pro 3', 'category_id' => 2, 'price' => 229.99, 'description' => 'Marathon racing shoes', 'features' => 'PWRRUN PB cushioning, Carbon plate, FORMFIT', 'icon' => '🏃', 'badge' => 'Limited', 'stock_quantity' => 23, 'is_featured' => true],
        
        // Basketball Shoes
        ['name' => 'Court Master Elite', 'category_id' => 3, 'price' => 249.99, 'description' => 'Professional basketball performance', 'features' => 'Full-length Air Max, Flyweave upper, Zoom Air', 'icon' => '🏀', 'badge' => 'Limited', 'stock_quantity' => 34, 'is_featured' => true],
        ['name' => 'Signature Pro 12', 'category_id' => 3, 'price' => 219.99, 'description' => 'Signature series basketball shoes', 'features' => 'Boost cushioning, Herringbone traction, Lockdown support', 'icon' => '🏀', 'badge' => null, 'stock_quantity' => 56, 'is_featured' => false],
        ['name' => 'Freak 4', 'category_id' => 3, 'price' => 159.99, 'description' => 'Versatile court performance', 'features' => 'Zoom Air, Multidirectional traction, Midfoot strap', 'icon' => '🏀', 'badge' => 'Sale', 'stock_quantity' => 71, 'is_featured' => false],
        
        // Tennis Shoes
        ['name' => 'Ace Serve Pro', 'category_id' => 4, 'price' => 179.99, 'description' => 'Professional tennis court shoes', 'features' => 'GEL cushioning, AHAR outsole, Flexion Fit', 'icon' => '🎾', 'badge' => null, 'stock_quantity' => 43, 'is_featured' => true],
        ['name' => 'Court Speed FF', 'category_id' => 4, 'price' => 149.99, 'description' => 'Fast court coverage', 'features' => 'FlyteFoam, Solyte midsole, PGuard toe', 'icon' => '🎾', 'badge' => null, 'stock_quantity' => 62, 'is_featured' => false],
        
        // Training Shoes
        ['name' => 'CrossFit Nano X3', 'category_id' => 5, 'price' => 139.99, 'description' => 'Versatile training shoes', 'features' => 'Flexweave upper, Floatride Energy, Wide toe box', 'icon' => '💪', 'badge' => 'New', 'stock_quantity' => 88, 'is_featured' => true],
        ['name' => 'Metcon 8', 'category_id' => 5, 'price' => 129.99, 'description' => 'Stable training platform', 'features' => 'React foam, Rope wrap, Breathable mesh', 'icon' => '💪', 'badge' => null, 'stock_quantity' => 94, 'is_featured' => false],
        
        // Trail Running
        ['name' => 'Trail Blazer X', 'category_id' => 6, 'price' => 229.99, 'description' => 'Conquer any terrain', 'features' => 'Vibram outsole, Gore-Tex upper, Rock plate', 'icon' => '🥾', 'badge' => null, 'stock_quantity' => 37, 'is_featured' => false],
        ['name' => 'Speedgoat 5', 'category_id' => 6, 'price' => 159.99, 'description' => 'Maximum cushion trail runner', 'features' => 'Meta-Rocker, Vibram Megagrip, Wide platform', 'icon' => '🥾', 'badge' => 'Sale', 'stock_quantity' => 51, 'is_featured' => true]
    ];
    
    $stmt = $db->prepare("INSERT INTO products (name, category_id, price, description, features, icon, badge, stock_quantity, is_featured) 
                          VALUES (:name, :category_id, :price, :description, :features, :icon, :badge, :stock_quantity, :is_featured)");
    
    foreach ($products as $product) {
        $stmt->execute($product);
    }
    
    // Create a test user
    $hashedPassword = password_hash('testpass123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, phone, address) 
                          VALUES (:username, :email, :password, :full_name, :phone, :address)");
    $stmt->execute([
        'username' => 'testuser',
        'email' => 'test@sportbootspro.com',
        'password' => $hashedPassword,
        'full_name' => 'Test User',
        'phone' => '+1234567890',
        'address' => '123 Test Street, Test City, TC 12345'
    ]);
    
    // Add some sample newsletter subscribers
    $subscribers = [
        'john@example.com',
        'sarah@example.com',
        'mike@example.com',
        'emma@example.com'
    ];
    
    $stmt = $db->prepare("INSERT INTO newsletter_subscribers (email) VALUES (:email)");
    foreach ($subscribers as $email) {
        $stmt->execute(['email' => $email]);
    }
    
    echo "Database setup complete! Sample data has been inserted successfully.\n";
    echo "Total products: " . count($products) . "\n";
    echo "Total categories: " . count($categories) . "\n";
    echo "Test user created: test@sportbootspro.com (password: testpass123)\n";
    
} catch(Exception $e) {
    echo "Setup failed: " . $e->getMessage() . "\n";
}
?>