<?php
/**
 * ORDIVO - Complete Vendor Management System
 * Super Admin can: Add vendors, Set featured restaurants, Manage vendor status
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle vendor actions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_vendor':
            try {
                global $pdo;
                
                // Validate required fields
                if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['password'])) {
                    throw new Exception('Name, email, and password are required.');
                }
                
                // Check if email already exists
                $existingUser = fetchRow("SELECT id FROM users WHERE email = ?", [$_POST['email']]);
                if ($existingUser) {
                    throw new Exception('Email already exists in the system.');
                }
                
                // Create user account
                $userData = [
                    'name' => sanitizeInput($_POST['name']),
                    'email' => sanitizeInput($_POST['email']),
                    'phone' => sanitizeInput($_POST['phone'] ?? ''),
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                    'role' => 'vendor',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $userId = insertData('users', $userData);
                
                // Create vendor profile
                $businessTypeId = (int)($_POST['business_type_id'] ?? 1);
                $cityId = (int)($_POST['city_id'] ?? 1);
                
                // Generate unique slug
                $businessName = sanitizeInput($_POST['business_name'] ?? $_POST['name']);
                $baseSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $businessName), '-'));
                
                // If slug is empty after sanitization, use 'vendor' as base
                if (empty($baseSlug)) {
                    $baseSlug = 'vendor';
                }
                
                // Create unique slug with user ID and timestamp to avoid duplicates
                $uniqueSlug = $baseSlug . '-' . $userId . '-' . time();
                
                $vendorData = [
                    'owner_id' => $userId,
                    'business_type_id' => $businessTypeId,
                    'name' => $businessName,
                    'slug' => $uniqueSlug,
                    'description' => sanitizeInput($_POST['description'] ?? ''),
                    'address' => sanitizeInput($_POST['address'] ?? 'Not specified'),
                    'city_id' => $cityId,
                    'phone' => sanitizeInput($_POST['phone'] ?? ''),
                    'email' => sanitizeInput($_POST['email']),
                    'is_active' => 1,
                    'is_verified' => 1,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                insertData('vendors', $vendorData);
                
                $success = 'Vendor added successfully! Login credentials sent to vendor email.';
            } catch (Exception $e) {
                $error = 'Failed to add vendor: ' . $e->getMessage();
            }
            break;
            
        case 'toggle_featured':
            try {
                $vendorId = (int)$_POST['vendor_id'];
                $currentStatus = (int)$_POST['current_status'];
                $newStatus = $currentStatus ? 0 : 1;
                
                updateData('vendors', ['is_featured' => $newStatus], 'id = ?', [$vendorId]);
                
                $success = $newStatus ? 'Restaurant marked as featured!' : 'Restaurant removed from featured!';
            } catch (Exception $e) {
                $error = 'Failed to update featured status: ' . $e->getMessage();
            }
            break;
            
        case 'update_status':
            try {
                $userId = (int)$_POST['user_id'];
                $status = sanitizeInput($_POST['status']);
                
                updateData('users', ['status' => $status], 'id = ?', [$userId]);
                
                $success = 'Vendor status updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update status: ' . $e->getMessage();
            }
            break;
            
        case 'delete_vendor':
            try {
                $userId = (int)$_POST['user_id'];
                
                // Delete vendor profile
                deleteData('vendors', 'owner_id = ?', [$userId]);
                
                // Delete user account
                deleteData('users', 'id = ?', [$userId]);
                
                $success = 'Vendor deleted successfully!';
            } catch (Exception $e) {
                $error = 'Failed to delete vendor: ' . $e->getMessage();
            }
            break;
    }
}

// Get filters
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$featuredFilter = sanitizeInput($_GET['featured'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');

// Build query
$whereConditions = ["u.role = 'vendor'"];
$params = [];

if ($statusFilter) {
    $whereConditions[] = "u.status = ?";
    $params[] = $statusFilter;
}

if ($featuredFilter !== '') {
    $whereConditions[] = "v.is_featured = ?";
    $params[] = (int)$featuredFilter;
}

if ($search) {
    $whereConditions[] = "(u.name LIKE ? OR u.email LIKE ? OR v.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

try {
    $vendors = fetchAll("
        SELECT 
            u.id as user_id,
            u.name as user_name,
            u.email,
            u.phone,
            u.status,
            u.created_at,
            v.id as vendor_id,
            v.name as business_name,
            v.logo,
            v.is_featured,
            v.is_verified,
            v.rating,
            v.total_orders,
            v.total_revenue
        FROM users u
        LEFT JOIN vendors v ON u.id = v.owner_id
        $whereClause
        ORDER BY u.created_at DESC
    ", $params);
    
    // Get statistics
    $stats = [
        'total' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'vendor'"),
        'active' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'vendor' AND status = 'active'"),
        'featured' => fetchValue("SELECT COUNT(*) FROM vendors WHERE is_featured = 1"),
        'pending' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'vendor' AND status = 'pending'")
    ];
    
    // Get business types for dropdown
    $businessTypes = fetchAll("SELECT id, name FROM business_types ORDER BY name");
    
    // Get cities for dropdown
    $cities = fetchAll("SELECT id, name FROM cities ORDER BY name");
    
} catch (Exception $e) {
    error_log("Vendor Management Error: " . $e->getMessage());
    $vendors = [];
    $stats = ['total' => 0, 'active' => 0, 'featured' => 0, 'pending' => 0];
    $businessTypes = [];
    $cities = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Management - ORDIVO Super Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-light: #f0fdf4;
            --ordivo-dark: #374151;
            --ordivo-accent: #f97316;
            --sidebar-width: 280px;
            --primary: #10b981;
            --secondary: #059669;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

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

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            text-align: center;
        }

        .sidebar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: white;
            text-decoration: none;
            display: block;
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

        /* Main Content - Mobile First */
        .main-content {
            margin-left: 0; /* No margin on mobile */
            min-height: 100vh;
            padding: 0.625rem 1rem 1rem; /* 10px top */
        }
        
        .page-header {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-top: 4px solid #10b981;
            display: flex;
            flex-direction: row;
            gap: 0.75rem;
            align-items: center;
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

        .page-header-content {
            flex: 1;
            min-width: 0;
        }

        .page-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .page-subtitle {
            font-size: 0.75rem;
            color: #6c757d;
            margin: 0;
            display: none; /* Hide on mobile */
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 10px #e5e7eb;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px #e5e7eb;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px #e5e7eb;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px #e5e7eb;
        }
        
        .vendor-logo {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: cover;
        }
        
        .badge-featured {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            font-weight: 600;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            border: none;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
        }

        /* Tablet and up */
        @media (min-width: 768px) {
            .sidebar-toggle-inline {
                display: none;
            }

            .sidebar {
                left: 0;
            }

            .sidebar-overlay {
                display: none !important;
            }

            .main-content {
                margin-left: var(--sidebar-width);
                padding: 1.5rem;
            }

            .page-header {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .page-title {
                font-size: 1.8rem;
                white-space: normal;
            }

            .page-subtitle {
                display: block;
                font-size: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-value {
                font-size: 2.5rem;
            }

            .stat-label {
                font-size: 0.9rem;
            }
        }

        @media (min-width: 1200px) {
            .main-content {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <?php 
                $settings = fetchRow("SELECT * FROM site_settings LIMIT 1");
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
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>User Management
                </a>
            </div>
            <div class="nav-item">
                <a href="vendors.php" class="nav-link active">
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
        <!-- Page Header -->
        <div class="page-header">
            <button class="sidebar-toggle-inline" id="sidebarToggleInline">
                <i class="fas fa-bars"></i>
            </button>
            <div class="page-header-content">
                <h1 class="page-title"><i class="fas fa-store me-2"></i>Vendor Management</h1>
                <p class="page-subtitle">Manage restaurants, set featured vendors, and control access</p>
            </div>
            <button class="btn btn-primary btn-sm d-none d-md-block" data-bs-toggle="modal" data-bs-target="#addVendorModal">
                <i class="fas fa-plus me-2"></i>Add New Vendor
            </button>
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="stat-label">Total Vendors</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-3">
                <div class="stat-card" style="border-left-color: var(--success);">
                    <div class="stat-value" style="color: var(--success);"><?= number_format($stats['active']) ?></div>
                    <div class="stat-label">Active Vendors</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-3">
                <div class="stat-card" style="border-left-color: #ffd700;">
                    <div class="stat-value" style="color: #ffd700;"><?= number_format($stats['featured']) ?></div>
                    <div class="stat-label">Featured Restaurants</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-3">
                <div class="stat-card" style="border-left-color: var(--warning);">
                    <div class="stat-value" style="color: var(--warning);"><?= number_format($stats['pending']) ?></div>
                    <div class="stat-label">Pending Approval</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="banned" <?= $statusFilter === 'banned' ? 'selected' : '' ?>>Banned</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Featured</label>
                        <select class="form-select" name="featured">
                            <option value="">All</option>
                            <option value="1" <?= $featuredFilter === '1' ? 'selected' : '' ?>>Featured Only</option>
                            <option value="0" <?= $featuredFilter === '0' ? 'selected' : '' ?>>Non-Featured</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Vendors Table -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Vendors List (<?= count($vendors) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Logo</th>
                                <th>Business Name</th>
                                <th>Owner</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Stats</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vendors)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="fas fa-store fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No vendors found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($vendors as $vendor): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($vendor['logo'])): ?>
                                                <img src="../<?= htmlspecialchars($vendor['logo']) ?>" alt="Logo" class="vendor-logo">
                                            <?php else: ?>
                                                <div class="vendor-logo bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-store text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($vendor['business_name'] ?? 'N/A') ?></strong>
                                            <?php if ($vendor['is_verified']): ?>
                                                <i class="fas fa-check-circle text-success ms-1" title="Verified"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($vendor['user_name']) ?></td>
                                        <td>
                                            <small>
                                                <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($vendor['email']) ?><br>
                                                <i class="fas fa-phone me-1"></i><?= htmlspecialchars($vendor['phone'] ?? 'N/A') ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'active' => 'success',
                                                'inactive' => 'secondary',
                                                'pending' => 'warning',
                                                'banned' => 'danger'
                                            ];
                                            $statusColor = $statusColors[$vendor['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $statusColor ?>"><?= ucfirst($vendor['status']) ?></span>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Toggle featured status?');">
                                                <input type="hidden" name="action" value="toggle_featured">
                                                <input type="hidden" name="vendor_id" value="<?= $vendor['vendor_id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= $vendor['is_featured'] ?>">
                                                <button type="submit" class="btn btn-sm <?= $vendor['is_featured'] ? 'btn-warning' : 'btn-outline-secondary' ?>">
                                                    <i class="fas fa-star"></i>
                                                    <?= $vendor['is_featured'] ? 'Featured' : 'Set Featured' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <small>
                                                <i class="fas fa-star text-warning"></i> <?= number_format($vendor['rating'], 1) ?><br>
                                                <i class="fas fa-shopping-cart text-primary"></i> <?= number_format($vendor['total_orders']) ?> orders<br>
                                                <i class="fas fa-dollar-sign text-success"></i> ৳<?= number_format($vendor['total_revenue']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="vendor_details.php?id=<?= $vendor['user_id'] ?>" class="btn btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#statusModal<?= $vendor['user_id'] ?>" title="Change Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this vendor? This action cannot be undone!');">
                                                    <input type="hidden" name="action" value="delete_vendor">
                                                    <input type="hidden" name="user_id" value="<?= $vendor['user_id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <!-- Status Modal -->
                                            <div class="modal fade" id="statusModal<?= $vendor['user_id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Change Vendor Status</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="user_id" value="<?= $vendor['user_id'] ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Select Status</label>
                                                                    <select class="form-select" name="status" required>
                                                                        <option value="active" <?= $vendor['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                                        <option value="inactive" <?= $vendor['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                                        <option value="pending" <?= $vendor['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                                        <option value="banned" <?= $vendor['status'] === 'banned' ? 'selected' : '' ?>>Banned</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Update Status</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Vendor Modal -->
    <div class="modal fade" id="addVendorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Vendor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_vendor">
                        
                        <h6 class="mb-3"><i class="fas fa-user me-2"></i>Owner Information</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Owner Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3"><i class="fas fa-store me-2"></i>Business Information</h6>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Business Name</label>
                                <input type="text" class="form-control" name="business_name" placeholder="Leave empty to use owner name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Business Type</label>
                                <select class="form-select" name="business_type_id">
                                    <?php foreach ($businessTypes as $type): ?>
                                        <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City</label>
                                <select class="form-select" name="city_id">
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?= $city['id'] ?>"><?= htmlspecialchars($city['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                                    <label class="form-check-label" for="is_featured">
                                        <i class="fas fa-star text-warning me-1"></i>Mark as Featured Restaurant
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Vendor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div><!-- End Main Content -->

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Mobile menu toggle
        const sidebarToggleInline = document.getElementById('sidebarToggleInline');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (sidebarToggleInline) {
            sidebarToggleInline.addEventListener('click', function() {
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
    </script>
</body>
</html>
