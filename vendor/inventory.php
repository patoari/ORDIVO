<?php
/**
 * ORDIVO - Vendor Inventory Management
 * Inventory management system for vendors and kitchen managers
 */

require_once '../config/db_connection.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['vendor', 'store_manager', 'store_staff', 'kitchen_manager', 'kitchen_staff'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Get vendor ID based on user role
if ($_SESSION['user_role'] === 'vendor') {
    $vendorId = $_SESSION['user_id'];
    global $pdo;
    $stmt = $pdo->prepare("SELECT name, logo FROM vendors WHERE owner_id = ? LIMIT 1");
    $stmt->execute([$vendorId]);
    $vendorInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $vendorName = $vendorInfo['name'] ?? 'Vendor';
    $vendorBusinessName = $vendorInfo['name'] ?? 'My Business';
    $vendorLogo = $vendorInfo['logo'] ?? '';
} else {
    // For staff members, get vendor ID from vendor_staff table
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT vs.vendor_id, v.name as vendor_name, v.logo as vendor_logo
        FROM vendor_staff vs 
        JOIN vendors v ON vs.vendor_id = v.id 
        WHERE vs.user_id = ? AND vs.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $staffInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staffInfo) {
        die('Staff member not assigned to any vendor');
    }
    
    $vendorId = $staffInfo['vendor_id'];
    $vendorName = $staffInfo['vendor_name'];
    $vendorBusinessName = $staffInfo['vendor_name'];
    $vendorLogo = $staffInfo['vendor_logo'] ?? '';
}

// Fix vendor logo path
if (!empty($vendorLogo) && $vendorLogo !== '🍔' && $vendorLogo !== '🍽️') {
    if (strpos($vendorLogo, 'uploads/') === 0) {
        $vendorLogo = '../' . $vendorLogo;
    }
    elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $vendorLogo)) {
        $vendorLogo = '../' . $vendorLogo;
    }
}

// Check permissions
$canManage = in_array($_SESSION['user_role'], ['vendor', 'kitchen_manager', 'store_manager']);
$canEdit = in_array($_SESSION['user_role'], ['vendor', 'kitchen_manager', 'store_manager', 'kitchen_staff']);

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

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'get_inventory_data':
            try {
                // Get inventory items with categories
                $items = fetchAll("
                    SELECT 
                        ii.*,
                        ic.name as category_name,
                        ic.color as category_color,
                        CASE 
                            WHEN ii.current_stock <= ii.minimum_stock THEN 'low'
                            WHEN ii.current_stock = 0 THEN 'out'
                            ELSE 'normal'
                        END as stock_status,
                        CASE 
                            WHEN ii.is_perishable = 1 AND ii.expiry_date <= CURDATE() THEN 'expired'
                            WHEN ii.is_perishable = 1 AND ii.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'expiring'
                            ELSE 'fresh'
                        END as freshness_status
                    FROM inventory_items ii
                    LEFT JOIN inventory_categories ic ON ii.category_id = ic.id
                    WHERE ii.vendor_id = ? AND ii.is_active = 1
                    ORDER BY ii.name
                ", [$vendorId]);
                
                // Get categories
                $categories = fetchAll("
                    SELECT * FROM inventory_categories 
                    WHERE vendor_id = ? AND is_active = 1 
                    ORDER BY name
                ", [$vendorId]);
                
                // Get alerts
                $alerts = fetchAll("
                    SELECT 
                        ia.*,
                        ii.name as item_name,
                        ii.current_stock,
                        ii.minimum_stock
                    FROM inventory_alerts ia
                    JOIN inventory_items ii ON ia.item_id = ii.id
                    WHERE ia.vendor_id = ? AND ia.is_resolved = 0
                    ORDER BY ia.created_at DESC
                    LIMIT 10
                ", [$vendorId]);
                
                // Calculate summary stats
                $stats = [
                    'total_items' => count($items),
                    'low_stock_items' => count(array_filter($items, fn($item) => $item['stock_status'] === 'low')),
                    'out_of_stock_items' => count(array_filter($items, fn($item) => $item['stock_status'] === 'out')),
                    'expiring_items' => count(array_filter($items, fn($item) => $item['freshness_status'] === 'expiring')),
                    'expired_items' => count(array_filter($items, fn($item) => $item['freshness_status'] === 'expired')),
                    'total_value' => array_sum(array_map(fn($item) => $item['current_stock'] * $item['cost_per_unit'], $items))
                ];
                
                echo json_encode([
                    'items' => $items,
                    'categories' => $categories,
                    'alerts' => $alerts,
                    'stats' => $stats
                ]);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'update_stock':
            if (!$canEdit) {
                echo json_encode(['error' => 'Permission denied']);
                exit;
            }
            
            try {
                $itemId = (int)($_POST['item_id'] ?? 0);
                $newStock = (float)($_POST['new_stock'] ?? 0);
                $transactionType = sanitizeInput($_POST['transaction_type'] ?? 'adjustment');
                $notes = sanitizeInput($_POST['notes'] ?? '');
                
                if (!$itemId || $newStock < 0) {
                    throw new Exception('Invalid parameters');
                }
                
                // Get current item data
                $item = fetchRow("SELECT * FROM inventory_items WHERE id = ? AND vendor_id = ?", [$itemId, $vendorId]);
                if (!$item) {
                    throw new Exception('Item not found');
                }
                
                $previousStock = $item['current_stock'];
                $quantity = $newStock - $previousStock;
                
                // Update item stock
                updateData('inventory_items', [
                    'current_stock' => $newStock,
                    'last_updated_by' => $_SESSION['user_id'],
                    'updated_at' => date('Y-m-d H:i:s')
                ], "id = $itemId");
                
                // Record transaction
                insertData('inventory_transactions', [
                    'vendor_id' => $vendorId,
                    'item_id' => $itemId,
                    'transaction_type' => $transactionType,
                    'quantity' => abs($quantity),
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'notes' => $notes,
                    'performed_by' => $_SESSION['user_id']
                ]);
                
                // Check for alerts
                if ($newStock <= $item['minimum_stock']) {
                    $alertType = $newStock == 0 ? 'out_of_stock' : 'low_stock';
                    $message = $newStock == 0 ? 
                        "Item '{$item['name']}' is out of stock" : 
                        "Item '{$item['name']}' is running low (Current: {$newStock}, Minimum: {$item['minimum_stock']})";
                    
                    insertData('inventory_alerts', [
                        'vendor_id' => $vendorId,
                        'item_id' => $itemId,
                        'alert_type' => $alertType,
                        'message' => $message
                    ]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'add_item':
            if (!$canManage) {
                echo json_encode(['error' => 'Permission denied']);
                exit;
            }
            
            try {
                $data = [
                    'vendor_id' => $vendorId,
                    'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
                    'name' => sanitizeInput($_POST['name'] ?? ''),
                    'description' => sanitizeInput($_POST['description'] ?? ''),
                    'sku' => sanitizeInput($_POST['sku'] ?? ''),
                    'unit' => sanitizeInput($_POST['unit'] ?? 'pcs'),
                    'current_stock' => (float)($_POST['current_stock'] ?? 0),
                    'minimum_stock' => (float)($_POST['minimum_stock'] ?? 0),
                    'cost_per_unit' => (float)($_POST['cost_per_unit'] ?? 0),
                    'supplier_name' => sanitizeInput($_POST['supplier_name'] ?? ''),
                    'supplier_contact' => sanitizeInput($_POST['supplier_contact'] ?? ''),
                    'storage_location' => sanitizeInput($_POST['storage_location'] ?? ''),
                    'is_perishable' => (int)($_POST['is_perishable'] ?? 0),
                    'expiry_date' => $_POST['expiry_date'] ?? null,
                    'last_updated_by' => $_SESSION['user_id']
                ];
                
                if (empty($data['name'])) {
                    throw new Exception('Item name is required');
                }
                
                $itemId = insertData('inventory_items', $data);
                
                // Record initial stock transaction
                if ($data['current_stock'] > 0) {
                    insertData('inventory_transactions', [
                        'vendor_id' => $vendorId,
                        'item_id' => $itemId,
                        'transaction_type' => 'stock_in',
                        'quantity' => $data['current_stock'],
                        'previous_stock' => 0,
                        'new_stock' => $data['current_stock'],
                        'notes' => 'Initial stock entry',
                        'performed_by' => $_SESSION['user_id']
                    ]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Item added successfully']);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - <?= htmlspecialchars($siteName) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-success: #10b981;
            --ordivo-warning: #ffc107;
            --ordivo-danger: #dc3545;
            --ordivo-info: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
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

        /* Navigation */
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: var(--ordivo-primary);
            border-bottom-color: var(--ordivo-primary);
            background: none;
        }

        /* Cards */
        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
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
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Stock Status */
        .stock-normal { color: var(--ordivo-success); }
        .stock-low { color: var(--ordivo-warning); }
        .stock-out { color: var(--ordivo-danger); }

        .freshness-fresh { color: var(--ordivo-success); }
        .freshness-expiring { color: var(--ordivo-warning); }
        .freshness-expired { color: var(--ordivo-danger); }

        /* Buttons */
        .btn-primary {
            background: var(--ordivo-primary);
            border-color: var(--ordivo-primary);
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
            border-color: var(--ordivo-secondary);
        }

        /* DataTables */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
        }

        /* Alerts */
        .alert-item {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid var(--ordivo-warning);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .alert-item.danger {
            border-left-color: var(--ordivo-danger);
        }

        /* Loading */
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
        }

        /* Desktop */
        @media (min-width: 1200px) {
            .main-content {
                padding: 20px;
            }
        }

        /* DataTable Controls - One Line Layout */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            display: inline-block;
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .row:first-child {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem;
        }

        .dataTables_wrapper .row:first-child > div {
            flex: 0 0 auto;
        }

        @media (min-width: 576px) {
            .dataTables_wrapper .dataTables_length {
                margin-right: auto;
            }
            
            .dataTables_wrapper .dataTables_filter {
                margin-left: auto;
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
                <a class="nav-link active" href="inventory.php">
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
        <!-- Header -->
        <div class="header-card">
            <div class="header-card-content">
                <!-- Mobile Hamburger Button -->
                <button class="sidebar-toggle-inline" id="sidebarToggleInline">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="header-info">
                    <h1>
                        <i class="fas fa-boxes me-2"></i>Inventory Management
                    </h1>
                    <p class="opacity-75">Track and manage your stock levels</p>
                </div>
                
                <?php if ($canManage): ?>
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addItemModal">
                    <i class="fas fa-plus me-2"></i>Add Item
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4" id="statsCards">
            <div class="col-12">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p class="mt-2">Loading inventory statistics...</p>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="inventoryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab">
                    <i class="fas fa-boxes me-2"></i>Inventory Items
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alerts" type="button" role="tab">
                    <i class="fas fa-exclamation-triangle me-2"></i>Alerts
                </button>
            </li>
            <?php if ($canManage): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
                    <i class="fas fa-tags me-2"></i>Categories
                </button>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="inventoryTabContent">
            <!-- Inventory Items Tab -->
            <div class="tab-pane fade show active" id="inventory" role="tabpanel">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-boxes text-primary me-2"></i>
                            Inventory Items
                        </h5>
                        <?php if ($canManage): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="fas fa-plus me-2"></i>Add Item
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="inventoryTable">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>SKU</th>
                                    <th>Current Stock</th>
                                    <th>Min Stock</th>
                                    <th>Unit</th>
                                    <th>Status</th>
                                    <th>Expiry</th>
                                    <?php if ($canEdit): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="inventoryTableBody">
                                <tr>
                                    <td colspan="<?= $canEdit ? 9 : 8 ?>" class="text-center">
                                        <div class="loading">
                                            <i class="fas fa-spinner"></i>
                                            <p class="mt-2">Loading inventory items...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Alerts Tab -->
            <div class="tab-pane fade" id="alerts" role="tabpanel">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Inventory Alerts
                    </h5>
                    <div id="alertsList">
                        <div class="loading">
                            <i class="fas fa-spinner"></i>
                            <p class="mt-2">Loading alerts...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories Tab -->
            <?php if ($canManage): ?>
            <div class="tab-pane fade" id="categories" role="tabpanel">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-tags text-info me-2"></i>
                        Inventory Categories
                    </h5>
                    <div id="categoriesList">
                        <div class="loading">
                            <i class="fas fa-spinner"></i>
                            <p class="mt-2">Loading categories...</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Item Modal -->
    <?php if ($canManage): ?>
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Inventory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addItemForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Item Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" id="categorySelect">
                                    <option value="">Select Category</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SKU</label>
                                <input type="text" class="form-control" name="sku">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unit</label>
                                <select class="form-select" name="unit">
                                    <option value="pcs">Pieces</option>
                                    <option value="kg">Kilograms</option>
                                    <option value="gm">Grams</option>
                                    <option value="ltr">Liters</option>
                                    <option value="ml">Milliliters</option>
                                    <option value="box">Box</option>
                                    <option value="pack">Pack</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Current Stock</label>
                                <input type="number" class="form-control" name="current_stock" min="0" step="0.01" value="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Minimum Stock</label>
                                <input type="number" class="form-control" name="minimum_stock" min="0" step="0.01" value="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cost per Unit</label>
                                <input type="number" class="form-control" name="cost_per_unit" min="0" step="0.01" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Supplier Name</label>
                                <input type="text" class="form-control" name="supplier_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Supplier Contact</label>
                                <input type="text" class="form-control" name="supplier_contact">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Storage Location</label>
                                <input type="text" class="form-control" name="storage_location">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_perishable" id="isPerishable">
                                    <label class="form-check-label" for="isPerishable">
                                        Perishable Item
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3" id="expiryDateField" style="display: none;">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" name="expiry_date">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addItem()">Add Item</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Update Stock Modal -->
    <?php if ($canEdit): ?>
    <div class="modal fade" id="updateStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStockForm">
                        <input type="hidden" id="updateItemId">
                        <div class="mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" class="form-control" id="updateItemName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Stock</label>
                            <input type="text" class="form-control" id="updateCurrentStock" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Stock *</label>
                            <input type="number" class="form-control" id="updateNewStock" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction Type</label>
                            <select class="form-select" id="updateTransactionType">
                                <option value="adjustment">Stock Adjustment</option>
                                <option value="stock_in">Stock In</option>
                                <option value="stock_out">Stock Out</option>
                                <option value="waste">Waste/Damage</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="updateNotes" rows="3" placeholder="Reason for stock update..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateStock()">Update Stock</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        let inventoryData = {};
        let inventoryTable;
        
        // Load data on page load
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

            loadInventoryData();
            
            // Perishable checkbox handler
            const perishableCheckbox = document.getElementById('isPerishable');
            const expiryDateField = document.getElementById('expiryDateField');
            
            if (perishableCheckbox) {
                perishableCheckbox.addEventListener('change', function() {
                    expiryDateField.style.display = this.checked ? 'block' : 'none';
                });
            }
        });

        async function loadInventoryData() {
            try {
                const response = await fetch('?ajax=get_inventory_data');
                const data = await response.json();
                
                if (data.error) {
                    console.error('Error loading inventory data:', data.error);
                    return;
                }
                
                inventoryData = data;
                renderStatsCards(data.stats);
                renderInventoryTable(data.items);
                renderAlerts(data.alerts);
                renderCategories(data.categories);
                populateCategorySelect(data.categories);
                
            } catch (error) {
                console.error('Failed to load inventory data:', error);
            }
        }

        function renderStatsCards(stats) {
            const statsHtml = `
                <div class="col-6 col-lg-2 mb-3">
                    <div class="dashboard-card stat-card">
                        <div class="stat-number text-primary">${stats.total_items}</div>
                        <div class="stat-label">Total Items</div>
                    </div>
                </div>
                <div class="col-6 col-lg-2 mb-3">
                    <div class="dashboard-card stat-card">
                        <div class="stat-number text-warning">${stats.low_stock_items}</div>
                        <div class="stat-label">Low Stock</div>
                    </div>
                </div>
                <div class="col-6 col-lg-2 mb-3">
                    <div class="dashboard-card stat-card">
                        <div class="stat-number text-danger">${stats.out_of_stock_items}</div>
                        <div class="stat-label">Out of Stock</div>
                    </div>
                </div>
                <div class="col-6 col-lg-2 mb-3">
                    <div class="dashboard-card stat-card">
                        <div class="stat-number text-warning">${stats.expiring_items}</div>
                        <div class="stat-label">Expiring Soon</div>
                    </div>
                </div>
                <div class="col-6 col-lg-2 mb-3">
                    <div class="dashboard-card stat-card">
                        <div class="stat-number text-danger">${stats.expired_items}</div>
                        <div class="stat-label">Expired</div>
                    </div>
                </div>
                <div class="col-6 col-lg-2 mb-3">
                    <div class="dashboard-card stat-card">
                        <div class="stat-number text-success">৳${parseFloat(stats.total_value).toFixed(2)}</div>
                        <div class="stat-label">Total Value</div>
                    </div>
                </div>
            `;
            
            document.getElementById('statsCards').innerHTML = statsHtml;
        }

        function renderInventoryTable(items) {
            if (inventoryTable) {
                inventoryTable.destroy();
            }
            
            const canEdit = <?= $canEdit ? 'true' : 'false' ?>;
            
            const tableBody = items.map(item => {
                const stockClass = `stock-${item.stock_status}`;
                const freshnessClass = `freshness-${item.freshness_status}`;
                
                let stockBadge = '';
                if (item.stock_status === 'out') {
                    stockBadge = '<span class="badge bg-danger">Out of Stock</span>';
                } else if (item.stock_status === 'low') {
                    stockBadge = '<span class="badge bg-warning">Low Stock</span>';
                } else {
                    stockBadge = '<span class="badge bg-success">Normal</span>';
                }
                
                let expiryInfo = '';
                if (item.is_perishable == 1 && item.expiry_date) {
                    const expiryDate = new Date(item.expiry_date);
                    const today = new Date();
                    const diffTime = expiryDate - today;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    if (diffDays < 0) {
                        expiryInfo = `<span class="badge bg-danger">Expired</span>`;
                    } else if (diffDays <= 3) {
                        expiryInfo = `<span class="badge bg-warning">Expires in ${diffDays} days</span>`;
                    } else {
                        expiryInfo = `<span class="text-muted">${item.expiry_date}</span>`;
                    }
                } else {
                    expiryInfo = '<span class="text-muted">N/A</span>';
                }
                
                let actionsHtml = '';
                if (canEdit) {
                    actionsHtml = `
                        <button class="btn btn-sm btn-outline-primary" onclick="openUpdateStockModal(${item.id}, '${item.name}', ${item.current_stock})">
                            <i class="fas fa-edit"></i>
                        </button>
                    `;
                }
                
                return `
                    <tr>
                        <td>
                            <div>
                                <strong>${item.name}</strong>
                                ${item.description ? `<br><small class="text-muted">${item.description}</small>` : ''}
                            </div>
                        </td>
                        <td>
                            ${item.category_name ? 
                                `<span class="badge" style="background-color: ${item.category_color}">${item.category_name}</span>` : 
                                '<span class="text-muted">Uncategorized</span>'
                            }
                        </td>
                        <td>${item.sku || '<span class="text-muted">N/A</span>'}</td>
                        <td class="${stockClass}"><strong>${item.current_stock} ${item.unit}</strong></td>
                        <td>${item.minimum_stock} ${item.unit}</td>
                        <td>${item.unit}</td>
                        <td>${stockBadge}</td>
                        <td>${expiryInfo}</td>
                        ${canEdit ? `<td>${actionsHtml}</td>` : ''}
                    </tr>
                `;
            }).join('');
            
            document.getElementById('inventoryTableBody').innerHTML = tableBody;
            
            // Initialize DataTable
            inventoryTable = $('#inventoryTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'asc']],
                language: {
                    search: "Search items:",
                    lengthMenu: "Show _MENU_ items per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ items",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }

        function renderAlerts(alerts) {
            if (alerts.length === 0) {
                document.getElementById('alertsList').innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <p>No active alerts</p>
                    </div>
                `;
                return;
            }
            
            const alertsHtml = alerts.map(alert => {
                const alertClass = alert.alert_type === 'out_of_stock' || alert.alert_type === 'expired' ? 'danger' : '';
                const iconClass = alert.alert_type === 'out_of_stock' ? 'fa-times-circle' : 
                                 alert.alert_type === 'expired' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle';
                
                return `
                    <div class="alert-item ${alertClass}">
                        <div class="d-flex align-items-start">
                            <i class="fas ${iconClass} me-3 mt-1"></i>
                            <div class="flex-grow-1">
                                <strong>${alert.item_name}</strong>
                                <p class="mb-1">${alert.message}</p>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    ${new Date(alert.created_at).toLocaleString()}
                                </small>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            document.getElementById('alertsList').innerHTML = alertsHtml;
        }

        function renderCategories(categories) {
            if (categories.length === 0) {
                document.getElementById('categoriesList').innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-tags fa-3x mb-3"></i>
                        <p>No categories created yet</p>
                    </div>
                `;
                return;
            }
            
            const categoriesHtml = categories.map(category => `
                <div class="d-flex align-items-center justify-content-between p-3 mb-2 bg-light rounded">
                    <div class="d-flex align-items-center">
                        <div class="me-3" style="width: 20px; height: 20px; background-color: ${category.color}; border-radius: 50%;"></div>
                        <div>
                            <strong>${category.name}</strong>
                            ${category.description ? `<br><small class="text-muted">${category.description}</small>` : ''}
                        </div>
                    </div>
                    <span class="badge bg-primary">${category.color}</span>
                </div>
            `).join('');
            
            document.getElementById('categoriesList').innerHTML = categoriesHtml;
        }

        function populateCategorySelect(categories) {
            const select = document.getElementById('categorySelect');
            if (!select) return;
            
            select.innerHTML = '<option value="">Select Category</option>';
            categories.forEach(category => {
                select.innerHTML += `<option value="${category.id}">${category.name}</option>`;
            });
        }

        function openUpdateStockModal(itemId, itemName, currentStock) {
            document.getElementById('updateItemId').value = itemId;
            document.getElementById('updateItemName').value = itemName;
            document.getElementById('updateCurrentStock').value = currentStock;
            document.getElementById('updateNewStock').value = currentStock;
            document.getElementById('updateTransactionType').value = 'adjustment';
            document.getElementById('updateNotes').value = '';
            
            new bootstrap.Modal(document.getElementById('updateStockModal')).show();
        }

        async function updateStock() {
            const formData = new FormData();
            formData.append('item_id', document.getElementById('updateItemId').value);
            formData.append('new_stock', document.getElementById('updateNewStock').value);
            formData.append('transaction_type', document.getElementById('updateTransactionType').value);
            formData.append('notes', document.getElementById('updateNotes').value);
            
            try {
                const response = await fetch('?ajax=update_stock', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.error) {
                    alert('Error: ' + result.error);
                } else {
                    bootstrap.Modal.getInstance(document.getElementById('updateStockModal')).hide();
                    loadInventoryData();
                    showNotification('Stock updated successfully', 'success');
                }
            } catch (error) {
                console.error('Error updating stock:', error);
                alert('Failed to update stock');
            }
        }

        async function addItem() {
            const form = document.getElementById('addItemForm');
            const formData = new FormData(form);
            
            // Convert checkbox to integer
            formData.set('is_perishable', document.getElementById('isPerishable').checked ? '1' : '0');
            
            try {
                const response = await fetch('?ajax=add_item', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.error) {
                    alert('Error: ' + result.error);
                } else {
                    bootstrap.Modal.getInstance(document.getElementById('addItemModal')).hide();
                    form.reset();
                    loadInventoryData();
                    showNotification('Item added successfully', 'success');
                }
            } catch (error) {
                console.error('Error adding item:', error);
                alert('Failed to add item');
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