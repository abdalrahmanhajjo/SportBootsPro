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

// Check if settings table exists
$table_exists = false;
try {
    $conn->query("SELECT 1 FROM settings LIMIT 1");
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // Create settings table if it doesn't exist
        if (!$table_exists) {
            $conn->exec("CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(50) PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            $table_exists = true;
        }
        
        // Update settings
        $settings = [
            'store_name' => $_POST['store_name'],
            'store_email' => $_POST['store_email'],
            'store_phone' => $_POST['store_phone'],
            'store_address' => $_POST['store_address'],
            'shipping_cost' => floatval($_POST['shipping_cost']),
            'tax_rate' => floatval($_POST['tax_rate']),
            'currency' => $_POST['currency'],
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        $conn->commit();
        $_SESSION['success_message'] = "Settings updated successfully!";
        header("Location: settings.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Error updating settings: " . $e->getMessage();
        header("Location: settings.php");
        exit();
    }
}

// Default values for settings
$default_settings = [
    'store_name' => 'SportBoots Pro',
    'store_email' => 'info@sportbootspro.com',
    'store_phone' => '+1 (555) 123-4567',
    'store_address' => '123 Sports Ave, City, Country',
    'shipping_cost' => '9.99',
    'tax_rate' => '8.00',
    'currency' => 'USD',
    'maintenance_mode' => '0'
];

// Fetch current settings if table exists
$settings = $default_settings;
if ($table_exists) {
    try {
        $settings_stmt = $conn->query("SELECT * FROM settings");
        while ($row = $settings_stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error loading settings: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Settings - SportBoots Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #000000;
            --primary-dark: #0a0a0a;
            --secondary: #ffffff;
            --accent: #FF385C;
            --accent-light: #FF5A5F;
            --accent-dark: #E31C5F;
            --success: #00A699;
            --warning: #FFB400;
            --danger: #E74C3C;
            --info: #3498db;
            
            --text-primary: #222222;
            --text-secondary: #717171;
            --text-light: #B0B0B0;
            
            --bg-primary: #FFFFFF;
            --bg-secondary: #F7F7F7;
            --bg-tertiary: #EBEBEB;
            
            --border-light: #DDDDDD;
            --border-medium: #C4C4C4;
            
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.08);
            --shadow-md: 0 3px 10px rgba(0,0,0,0.12);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.16);
            --shadow-xl: 0 20px 40px rgba(0,0,0,0.2);
            
            --radius-xs: 8px;
            --radius-sm: 12px;
            --radius-md: 16px;
            --radius-lg: 24px;
            --radius-full: 9999px;
            
            --header-height: 56px;
            --bottom-nav-height: 65px;
            --safe-area-inset-top: env(safe-area-inset-top);
            --safe-area-inset-bottom: env(safe-area-inset-bottom);
            
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 250ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 350ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
        }

        html {
            height: 100%;
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.5;
            height: 100%;
            overflow: hidden;
            position: fixed;
            width: 100%;
            overscroll-behavior: none;
        }

        /* App Container */
        .app {
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: calc(var(--vh, 1vh) * 100);
            position: relative;
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: calc(var(--header-height) + var(--safe-area-inset-top));
            padding-top: var(--safe-area-inset-top);
            background: var(--bg-primary);
            z-index: 100;
            border-bottom: 1px solid var(--border-light);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .header-content {
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
        }

        .header-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-actions {
            display: flex;
            gap: 8px;
        }

        .header-btn {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-full);
            border: none;
            background: transparent;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-fast);
            position: relative;
        }

        .header-btn:active {
            transform: scale(0.92);
            background: var(--bg-tertiary);
        }

        /* Main Content */
        .main {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            padding-top: calc(var(--header-height) + var(--safe-area-inset-top));
            padding-bottom: calc(var(--bottom-nav-height) + var(--safe-area-inset-bottom) + 20px);
            scroll-behavior: smooth;
        }

        .main-content {
            padding: 20px 16px;
        }

        /* Form Elements */
        .form-section {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: 16px;
            background: var(--bg-primary);
            transition: all var(--transition-fast);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255, 56, 92, 0.1);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--border-light);
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: var(--accent);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        /* Messages */
        .alert-message {
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background-color: rgba(0, 166, 153, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:active {
            background: var(--accent-dark);
        }

        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: calc(var(--bottom-nav-height) + var(--safe-area-inset-bottom));
            padding-bottom: var(--safe-area-inset-bottom);
            background: var(--bg-primary);
            border-top: 1px solid var(--border-light);
            z-index: 100;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .bottom-nav-content {
            height: var(--bottom-nav-height);
            display: flex;
            align-items: center;
            justify-content: space-around;
        }

        .nav-item {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            text-decoration: none;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-fast);
            position: relative;
        }

        .nav-item.active {
            color: var(--accent);
        }

        .nav-item:active {
            transform: scale(0.95);
        }

        .nav-icon {
            font-size: 22px;
            position: relative;
        }

        .nav-label {
            font-size: 10px;
            font-weight: 600;
        }

        .nav-indicator {
            position: absolute;
            top: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            background: var(--accent);
            border-radius: 50%;
            opacity: 0;
            transition: opacity var(--transition-fast);
        }

        .nav-item.active .nav-indicator {
            opacity: 1;
        }

        /* Responsive Adjustments */
        @media (max-width: 320px) {
            .form-control {
                padding: 10px 14px;
            }
            
            .btn {
                padding: 10px 16px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1 class="header-title">
                    <i class="fas fa-cog"></i>
                    Settings
                </h1>
                <div class="header-actions">
                    <button class="header-btn" id="saveBtn" type="submit" form="settingsForm">
                        <i class="fas fa-save"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main" id="mainContent">
            <div class="main-content">
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert-message alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?= htmlspecialchars($_SESSION['success_message']) ?></div>
                </div>
                <?php unset($_SESSION['success_message']); endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert-message alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?= htmlspecialchars($_SESSION['error_message']) ?></div>
                </div>
                <?php unset($_SESSION['error_message']); endif; ?>

                <form id="settingsForm" method="POST">
                    <!-- Store Information Section -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-store"></i>
                            Store Information
                        </h2>
                        <p class="section-subtitle">Basic information about your store</p>

                        <div class="form-group">
                            <label class="form-label" for="store_name">Store Name</label>
                            <input type="text" class="form-control" id="store_name" name="store_name" value="<?= htmlspecialchars($settings['store_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="store_email">Contact Email</label>
                            <input type="email" class="form-control" id="store_email" name="store_email" value="<?= htmlspecialchars($settings['store_email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="store_phone">Contact Phone</label>
                            <input type="tel" class="form-control" id="store_phone" name="store_phone" value="<?= htmlspecialchars($settings['store_phone']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="store_address">Store Address</label>
                            <textarea class="form-control form-textarea" id="store_address" name="store_address"><?= htmlspecialchars($settings['store_address']) ?></textarea>
                        </div>
                    </div>

                    <!-- Shipping & Tax Section -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-truck"></i>
                            Shipping & Tax
                        </h2>
                        <p class="section-subtitle">Configure your shipping costs and tax rates</p>

                        <div class="form-group">
                            <label class="form-label" for="shipping_cost">Standard Shipping Cost</label>
                            <input type="number" step="0.01" class="form-control" id="shipping_cost" name="shipping_cost" value="<?= htmlspecialchars($settings['shipping_cost']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="tax_rate">Tax Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" id="tax_rate" name="tax_rate" value="<?= htmlspecialchars($settings['tax_rate']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="currency">Currency</label>
                            <select class="form-control" id="currency" name="currency">
                                <option value="USD" <?= $settings['currency'] === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                                <option value="EUR" <?= $settings['currency'] === 'EUR' ? 'selected' : '' ?>>EUR (€)</option>
                                <option value="GBP" <?= $settings['currency'] === 'GBP' ? 'selected' : '' ?>>GBP (£)</option>
                                <option value="JPY" <?= $settings['currency'] === 'JPY' ? 'selected' : '' ?>>JPY (¥)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Maintenance Mode Section -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-tools"></i>
                            Maintenance Mode
                        </h2>
                        <p class="section-subtitle">Take your store offline for maintenance</p>

                        <div class="form-group" style="display: flex; align-items: center; gap: 12px;">
                            <label class="toggle-switch">
                                <input type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <label for="maintenance_mode" style="font-weight: 500;">Enable Maintenance Mode</label>
                        </div>
                        <p style="font-size: 12px; color: var(--text-secondary);">When enabled, only administrators can access the store</p>
                    </div>

                    <!-- Save Button -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <div class="bottom-nav-content">
                <a href="dashboard.php" class="nav-item">
                    <div class="nav-icon">
                        <i class="fas fa-home"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Home</span>
                </a>
                
                <a href="orders.php" class="nav-item">
                    <div class="nav-icon">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Orders</span>
                </a>
                
                <a href="products.php" class="nav-item">
                    <div class="nav-icon">
                        <i class="fas fa-shoe-prints"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Products</span>
                </a>
                
                <a href="settings.php" class="nav-item active">
                    <div class="nav-icon">
                        <i class="fas fa-cog"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Settings</span>
                </a>
            </div>
        </nav>
    </div>

    <script>
        // Set viewport height for mobile browsers
        function setViewportHeight() {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);

        // Add haptic feedback to buttons
        document.querySelectorAll('button, a').forEach(element => {
            element.addEventListener('click', () => {
                if ('vibrate' in navigator) {
                    navigator.vibrate(5);
                }
            });
        });

        // Form validation
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            const shippingCost = parseFloat(document.getElementById('shipping_cost').value);
            const taxRate = parseFloat(document.getElementById('tax_rate').value);
            
            if (isNaN(shippingCost) || shippingCost < 0) {
                alert('Please enter a valid shipping cost');
                e.preventDefault();
            }
            
            if (isNaN(taxRate) || taxRate < 0 || taxRate > 100) {
                alert('Please enter a valid tax rate between 0 and 100');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>