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
        .sidebar {
            position: fixed;
            top: 0;
            left: 0; /* Always visible */
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: none;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar.show {
            left: 0;
        }

        /* Hide overlay - not needed for static sidebar */
        .sidebar-overlay {
            display: none;
        }

        /* Hide toggle button - not needed for static sidebar */
        .sidebar-toggle {
            display: none;
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
            background: #ffffff;
            color: white;
            transform: translateX(5px);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }

        .main-content {
            margin-left: var(--sidebar-width); /* Always offset by sidebar width */
            min-height: 100vh;
            padding: 2rem;
        }

        .top-bar {
            background: white;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text {
            flex: 1;
        }

        .welcome-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin: 0;
        }

        .welcome-subtitle {
            color: #6c757d;
            margin: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px #e5e7eb;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--ordivo-dark);
            margin-bottom: 0.5rem;
        }

        .stat-change {
            font-size: 0.85rem;
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
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px #e5e7eb;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin: 0;
        }

        .btn-primary {
            background: #10b981; 100%);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px #f97316;
        }

        .table {
            margin: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--ordivo-dark);
            background: #f8f9fa;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }

        .modal-header {
            background: #10b981; 100%);
            color: white;
            border-bottom: none;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                padding-top: 4rem;
            }
            
            .sidebar-toggle {
                top: 15px;
                left: 15px;
                width: 45px;
                height: 45px;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        @media (min-width: 1200px) {
            .main-content {
                padding: 3rem;
                padding-top: 6rem;
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
                $sidebarSiteName = $settings['site_name'] ?? 'ORDIVO';
                
                // Fix path for super_admin directory
                if (!empty($sidebarLogoUrl) && $sidebarLogoUrl !== 'ðŸ”' && $sidebarLogoUrl !== 'ðŸ½ï¸') {
                    if (strpos($sidebarLogoUrl, 'uploads/') === 0) {
                        $sidebarLogoUrl = '../' . $sidebarLogoUrl;
                    }
                }
                ?>
                
                <?php if (!empty($sidebarLogoUrl) && $sidebarLogoUrl !== 'ðŸ”' && $sidebarLogoUrl !== 'ðŸ½ï¸'): ?>
                    <img src="<?= htmlspecialchars($sidebarLogoUrl) ?>" alt="<?= htmlspecialchars($sidebarSiteName) ?>" 
                         style="height: 32px; width: auto; margin-right: 8px; vertical-align: middle;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                    <i class="fas fa-utensils me-2" style="display: none;"></i>
                    <?= htmlspecialchars($sidebarSiteName) ?>
                <?php else: ?>
                    <i class="fas fa-utensils me-2"></i><?= htmlspecialchars($sidebarSiteName) ?>
                <?php endif; ?>
            </div>
            <div class="sidebar-subtitle">Super Admin Panel</div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="#dashboard" class="nav-link active" data-section="dashboard">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="#users" class="nav-link" data-section="users">
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
                <a href="#categories" class="nav-link" data-section="categories">
                    <i class="fas fa-tags"></i>Categories
                </a>
            </div>
            <div class="nav-item">
                <a href="#orders" class="nav-link" data-section="orders">
                    <i class="fas fa-shopping-cart"></i>Orders
                </a>
            </div>
            <div class="nav-item">
                <a href="#analytics" class="nav-link" data-section="analytics">
                    <i class="fas fa-chart-bar"></i>Analytics
                </a>
            </div>
            <div class="nav-item">
                <a href="#settings" class="nav-link" data-section="settings">
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
            <div class="welcome-text">
                <h1 class="welcome-title">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
                <p class="welcome-subtitle">Here's what's happening with your platform today.</p>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                </div>
                <div>
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
                <div class="col-md-6 mb-3">
                    <a href="vendors_new.php" class="text-decoration-none">
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
                <div class="col-md-6 mb-3">
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
                <div class="col-lg-8">
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
                
                <div class="col-lg-4">
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

        <!-- Settings Section -->
        <div id="section-settings" class="content-section" style="display: none;">
            <div class="section-header">
                <h3 class="section-title">Site Settings</h3>
            </div>
            
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="update_settings">
                
                <div class="col-md-6">
                    <label for="site_name" class="form-label">Site Name</label>
                    <input type="text" class="form-control" id="site_name" name="site_name" 
                           value="<?= htmlspecialchars($settings['site_name']) ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="contact_email" class="form-label">Contact Email</label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email" 
                           value="<?= htmlspecialchars($settings['contact_email']) ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="contact_phone" class="form-label">Contact Phone</label>
                    <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                           value="<?= htmlspecialchars($settings['contact_phone']) ?>">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Settings
                    </button>
                </div>
            </form>
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
