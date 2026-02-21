<?php
/**
 * ORDIVO - Kitchen Manager Dashboard
 * Comprehensive kitchen operations management system
 */

require_once '../config/db_connection.php';

// Check if user is logged in and has kitchen manager role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

if (!in_array($_SESSION['user_role'], ['kitchen_manager', 'kitchen_staff', 'super_admin', 'vendor', 'store_manager', 'store_staff'])) {
    header('Location: ../customer/index.php');
    exit;
}

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

// Get vendor ID based on user role
if ($_SESSION['user_role'] === 'super_admin') {
    $vendorId = $_GET['vendor_id'] ?? 1; // Default to first vendor for super admin
    $vendorName = 'All Vendors';
} elseif ($_SESSION['user_role'] === 'vendor') {
    // For vendors, get their own vendor profile
    $vendorProfile = fetchRow("SELECT id, name FROM vendors WHERE owner_id = ?", [$_SESSION['user_id']]);
    if ($vendorProfile) {
        $vendorId = $vendorProfile['id'];
        $vendorName = $vendorProfile['name'];
    } else {
        // If no vendor profile, use user ID as vendor ID
        $vendorId = $_SESSION['user_id'];
        $vendorName = $_SESSION['user_name'] ?? 'Vendor';
    }
} else {
    // For kitchen staff and store staff, get vendor ID from vendor_staff table
    $kitchenStaff = fetchRow("
        SELECT vs.vendor_id, v.name as vendor_name 
        FROM vendor_staff vs 
        JOIN vendors v ON vs.vendor_id = v.id 
        WHERE vs.user_id = ? AND vs.status = 'active'
    ", [$_SESSION['user_id']]);

    $vendorId = $kitchenStaff['vendor_id'] ?? null;
    $vendorName = $kitchenStaff['vendor_name'] ?? 'Kitchen';

    if (!$vendorId) {
        die('Staff member not assigned to any vendor');
    }
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'dashboard_stats':
            try {
                $whereClause = $_SESSION['user_role'] === 'super_admin' ? '' : "WHERE o.vendor_id = $vendorId";
                
                // Today's stats
                $todayStats = fetchRow("
                    SELECT 
                        COUNT(*) as total_orders,
                        COUNT(CASE WHEN o.status = 'pending' THEN 1 END) as pending_orders,
                        COUNT(CASE WHEN o.status = 'preparing' THEN 1 END) as preparing_orders,
                        COUNT(CASE WHEN o.status = 'ready' THEN 1 END) as ready_orders,
                        COUNT(CASE WHEN o.status = 'delivered' THEN 1 END) as completed_orders,
                        AVG(CASE WHEN o.status = 'delivered' THEN 
                            TIMESTAMPDIFF(MINUTE, o.created_at, o.updated_at) 
                        END) as avg_preparation_time,
                        SUM(CASE WHEN o.status = 'delivered' THEN o.total_amount ELSE 0 END) as revenue
                    FROM orders o 
                    $whereClause AND DATE(o.created_at) = CURDATE()
                ") ?? [
                    'total_orders' => 0,
                    'pending_orders' => 0,
                    'preparing_orders' => 0,
                    'ready_orders' => 0,
                    'completed_orders' => 0,
                    'avg_preparation_time' => 0,
                    'revenue' => 0
                ];
                
                // Active kitchen workflows (check if table exists)
                $activeWorkflows = [];
                try {
                    $activeWorkflows = fetchAll("
                        SELECT 
                            kw.id,
                            kw.order_id,
                            o.order_number,
                            kw.workflow_status,
                            kw.priority,
                            kw.estimated_completion,
                            kw.assigned_chef,
                            u.name as chef_name,
                            ks.name as current_station,
                            TIMESTAMPDIFF(MINUTE, kw.created_at, NOW()) as elapsed_time
                        FROM kitchen_workflows kw
                        JOIN orders o ON kw.order_id = o.id
                        LEFT JOIN users u ON kw.assigned_chef = u.id
                        LEFT JOIN kitchen_stations ks ON kw.current_station_id = ks.id
                        WHERE kw.workflow_status IN ('pending', 'in_progress')
                        " . ($_SESSION['user_role'] !== 'super_admin' ? "AND kw.vendor_id = $vendorId" : "") . "
                        ORDER BY kw.priority DESC, kw.created_at ASC
                        LIMIT 10
                    ") ?? [];
                } catch (Exception $e) {
                    // If kitchen_workflows table doesn't exist, get orders directly
                    $activeWorkflows = fetchAll("
                        SELECT 
                            o.id,
                            o.id as order_id,
                            o.order_number,
                            o.status as workflow_status,
                            'normal' as priority,
                            NULL as estimated_completion,
                            NULL as assigned_chef,
                            NULL as chef_name,
                            NULL as current_station,
                            TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) as elapsed_time
                        FROM orders o
                        $whereClause 
                        AND o.status IN ('confirmed', 'preparing', 'ready')
                        ORDER BY o.created_at ASC
                        LIMIT 10
                    ") ?? [];
                }
                
                // Kitchen stations status (check if table exists)
                $stationsStatus = [];
                try {
                    $stationsStatus = fetchAll("
                        SELECT 
                            ks.id,
                            ks.name,
                            ks.station_type,
                            ks.capacity,
                            COUNT(kw.id) as active_orders,
                            ks.is_active
                        FROM kitchen_stations ks
                        LEFT JOIN kitchen_workflows kw ON ks.id = kw.current_station_id 
                            AND kw.workflow_status IN ('pending', 'in_progress')
                        " . ($_SESSION['user_role'] !== 'super_admin' ? "WHERE ks.vendor_id = $vendorId" : "") . "
                        GROUP BY ks.id, ks.name, ks.station_type, ks.capacity, ks.is_active
                        ORDER BY ks.sort_order
                    ") ?? [];
                } catch (Exception $e) {
                    // Default stations if table doesn't exist
                    $stationsStatus = [
                        [
                            'id' => 1,
                            'name' => 'Preparation',
                            'station_type' => 'prep',
                            'capacity' => 5,
                            'active_orders' => 0,
                            'is_active' => 1
                        ],
                        [
                            'id' => 2,
                            'name' => 'Cooking',
                            'station_type' => 'cooking',
                            'capacity' => 3,
                            'active_orders' => 0,
                            'is_active' => 1
                        ],
                        [
                            'id' => 3,
                            'name' => 'Packaging',
                            'station_type' => 'packaging',
                            'capacity' => 2,
                            'active_orders' => 0,
                            'is_active' => 1
                        ]
                    ];
                }
                
                echo json_encode([
                    'today_stats' => $todayStats,
                    'active_workflows' => $activeWorkflows,
                    'stations_status' => $stationsStatus
                ]);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'update_order_status':
            try {
                $orderId = (int)($_POST['order_id'] ?? 0);
                $newStatus = sanitizeInput($_POST['status'] ?? '');
                $notes = sanitizeInput($_POST['notes'] ?? '');
                
                if (!$orderId || !$newStatus) {
                    throw new Exception('Missing required parameters');
                }
                
                // Update order status
                updateData('orders', [
                    'status' => $newStatus,
                    'updated_at' => date('Y-m-d H:i:s')
                ], "id = $orderId");
                
                // Add to status history
                insertData('order_status_history', [
                    'order_id' => $orderId,
                    'status' => $newStatus,
                    'notes' => $notes,
                    'changed_by' => $_SESSION['user_id']
                ]);
                
                // Update kitchen workflow if exists
                if (in_array($newStatus, ['preparing', 'ready', 'delivered'])) {
                    $workflowStatus = match($newStatus) {
                        'preparing' => 'in_progress',
                        'ready' => 'completed',
                        'delivered' => 'completed',
                        default => 'pending'
                    };
                    
                    updateData('kitchen_workflows', [
                        'workflow_status' => $workflowStatus,
                        'actual_completion_time' => $newStatus === 'ready' ? date('Y-m-d H:i:s') : null
                    ], "order_id = $orderId");
                }
                
                echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'assign_chef':
            try {
                $workflowId = (int)($_POST['workflow_id'] ?? 0);
                $chefId = (int)($_POST['chef_id'] ?? 0);
                
                if (!$workflowId || !$chefId) {
                    throw new Exception('Missing required parameters');
                }
                
                updateData('kitchen_workflows', [
                    'assigned_chef' => $chefId,
                    'workflow_status' => 'in_progress',
                    'actual_start_time' => date('Y-m-d H:i:s')
                ], "id = $workflowId");
                
                echo json_encode(['success' => true, 'message' => 'Chef assigned successfully']);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
    }
}

// Get kitchen staff for assignment
$kitchenStaff = fetchAll("
    SELECT u.id, u.name, u.role
    FROM users u
    JOIN vendor_staff vs ON u.id = vs.user_id
    WHERE vs.vendor_id = ? AND u.role IN ('kitchen_manager', 'kitchen_staff') 
    AND vs.status = 'active'
    ORDER BY u.name
", [$vendorId]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Manager Dashboard - <?= htmlspecialchars($siteName) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-success: #28a745;
            --ordivo-warning: #ffc107;
            --ordivo-danger: #dc3545;
            --ordivo-info: #17a2b8;
            --ordivo-dark: #1a1a1a;
            --ordivo-light: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 0;
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

        /* User dropdown in header */
        .user-dropdown {
            display: block;
            flex-shrink: 0;
        }

        .user-dropdown .btn-light {
            background: white;
            border: none;
            color: #10b981;
            font-weight: 600;
        }

        .user-dropdown .btn-light:hover {
            background: rgba(255, 255, 255, 0.9);
        }

        .user-dropdown .dropdown-menu {
            right: 0;
            left: auto;
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

        /* Dashboard Cards */
        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-card {
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            font-size: 2rem;
            opacity: 0.1;
            position: absolute;
            top: 50%;
            right: 0.5rem;
            transform: translateY(-50%);
        }

        /* Order Status Colors */
        .status-pending { color: var(--ordivo-warning); }
        .status-preparing { color: var(--ordivo-info); }
        .status-ready { color: var(--ordivo-success); }
        .status-delivered { color: #28a745; }
        .status-cancelled { color: var(--ordivo-danger); }

        .priority-urgent { border-left: 4px solid var(--ordivo-danger); }
        .priority-high { border-left: 4px solid var(--ordivo-warning); }
        .priority-normal { border-left: 4px solid var(--ordivo-info); }
        .priority-low { border-left: 4px solid #6c757d; }

        /* Kitchen Stations */
        .station-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--ordivo-primary);
            transition: all 0.3s ease;
        }

        .station-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .station-active {
            border-left-color: var(--ordivo-success);
        }

        .station-busy {
            border-left-color: var(--ordivo-warning);
        }

        .station-overloaded {
            border-left-color: var(--ordivo-danger);
        }

        /* Workflow Cards */
        .workflow-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .workflow-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* Action Buttons */
        .btn-action {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border-radius: 6px;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--ordivo-primary);
            border-color: var(--ordivo-primary);
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
            border-color: var(--ordivo-secondary);
        }

        /* Loading States */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

            .dashboard-card {
                padding: 1.5rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }

            .stat-label {
                font-size: 0.9rem;
            }

            .stat-icon {
                font-size: 3rem;
                right: 1rem;
            }
            
            .workflow-card {
                padding: 1rem;
            }
        }

        /* Desktop */
        @media (min-width: 1200px) {
            .main-content {
                padding: 20px;
            }

            .dashboard-card {
                padding: 1.5rem;
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
                <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                    <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" 
                         style="height: 60px; width: 60px; object-fit: cover; border-radius: 10px; margin-right: 12px; background: white; padding: 5px;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="display: none; width: 60px; height: 60px; background: #ffffff; border-radius: 10px; align-items: center; justify-content: center; margin-right: 12px;">
                        <i class="fas fa-utensils fa-2x" style="color: #10b981;"></i>
                    </div>
                <?php else: ?>
                    <div style="display: flex; width: 60px; height: 60px; background: #ffffff; border-radius: 10px; align-items: center; justify-content: center; margin-right: 12px;">
                        <i class="fas fa-utensils fa-2x" style="color: #10b981;"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <h5 class="text-white mb-0" style="font-size: 1.1rem; font-weight: 600;"><?= htmlspecialchars($vendorName) ?></h5>
                    <small class="text-white-50">Kitchen Portal</small>
                </div>
            </div>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="../vendor/dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../vendor/products.php">
                    <i class="fas fa-box me-2"></i>Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../vendor/orders.php">
                    <i class="fas fa-shopping-cart me-2"></i>Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../vendor/inventory.php">
                    <i class="fas fa-boxes me-2"></i>Inventory
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../vendor/categories.php">
                    <i class="fas fa-tags me-2"></i>Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-utensils me-2"></i>Kitchen
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../vendor/staff.php">
                    <i class="fas fa-users me-2"></i>Staff
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../vendor/settings.php">
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
                        <i class="fas fa-fire me-2"></i>Kitchen Manager Dashboard
                    </h1>
                    <p class="opacity-75">Real-time kitchen operations and order management</p>
                </div>
                
                <!-- User Dropdown -->
                <div class="dropdown user-dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user me-2"></i><span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="inventory.php"><i class="fas fa-boxes me-2"></i>Kitchen Inventory</a></li>
                        <li><a class="dropdown-item" href="../vendor/inventory.php"><i class="fas fa-warehouse me-2"></i>Full Inventory</a></li>
                        <li><a class="dropdown-item" href="../customer/profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Dashboard Stats -->
        <div class="row mb-3" id="dashboardStats">
            <div class="col-12">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p class="mt-2">Loading dashboard...</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Active Orders & Workflows -->
            <div class="col-6 col-lg-8 mb-3">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-fire text-danger me-2"></i>
                            Active Kitchen Workflows
                        </h5>
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshWorkflows()">
                            <i class="fas fa-sync-alt"></i> <span class="d-none d-md-inline">Refresh</span>
                        </button>
                    </div>
                    <div id="activeWorkflows">
                        <div class="loading">
                            <i class="fas fa-spinner"></i>
                            <p class="mt-2">Loading workflows...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kitchen Stations -->
            <div class="col-6 col-lg-4 mb-3">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-industry text-primary me-2"></i>
                        Kitchen Stations
                    </h5>
                    <div id="kitchenStations">
                        <div class="loading">
                            <i class="fas fa-spinner"></i>
                            <p class="mt-2">Loading stations...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="statusForm">
                        <input type="hidden" id="statusOrderId">
                        <div class="mb-3">
                            <label class="form-label">New Status</label>
                            <select class="form-select" id="newStatus" required>
                                <option value="">Select Status</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="preparing">Preparing</option>
                                <option value="ready">Ready</option>
                                <option value="out_for_delivery">Out for Delivery</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="statusNotes" rows="3" placeholder="Add any notes about this status change..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateOrderStatus()">Update Status</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Chef Assignment Modal -->
    <div class="modal fade" id="chefModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Chef</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="chefForm">
                        <input type="hidden" id="workflowId">
                        <div class="mb-3">
                            <label class="form-label">Select Chef</label>
                            <select class="form-select" id="chefId" required>
                                <option value="">Select Chef</option>
                                <?php foreach ($kitchenStaff as $staff): ?>
                                    <option value="<?= $staff['id'] ?>"><?= htmlspecialchars($staff['name']) ?> (<?= ucfirst($staff['role']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="assignChef()">Assign Chef</button>
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

            // Load dashboard data
            loadDashboardData();
            
            // Auto-refresh every 30 seconds
            setInterval(loadDashboardData, 30000);
        });

        async function loadDashboardData() {
            try {
                const response = await fetch('?ajax=dashboard_stats');
                const data = await response.json();
                
                if (data.error) {
                    console.error('Dashboard error:', data.error);
                    return;
                }
                
                renderDashboardStats(data.today_stats);
                renderActiveWorkflows(data.active_workflows);
                renderKitchenStations(data.stations_status);
                
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
            }
        }

        function renderDashboardStats(stats) {
            const avgTime = stats.avg_preparation_time ? Math.round(stats.avg_preparation_time) : 0;
            const revenue = parseFloat(stats.revenue || 0).toFixed(2);
            
            document.getElementById('dashboardStats').innerHTML = `
                <div class="col-6 col-md-3 mb-3">
                    <div class="dashboard-card stat-card">
                        <i class="fas fa-clock stat-icon"></i>
                        <div class="stat-number status-pending">${stats.pending_orders || 0}</div>
                        <div class="stat-label">Pending Orders</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <div class="dashboard-card stat-card">
                        <i class="fas fa-fire stat-icon"></i>
                        <div class="stat-number status-preparing">${stats.preparing_orders || 0}</div>
                        <div class="stat-label">Preparing</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <div class="dashboard-card stat-card">
                        <i class="fas fa-check-circle stat-icon"></i>
                        <div class="stat-number status-ready">${stats.ready_orders || 0}</div>
                        <div class="stat-label">Ready</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <div class="dashboard-card stat-card">
                        <i class="fas fa-chart-line stat-icon"></i>
                        <div class="stat-number text-success">৳${revenue}</div>
                        <div class="stat-label">Today's Revenue</div>
                    </div>
                </div>
                <div class="col-6 col-md-6 mb-3">
                    <div class="dashboard-card stat-card">
                        <i class="fas fa-stopwatch stat-icon"></i>
                        <div class="stat-number text-info">${avgTime} min</div>
                        <div class="stat-label">Avg Prep Time</div>
                    </div>
                </div>
                <div class="col-6 col-md-6 mb-3">
                    <div class="dashboard-card stat-card">
                        <i class="fas fa-trophy stat-icon"></i>
                        <div class="stat-number status-delivered">${stats.completed_orders || 0}</div>
                        <div class="stat-label">Completed Today</div>
                    </div>
                </div>
            `;
        }

        function renderActiveWorkflows(workflows) {
            if (!workflows || workflows.length === 0) {
                document.getElementById('activeWorkflows').innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-clipboard-check fa-3x mb-3"></i>
                        <p>No active workflows at the moment</p>
                    </div>
                `;
                return;
            }
            
            const workflowsHtml = workflows.map(workflow => {
                const priorityClass = `priority-${workflow.priority}`;
                const statusClass = `status-${workflow.workflow_status}`;
                const elapsedTime = workflow.elapsed_time;
                
                return `
                    <div class="workflow-card ${priorityClass}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">Order #${workflow.order_number}</h6>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>${elapsedTime} min ago
                                    ${workflow.current_station ? `• <i class="fas fa-industry me-1"></i>${workflow.current_station}` : ''}
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-${workflow.priority === 'urgent' ? 'danger' : workflow.priority === 'high' ? 'warning' : 'info'} mb-1">
                                    ${workflow.priority.toUpperCase()}
                                </span>
                                <br>
                                <span class="badge ${statusClass === 'status-preparing' ? 'bg-info' : 'bg-warning'}">
                                    ${workflow.workflow_status.replace('_', ' ').toUpperCase()}
                                </span>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                ${workflow.chef_name ? 
                                    `<small><i class="fas fa-user-tie me-1"></i>Chef: ${workflow.chef_name}</small>` :
                                    '<small class="text-muted"><i class="fas fa-user-slash me-1"></i>No chef assigned</small>'
                                }
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-action" onclick="openStatusModal(${workflow.order_id})" title="Update Status">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${!workflow.chef_name ? 
                                    `<button class="btn btn-outline-success btn-action" onclick="openChefModal(${workflow.id})" title="Assign Chef">
                                        <i class="fas fa-user-plus"></i>
                                    </button>` : ''
                                }
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            document.getElementById('activeWorkflows').innerHTML = workflowsHtml;
        }

        function renderKitchenStations(stations) {
            if (!stations || stations.length === 0) {
                document.getElementById('kitchenStations').innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-industry fa-2x mb-2"></i>
                        <p>No kitchen stations configured</p>
                    </div>
                `;
                return;
            }
            
            const stationsHtml = stations.map(station => {
                const utilizationRate = station.capacity > 0 ? (station.active_orders / station.capacity) * 100 : 0;
                let stationClass = 'station-active';
                
                if (utilizationRate >= 100) {
                    stationClass = 'station-overloaded';
                } else if (utilizationRate >= 70) {
                    stationClass = 'station-busy';
                }
                
                return `
                    <div class="station-card ${stationClass}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">${station.name}</h6>
                            <span class="badge ${station.is_active ? 'bg-success' : 'bg-secondary'}">
                                ${station.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-cogs me-1"></i>${station.station_type.replace('_', ' ')}
                            </small>
                            <small>
                                <i class="fas fa-tasks me-1"></i>
                                ${station.active_orders}/${station.capacity} orders
                            </small>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar ${utilizationRate >= 100 ? 'bg-danger' : utilizationRate >= 70 ? 'bg-warning' : 'bg-success'}" 
                                 style="width: ${Math.min(utilizationRate, 100)}%"></div>
                        </div>
                    </div>
                `;
            }).join('');
            
            document.getElementById('kitchenStations').innerHTML = stationsHtml;
        }

        function refreshWorkflows() {
            loadDashboardData();
        }

        function openStatusModal(orderId) {
            document.getElementById('statusOrderId').value = orderId;
            document.getElementById('newStatus').value = '';
            document.getElementById('statusNotes').value = '';
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }

        function openChefModal(workflowId) {
            document.getElementById('workflowId').value = workflowId;
            document.getElementById('chefId').value = '';
            new bootstrap.Modal(document.getElementById('chefModal')).show();
        }

        async function updateOrderStatus() {
            const orderId = document.getElementById('statusOrderId').value;
            const status = document.getElementById('newStatus').value;
            const notes = document.getElementById('statusNotes').value;
            
            if (!orderId || !status) {
                alert('Please fill in all required fields');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('order_id', orderId);
                formData.append('status', status);
                formData.append('notes', notes);
                
                const response = await fetch('?ajax=update_order_status', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.error) {
                    alert('Error: ' + result.error);
                } else {
                    bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
                    loadDashboardData();
                    showNotification('Order status updated successfully', 'success');
                }
            } catch (error) {
                console.error('Error updating status:', error);
                alert('Failed to update order status');
            }
        }

        async function assignChef() {
            const workflowId = document.getElementById('workflowId').value;
            const chefId = document.getElementById('chefId').value;
            
            if (!workflowId || !chefId) {
                alert('Please select a chef');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('workflow_id', workflowId);
                formData.append('chef_id', chefId);
                
                const response = await fetch('?ajax=assign_chef', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.error) {
                    alert('Error: ' + result.error);
                } else {
                    bootstrap.Modal.getInstance(document.getElementById('chefModal')).hide();
                    loadDashboardData();
                    showNotification('Chef assigned successfully', 'success');
                }
            } catch (error) {
                console.error('Error assigning chef:', error);
                alert('Failed to assign chef');
            }
        }

        function showNotification(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            
            const notification = document.createElement('div');
            notification.className = 'position-fixed top-0 end-0 p-3';
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show">
                    <i class="fas ${iconClass} me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    </script>
</body>
</html>