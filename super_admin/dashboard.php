<?php
/**
 * ORDIVO - Super Admin Dashboard
 * Multi-vendor Food & Grocery Delivery Platform
 * 
 * Complete platform oversight and management system
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'stats':
            try {
                $stats = [
                    'total_users' => fetchValue("SELECT COUNT(*) FROM users"),
                    'total_vendors' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'vendor'"),
                    'total_customers' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'customer'"),
                    'pending_vendors' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'vendor' AND status = 'pending'"),
                    'active_vendors' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'vendor' AND status = 'active'"),
                    'total_orders' => fetchValue("SELECT COUNT(*) FROM orders") ?: 0,
                    'total_revenue' => fetchValue("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'") ?: 0,
                    'active_orders' => fetchValue("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery')") ?: 0,
                    'total_products' => fetchValue("SELECT COUNT(*) FROM products") ?: 0,
                    'total_categories' => fetchValue("SELECT COUNT(*) FROM categories") ?: 0,
                ];
                echo json_encode($stats);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'recent_users':
            try {
                $users = fetchAll("
                    SELECT id, name, email, role, status, created_at
                    FROM users
                    WHERE role != 'super_admin'
                    ORDER BY created_at DESC
                    LIMIT 20
                ");
                echo json_encode($users);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'pending_vendors':
            try {
                $vendors = fetchAll("
                    SELECT id, name, email, phone, created_at
                    FROM users
                    WHERE role = 'vendor' AND status = 'pending'
                    ORDER BY created_at DESC
                ");
                echo json_encode($vendors);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
    }
}

// Handle form submissions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'approve_vendor':
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId) {
                try {
                    updateData('users', ['status' => 'active'], 'id = ? AND role = ?', [$userId, 'vendor']);
                    $success = 'Vendor approved successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to approve vendor: ' . $e->getMessage();
                }
            }
            break;
            
        case 'reject_vendor':
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId) {
                try {
                    updateData('users', ['status' => 'inactive'], 'id = ? AND role = ?', [$userId, 'vendor']);
                    $success = 'Vendor rejected successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to reject vendor: ' . $e->getMessage();
                }
            }
            break;
            
        case 'ban_user':
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId) {
                try {
                    updateData('users', ['status' => 'banned'], 'id = ?', [$userId]);
                    $success = 'User banned successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to ban user: ' . $e->getMessage();
                }
            }
            break;
            
        case 'unban_user':
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId) {
                try {
                    updateData('users', ['status' => 'active'], 'id = ?', [$userId]);
                    $success = 'User unbanned successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to unban user: ' . $e->getMessage();
                }
            }
            break;
            
        case 'delete_user':
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId) {
                try {
                    deleteData('users', 'id = ? AND role != ?', [$userId, 'super_admin']);
                    $success = 'User deleted successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to delete user: ' . $e->getMessage();
                }
            }
            break;
            
        case 'create_category':
            $name = sanitizeInput($_POST['category_name'] ?? '');
            $description = sanitizeInput($_POST['category_description'] ?? '');
            if ($name) {
                try {
                    insertData('categories', [
                        'name' => $name,
                        'description' => $description,
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $success = 'Category created successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to create category: ' . $e->getMessage();
                }
            }
            break;
            
        case 'update_settings':
            $siteName = sanitizeInput($_POST['site_name'] ?? '');
            $contactEmail = sanitizeInput($_POST['contact_email'] ?? '');
            $contactPhone = sanitizeInput($_POST['contact_phone'] ?? '');
            
            try {
                // Check if settings exist
                $settingsExist = fetchValue("SELECT COUNT(*) FROM site_settings");
                
                if ($settingsExist) {
                    updateData('site_settings', [
                        'site_name' => $siteName,
                        'contact_email' => $contactEmail,
                        'contact_phone' => $contactPhone,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = 1');
                } else {
                    insertData('site_settings', [
                        'site_name' => $siteName,
                        'contact_email' => $contactEmail,
                        'contact_phone' => $contactPhone,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
                $success = 'Settings updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update settings: ' . $e->getMessage();
            }
            break;
    }
}

// Get current settings
try {
    $settings = fetchRow("SELECT * FROM site_settings LIMIT 1") ?: [
        'site_name' => 'ORDIVO',
        'contact_email' => '',
        'contact_phone' => ''
    ];
} catch (Exception $e) {
    $settings = [
        'site_name' => 'ORDIVO',
        'contact_email' => '',
        'contact_phone' => ''
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - ORDIVO</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-light: #f0fdf4;
            --ordivo-dark: #374151;
            --ordivo-accent: #f97316;
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        /* Static Sidebar - Always Visible */
        /* Mobile First - Sidebar hidden by default on mobile */
        .sidebar {
            position: fixed;
            top: 0;
            left: -280px; /* Hidden by default on mobile */
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: left 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
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

        /* Mobile toggle button - Hidden, using inline version */
        .sidebar-toggle {
            display: none;
        }

        .sidebar-toggle:hover {
            background: #059669;
            transform: scale(1.05);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid #ffffff;
            text-align: center;
        }

        .sidebar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .sidebar-subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            color: #ffffff;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            transform: translateX(5px);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }

        .main-content {
            margin-left: 0; /* No margin on mobile */
            min-height: 100vh;
            padding: 0.625rem 1rem 1rem; /* 10px top, 1rem sides and bottom */
        }

        .top-bar {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px #e5e7eb;
            display: flex;
            flex-direction: row;
            gap: 0.75rem;
            align-items: center;
            border-top: 4px solid #10b981;
        }

        /* Inline hamburger button for mobile */
        .sidebar-toggle-inline {
            display: block;
            width: 40px;
            height: 40px;
            background: #10b981;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }

        .sidebar-toggle-inline:hover {
            background: #059669;
            transform: scale(1.05);
        }

        .welcome-text {
            flex: 1;
            min-width: 0;
        }

        .welcome-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .welcome-subtitle {
            color: #6c757d;
            margin: 0;
            font-size: 0.75rem;
            display: none; /* Hide subtitle on mobile */
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .user-info-text {
            display: none; /* Hide on mobile */
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--ordivo-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 10px #e5e7eb;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .stat-title {
            font-size: 0.7rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .stat-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--ordivo-dark);
            margin-bottom: 0.25rem;
        }

        .stat-change {
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .stat-change.positive {
            color: #28a745;
        }

        .stat-change.negative {
            color: #dc3545;
        }

        .content-section {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px #e5e7eb;
        }

        .section-header {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin: 0;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .table {
            margin: 0;
            font-size: 0.875rem;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--ordivo-dark);
            background: #f8f9fa;
            font-size: 0.8rem;
            padding: 0.75rem 0.5rem;
        }

        .table td {
            padding: 0.75rem 0.5rem;
        }

        .badge {
            font-size: 0.7rem;
            padding: 0.3rem 0.5rem;
        }

        /* Quick Access Cards - Mobile Styles */
        .card-title {
            font-size: 0.9rem !important;
        }

        .card-text {
            font-size: 0.7rem !important;
        }

        .card-body .fa-store,
        .card-body .fa-star {
            font-size: 2rem !important;
        }

        .card-body .me-3 {
            margin-right: 0.75rem !important;
        }

        /* Tablet and up */
        @media (min-width: 768px) {
            .card-title {
                font-size: 1.25rem !important;
            }

            .card-text {
                font-size: 1rem !important;
            }

            .card-body .fa-store,
            .card-body .fa-star {
                font-size: 3rem !important;
            }

            .card-body .me-3 {
                margin-right: 1rem !important;
            }

            .sidebar-toggle {
                display: none;
            }

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
                margin-left: var(--sidebar-width);
                padding: 1.5rem;
            }

            .top-bar {
                flex-direction: row;
                justify-content: space-between;
                padding: 1rem 1.5rem;
                margin-bottom: 2rem;
            }

            .welcome-title {
                font-size: 1.5rem;
                white-space: normal;
            }

            .welcome-subtitle {
                font-size: 1rem;
                display: block; /* Show subtitle on tablet+ */
            }

            .user-info-text {
                display: block; /* Show user info text on tablet+ */
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
                margin-bottom: 2rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-value {
                font-size: 2rem;
            }

            .stat-title {
                font-size: 0.9rem;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .content-section {
                padding: 1.5rem;
                margin-bottom: 2rem;
            }

            .section-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1.5rem;
            }

            .section-title {
                font-size: 1.25rem;
            }

            .table {
                font-size: 1rem;
            }

            .table th {
                font-size: 0.9rem;
                padding: 0.75rem;
            }

            .table td {
                padding: 0.75rem;
            }

            .badge {
                font-size: 0.75rem;
                padding: 0.35rem 0.65rem;
            }
        }

        /* Desktop */
        @media (min-width: 1200px) {
            .main-content {
                padding: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <?php 
                $sidebarLogoUrl = $settings['logo_url'] ?? '';
                
                // Fix path for super_admin directory
                if (!empty($sidebarLogoUrl)) {
                    if (strpos($sidebarLogoUrl, 'uploads/') === 0) {
                        $sidebarLogoUrl = '../' . $sidebarLogoUrl;
                    }
                }
                ?>
                
                <?php if (!empty($sidebarLogoUrl)): ?>
                    <img src="<?= htmlspecialchars($sidebarLogoUrl) ?>" alt="ORDIVO" 
                         style="height: 90px; width: auto; vertical-align: middle;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                    <i class="fas fa-utensils" style="display: none; font-size: 2rem;"></i>
                <?php else: ?>
                    <i class="fas fa-utensils" style="font-size: 2rem;"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-subtitle">Super Admin Panel</div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>User Management
                </a>
            </div>
            <div class="nav-item">
                <a href="vendors.php" class="nav-link">
                    <i class="fas fa-store"></i>Vendor Management
                </a>
            </div>
            <div class="nav-item">
                <a href="products_featured.php" class="nav-link">
                    <i class="fas fa-star"></i>Featured Products
                </a>
            </div>
            <div class="nav-item">
                <a href="categories.php" class="nav-link">
                    <i class="fas fa-tags"></i>Categories
                </a>
            </div>
            <div class="nav-item">
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>Orders
                </a>
            </div>
            <div class="nav-item">
                <a href="analytics.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>Analytics
                </a>
            </div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>Settings
                </a>
            </div>
            <div class="nav-item mt-4">
                <a href="../auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <button class="sidebar-toggle-inline" id="sidebarToggleInline">
                <i class="fas fa-bars"></i>
            </button>
            <div class="welcome-text">
                <h1 class="welcome-title">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
                <p class="welcome-subtitle">Here's what's happening with your platform today.</p>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                </div>
                <div class="user-info-text">
                    <div class="fw-bold"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    <small class="text-muted">Super Administrator</small>
                </div>
            </div>
        </div>

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

        <!-- Dashboard Section -->
        <div id="section-dashboard" class="content-section">
            <!-- Quick Access Cards -->
            <div class="row mb-4">
                <div class="col-6 mb-3">
                    <a href="vendors.php" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100" style="background: #f97316;">
                            <div class="card-body text-white">
                                <div class="d-flex align-items-center">
                                    <div class="me-3" style="font-size: 3rem;">
                                        <i class="fas fa-store"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-1">Vendor Management</h5>
                                        <p class="card-text mb-0 opacity-75">Add vendors, set featured restaurants</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 mb-3">
                    <a href="products_featured.php" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);">
                            <div class="card-body text-dark">
                                <div class="d-flex align-items-center">
                                    <div class="me-3" style="font-size: 3rem;">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-1">Featured Products</h5>
                                        <p class="card-text mb-0 opacity-75">Manage featured products display</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Users</span>
                        <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="totalUsers">0</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>All registered users</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Active Vendors</span>
                        <div class="stat-icon" style="background: #f97316; color: var(--ordivo-primary);">
                            <i class="fas fa-store"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="activeVendors">0</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>Approved vendors</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Pending Approvals</span>
                        <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="pendingVendors">0</div>
                    <div class="stat-change">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Awaiting review</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Orders</span>
                        <div class="stat-icon" style="background: rgba(23, 162, 184, 0.1); color: #17a2b8;">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="totalOrders">0</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>All time orders</span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-lg-9">
                    <div class="content-section">
                        <div class="section-header">
                            <h3 class="section-title">Recent Users</h3>
                            <button class="btn btn-primary btn-sm" onclick="refreshRecentUsers()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="recentUsersTable">
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <i class="fas fa-spinner fa-spin"></i> Loading...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3">
                    <div class="content-section">
                        <div class="section-header">
                            <h3 class="section-title">Quick Actions</h3>
                        </div>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                                <i class="fas fa-plus me-2"></i>Add Category
                            </button>
                            <button class="btn btn-outline-primary" onclick="showSection('vendors')">
                                <i class="fas fa-store me-2"></i>Manage Vendors
                            </button>
                            <button class="btn btn-outline-primary" onclick="showSection('settings')">
                                <i class="fas fa-cog me-2"></i>Site Settings
                            </button>
                            <button class="btn btn-outline-danger" onclick="refreshAllData()">
                                <i class="fas fa-sync-alt me-2"></i>Refresh Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Management Section -->
        <div id="section-users" class="content-section" style="display: none;">
            <div class="section-header">
                <h3 class="section-title">User Management</h3>
                <button class="btn btn-primary" onclick="refreshRecentUsers()">
                    <i class="fas fa-sync-alt me-2"></i>Refresh
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="allUsersTable">
                        <tr>
                            <td colspan="7" class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Loading users...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Vendor Management Section -->
        <div id="section-vendors" class="content-section" style="display: none;">
            <div class="section-header">
                <h3 class="section-title">Vendor Management</h3>
                <button class="btn btn-primary" onclick="refreshPendingVendors()">
                    <i class="fas fa-sync-alt me-2"></i>Refresh
                </button>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <h5 class="mb-3">Pending Vendor Approvals</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Applied</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="pendingVendorsTable">
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Loading pending vendors...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div id="section-categories" class="content-section" style="display: none;">
            <div class="section-header">
                <h3 class="section-title">Category Management</h3>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                    <i class="fas fa-plus me-2"></i>Add Category
                </button>
            </div>
            <div id="categoriesContent">
                <p class="text-muted">Category management features will be loaded here.</p>
            </div>
        </div>
    </div>

    <!-- Create Category Modal -->
    <div class="modal fade" id="createCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_category">
                        
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_description" class="form-label">Description</label>
                            <textarea class="form-control" id="category_description" name="category_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Mobile menu toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarToggleInline = document.getElementById('sidebarToggleInline');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        // Handle inline toggle button
        if (sidebarToggleInline) {
            sidebarToggleInline.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            });
        }

        // Handle fixed toggle button (if exists)
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
        }

        // Close sidebar when clicking a link on mobile
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                }
            });
        });

        // Navigation handling
        document.querySelectorAll('.nav-link[data-section]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.dataset.section;
                showSection(section);
                
                // Update active nav
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });

        function showSection(sectionName) {
            // Hide all sections
            document.querySelectorAll('[id^="section-"]').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected section
            const section = document.getElementById('section-' + sectionName);
            if (section) {
                section.style.display = 'block';
            }
            
            // Load section-specific data
            if (sectionName === 'users') {
                refreshRecentUsers();
            } else if (sectionName === 'vendors') {
                refreshPendingVendors();
            }
        }

        // Data loading functions
        async function loadStats() {
            try {
                const response = await fetch('?ajax=stats');
                const stats = await response.json();
                
                if (stats.error) {
                    console.error('Stats error:', stats.error);
                    return;
                }
                
                document.getElementById('totalUsers').textContent = stats.total_users || 0;
                document.getElementById('activeVendors').textContent = stats.active_vendors || 0;
                document.getElementById('pendingVendors').textContent = stats.pending_vendors || 0;
                document.getElementById('totalOrders').textContent = stats.total_orders || 0;
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        }

        async function refreshRecentUsers() {
            const tableBody = document.getElementById('recentUsersTable');
            const allUsersTable = document.getElementById('allUsersTable');
            
            // Show loading
            const loadingRow = '<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
            if (tableBody) tableBody.innerHTML = loadingRow;
            if (allUsersTable) allUsersTable.innerHTML = '<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
            
            try {
                const response = await fetch('?ajax=recent_users');
                const users = await response.json();
                
                if (users.error) {
                    console.error('Users error:', users.error);
                    return;
                }
                
                const userRows = users.map(user => {
                    const statusBadge = getStatusBadge(user.status);
                    const roleBadge = getRoleBadge(user.role);
                    const date = new Date(user.created_at).toLocaleDateString();
                    
                    return `
                        <tr>
                            <td>${user.id || ''}</td>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td>${roleBadge}</td>
                            <td>${statusBadge}</td>
                            <td>${date}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    ${getUserActions(user)}
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
                
                if (tableBody) tableBody.innerHTML = userRows || '<tr><td colspan="6" class="text-center text-muted">No users found</td></tr>';
                if (allUsersTable) allUsersTable.innerHTML = userRows || '<tr><td colspan="7" class="text-center text-muted">No users found</td></tr>';
                
            } catch (error) {
                console.error('Failed to load users:', error);
                const errorRow = '<tr><td colspan="6" class="text-center text-danger">Failed to load users</td></tr>';
                if (tableBody) tableBody.innerHTML = errorRow;
                if (allUsersTable) allUsersTable.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Failed to load users</td></tr>';
            }
        }

        async function refreshPendingVendors() {
            const tableBody = document.getElementById('pendingVendorsTable');
            if (!tableBody) return;
            
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
            
            try {
                const response = await fetch('?ajax=pending_vendors');
                const vendors = await response.json();
                
                if (vendors.error) {
                    console.error('Vendors error:', vendors.error);
                    return;
                }
                
                const vendorRows = vendors.map(vendor => {
                    const date = new Date(vendor.created_at).toLocaleDateString();
                    
                    return `
                        <tr>
                            <td>${vendor.name}</td>
                            <td>${vendor.email}</td>
                            <td>${vendor.phone || 'N/A'}</td>
                            <td>${date}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-success" onclick="approveVendor(${vendor.id})">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn btn-danger" onclick="rejectVendor(${vendor.id})">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
                
                tableBody.innerHTML = vendorRows || '<tr><td colspan="5" class="text-center text-muted">No pending vendors</td></tr>';
                
            } catch (error) {
                console.error('Failed to load pending vendors:', error);
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load vendors</td></tr>';
            }
        }

        // Helper functions
        function getStatusBadge(status) {
            const badges = {
                'active': '<span class="badge bg-success">Active</span>',
                'inactive': '<span class="badge bg-secondary">Inactive</span>',
                'pending': '<span class="badge bg-warning">Pending</span>',
                'banned': '<span class="badge bg-danger">Banned</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
        }

        function getRoleBadge(role) {
            const badges = {
                'customer': '<span class="badge bg-primary">Customer</span>',
                'vendor': '<span class="badge bg-info">Vendor</span>',
                'super_admin': '<span class="badge bg-dark">Super Admin</span>'
            };
            return badges[role] || '<span class="badge bg-secondary">' + role + '</span>';
        }

        function getUserActions(user) {
            let actions = '';
            
            if (user.status === 'banned') {
                actions += `<button class="btn btn-success" onclick="unbanUser(${user.id})"><i class="fas fa-unlock"></i></button>`;
            } else {
                actions += `<button class="btn btn-warning" onclick="banUser(${user.id})"><i class="fas fa-ban"></i></button>`;
            }
            
            if (user.role !== 'super_admin') {
                actions += `<button class="btn btn-danger" onclick="deleteUser(${user.id})"><i class="fas fa-trash"></i></button>`;
            }
            
            return actions;
        }

        // Action functions
        function approveVendor(userId) {
            if (confirm('Are you sure you want to approve this vendor?')) {
                submitAction('approve_vendor', userId);
            }
        }

        function rejectVendor(userId) {
            if (confirm('Are you sure you want to reject this vendor?')) {
                submitAction('reject_vendor', userId);
            }
        }

        function banUser(userId) {
            if (confirm('Are you sure you want to ban this user?')) {
                submitAction('ban_user', userId);
            }
        }

        function unbanUser(userId) {
            if (confirm('Are you sure you want to unban this user?')) {
                submitAction('unban_user', userId);
            }
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                submitAction('delete_user', userId);
            }
        }

        function submitAction(action, userId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="${action}">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function refreshAllData() {
            loadStats();
            refreshRecentUsers();
            refreshPendingVendors();
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            refreshRecentUsers();
            
            // Auto-refresh every 30 seconds
            setInterval(loadStats, 30000);
        });
    </script>
</body>
</html>
