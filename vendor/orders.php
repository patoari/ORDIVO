<?php
/**
 * ORDIVO - Vendor Order Management
 * Order processing and management for vendors
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is vendor
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'vendor') {
    header('Location: ../auth/login.php');
    exit;
}

$vendorId = $_SESSION['user_id'];

// Get vendor business information
try {
    global $pdo;
    $stmt = $pdo->prepare("SELECT v.name, v.logo FROM vendors v WHERE v.owner_id = ? LIMIT 1");
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
    error_log("Error loading vendor info in vendor orders: " . $e->getMessage());
    $vendorBusinessName = 'My Business';
    $vendorLogo = '';
}

// Handle order actions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_status':
            try {
                $orderId = (int)$_POST['order_id'];
                $newStatus = sanitizeInput($_POST['status']);
                
                updateData('orders', 
                    ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')], 
                    "id = ? AND vendor_id = ?", 
                    [$orderId, $vendorId]
                );
                
                $success = 'Order status updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update order status: ' . $e->getMessage();
            }
            break;
    }
}

// Get filters
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build WHERE clause
$whereClause = "WHERE o.vendor_id = ?";
$params = [$vendorId];

if ($statusFilter) {
    $whereClause .= " AND COALESCE(o.status, 'pending') = ?";
    $params[] = $statusFilter;
}

if ($dateFilter) {
    $whereClause .= " AND DATE(o.created_at) = ?";
    $params[] = $dateFilter;
}

if ($searchQuery) {
    $whereClause .= " AND (o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Get orders
try {
    $orders = fetchAll("
        SELECT o.*, 
               u.name as customer_name,
               u.email as customer_email,
               u.phone as customer_phone
        FROM orders o 
        LEFT JOIN users u ON o.customer_id = u.id 
        $whereClause
        ORDER BY o.created_at DESC
    ", $params);
    
    // Get statistics
    $stats = [
        'total' => count($orders),
        'pending' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'pending')),
        'processing' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'preparing')),
        'completed' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'delivered')),
        'delivered' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'delivered')),
        'cancelled' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'cancelled')),
        'total_revenue' => array_sum(array_map(fn($o) => ($o['status'] ?? 'pending') === 'delivered' ? $o['total_amount'] : 0, $orders))
    ];
    
} catch (Exception $e) {
    $orders = [];
    $stats = ['total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'delivered' => 0, 'cancelled' => 0, 'total_revenue' => 0];
    $error = 'Failed to load orders: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - ORDIVO Vendor</title>
    
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
            color: #ffffff;
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

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
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
                <a class="nav-link active" href="orders.php">
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
        <!-- Header -->
        <div class="header-card">
            <div class="header-card-content">
                <!-- Mobile Hamburger Button -->
                <button class="sidebar-toggle-inline" id="sidebarToggleInline">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="header-info">
                    <h1>
                        <i class="fas fa-shopping-cart me-2"></i>Order Management
                    </h1>
                    <p class="opacity-75">Track and manage your customer orders</p>
                </div>
                
                <div class="text-end">
                    <div class="h5 mb-0">৳<?= number_format($stats['total_revenue'], 0) ?></div>
                    <div class="opacity-75">Total Revenue</div>
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

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-6 col-lg-2 col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-primary"><?= number_format($stats['total']) ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="col-6 col-lg-2 col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-warning"><?= number_format($stats['pending']) ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-6 col-lg-2 col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-info"><?= number_format($stats['processing']) ?></div>
                    <div class="stat-label">Processing</div>
                </div>
            </div>
            <div class="col-6 col-lg-2 col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-success"><?= number_format($stats['completed']) ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            <div class="col-6 col-lg-2 col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-danger"><?= number_format($stats['cancelled']) ?></div>
                    <div class="stat-label">Cancelled</div>
                </div>
            </div>
            <div class="col-6 col-lg-2 col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-success">৳<?= number_format($stats['total_revenue'], 0) ?></div>
                    <div class="stat-label">Revenue</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search Orders</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Order ID, customer name...">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="preparing" <?= $statusFilter === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                            <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Filter
                            </button>
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Your Orders
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Orders Found</h4>
                        <p class="text-muted mb-4">Orders will appear here when customers place them</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?= $order['id'] ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($order['customer_name'] ?? 'Unknown') ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($order['customer_email'] ?? '') ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>৳<?= number_format($order['total_amount'], 0) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                ($order['status'] ?? 'pending') === 'delivered' ? 'success' : 
                                                (($order['status'] ?? 'pending') === 'preparing' ? 'info' : 
                                                (($order['status'] ?? 'pending') === 'cancelled' ? 'danger' : 'warning')) 
                                            ?>">
                                                <?= ucfirst($order['status'] ?? 'pending') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <?= date('M j, Y', strtotime($order['created_at'])) ?>
                                                <br><small class="text-muted"><?= date('g:i A', strtotime($order['created_at'])) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="viewOrder(<?= $order['id'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (($order['status'] ?? 'pending') !== 'delivered' && ($order['status'] ?? 'pending') !== 'cancelled'): ?>
                                                    <button class="btn btn-outline-success" onclick="updateStatus(<?= $order['id'] ?>, 'preparing')">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                    <button class="btn btn-outline-info" onclick="updateStatus(<?= $order['id'] ?>, 'delivered')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
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

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebarToggleInline = document.getElementById('sidebarToggleInline');

            console.log('Sidebar:', sidebar);
            console.log('Overlay:', sidebarOverlay);
            console.log('Toggle button:', sidebarToggleInline);

            function toggleSidebar() {
                console.log('Toggle sidebar called');
                if (sidebar && sidebarOverlay) {
                    sidebar.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                    console.log('Sidebar classes:', sidebar.className);
                    console.log('Overlay classes:', sidebarOverlay.className);
                }
            }

            if (sidebarToggleInline) {
                sidebarToggleInline.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Hamburger clicked');
                    toggleSidebar();
                });
                console.log('Event listener added to hamburger button');
            } else {
                console.error('Hamburger button not found!');
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleSidebar();
                });
            }
        });

        function viewOrder(orderId) {
            // Implementation for view order details
            alert('View order details - Order ID: ' + orderId);
        }

        function updateStatus(orderId, newStatus) {
            if (confirm(`Are you sure you want to update this order to ${newStatus}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="${orderId}">
                    <input type="hidden" name="status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>