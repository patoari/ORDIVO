<?php
/**
 * ORDIVO - Vendor Staff Management
 * Staff management for vendor operations
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is vendor
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'vendor') {
    header('Location: ../auth/login.php');
    exit;
}

$vendorId = $_SESSION['user_id'];

// Get vendor business information - force fresh query, no caching
try {
    global $pdo;
    $stmt = $pdo->prepare("SELECT v.name, v.logo FROM vendors v WHERE v.owner_id = ? LIMIT 1");
    $stmt->execute([$vendorId]);
    $vendorInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $vendorBusinessName = $vendorInfo['name'] ?? 'My Business';
    $vendorLogo = $vendorInfo['logo'] ?? '';
    
    // Debug: Log the vendor info (remove this after testing)
    error_log("Vendor ID: $vendorId, Business Name: $vendorBusinessName, Logo: $vendorLogo");
    
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
    error_log("Error loading vendor info in vendor staff: " . $e->getMessage());
    $vendorBusinessName = 'My Business';
    $vendorLogo = '';
}

// Handle staff actions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_staff':
            try {
                global $pdo;
                
                // Check if email already exists
                $existingUser = fetchRow("SELECT id FROM users WHERE email = ?", [$_POST['email']]);
                if ($existingUser) {
                    $error = 'Email already exists in the system.';
                    break;
                }
                
                // Check if vendor_id column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM users");
                $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $hasVendorId = in_array('vendor_id', $existingColumns);
                
                $data = [
                    'name' => sanitizeInput($_POST['name']),
                    'email' => sanitizeInput($_POST['email']),
                    'phone' => sanitizeInput($_POST['phone']),
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                    'role' => sanitizeInput($_POST['role']),
                    'status' => 'active'
                ];
                
                // Add vendor_id only if column exists
                if ($hasVendorId) {
                    $data['vendor_id'] = $vendorId;
                }
                
                // Add created_at if column exists
                if (in_array('created_at', $existingColumns)) {
                    $data['created_at'] = date('Y-m-d H:i:s');
                }
                
                insertData('users', $data);
                $success = 'Staff member added successfully!';
            } catch (Exception $e) {
                $error = 'Failed to add staff member: ' . $e->getMessage();
            }
            break;
            
        case 'update_staff':
            try {
                global $pdo;
                
                $staffId = (int)$_POST['staff_id'];
                
                // Check if vendor_id column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM users");
                $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $hasVendorId = in_array('vendor_id', $existingColumns);
                
                $data = [
                    'name' => sanitizeInput($_POST['name']),
                    'email' => sanitizeInput($_POST['email']),
                    'phone' => sanitizeInput($_POST['phone']),
                    'role' => sanitizeInput($_POST['role'])
                ];
                
                // Add updated_at if column exists
                if (in_array('updated_at', $existingColumns)) {
                    $data['updated_at'] = date('Y-m-d H:i:s');
                }
                
                // Update password if provided
                if (!empty($_POST['password'])) {
                    $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                
                // Use vendor_id in WHERE clause only if column exists
                if ($hasVendorId) {
                    updateData('users', $data, "id = ? AND vendor_id = ?", [$staffId, $vendorId]);
                } else {
                    updateData('users', $data, "id = ?", [$staffId]);
                }
                
                $success = 'Staff member updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update staff member: ' . $e->getMessage();
            }
            break;
            
        case 'toggle_status':
            try {
                global $pdo;
                
                $staffId = (int)$_POST['staff_id'];
                $newStatus = $_POST['status'] === 'active' ? 'inactive' : 'active';
                
                // Check if vendor_id column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM users");
                $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $hasVendorId = in_array('vendor_id', $existingColumns);
                
                // Use vendor_id in WHERE clause only if column exists
                if ($hasVendorId) {
                    updateData('users', ['status' => $newStatus], "id = ? AND vendor_id = ?", [$staffId, $vendorId]);
                } else {
                    updateData('users', ['status' => $newStatus], "id = ?", [$staffId]);
                }
                
                $success = 'Staff status updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update staff status: ' . $e->getMessage();
            }
            break;
            
        case 'delete_staff':
            try {
                global $pdo;
                
                $staffId = (int)$_POST['staff_id'];
                
                // Check if vendor_id column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM users");
                $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $hasVendorId = in_array('vendor_id', $existingColumns);
                
                // Use vendor_id in WHERE clause only if column exists
                if ($hasVendorId) {
                    deleteData('users', "id = ? AND vendor_id = ?", [$staffId, $vendorId]);
                } else {
                    deleteData('users', "id = ?", [$staffId]);
                }
                
                $success = 'Staff member deleted successfully!';
            } catch (Exception $e) {
                $error = 'Failed to delete staff member: ' . $e->getMessage();
            }
            break;
    }
}

// Get staff members
try {
    // First check if vendor_id column exists in users table
    global $pdo;
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $hasVendorId = in_array('vendor_id', $existingColumns);
    
    if ($hasVendorId) {
        // Use vendor_id if it exists
        $staff = fetchAll("
            SELECT * FROM users 
            WHERE vendor_id = ? AND role IN ('kitchen_manager', 'kitchen_staff', 'store_manager', 'store_staff')
            ORDER BY created_at DESC
        ", [$vendorId]);
    } else {
        // Fallback: get all staff members (for now, since vendor_id doesn't exist)
        $staff = fetchAll("
            SELECT * FROM users 
            WHERE role IN ('kitchen_manager', 'kitchen_staff', 'store_manager', 'store_staff')
            ORDER BY created_at DESC
        ");
    }
    
    // Get statistics
    $stats = [
        'total_staff' => count($staff),
        'active_staff' => count(array_filter($staff, fn($s) => ($s['status'] ?? 'active') === 'active')),
        'kitchen_staff' => count(array_filter($staff, fn($s) => in_array($s['role'], ['kitchen_manager', 'kitchen_staff']))),
        'store_staff' => count(array_filter($staff, fn($s) => in_array($s['role'], ['store_manager', 'store_staff'])))
    ];
    
} catch (Exception $e) {
    $staff = [];
    $stats = ['total_staff' => 0, 'active_staff' => 0, 'kitchen_staff' => 0, 'store_staff' => 0];
    $error = 'Failed to load staff: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - ORDIVO Vendor</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/ordivo-responsive.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-accent: #f97316;
            --ordivo-light: #f0fdf4;
            --ordivo-dark: #1a1a1a;
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
            color: rgba(255, 255, 255, 0.9);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #ffffff;
            color: #10b981;
            border-radius: 0.5rem;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active i {
            color: #10b981;
        }
            transform: translateX(5px);
        }

        .main-content {
            margin-left: 0; /* No margin on mobile */
            padding: 0.625rem 1rem 1rem; /* 10px top, 1rem sides and bottom */
            transition: all 0.3s ease;
        }

        .header-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-top: 4px solid #10b981;
        }

        .header-card-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* Inline hamburger button for mobile - positioned in header card */
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .sidebar-toggle-inline:hover {
            background: rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }

        .header-info {
            flex: 1;
            min-width: 0;
        }

        .header-info h1 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .header-info p {
            font-size: 0.875rem;
            margin-bottom: 0;
        }

        @media (max-width: 576px) {
            .header-card-content {
                flex-direction: column;
                align-items: stretch;
            }

            .sidebar-toggle-inline {
                width: 100%;
            }

            .header-info {
                width: 100%;
                text-align: center;
            }

            .header-card-content .btn {
                width: 100%;
            }
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: none;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: var(--ordivo-primary);
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .staff-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .table th {
            border: none;
            background: #f8f9fa;
            font-weight: 600;
            color: var(--ordivo-dark);
            padding: 1rem;
        }

        .table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }

        .table tbody tr {
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
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

            .header-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }

            .header-info h1 {
                font-size: 1.8rem;
            }

            .header-info p {
                font-size: 1rem;
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
                <a class="nav-link" href="dashboard.php">
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
                <a class="nav-link active" href="staff.php">
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
        <!-- Header -->
        <div class="header-card">
            <div class="header-card-content">
                <!-- Mobile Hamburger Button -->
                <button class="sidebar-toggle-inline" id="sidebarToggleInline">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="header-info">
                    <h1>
                        <i class="fas fa-users me-2"></i>Staff Management
                    </h1>
                    <p class="opacity-75">Manage your team members and their roles</p>
                </div>
                
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                    <i class="fas fa-plus me-2"></i>Add Staff
                </button>
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

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-primary"><?= number_format($stats['total_staff']) ?></div>
                    <div class="stat-label">Total Staff</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-success"><?= number_format($stats['active_staff']) ?></div>
                    <div class="stat-label">Active Staff</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-info"><?= number_format($stats['kitchen_staff']) ?></div>
                    <div class="stat-label">Kitchen Staff</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-warning"><?= number_format($stats['store_staff']) ?></div>
                    <div class="stat-label">Store Staff</div>
                </div>
            </div>
        </div>

        <!-- Staff List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Your Team
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($staff)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Staff Members Yet</h4>
                        <p class="text-muted mb-4">Start building your team by adding staff members</p>
                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                            <i class="fas fa-plus me-2"></i>Add Your First Staff Member
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Staff Member</th>
                                    <th>Role</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff as $member): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="staff-avatar me-3">
                                                    <?= strtoupper(substr($member['name'], 0, 2)) ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($member['name']) ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($member['email']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= ucwords(str_replace('_', ' ', $member['role'])) ?></span>
                                        </td>
                                        <td>
                                            <div>
                                                <small class="text-muted">
                                                    <i class="fas fa-phone me-1"></i><?= htmlspecialchars($member['phone'] ?: 'Not provided') ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $member['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($member['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= date('M j, Y', strtotime($member['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="editStaff(<?= $member['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-<?= $member['status'] === 'active' ? 'warning' : 'success' ?>" onclick="toggleStatus(<?= $member['id'] ?>, '<?= $member['status'] ?>')">
                                                    <i class="fas fa-<?= $member['status'] === 'active' ? 'pause' : 'play' ?>"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="deleteStaff(<?= $member['id'] ?>, '<?= htmlspecialchars($member['name']) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add Staff Member
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_staff">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="kitchen_manager">Kitchen Manager</option>
                                <option value="kitchen_staff">Kitchen Staff</option>
                                <option value="store_manager">Store Manager</option>
                                <option value="store_staff">Store Staff</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()">
                                    <i class="fas fa-random"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Staff Member
                        </button>
                    </div>
                </form>
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
                    e.stopPropagation();
                    toggleSidebar();
                });
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
        });
        function editStaff(staffId) {
            // Implementation for edit staff modal
            alert('Edit staff functionality - Staff ID: ' + staffId);
        }

        function toggleStatus(staffId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            
            if (confirm(`Are you sure you want to ${action} this staff member?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="staff_id" value="${staffId}">
                    <input type="hidden" name="status" value="${currentStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteStaff(staffId, staffName) {
            if (confirm(`Are you sure you want to delete "${staffName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_staff">
                    <input type="hidden" name="staff_id" value="${staffId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function generatePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let password = '';
            for (let i = 0; i < 8; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>