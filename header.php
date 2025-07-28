<?php
// Start session
session_start();

// Generate a unique device ID if it doesn't exist
if (!isset($_SESSION['device_id'])) {
    // Create a fingerprint from various device/browser characteristics
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    
    // Create a hash that will be unique per device
    $_SESSION['device_id'] = md5($userAgent . $ipAddress . $acceptLanguage);
    
    // Initialize cart for this device
    $_SESSION['cart'] = [];
}
// Include database connection
require_once 'config.php';

// Get categories for navigation
$categories = [];
$result = $conn->query("SELECT * FROM categories");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get cart count
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportBoots Pro | Engineered for Champions</title>
    <meta name="description" content="Professional athletic footwear engineered for elite performance. Discover our innovative sports boots trusted by champions worldwide.">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Preconnect to external resources -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Loader -->
    <div class="loader" id="loader">
        <div class="loader-content">
            <div class="loader-icon">👟</div>
            <p style="color: var(--gray-600); font-weight: 600;">Loading SportBoots Pro...</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="nav" id="nav">
        <div class="container">
            <div class="nav-container">
                <a class="nav-logo" href="index.php">
                    <div class="nav-logo-icon">👟</div>
                    <span>SportBoots<span style="color: var(--primary);">Pro</span></span>
                </a>

                <ul class="nav-menu">
                    <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">Home</a></li>
                    <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'shop.php' ? 'active' : '' ?>" href="shop.php">Shop</a></li>
                    <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'technology.php' ? 'active' : '' ?>" href="technology.php">Technology</a></li>
                    <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : '' ?>" href="about.php">About</a></li>
                    <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>" href="contact.php">Contact</a></li>
                </ul>

                <div class="nav-actions">
                    <div class="nav-cart" onclick="window.location.href='cart.php'">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 22C9.55228 22 10 21.5523 10 21C10 20.4477 9.55228 20 9 20C8.44772 20 8 20.4477 8 21C8 21.5523 8.44772 22 9 22Z" />
                            <path d="M20 22C20.5523 22 21 21.5523 21 21C21 20.4477 20.5523 20 20 20C19.4477 20 19 20.4477 19 21C19 21.5523 19.4477 22 20 22Z" />
                            <path d="M1 1H5L7.68 14.39C7.77144 14.8504 8.02191 15.264 8.38755 15.5583C8.75318 15.8526 9.2107 16.009 9.68 16H19.4C19.8693 16.009 20.3268 15.8526 20.6925 15.5583C21.0581 15.264 21.3086 14.8504 21.4 14.39L23 6H6" />
                        </svg>
                        <span class="cart-count" id="cartCount"><?= $cart_count ?></span>
                    </div>
                    <a class="btn btn-primary" href="shop.php">Get Started</a>
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </div>
    </nav>
      <!-- WhatsApp Floating Button -->
    <a href="https://wa.me/96176536462" class="whatsapp-float" target="_blank" rel="noopener noreferrer">
        <i class="fab fa-whatsapp whatsapp-icon"></i>
    </a>
     <style>
        /* WhatsApp Floating Button Styles */
        .whatsapp-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background-color: #25D366;
            color: #FFF;
            border-radius: 50%;
            text-align: center;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }

        .whatsapp-float:hover {
            background-color: #128C7E;
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(37, 211, 102, 0.4);
        }

        .whatsapp-icon {
            font-size: 32px;
        }

        /* Back to Top Button Styles */
        .back-to-top {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 50px;
            height: 50px;
            background-color: var(--primary);
            color: #FFF;
            border: none;
            border-radius: 50%;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 99;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
        }

        /* Animation for WhatsApp button */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7);
            }
            70% {
                box-shadow: 0 0 0 12px rgba(37, 211, 102, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(37, 211, 102, 0);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .whatsapp-float {
                width: 50px;
                height: 50px;
                bottom: 20px;
                right: 20px;
            }

            .whatsapp-icon {
                font-size: 28px;
            }

            .back-to-top {
                width: 45px;
                height: 45px;
                bottom: 80px;
                right: 20px;
            }
        }
    </style>

    <script>
        // WhatsApp button functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Back to Top Button
            const backToTopButton = document.getElementById('backToTop');
            
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.add('visible');
                } else {
                    backToTopButton.classList.remove('visible');
                }
            });
            
            backToTopButton.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            // Mobile menu functionality (if not already in your code)
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const navMenu = document.querySelector('.nav-menu');
            
            if (mobileMenuBtn && navMenu) {
                mobileMenuBtn.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    mobileMenuBtn.classList.toggle('active');
                });
            }
        });
        
    </script>