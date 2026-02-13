<?php
/**
 * ORDIVO - Order Management System
 * Complete order tracking and management
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle order actions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    
    switch ($action) {
        case 'update_status':
            $newStatus = sanitizeInput($_POST['new_status'] ?? '');
            if ($orderId && $newStatus) {
                try {
                    updateData('orders', ['status' => $newStatus], 'id = ?', [$orderId]);
                    $success = 'Order status updated successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to update order status: ' . $e->getMessage();
                }
            }
            break;
            
        case 'cancel_order':
            if ($orderId) {
                try {
                    updateData('orders', ['status' => 'cancelled'], 'id = ?', [$orderId]);
                    $success = 'Order cancelled successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to cancel order: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Get orders with filters
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');
$dateFilter = sanitizeInput($_GET['date'] ?? '');

$whereConditions = [];
$params = [];

if ($statusFilter) {
    $whereConditions[] = "o.status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $whereConditions[] = "(o.order_number LIKE ? OR c.name LIKE ? OR v.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($dateFilter) {
    $whereConditions[] = "DATE(o.created_at) = ?";
    $params[] = $dateFilter;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    // Check if orders table exists
    $tableExists = fetchValue("SHOW TABLES LIKE 'orders'");
    
    if ($tableExists) {
        $orders = fetchAll("
            SELECT o.id, o.order_number, o.total_amount, o.status, o.payment_status, 
                   o.created_at, o.delivery_address,
                   c.name as customer_name, c.email as customer_email,
                   v.name as vendor_name
            FROM orders o
            LEFT JOIN users c ON o.customer_id = c.id
            LEFT JOIN users v ON o.vendor_id = v.id
            $whereClause
            ORDER BY o.created_at DESC
            LIMIT 50
        ", $params);
        
        // Get statistics
        $stats = [
            'total' => fetchValue("SELECT COUNT(*) FROM orders") ?: 0,
            'pending' => fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'pending'") ?: 0,
            'confirmed' => fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'confirmed'") ?: 0,
            'delivered' => fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'delivered'") ?: 0,
            'cancelled' => fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'") ?: 0,
            'total_revenue' => fetchValue("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'") ?: 0
        ];
    } else {
        $orders = [];
        $stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'delivered' => 0, 'cancelled' => 0, 'total_revenue' => 0];
    }
} catch (Exception $e) {
    $orders = [];
    $stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'delivered' => 0, 'cancelled' => 0, 'total_revenue' => 0];
    $error = 'Failed to load orders: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - ORDIVO Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-light: #f0fdf4;
            --ordivo-dark: #374151;
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        /* Mobile First - Sidebar hidden by default on mobile */
        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
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

        /* Main Content */
        .main-content {
            margin-left: 0; /* No margin on mobile */
            min-height: 100vh;
            padding: 0.625rem 1rem 1rem; /* 10px top, 1rem sides and bottom */
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
            display: none; /* Hide subtitle on mobile */
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            border-top: none;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }

        .order-number {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: var(--ordivo-primary);
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
                margin-left: var(--sidebar-width);
                padding: 1.5rem;
            }

            .page-header {
                padding: 1.5rem;
                margin-bottom: 2rem;
            }

            .page-title {
                font-size: 1.8rem;
                white-space: normal;
            }

            .page-subtitle {
                font-size: 1rem;
                display: block; /* Show subtitle on tablet+ */
            }
        }

        /* Desktop */
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
                <a href="orders.php" class="nav-link active">
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
                <h1 class="page-title">
                    <i class="fas fa-shopping-cart me-2"></i>Order Management
                </h1>
                <p class="page-subtitle">Track and manage all platform orders</p>
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
            <div class="col-6 col-md-2 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-primary"><?= number_format($stats['total']) ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-warning"><?= number_format($stats['pending']) ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-info"><?= number_format($stats['confirmed']) ?></div>
                    <div class="stat-label">Confirmed</div>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-success"><?= number_format($stats['delivered']) ?></div>
                    <div class="stat-label">Delivered</div>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-danger"><?= number_format($stats['cancelled']) ?></div>
                    <div class="stat-label">Cancelled</div>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
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
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Orders</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Order number, customer, or vendor..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="preparing" <?= $statusFilter === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                            <option value="ready" <?= $statusFilter === 'ready' ? 'selected' : '' ?>>Ready</option>
                            <option value="out_for_delivery" <?= $statusFilter === 'out_for_delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                            <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-shopping-cart me-2"></i>Orders
                </h5>
                <div>
                    <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Vendor</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-shopping-cart fa-2x mb-3"></i><br>
                                        <?= $tableExists ? 'No orders found' : 'Orders table not created yet. Orders will appear here once customers start placing orders.' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <span class="order-number"><?= htmlspecialchars($order['order_number']) ?></span>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($order['customer_name'] ?? 'Unknown') ?></div>
                                            <?php if ($order['customer_email']): ?>
                                                <small class="text-muted"><?= htmlspecialchars($order['customer_email']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($order['vendor_name'] ?? 'Unknown') ?>
                                        </td>
                                        <td>
                                            <strong>৳<?= number_format($order['total_amount'], 0) ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'confirmed' => 'info',
                                                'preparing' => 'primary',
                                                'ready' => 'success',
                                                'out_for_delivery' => 'info',
                                                'delivered' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            $statusColor = $statusColors[$order['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $statusColor ?>">
                                                <?= ucwords(str_replace('_', ' ', $order['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $paymentColors = [
                                                'paid' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                'refunded' => 'info'
                                            ];
                                            $paymentColor = $paymentColors[$order['payment_status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $paymentColor ?>">
                                                <?= ucfirst($order['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#updateStatusModal"
                                                        onclick="updateOrderStatus(<?= $order['id'] ?>, '<?= $order['status'] ?>')"
                                                        title="Update Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'delivered'): ?>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="cancelOrder(<?= $order['id'] ?>)"
                                                            title="Cancel Order">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-outline-info" 
                                                        onclick="viewOrderDetails(<?= $order['id'] ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="updateStatusForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" id="updateOrderId">
                        
                        <div class="mb-3">
                            <label for="updateOrderStatus" class="form-label">Order Status</label>
                            <select class="form-select" id="updateOrderStatus" name="new_status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="preparing">Preparing</option>
                                <option value="ready">Ready</option>
                                <option value="out_for_delivery">Out for Delivery</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
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

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Mobile menu toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarToggleInline = document.getElementById('sidebarToggleInline');

        function toggleSidebar() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        }

        if (sidebarToggleInline) {
            sidebarToggleInline.addEventListener('click', toggleSidebar);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }

        function updateOrderStatus(orderId, currentStatus) {
            document.getElementById('updateOrderId').value = orderId;
            document.getElementById('updateOrderStatus').value = currentStatus;
        }

        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                submitAction('cancel_order', orderId);
            }
        }

        function viewOrderDetails(orderId) {
            // This could open a modal or redirect to a detailed view
            alert('Order details view - Feature coming soon!');
        }

        function submitAction(action, orderId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="${action}">
                <input type="hidden" name="order_id" value="${orderId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    </div><!-- End Main Content -->
</body>
</html>