<?php
session_start();

// Hardcoded admin credentials
define('ADMIN_USERNAME', 'sportboots_admin');
define('ADMIN_PASSWORD', 'SportBoots@2024');

$error = '';

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_time'] = time();
        
        // Redirect to dashboard
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - SportBoots Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            --secondary-color: #3b82f6;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-light: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-heavy: 0 20px 60px rgba(0,0,0,0.15);
            --border-radius: 16px;
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .login-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-heavy);
            border: 1px solid var(--glass-border);
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 2;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-icon {
            width: 80px;
            height: 80px;
            background: var(--primary-gradient);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: var(--shadow-light);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .brand-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .brand-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e3a8a;
            margin-bottom: 0.5rem;
        }

        .brand-subtitle {
            color: #64748b;
            font-weight: 500;
            margin-bottom: 0;
        }

        .form-floating {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            font-size: 1rem;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            background: white;
            transform: translateY(-2px);
        }

        .form-floating > label {
            color: #64748b;
            font-weight: 500;
            padding-left: 1.25rem;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            z-index: 3;
            transition: all 0.3s ease;
        }

        .form-control:focus + label + .input-icon {
            color: var(--secondary-color);
        }

        .btn-login {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-light);
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-heavy);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }

        .login-footer p {
            color: #64748b;
            font-size: 0.9rem;
            margin: 0;
        }

        .security-note {
            text-align: center;
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(52, 211, 153, 0.1);
            border-radius: 12px;
            border-left: 4px solid #10b981;
        }

        .security-note i {
            color: #10b981;
            margin-right: 0.5rem;
        }

        .security-note p {
            color: #065f46;
            font-size: 0.85rem;
            margin: 0;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .login-container {
                margin: 1rem;
                padding: 2rem 1.5rem;
            }
            
            .brand-title {
                font-size: 1.5rem;
            }
            
            .brand-icon {
                width: 60px;
                height: 60px;
            }
            
            .brand-icon i {
                font-size: 2rem;
            }
        }

        /* Loading animation */
        .btn-login.loading {
            position: relative;
            color: transparent;
        }

        .btn-login.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="brand-logo">
            <div class="brand-icon">
                <i class="fas fa-running"></i>
            </div>
            <h1 class="brand-title">SportBoots Pro</h1>
            <p class="brand-subtitle">Admin Dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required autocomplete="username">
                <label for="username">Username</label>
                <i class="fas fa-user input-icon"></i>
            </div>

            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required autocomplete="current-password">
                <label for="password">Password</label>
                <i class="fas fa-lock input-icon"></i>
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>
                Sign In
            </button>
        </form>

        <div class="security-note">
            <i class="fas fa-info-circle"></i>
            <p>For security, admin sessions expire after 2 hours of inactivity</p>
        </div>

        <div class="login-footer">
            <p>© <?= date('Y') ?> SportBoots Pro. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.querySelector('.btn-login');
            btn.classList.add('loading');
            
            // Re-enable button after form submission completes
            setTimeout(() => {
                btn.classList.remove('loading');
            }, 2000);
        });

        // Add focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });

        // Add enter key support
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });
    </script>
</body>
</html>