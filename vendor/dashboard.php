<?php
/**
 * ORDIVO - Vendor Dashboard
 * Main dashboard for vendor operations
 */

require_once '../config/db_connection.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['vendor', 'store_manager', 'store_staff'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Get vendor ID based on user role
if ($_SESSION['user_role'] === 'vendor') {
    $vendorId = $_SESSION['user_id'];
} else {
    // For store managers and staff, get vendor ID from vendor_staff table
    $staffInfo = fetchRow("
        SELECT vs.vendor_id, v.name as vendor_name 
        FROM vendor_staff vs 
        JOIN vendors v ON vs.vendor_id = v.id 
        WHERE vs.user_id = ? AND vs.status = 'active'
    ", [$_SESSION['user_id']]);
    
    if (!$staffInfo) {
        die('Staff member not assigned to any vendor');
    }
    
    $vendorId = $staffInfo['vendor_id'];
    $vendorName = $staffInfo['vendor_name'];
}

// Get site settings for logo and branding
try {
    $siteSettings = fetchRow("SELECT * FROM site_settings WHERE id = 1") ?? [];
    $siteLogo = $siteSettings['logo_url'] ?? '';
    $siteName = $siteSettings['site_name'] ?? 'ORDIVO';
    
    // Fix logo path for vendor directory - add ../ prefix if it's a relative path
    if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️') {
        if (strpos($siteLogo, 'uploads/') === 0) {
            $siteLogo = '../' . $siteLogo;
        }
        elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $siteLogo)) {
            $siteLogo = '../' . $siteLogo;
        }
    }
} catch (Exception $e) {
    error_log("Error loading site settings in vendor dashboard: " . $e->getMessage());
    $siteLogo = '';
    $siteName = 'ORDIVO';
}

// Get vendor business information
try {
    global $pdo;
    $stmt = $pdo->prepare("SELECT v.name, v.logo, v.banner_image FROM vendors v WHERE v.owner_id = ? LIMIT 1");
    $stmt->execute([$vendorId]);
    $vendorInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $vendorBusinessName = $vendorInfo['name'] ?? 'My Business';
    $vendorLogo = $vendorInfo['logo'] ?? '';
    
    // Fix logo path for vendor directory - add ../ prefix if it's a relative path
    if (!empty($vendorLogo) && $vendorLogo !== '🍔' && $vendorLogo !== '🍽️') {
        if (strpos($vendorLogo, 'uploads/') === 0) {
            $vendorLogo = '../' . $vendorLogo;
        }
        elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $vendorLogo)) {
            $vendorLogo = '../' . $vendorLogo;
        }
    }
} catch (Exception $e) {
    error_log("Error loading vendor info in vendor dashboard: " . $e->getMessage());
    $vendorBusinessName = 'My Business';
    $vendorLogo = '';
    $vendorInfo = [];
}

// Get vendor statistics
try {
    // First, let's check if this user has a vendor profile
    $vendorProfile = fetchRow("SELECT id FROM vendors WHERE owner_id = ?", [$vendorId]);
    $actualVendorId = $vendorProfile ? $vendorProfile['id'] : null;
    
    if ($actualVendorId) {
        // Use vendor profile ID for orders
        $stats = [
            'total_products' => fetchValue("SELECT COUNT(*) FROM products WHERE vendor_id = ?", [$vendorId]),
            'active_products' => fetchValue("SELECT COUNT(*) FROM products WHERE vendor_id = ? AND is_available = 1", [$vendorId]),
            'total_orders' => fetchValue("SELECT COUNT(*) FROM orders WHERE vendor_id = ?", [$actualVendorId]),
            'pending_orders' => fetchValue("SELECT COUNT(*) FROM orders WHERE vendor_id = ? AND status = 'pending'", [$actualVendorId]),
            'total_revenue' => fetchValue("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE vendor_id = ? AND status = 'delivered'", [$actualVendorId]),
            'monthly_revenue' => fetchValue("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE vendor_id = ? AND status = 'delivered' AND MONTH(created_at) = MONTH(CURRENT_DATE())", [$actualVendorId])
        ];
        
        // Get recent orders
        $recentOrders = fetchAll("
            SELECT o.*, u.name as customer_name 
            FROM orders o 
            LEFT JOIN users u ON o.customer_id = u.id 
            WHERE o.vendor_id = ? 
            ORDER BY o.created_at DESC 
            LIMIT 10
        ", [$actualVendorId]);
    } else {
        // No vendor profile yet, use user ID for products only
        $stats = [
            'total_products' => fetchValue("SELECT COUNT(*) FROM products WHERE vendor_id = ?", [$vendorId]),
            'active_products' => fetchValue("SELECT COUNT(*) FROM products WHERE vendor_id = ? AND is_available = 1", [$vendorId]),
            'total_orders' => 0,
            'pending_orders' => 0,
            'total_revenue' => 0,
            'monthly_revenue' => 0
        ];
        $recentOrders = [];
    }
    
    // Get vendor info
    $vendor = fetchRow("SELECT * FROM users WHERE id = ?", [$vendorId]);
    
} catch (Exception $e) {
    error_log("Vendor Dashboard Error: " . $e->getMessage());
    $stats = ['total_products' => 0, 'active_products' => 0, 'total_orders' => 0, 'pending_orders' => 0, 'total_revenue' => 0, 'monthly_revenue' => 0];
    $recentOrders = [];
    $vendor = [];
}

// Load cover photo and profile picture AFTER $vendor is loaded
require_once 'components/load_vendor_images.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - ORDIVO</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="../assets/css/ordivo-responsive.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-accent: #f97316;
            --ordivo-light: #f0fdf4;
            --ordivo-dark: #374151;
            --ordivo-gray: #6b7280;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }

        /* Mobile First - Sidebar hidden by default on mobile */
        .sidebar {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            width: 250px;
            position: fixed;
            left: -250px; /* Hidden by default on mobile */
            top: 0;
            z-index: 1000;
            transition: left 0.3s ease;
            overflow-y: auto;
        }

        .sidebar.show {
            left: 0;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        .sidebar .nav-link {
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: #ffffff;
            color: #10b981;
            border-radius: 0.5rem;
            font-weight: 600;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active i {
            color: #10b981;
        }

        .main-content {
            margin-left: 0; /* No margin on mobile */
            padding: 0.625rem 1rem 1rem; /* 10px top, 1rem sides and bottom */
            transition: all 0.3s ease;
        }

        .welcome-card {
            position: relative;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 15px;
            padding: 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px #e5e7eb;
            overflow: hidden;
            min-height: 200px;
        }

        .welcome-card-cover {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.15;
            z-index: 0;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.85) 0%, rgba(5, 150, 105, 0.85) 100%);
            z-index: 0;
        }

        .welcome-card-content {
            position: relative;
            z-index: 1;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        /* Inline hamburger button for mobile - positioned in welcome card */
        .sidebar-toggle-inline {
            display: block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.3);
            border: 2px solid white;
            border-radius: 8px;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            order: -1; /* Place it first */
        }

        .sidebar-toggle-inline:hover {
            background: rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }

        .welcome-card-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            background: white;
            box-shadow: 0 5px 15px #e5e7eb;
            flex-shrink: 0;
        }

        .welcome-card-avatar-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid white;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 5px 15px #e5e7eb;
        }

        .welcome-card-info {
            flex: 1;
        }

        .welcome-card-time {
            text-align: right;
        }

        @media (max-width: 768px) {
            .welcome-card-content {
                flex-direction: row;
                flex-wrap: wrap;
                text-align: left;
                padding: 1rem;
                gap: 1rem;
            }

            .sidebar-toggle-inline {
                width: 100%;
                order: -1;
            }
            
            .welcome-card-info {
                width: 100%;
                order: 2;
            }

            .welcome-card-info h1 {
                font-size: 1.25rem;
            }

            .welcome-card-info p {
                font-size: 0.875rem;
            }
            
            .welcome-card-time {
                text-align: left;
                width: 100%;
                order: 3;
            }

            .welcome-card-time .h5 {
                font-size: 1rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .stat-label {
                font-size: 0.75rem;
            }

            .quick-action-card h6 {
                font-size: 0.9rem;
            }

            .quick-action-card p {
                font-size: 0.75rem;
            }

            .quick-action-icon {
                font-size: 2rem;
            }
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px #e5e7eb;
            transition: all 0.3s ease;
            border: none;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px #e5e7eb;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px #e5e7eb;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 15px 35px #e5e7eb;
        }

        .btn-primary {
            background: #10b981; 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px #e5e7eb;
        }

        .quick-action-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            cursor: pointer;
        }

        .quick-action-card:hover {
            border-color: var(--ordivo-primary);
            transform: translateY(-5px);
            box-shadow: 0 15px 35px #e5e7eb;
        }

        .quick-action-icon {
            font-size: 2.5rem;
            color: var(--ordivo-primary);
            margin-bottom: 1rem;
        }

        /* Tablet and up */
        @media (min-width: 768px) {
            .sidebar-toggle-inline {
                display: none; /* Hide inline hamburger on tablet+ */
            }

            .sidebar {
                left: 0; /* Always visible on tablet+ */
            }

            .sidebar-overlay {
                display: none !important;
            }

            .main-content {
                margin-left: 250px;
                padding: 1.5rem;
            }
        }

        /* Desktop */
        @media (min-width: 1200px) {
            .main-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="p-4">
            <div class="d-flex align-items-center mb-4">
                <?php if (!empty($vendorLogo)): ?>
                    <img src="<?= htmlspecialchars($vendorLogo) ?>" alt="<?= htmlspecialchars($vendorBusinessName) ?>" 
                         style="height: 60px; width: 60px; object-fit: cover; border-radius: 10px; margin-right: 12px; background: white; padding: 5px;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="display: none; width: 60px; height: 60px; background: #ffffff; border-radius: 10px; align-items: center; justify-content: center; margin-right: 12px;">
                        <i class="fas fa-store fa-2x text-white"></i>
                    </div>
                <?php else: ?>
                    <div style="display: flex; width: 60px; height: 60px; background: #ffffff; border-radius: 10px; align-items: center; justify-content: center; margin-right: 12px;">
                        <i class="fas fa-store fa-2x text-white"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <h5 class="text-white mb-0" style="font-size: 1.1rem; font-weight: 600;"><?= htmlspecialchars($vendorBusinessName) ?></h5>
                    <small class="text-white-50">Vendor Portal</small>
                </div>
            </div>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="products.php">
                    <i class="fas fa-box me-2"></i>Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i>Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="inventory.php">
                    <i class="fas fa-boxes me-2"></i>Inventory
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="categories.php">
                    <i class="fas fa-tags me-2"></i>Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../kitchen/dashboard.php">
                    <i class="fas fa-utensils me-2"></i>Kitchen
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="staff.php">
                    <i class="fas fa-users me-2"></i>Staff
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link" href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Header with Cover Photo and Profile Picture -->
        <div class="welcome-card">
            <?php if (!empty($vendorCover)): ?>
                <img src="<?= htmlspecialchars($vendorCover) ?>" alt="Cover Photo" class="welcome-card-cover">
            <?php endif; ?>
            
            <div class="welcome-card-content">
                <!-- Mobile Hamburger Button -->
                <button class="sidebar-toggle-inline" id="sidebarToggleInline">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Profile Picture -->
                <div>
                    <?php if (!empty($userAvatar)): ?>
                        <img src="<?= htmlspecialchars($userAvatar) ?>" 
                             alt="Profile Picture" 
                             class="welcome-card-avatar"
                             onerror="console.error('Failed to load profile picture:', this.src); this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="welcome-card-avatar-placeholder" style="display: none;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php else: ?>
                        <div class="welcome-card-avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                        <!-- Debug: <?= 'userAvatar is empty. vendor[avatar]=' . ($vendor['avatar'] ?? 'NOT SET') ?> -->
                    <?php endif; ?>
                </div>
                
                <!-- Welcome Text -->
                <div class="welcome-card-info">
                    <h1 class="mb-2">Welcome back, <?= htmlspecialchars($vendor['name'] ?? 'Vendor') ?>!</h1>
                    <p class="mb-0 opacity-75">Here's what's happening with your business today</p>
                </div>
                
                <!-- Date/Time -->
                <div class="welcome-card-time">
                    <div class="h5 mb-0"><?= date('l, F j, Y') ?></div>
                    <div class="opacity-75"><?= date('g:i A') ?></div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-primary"><?= number_format($stats['total_products']) ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-success"><?= number_format($stats['active_products']) ?></div>
                    <div class="stat-label">Active Products</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-info"><?= number_format($stats['total_orders']) ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-warning"><?= number_format($stats['pending_orders']) ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>
        </div>

        <!-- Revenue Cards -->
        <div class="row mb-4">
            <div class="col-6 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-success">৳<?= number_format($stats['total_revenue'], 0) ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-6 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-info">৳<?= number_format($stats['monthly_revenue'], 0) ?></div>
                    <div class="stat-label">This Month</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3">Quick Actions</h4>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="quick-action-card" onclick="location.href='products.php'">
                    <div class="quick-action-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h6>Add Product</h6>
                    <p class="text-muted mb-0">Add new products to your inventory</p>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="quick-action-card" onclick="location.href='orders.php'">
                    <div class="quick-action-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h6>View Orders</h6>
                    <p class="text-muted mb-0">Manage your customer orders</p>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="quick-action-card" onclick="location.href='../kitchen/dashboard.php'">
                    <div class="quick-action-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h6>Kitchen</h6>
                    <p class="text-muted mb-0">Manage ingredients and recipes</p>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="quick-action-card" onclick="location.href='staff.php'">
                    <div class="quick-action-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h6>Staff</h6>
                    <p class="text-muted mb-0">Manage your team members</p>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Recent Orders
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentOrders)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-shopping-cart fa-2x mb-3"></i><br>
                                No orders yet
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td>#<?= $order['id'] ?></td>
                                                <td><?= htmlspecialchars($order['customer_name'] ?? 'Unknown') ?></td>
                                                <td>৳<?= number_format($order['total_amount'], 0) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'info') ?>">
                                                        <?= ucfirst($order['status'] ?? 'pending') ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebarToggleInline = document.getElementById('sidebarToggleInline');

            function toggleSidebar() {
                if (sidebar && sidebarOverlay) {
                    sidebar.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                }
            }

            if (sidebarToggleInline) {
                sidebarToggleInline.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleSidebar();
                });
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleSidebar();
                });
            }
        });
    </script>
</body>
</html>