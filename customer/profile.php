<?php
/**
 * ORDIVO - Customer Profile Page
 * User profile management and settings
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

// Get user data from database if logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $userId = $_SESSION['user_id'];
    $dbUser = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);
    
    // Fix image paths for customer directory (add ../ prefix if needed)
    $avatar = $dbUser['avatar'] ?? '';
    $coverPhoto = $dbUser['cover_photo'] ?? '';
    
    if (!empty($avatar) && strpos($avatar, 'uploads/') === 0) {
        $avatar = '../' . $avatar;
    }
    
    if (!empty($coverPhoto) && strpos($coverPhoto, 'uploads/') === 0) {
        $coverPhoto = '../' . $coverPhoto;
    }
    
    $user = [
        'name' => $dbUser['name'] ?? $_SESSION['user_name'] ?? 'Al amin',
        'email' => $dbUser['email'] ?? $_SESSION['user_email'] ?? 'alamin@example.com',
        'phone' => $dbUser['phone'] ?? $_SESSION['user_phone'] ?? '+8801234567890',
        'avatar' => $avatar,
        'cover_photo' => $coverPhoto,
        'date_of_birth' => $dbUser['date_of_birth'] ?? '1995-01-15',
        'gender' => $dbUser['gender'] ?? 'male',
        'address' => $dbUser['address'] ?? 'New address Road 71 Road 71, Dhaka, Bangladesh Dhaka'
    ];
} else {
    // For demo purposes, use default user data
    $user = [
        'name' => 'Al amin',
        'email' => 'alamin@example.com',
        'phone' => '+8801234567890',
        'avatar' => '',
        'cover_photo' => '',
        'date_of_birth' => '1995-01-15',
        'gender' => 'male',
        'address' => 'New address Road 71 Road 71, Dhaka, Bangladesh Dhaka'
    ];
}

// Handle profile update
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $dateOfBirth = $_POST['date_of_birth'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $address = trim($_POST['address'] ?? '');
        
        if (empty($name) || empty($email)) {
            throw new Exception('Name and email are required');
        }
        
        // In a real app, this would update the database
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_phone'] = $phone;
        
        $user['name'] = $name;
        $user['email'] = $email;
        $user['phone'] = $phone;
        $user['date_of_birth'] = $dateOfBirth;
        $user['gender'] = $gender;
        $user['address'] = $address;
        
        $success = 'Profile updated successfully!';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get user statistics (demo data)
$stats = [
    'total_orders' => 15,
    'total_spent' => 4500,
    'favorite_restaurants' => 8,
    'member_since' => '2023-06-15'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ORDIVO</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="../assets/logo-animations.css" rel="stylesheet">
    <link href="../assets/css/homepage.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            line-height: 1.6;
            margin: 0;
            padding-top: 160px; /* Header (100px) + Nav tabs (60px) */
        }

        .profile-header {
            background: #10b981;
            color: white;
            padding: 3rem 0;
            position: relative;
            overflow: hidden;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
            border: 4px solid #ffffff;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px #e5e7eb;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 8px #e5e7eb;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px #e5e7eb;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--ordivo-primary);
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            background: var(--ordivo-primary);
            border: none;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
        }

        .nav-pills .nav-link {
            border-radius: 8px;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .nav-pills .nav-link.active {
            background: var(--ordivo-primary);
        }

        .form-control:focus {
            border-color: var(--ordivo-primary);
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25);
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            body {
                padding-top: 160px; /* Keep same as desktop since we're using includes */
            }

            .profile-header {
                padding: 2rem 0;
            }

            .profile-header h1,
            .profile-header .display-5 {
                font-size: 1.5rem !important;
                margin-bottom: 0.5rem !important;
            }

            .profile-header .lead {
                font-size: 0.9rem;
            }

            .profile-header p {
                font-size: 0.85rem;
                margin-bottom: 0.5rem;
            }

            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
                margin: 0 auto 1rem;
            }

            .btn-light {
                font-size: 0.85rem;
                padding: 0.4rem 0.75rem;
            }

            /* Statistics Cards */
            .stats-card {
                padding: 1rem;
                margin-bottom: 0.75rem;
            }

            .stats-number {
                font-size: 1.5rem;
            }

            .stats-card .text-muted {
                font-size: 0.85rem;
            }

            /* Make stats 2 per row on mobile */
            .col-md-3 {
                flex: 0 0 50%;
                max-width: 50%;
            }

            /* Profile Navigation */
            .profile-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .profile-card h4,
            .profile-card h5 {
                font-size: 1.1rem;
                margin-bottom: 0.75rem;
            }

            .nav-pills {
                flex-direction: column;
                gap: 0.5rem;
            }

            .nav-pills .nav-item {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 0;
            }

            .nav-pills .nav-link {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
                text-align: left;
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: flex-start;
                min-height: auto;
            }

            .nav-pills .nav-link i {
                display: inline-block;
                margin-bottom: 0;
                margin-right: 0.75rem !important;
                font-size: 1.2rem;
            }

            /* Form Elements */
            .form-label {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }

            .form-control,
            .form-select {
                font-size: 0.9rem;
                padding: 0.5rem 0.75rem;
            }

            textarea.form-control {
                min-height: 80px;
            }

            .btn {
                font-size: 0.9rem;
                padding: 0.6rem 1rem;
            }

            .btn-lg {
                font-size: 1rem;
                padding: 0.75rem 1.25rem;
            }

            /* Address Cards */
            .address-card {
                padding: 1rem;
                margin-bottom: 0.75rem;
            }

            .address-card h6 {
                font-size: 0.95rem;
            }

            .address-card p {
                font-size: 0.85rem;
            }

            /* Payment Method Cards */
            .payment-card {
                padding: 1rem;
                margin-bottom: 0.75rem;
            }

            .payment-card h6 {
                font-size: 0.95rem;
            }

            /* Alerts */
            .alert {
                font-size: 0.9rem;
                padding: 0.75rem;
            }

            /* Tab Content */
            .tab-content {
                margin-top: 1rem;
            }

            /* Stack columns on mobile */
            .row .col-md-6,
            .row .col-md-9,
            .row .col-md-3 {
                margin-bottom: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .profile-header h1,
            .profile-header .display-5 {
                font-size: 1.3rem !important;
            }

            .profile-header .lead {
                font-size: 0.85rem;
            }

            .profile-header p {
                font-size: 0.8rem;
            }

            .profile-avatar {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
            }

            .stats-number {
                font-size: 1.3rem;
            }

            .profile-card h4,
            .profile-card h5 {
                font-size: 1rem;
            }

            .nav-pills .nav-link {
                padding: 0.65rem 0.85rem;
                font-size: 0.85rem;
            }

            .nav-pills .nav-link i {
                font-size: 1.1rem;
                margin-right: 0.6rem !important;
            }

            .form-control,
            .form-select {
                font-size: 0.85rem;
            }

            .btn {
                font-size: 0.85rem;
                padding: 0.5rem 0.85rem;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Set user location for header
    $userLocation = $_SESSION['user_location'] ?? 'Dhaka, Bangladesh';
    include 'includes/header_with_nav.php'; 
    ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <!-- Cover Photo -->
            <?php if (!empty($user['cover_photo'])): ?>
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-image: url('<?= htmlspecialchars($user['cover_photo']) ?>'); background-size: cover; background-position: center; opacity: 0.3;"></div>
            <?php endif; ?>
            
            <div class="text-center" style="position: relative; z-index: 2;">
                <div class="profile-avatar">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <h1 class="display-5 mb-2"><?= htmlspecialchars($user['name']) ?></h1>
                <p class="lead mb-0">
                    <i class="fas fa-envelope me-2"></i><?= htmlspecialchars($user['email']) ?>
                </p>
                <p class="mb-3">
                    <i class="fas fa-phone me-2"></i><?= htmlspecialchars($user['phone']) ?>
                </p>
                
                <!-- Profile Management Button -->
                <a href="../universal_profile.php" class="btn btn-light btn-sm">
                    <i class="fas fa-camera me-2"></i>Manage Photos
                </a>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?= $stats['total_orders'] ?></div>
                    <div class="text-muted">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number">৳<?= number_format($stats['total_spent']) ?></div>
                    <div class="text-muted">Total Spent</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?= $stats['favorite_restaurants'] ?></div>
                    <div class="text-muted">Favorite Restaurants</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?= date('M Y', strtotime($stats['member_since'])) ?></div>
                    <div class="text-muted">Member Since</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Navigation -->
            <div class="col-md-3">
                <div class="profile-card">
                    <h5 class="mb-3">Account Settings</h5>
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="pill" href="#profile-info">
                                <i class="fas fa-user me-2"></i>Profile Information
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" href="#addresses">
                                <i class="fas fa-map-marker-alt me-2"></i>Delivery Addresses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" href="#payment-methods">
                                <i class="fas fa-credit-card me-2"></i>Payment Methods
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" href="#notifications">
                                <i class="fas fa-bell me-2"></i>Notifications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" href="#security">
                                <i class="fas fa-shield-alt me-2"></i>Security
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Content -->
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Profile Information -->
                    <div class="tab-pane fade show active" id="profile-info">
                        <div class="profile-card">
                            <h4 class="mb-4">
                                <i class="fas fa-user text-primary me-2"></i>Profile Information
                            </h4>
                            
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($user['date_of_birth']) ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-control" id="gender" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="male" <?= $user['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                                            <option value="female" <?= $user['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                                            <option value="other" <?= $user['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($user['address']) ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Delivery Addresses -->
                    <div class="tab-pane fade" id="addresses">
                        <div class="profile-card">
                            <h4 class="mb-4">
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>Delivery Addresses
                            </h4>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Manage your delivery addresses for faster checkout.
                            </div>
                            
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">Home</h6>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($user['address']) ?></p>
                                        <small class="text-success">
                                            <i class="fas fa-check-circle me-1"></i>Default Address
                                        </small>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                            <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <button class="btn btn-outline-primary">
                                <i class="fas fa-plus me-2"></i>Add New Address
                            </button>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="tab-pane fade" id="payment-methods">
                        <div class="profile-card">
                            <h4 class="mb-4">
                                <i class="fas fa-credit-card text-primary me-2"></i>Payment Methods
                            </h4>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Add and manage your payment methods for quick checkout.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3 text-center">
                                        <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                                        <h6>Cash on Delivery</h6>
                                        <small class="text-muted">Pay when your order arrives</small>
                                        <div class="mt-2">
                                            <span class="badge bg-success">Active</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3 text-center">
                                        <i class="fas fa-mobile-alt fa-2x text-primary mb-2"></i>
                                        <h6>Mobile Banking</h6>
                                        <small class="text-muted">bKash, Nagad, Rocket</small>
                                        <div class="mt-2">
                                            <button class="btn btn-outline-primary btn-sm">Add Account</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="tab-pane fade" id="notifications">
                        <div class="profile-card">
                            <h4 class="mb-4">
                                <i class="fas fa-bell text-primary me-2"></i>Notification Preferences
                            </h4>
                            
                            <div class="mb-4">
                                <h6>Order Updates</h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="email-orders" checked>
                                    <label class="form-check-label" for="email-orders">
                                        Email notifications for order updates
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="sms-orders" checked>
                                    <label class="form-check-label" for="sms-orders">
                                        SMS notifications for order updates
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6>Promotions & Offers</h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="email-promos" checked>
                                    <label class="form-check-label" for="email-promos">
                                        Email notifications for deals and offers
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="push-promos">
                                    <label class="form-check-label" for="push-promos">
                                        Push notifications for special offers
                                    </label>
                                </div>
                            </div>
                            
                            <button class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Preferences
                            </button>
                        </div>
                    </div>

                    <!-- Security -->
                    <div class="tab-pane fade" id="security">
                        <div class="profile-card">
                            <h4 class="mb-4">
                                <i class="fas fa-shield-alt text-primary me-2"></i>Security Settings
                            </h4>
                            
                            <div class="mb-4">
                                <h6>Change Password</h6>
                                <form>
                                    <div class="mb-3">
                                        <label for="current-password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current-password">
                                    </div>
                                    <div class="mb-3">
                                        <label for="new-password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new-password">
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm-password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm-password">
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Update Password
                                    </button>
                                </form>
                            </div>
                            
                            <div class="mb-4">
                                <h6>Two-Factor Authentication</h6>
                                <p class="text-muted">Add an extra layer of security to your account.</p>
                                <button class="btn btn-outline-primary">
                                    <i class="fas fa-mobile-alt me-2"></i>Enable 2FA
                                </button>
                            </div>
                            
                            <div class="mb-4">
                                <h6>Account Deactivation</h6>
                                <p class="text-muted">Temporarily deactivate your account.</p>
                                <button class="btn btn-outline-danger">
                                    <i class="fas fa-user-slash me-2"></i>Deactivate Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/modals.php'; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/location-tracker.js"></script>
    
    <script>
        // Set active navigation tab
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('#mainNavTabs .nav-link');
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }
            });
        });
    </script>

    <?php include 'includes/modals.php'; ?>
    <?php include 'includes/footer.php'; ?>
</body>
</html>