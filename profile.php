<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if config constants are defined, if not define them
if (!defined('ADMIN_USERNAME')) {
    define('ADMIN_USERNAME', 'sportboots_admin');
    define('ADMIN_PASSWORD', 'SportBoots@2024'); // Replace with your actual password
}

// Authentication check
if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_username']) || $_SESSION['admin_username'] !== ADMIN_USERNAME) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate current password if changing password
    if (!empty($new_password)) {
        if ($current_password !== ADMIN_PASSWORD) {
            $error = "Current password is incorrect";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match";
        } else {
            // In a real application, you would update the password in a secure way
            $_SESSION['success_message'] = "Password updated successfully! (Note: In this demo, the password isn't actually changed in the config file)";
            header("Location: profile.php");
            exit();
        }
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
    <title>Profile - SportBoots Pro</title>
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
            
            --spring-bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
            --spring-smooth: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-primary: #1a1a1a;
                --bg-secondary: #2a2a2a;
                --bg-tertiary: #3a3a3a;
                --text-primary: #ffffff;
                --text-secondary: #b0b0b0;
                --border-light: #3a3a3a;
                --border-medium: #4a4a4a;
            }
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

        /* Search Bar */
        .search-container {
            margin-bottom: 20px;
        }

        .search-wrapper {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-full);
            font-size: 14px;
            background: var(--bg-primary);
            transition: all var(--transition-fast);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255, 56, 92, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 16px;
        }

        /* Stats Cards */
        .stats-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -16px;
            padding: 0 16px;
            scrollbar-width: none;
            -ms-overflow-style: none;
            margin-bottom: 20px;
        }

        .stats-scroll::-webkit-scrollbar {
            display: none;
        }

        .stats-container {
            display: flex;
            gap: 12px;
            padding-bottom: 4px;
            min-width: min-content;
        }

        .stat-card {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: 16px;
            min-width: 140px;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-base);
            border: 1px solid var(--border-light);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .stat-card:active {
            transform: scale(0.98);
            box-shadow: var(--shadow-md);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent);
            transform: scaleX(0);
            transition: transform var(--transition-base);
        }

        .stat-card.primary::before {
            transform: scaleX(1);
        }

        .stat-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background: rgba(255, 56, 92, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            margin-bottom: 12px;
            font-size: 16px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 4px;
            color: var(--text-primary);
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .stat-trend {
            position: absolute;
            top: 16px;
            right: 16px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .stat-trend.up {
            color: var(--success);
        }

        .stat-trend.down {
            color: var(--danger);
        }

        /* Status Filter Pills */
        .filter-section {
            margin-bottom: 20px;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .filter-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .filter-action {
            font-size: 14px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .status-pills {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 4px;
            margin: 0 -16px;
            padding: 0 16px 4px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .status-pills::-webkit-scrollbar {
            display: none;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--bg-primary);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-full);
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            white-space: nowrap;
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
            flex-shrink: 0;
        }

        .status-pill:active {
            transform: scale(0.95);
        }

        .status-pill.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .status-count {
            background: rgba(0, 0, 0, 0.1);
            padding: 2px 8px;
            border-radius: var(--radius-full);
            font-size: 12px;
            font-weight: 600;
        }

        .status-pill.active .status-count {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Order Cards */
        .order-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .order-card {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            overflow: hidden;
            cursor: pointer;
            transition: all var(--transition-base);
            text-decoration: none;
            color: inherit;
        }

        .order-card:active {
            transform: scale(0.98);
            box-shadow: var(--shadow-md);
        }

        .order-card-main {
            padding: 16px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .order-id {
            font-size: 16px;
            font-weight: 600;
            color: var(--accent);
        }

        .order-status {
            padding: 4px 12px;
            border-radius: var(--radius-full);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: rgba(255, 180, 0, 0.1);
            color: var(--warning);
        }

        .status-processing {
            background: rgba(52, 152, 219, 0.1);
            color: var(--info);
        }

        .status-shipped {
            background: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }

        .status-delivered {
            background: rgba(0, 166, 153, 0.1);
            color: var(--success);
        }

        .status-cancelled {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .order-customer {
            margin-bottom: 12px;
        }

        .customer-name {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .customer-details {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .customer-detail {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .customer-detail i {
            font-size: 12px;
            color: var(--text-light);
        }

        .order-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid var(--border-light);
        }

        .order-details {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .order-amount {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .order-time {
            font-size: 11px;
            color: var(--text-light);
            margin-top: 4px;
        }

        .order-actions {
            display: flex;
            border-top: 1px solid var(--border-light);
        }

        .order-action {
            flex: 1;
            padding: 12px;
            border: none;
            background: none;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .order-action:not(:last-child) {
            border-right: 1px solid var(--border-light);
        }

        .order-action:active {
            background: var(--bg-secondary);
        }

        .order-action.primary {
            color: var(--accent);
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

        /* Side Sheet */
        .side-sheet {
            position: fixed;
            top: 0;
            right: -100%;
            width: 85%;
            max-width: 320px;
            height: 100%;
            background: var(--bg-primary);
            z-index: 200;
            transition: right var(--transition-slow) var(--spring-smooth);
            box-shadow: -10px 0 30px rgba(0,0,0,0.1);
        }

        .side-sheet.active {
            right: 0;
        }

        .side-sheet-header {
            padding: var(--safe-area-inset-top) 20px 20px;
            padding-top: calc(var(--safe-area-inset-top) + 20px);
            border-bottom: 1px solid var(--border-light);
        }

        .side-sheet-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .side-sheet-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .side-sheet-content {
            padding: 20px;
            overflow-y: auto;
            height: calc(100% - 100px);
            -webkit-overflow-scrolling: touch;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 0;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            border-bottom: 1px solid var(--border-light);
            transition: all var(--transition-fast);
        }

        .menu-item:active {
            opacity: 0.7;
        }

        .menu-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            background: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
        }

        .menu-item.danger {
            color: var(--danger);
        }

        .menu-item.danger .menu-icon {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        /* Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            opacity: 0;
            visibility: hidden;
            z-index: 150;
            transition: all var(--transition-base);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 48px;
            color: var(--text-light);
            margin-bottom: 16px;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .empty-text {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Loading Spinner */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .spinner {
            animation: spin 0.8s linear infinite;
        }

        /* Animations */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Safe Area Adjustments */
        @supports (padding: max(0px)) {
            .header {
                padding-top: max(var(--safe-area-inset-top), 0px);
            }
            
            .bottom-nav {
                padding-bottom: max(var(--safe-area-inset-bottom), 0px);
            }
        }

        /* Responsive Adjustments */
        @media (max-width: 320px) {
            .stats-container {
                gap: 8px;
            }
            
            .stat-card {
                min-width: 120px;
                padding: 12px;
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
                    <i class="fas fa-user"></i>
                    Profile
                </h1>
                <div class="header-actions">
                    <button class="header-btn" id="saveBtn" type="submit" form="profileForm">
                        <i class="fas fa-save"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main" id="mainContent">
            <div class="main-content">
                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="form-section">
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
                    </div>
                </div>
                <?php unset($_SESSION['success_message']); endif; ?>

                <?php if (isset($error)): ?>
                <div class="form-section">
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    </div>
                </div>
                <?php endif; ?>

                <form id="profileForm" method="POST" class="form-section">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?= strtoupper(substr(ADMIN_USERNAME, 0, 1)) ?>
                        </div>
                        <div class="profile-info">
                            <div class="profile-name"><?= ADMIN_USERNAME ?></div>
                            <div class="profile-role">Administrator</div>
                        </div>
                    </div>

                    <h3 class="form-title">
                        <i class="fas fa-lock"></i>
                        Change Password
                    </h3>

                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-save"></i> Update Password
                        </button>
                    </div>
                </form>

                <div class="form-section">
                    <h3 class="form-title">
                        <i class="fas fa-cog"></i>
                        Account Settings
                    </h3>
                    
                    <div class="form-group">
                        <a href="logout.php" class="btn btn-secondary" style="width: 100%; display: block; text-align: center; text-decoration: none;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
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
                
                <a href="profile.php" class="nav-item active">
                    <div class="nav-icon">
                        <i class="fas fa-user"></i>
                        <span class="nav-indicator"></span>
                    </div>
                    <span class="nav-label">Profile</span>
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

        // Password confirmation validation
        const form = document.getElementById('profileForm');
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        form.addEventListener('submit', function(e) {
            if (newPassword.value !== confirmPassword.value) {
                e.preventDefault();
                alert('New password and confirmation do not match');
            }
        });

        // Add haptic feedback to buttons
        document.querySelectorAll('button, a').forEach(element => {
            element.addEventListener('click', () => {
                if ('vibrate' in navigator) {
                    navigator.vibrate(5);
                }
            });
        });
    </script>
</body>
</html>