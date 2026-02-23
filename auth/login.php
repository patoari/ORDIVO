<?php
/**
 * ORDIVO - Login Page
 * Simple authentication for demo purposes
 */

require_once '../config/db_connection.php';

// Get site settings
$siteSettings = fetchRow("SELECT * FROM site_settings WHERE id = 1") ?? [];
$siteLogo = $siteSettings['logo_url'] ?? '';
$siteName = $siteSettings['site_name'] ?? 'ORDIVO';

// Fix logo path
if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️') {
    if (strpos($siteLogo, 'uploads/') === 0) {
        $siteLogo = '../' . $siteLogo;
    }
    elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $siteLogo)) {
        $siteLogo = '../' . $siteLogo;
    }
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        try {
            // Check if user exists
            $user = fetchRow("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_phone'] = $user['phone'] ?? '';
                $_SESSION['user_address'] = $user['address'] ?? '';
                
                // Check if there's a redirect parameter
                $redirect = $_GET['redirect'] ?? '';
                
                // If redirect is checkout, go to checkout page
                if ($redirect === 'checkout') {
                    header('Location: ../customer/checkout.php');
                    exit;
                }
                
                // Otherwise, redirect based on role from database
                switch ($user['role']) {
                    case 'super_admin':
                        header('Location: ../super_admin/dashboard.php');
                        break;
                    case 'vendor':
                        header('Location: ../vendor/dashboard.php');
                        break;
                    case 'kitchen_manager':
                        header('Location: ../kitchen/dashboard.php');
                        break;
                    case 'kitchen_staff':
                        header('Location: ../kitchen/dashboard.php');
                        break;
                    case 'store_manager':
                        header('Location: ../vendor/dashboard.php');
                        break;
                    case 'store_staff':
                        header('Location: ../vendor/dashboard.php');
                        break;
                    case 'delivery_rider':
                        header('Location: ../customer/index.php'); // No specific delivery dashboard yet
                        break;
                    case 'customer':
                    default:
                        header('Location: ../customer/index.php');
                        break;
                }
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}

// Demo login credentials
$demoCredentials = [
    ['email' => 'customar@ordivo.com', 'password' => '112233', 'role' => 'Customer'],
    ['email' => 'vendor@ordivo.com', 'password' => '112233', 'role' => 'Vendor'],
    ['email' => 'kitchen@ordivo.com', 'password' => '112233', 'role' => 'Kitchen Manager'],
    ['email' => 'kitchenmanager.burgerkingbangladesh113@ordivo.com', 'password' => '112233', 'role' => 'Kitchen Manager'],
    ['email' => 'storemanager.burgerkingbangladesh113@ordivo.com', 'password' => '112233', 'role' => 'Store Manager'],
    ['email' => 'admin@ordivo.com', 'password' => '112233', 'role' => 'Admin']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ORDIVO</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-pink: #f97316;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
            position: relative;
        }

        /* Homepage Background - Real Homepage */
        .homepage-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
            background: #ffffff;
        }

        .homepage-background iframe {
            width: 100%;
            height: 100%;
            border: none;
            pointer-events: none;
            transform: scale(1);
            filter: blur(3px) brightness(0.85);
        }

        /* Overlay to prevent interaction with background */
        .homepage-background::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            z-index: 2;
            pointer-events: none;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Login overlay */
        .login-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            overflow-y: auto;
        }

        .login-box {
            background: #ffffff;
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 25px 50px #e5e7eb;
            overflow: visible;
            max-width: 400px;
            width: 100%;
            border: 2px solid #ffffff;
            animation: slideInUp 0.6s ease-out;
            max-height: none;
            margin: auto;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-content {
            padding: 2rem 2.5rem;
            text-align: center;
            overflow: visible;
        }

        .logo {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo img {
            height: 60px;
            animation: logoFloat 3s ease-in-out infinite, logoColorShift 6s ease-in-out infinite;
            transition: all 0.3s ease;
            
        }

        .logo img:hover {
            transform: scale(1.1) rotate(5deg);
            
        }

        .logo i {
            font-size: 2.5rem !important;
            animation: logoPulse 2s ease-in-out infinite, logoColorShift 6s ease-in-out infinite;
            color: var(--ordivo-primary);
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-5px) rotate(2deg); }
            50% { transform: translateY(-8px) rotate(0deg); }
            75% { transform: translateY(-5px) rotate(-2deg); }
        }

        @keyframes logoColorShift {
            0%, 100% {  }
            25% {  }
            50% {  }
            75% {  }
        }

        @keyframes logoPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.9; }
        }

        .welcome-text {
            margin-bottom: 1.5rem;
        }

        .welcome-text h2 {
            color: var(--ordivo-primary);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .welcome-text p {
            color: #666;
            margin-bottom: 0;
        }

        .btn-primary {
            background: var(--ordivo-primary);
            border: none;
            border-radius: 10px;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px #f97316;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.6rem 0.8rem;
            transition: all 0.3s ease;
            background: #ffffff;
        }

        .form-control:focus {
            border-color: var(--ordivo-primary);
            box-shadow: 0 0 0 0.2rem #f97316;
            background: white;
        }

        .input-group-text {
            background: rgba(248, 249, 250, 0.9);
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .registration-prompt {
            margin-top: 1rem;
        }

        .registration-prompt .btn-outline-primary {
            border: 2px solid var(--ordivo-primary);
            color: var(--ordivo-primary);
            background: transparent;
            transition: all 0.3s ease;
        }

        .registration-prompt .btn-outline-primary:hover {
            background: var(--ordivo-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px #f97316;
        }

        .links {
            margin-top: 1rem;
        }

        .links a {
            color: var(--ordivo-primary);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .links a:hover {
            color: var(--ordivo-secondary);
            text-decoration: underline;
        }

        /* Logout message */
        .logout-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 20;
            background: rgba(40, 167, 69, 0.95);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            animation: slideIn 0.5s ease;
            border: 1px solid #ffffff;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Close button for login box */
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #999;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 11;
        }

        .close-btn:hover {
            color: var(--ordivo-primary);
            transform: rotate(90deg);
        }

        @media (max-width: 768px) {
            .login-content {
                padding: 1.5rem 2rem;
            }
            
            .login-box {
                margin: 1rem;
                max-width: none;
                max-height: 85vh;
            }
            
            .logo img {
                height: 50px;
            }
            
            .logo i {
                font-size: 2rem !important;
            }
        }

        /* SweetAlert2 Custom Styles */
        .compact-alert {
            width: 350px !important;
            padding: 1rem !important;
        }

        .compact-title {
            font-size: 1.2rem !important;
            color: var(--ordivo-primary) !important;
            margin-bottom: 0.5rem !important;
        }

        .compact-content {
            font-size: 0.9rem !important;
            color: #666 !important;
        }

        .swal2-timer-progress-bar {
            background: var(--ordivo-primary) !important;
        }

        .swal2-popup {
            border-radius: 15px !important;
            box-shadow: 0 15px 35px #e5e7eb !important;
        }

        @media (max-width: 768px) {
            .compact-alert {
                width: 90% !important;
                max-width: 320px !important;
            }
        }
    </style>
</head>
<body>
    <!-- Homepage Background -->
    <div class="homepage-background">
        <iframe src="../customer/index.php" sandbox="allow-same-origin allow-scripts"></iframe>
    </div>

    <!-- Logout Message -->
    <?php if (isset($_GET['message']) && $_GET['message'] === 'logged_out'): ?>
        <div class="logout-message" id="logoutMessage">
            <i class="fas fa-check-circle me-2"></i>You have been successfully logged out
        </div>
    <?php endif; ?>

    <!-- Login Overlay -->
    <div class="login-overlay">
        <div class="login-box">
            <!-- Close Button -->
            <button class="close-btn" onclick="window.location.href='../customer/index.php'" title="Continue as Guest">
                <i class="fas fa-times"></i>
            </button>

            <div class="login-content">
                <!-- Logo -->
                <div class="logo">
                    <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                        <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>">
                    <?php else: ?>
                        <i class="fas fa-utensils"></i>
                    <?php endif; ?>
                </div>

                <!-- Welcome Text -->
                <div class="welcome-text">
                    <h2>Welcome Back!</h2>
                    <?php if (isset($_GET['redirect']) && $_GET['redirect'] === 'checkout'): ?>
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>Please login to complete your checkout
                        </div>
                    <?php else: ?>
                        <p>Sign in to continue your food journey</p>
                    <?php endif; ?>
                </div>
                
                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST">
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check text-start">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>
                </form>

                <!-- Registration Prompt -->
                <div class="registration-prompt">
                    <div class="text-center p-2 mb-2" style="background: #f97316; border-radius: 12px; border: 2px solid #f97316;">
                        <h6 class="mb-2" style="color: var(--ordivo-primary); font-size: 0.9rem;">New to ORDIVO?</h6>
                        <p class="mb-2 text-muted small">Join thousands of food lovers!</p>
                        <a href="register.php" class="btn btn-outline-primary w-100 btn-sm" style="border-radius: 10px; font-weight: 500;">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </a>
                    </div>
                </div>

                <!-- Links -->
                <div class="links">
                    <div class="text-center">
                        <a href="../customer/index.php">
                            <i class="fas fa-arrow-left me-2"></i>Continue as Guest
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Auto-focus email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
            
            // Auto-dismiss error alert after 5 seconds
            const errorAlert = document.getElementById('errorAlert');
            if (errorAlert) {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(errorAlert);
                    bsAlert.close();
                }, 5000);
            }
            
            // Hide logout message after 3 seconds
            const logoutMessage = document.getElementById('logoutMessage');
            if (logoutMessage) {
                setTimeout(() => {
                    logoutMessage.style.animation = 'slideIn 0.5s ease reverse';
                    setTimeout(() => {
                        logoutMessage.remove();
                    }, 500);
                }, 3000);
            }

            // Show SweetAlert for registration success
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('message') === 'registration_success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Welcome to ORDIVO!',
                    text: 'Your account has been created successfully. Please sign in with your credentials.',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    allowOutsideClick: true,
                    allowEscapeKey: true,
                    customClass: {
                        popup: 'compact-alert',
                        title: 'compact-title',
                        content: 'compact-content'
                    }
                });
                
                // Clean up URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });


    </script>
</body>
</html>