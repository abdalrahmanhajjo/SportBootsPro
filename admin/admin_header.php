<?php
session_start();

// Authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}
?>
<!-- Admin Header and Sidebar -->
<style>
   :root {
            --primary: #000000;
            --primary-light: #333333;
            --secondary: #ffffff;
            --accent: #e63946;
            --accent-light: #f8aaaf;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --text: #2d3436;
            --text-light: #636e72;
            --text-lighter: #b2bec3;
            --bg: #f5f6fa;
            --card-bg: #ffffff;
            --border: #dfe6e9;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 20px rgba(0,0,0,0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --header-height: 60px;
            --sidebar-width: 280px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            padding-top: var(--header-height); /* Add this */
        }

        @supports (font-variation-settings: normal) {
            body { font-family: 'Inter var', sans-serif; }
        }

        /* Layout */
        .app-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            position: relative;
        }

        /* Header */
        .app-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background-color: var(--primary);
            color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1000;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .app-header h1 {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .app-header-actions {
            display: flex;
            gap: 15px;
        }

        .header-btn {
            background: none;
            border: none;
            color: var(--secondary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-btn:hover {
            background: rgba(255,255,255,0.1);
        }

        /* Sidebar */
        .app-sidebar {
            position: fixed;
            top: var(--header-height);
            left: calc(-1 * var(--sidebar-width));
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background-color: var(--primary);
            color: var(--secondary);
            transition: var(--transition);
            z-index: 999;
            overflow-y: auto;
            padding-bottom: 20px;
        }

        .app-sidebar.active {
            left: 0;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 2px;
        }

        .sidebar-menu a {
            color: var(--secondary);
            text-decoration: none;
            padding: 16px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
            position: relative;
            font-size: 0.95rem;
            font-weight: 500;
            opacity: 0.9;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.08);
            opacity: 1;
        }

        .sidebar-menu a.active {
            font-weight: 600;
        }

        .sidebar-menu a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--accent);
        }

        .sidebar-menu i {
            width: 24px;
            font-size: 1.1rem;
            display: flex;
            justify-content: center;
        }

        /* Overlay */
        .sidebar-overlay {
            position: fixed;
            top: var(--header-height);
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Spinner animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .spinner {
            animation: spin 1s linear infinite;
        }

        /* Main content area */
        .main-content {
            padding: 20px;
            margin-left: 0;
            transition: var(--transition);
        }
        
        @media (min-width: 1024px) {
            .app-sidebar {
                left: 0;
            }
            .main-content {
                margin-left: var(--sidebar-width);
            }
            .sidebar-overlay {
                display: none;
            }
        }
</style>

<header class="app-header">
    <h1>
        <i class="fas fa-shoe-prints"></i>
        SportBoots Pro
    </h1>
    <div class="app-header-actions">
        <button class="header-btn" id="refreshBtn" aria-label="Refresh">
            <i class="fas fa-sync-alt"></i>
        </button>
        <button class="header-btn" id="menuBtn" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>

<aside class="app-sidebar" id="sidebar">
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> Orders</a></li>
        <li><a href="products.php" class="<?= basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : '' ?>"><i class="fas fa-shoe-prints"></i> Products</a></li>
        <li><a href="customers.php" class="<?= basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Customers</a></li>
        <li><a href="inventory.php" class="<?= basename($_SERVER['PHP_SELF']) === 'inventory.php' ? 'active' : '' ?>"><i class="fas fa-warehouse"></i> Inventory</a></li>
        <li><a href="reports.php" class="<?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>"><i class="fas fa-chart-bar"></i> Reports</a></li>
        <li><a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuBtn = document.getElementById('menuBtn');
        const refreshBtn = document.getElementById('refreshBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (menuBtn && sidebar && overlay) {
            menuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            });

            overlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            });
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.add('spinner');
                    setTimeout(() => {
                        icon.classList.remove('spinner');
                        location.reload();
                    }, 1000);
                }
            });
        }
    });
</script>

<!-- Main Content Wrapper - Add this to your pages -->
<div class="main-content">
    <!-- Your page content goes here -->
</div>
