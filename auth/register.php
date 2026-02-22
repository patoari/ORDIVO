<?php
/**
 * ORDIVO - Registration Page
 * User registration for demo purposes
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

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    
    try {
        // Validation
        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception('Please fill in all required fields');
        }
        
        if (!validateEmail($email)) {
            throw new Exception('Please enter a valid email address');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters long');
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }
        
        // Check if email already exists
        $existingUser = fetchRow("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existingUser) {
            throw new Exception('An account with this email already exists');
        }
        
        // Create user account
        $userId = insertData('users', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'status' => 'active'
        ]);
        
        // Show SweetAlert and redirect
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: "success",
                    title: "Account Created!",
                    text: "Your account has been created successfully. Redirecting to login page...",
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    customClass: {
                        popup: "compact-alert",
                        title: "compact-title",
                        content: "compact-content"
                    },
                    didOpen: () => {
                        Swal.showLoading();
                    }
                }).then(() => {
                    window.location.href = "login.php?message=registration_success";
                });
            });
        </script>';
        
        // Set flag to prevent form display
        $registrationComplete = true;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ORDIVO</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            height: 100vh;
            overflow: hidden;
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

        /* Registration overlay */
        .register-overlay {
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

        .register-box {
            background: #ffffff;
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 25px 50px #e5e7eb;
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            border: 2px solid #ffffff;
            margin: 2rem 0;
            animation: slideInUp 0.6s ease-out;
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

        .register-content {
            padding: 3rem;
            text-align: center;
        }

        .logo {
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo img {
            height: 80px;
            animation: logoFloat 3s ease-in-out infinite, logoColorShift 6s ease-in-out infinite;
            transition: all 0.3s ease;
            
        }

        .logo img:hover {
            transform: scale(1.1) rotate(5deg);
            
        }

        .logo i {
            font-size: 3rem !important;
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

        /* Step indicator */
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .step.active {
            background: var(--ordivo-primary);
            color: white;
            transform: scale(1.1);
        }

        .step.completed {
            background: #28a745;
            color: white;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 30px;
            height: 2px;
            background: #e9ecef;
            transform: translateY(-50%);
            z-index: -1;
        }

        .step.completed:not(:last-child)::after {
            background: #28a745;
        }

        /* Form steps */
        .form-step {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .form-step.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .welcome-text {
            margin-bottom: 2rem;
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
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px #f97316;
        }

        .btn-outline-secondary {
            border: 2px solid #6c757d;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            transform: translateY(-2px);
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
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
            border-radius: 12px 0 0 12px;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }

        .role-option {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #ffffff;
            backdrop-filter: blur(5px);
        }

        .role-option:hover {
            border-color: var(--ordivo-primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px #f97316;
        }

        .role-option.selected {
            border-color: var(--ordivo-primary);
            background: #f97316;
        }

        .links {
            margin-top: 1.5rem;
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

        /* Close button for registration box */
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
            .register-content {
                padding: 2rem;
            }
            
            .register-box {
                margin: 1rem;
                max-width: none;
            }
            
            .step {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }
            
            .step:not(:last-child)::after {
                width: 20px;
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

    <!-- Registration Overlay -->
    <div class="register-overlay">
        <div class="register-box">
            <!-- Close Button -->
            <button class="close-btn" onclick="window.location.href='../customer/index.php'" title="Continue as Guest">
                <i class="fas fa-times"></i>
            </button>

            <div class="register-content">
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
                    <h2>Join Us Today!</h2>
                    <p>Create your account step by step</p>
                </div>

                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step active" id="step1">1</div>
                    <div class="step" id="step2">2</div>
                    <div class="step" id="step3">3</div>
                    <div class="step" id="step4">4</div>
                </div>
                
                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                        <div class="mt-2">
                            <small class="text-muted">You will be redirected to the login page in 2 seconds...</small>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!isset($registrationComplete)): ?>
                <!-- Registration Form -->
                <form method="POST" id="registerForm">
                    <!-- Step 1: Name and Email -->
                    <div class="form-step active" id="formStep1">
                        <h4 class="mb-3">Basic Information</h4>
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary w-100" onclick="nextStep(2)">
                            Continue <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>

                    <!-- Step 2: Phone and Account Type -->
                    <div class="form-step" id="formStep2">
                        <h4 class="mb-3">Contact & Account Type</h4>
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter your phone number">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Type</label>
                            <div class="role-option selected" onclick="selectRole('customer')">
                                <input type="radio" name="role" value="customer" id="role_customer" checked hidden>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user fa-2x text-primary me-3"></i>
                                    <div class="text-start">
                                        <h6 class="mb-1">Customer</h6>
                                        <small class="text-muted">Order food from restaurants</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="role-option" onclick="selectRole('vendor')">
                                <input type="radio" name="role" value="vendor" id="role_vendor" hidden>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-store fa-2x text-success me-3"></i>
                                    <div class="text-start">
                                        <h6 class="mb-1">Restaurant Owner</h6>
                                        <small class="text-muted">Manage your restaurant and menu</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary flex-fill" onclick="prevStep(1)">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </button>
                            <button type="button" class="btn btn-primary flex-fill" onclick="nextStep(3)">
                                Continue <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Password -->
                    <div class="form-step" id="formStep3">
                        <h4 class="mb-3">Secure Your Account</h4>
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
                            </div>
                            <small class="text-muted">At least 6 characters</small>
                        </div>
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary flex-fill" onclick="prevStep(2)">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </button>
                            <button type="button" class="btn btn-primary flex-fill" onclick="nextStep(4)">
                                Continue <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Terms and Submit -->
                    <div class="form-step" id="formStep4">
                        <h4 class="mb-3">Almost Done!</h4>
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Please review your information and accept our terms to complete registration.
                            </div>
                        </div>
                        <div class="mb-3 form-check text-start">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and <a href="#" class="text-decoration-none">Privacy Policy</a>
                            </label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary flex-fill" onclick="prevStep(3)">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </button>
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Links -->
                <div class="links">
                    <div class="text-center mb-2">
                        <small class="text-muted">
                            Already have an account? 
                            <a href="login.php">Sign in here</a>
                        </small>
                    </div>
                    
                    <div class="text-center">
                        <a href="../customer/index.php">
                            <i class="fas fa-arrow-left me-2"></i>Continue as Guest
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        let currentStep = 1;

        function nextStep(step) {
            // Validate current step
            if (!validateStep(currentStep)) {
                return;
            }

            // Hide current step
            document.getElementById('formStep' + currentStep).classList.remove('active');
            document.getElementById('step' + currentStep).classList.remove('active');
            document.getElementById('step' + currentStep).classList.add('completed');

            // Show next step
            currentStep = step;
            document.getElementById('formStep' + currentStep).classList.add('active');
            document.getElementById('step' + currentStep).classList.add('active');

            // Focus first input in new step
            const firstInput = document.querySelector('#formStep' + currentStep + ' input:not([type="hidden"]):not([type="radio"])');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }

        function prevStep(step) {
            // Hide current step
            document.getElementById('formStep' + currentStep).classList.remove('active');
            document.getElementById('step' + currentStep).classList.remove('active');

            // Show previous step
            currentStep = step;
            document.getElementById('formStep' + currentStep).classList.add('active');
            document.getElementById('step' + currentStep).classList.add('active');
            document.getElementById('step' + currentStep).classList.remove('completed');

            // Focus first input in previous step
            const firstInput = document.querySelector('#formStep' + currentStep + ' input:not([type="hidden"]):not([type="radio"])');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }

        function validateStep(step) {
            switch (step) {
                case 1:
                    const name = document.getElementById('name').value.trim();
                    const email = document.getElementById('email').value.trim();
                    if (!name || !email) {
                        alert('Please fill in your name and email address.');
                        return false;
                    }
                    if (!isValidEmail(email)) {
                        alert('Please enter a valid email address.');
                        return false;
                    }
                    break;
                case 2:
                    // Phone is optional, role is always selected
                    break;
                case 3:
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    if (!password || !confirmPassword) {
                        alert('Please enter and confirm your password.');
                        return false;
                    }
                    if (password.length < 6) {
                        alert('Password must be at least 6 characters long.');
                        return false;
                    }
                    if (password !== confirmPassword) {
                        alert('Passwords do not match.');
                        return false;
                    }
                    break;
            }
            return true;
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function selectRole(role) {
            // Remove selected class from all options
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.getElementById('role_' + role).checked = true;
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Enter key navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                e.preventDefault();
                if (currentStep < 4) {
                    nextStep(currentStep + 1);
                }
            }
        });
        
        // Auto-focus name field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('name').focus();
        });

        // Prevent iframe interaction
        document.addEventListener('DOMContentLoaded', function() {
            const iframe = document.querySelector('iframe');
            if (iframe) {
                iframe.style.pointerEvents = 'none';
            }
        });
    </script>
</body>
</html>