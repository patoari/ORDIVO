<?php
/**
 * ORDIVO - Kitchen Staff Interface
 * Simple interface for kitchen staff to manage their assigned orders
 */

require_once '../config/db_connection.php';

// Check if user is logged in and has kitchen staff role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

if (!in_array($_SESSION['user_role'], ['kitchen_staff', 'kitchen_manager', 'super_admin', 'vendor', 'store_manager', 'store_staff'])) {
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
    $staffInfo = fetchRow("
        SELECT vs.vendor_id, v.name as vendor_name 
        FROM vendor_staff vs 
        JOIN vendors v ON vs.vendor_id = v.id 
        WHERE vs.user_id = ? AND vs.status = 'active'
    ", [$_SESSION['user_id']]);

    $vendorId = $staffInfo['vendor_id'] ?? null;
    $vendorName = $staffInfo['vendor_name'] ?? 'Kitchen';

    if (!$vendorId) {
        die('Staff member not assigned to any vendor');
    }
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'my_orders':
            try {
                $whereClause = $_SESSION['user_role'] === 'super_admin' ? '' : "WHERE o.vendor_id = $vendorId";
                
                // Get orders assigned to this staff member or pending orders
                $orders = fetchAll("
                    SELECT 
                        o.id,
                        o.order_number,
                        o.status,
                        o.total_amount,
                        o.created_at,
                        u.name as customer_name,
                        kw.workflow_status,
                        kw.priority,
                        kw.assigned_chef,
                        TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) as elapsed_time,
                        GROUP_CONCAT(
                            CONCAT(oi.quantity, 'x ', oi.product_name)
                            ORDER BY oi.id SEPARATOR ', '
                        ) as items
                    FROM orders o
                    JOIN users u ON o.customer_id = u.id
                    LEFT JOIN kitchen_workflows kw ON o.id = kw.order_id
                    LEFT JOIN order_items oi ON o.id = oi.order_id
                    $whereClause 
                    AND o.status IN ('confirmed', 'preparing', 'ready')
                    AND (kw.assigned_chef = {$_SESSION['user_id']} OR kw.assigned_chef IS NULL)
                    GROUP BY o.id, o.order_number, o.status, o.total_amount, o.created_at, 
                             u.name, kw.workflow_status, kw.priority, kw.assigned_chef
                    ORDER BY kw.priority DESC, o.created_at ASC
                    LIMIT 20
                ");
                
                echo json_encode(['orders' => $orders]);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'start_order':
            try {
                $orderId = (int)($_POST['order_id'] ?? 0);
                
                if (!$orderId) {
                    throw new Exception('Order ID is required');
                }
                
                // Update order status to preparing
                updateData('orders', [
                    'status' => 'preparing',
                    'updated_at' => date('Y-m-d H:i:s')
                ], "id = $orderId");
                
                // Update or create kitchen workflow
                $existingWorkflow = fetchRow("SELECT id FROM kitchen_workflows WHERE order_id = ?", [$orderId]);
                
                if ($existingWorkflow) {
                    updateData('kitchen_workflows', [
                        'workflow_status' => 'in_progress',
                        'assigned_chef' => $_SESSION['user_id'],
                        'actual_start_time' => date('Y-m-d H:i:s')
                    ], "order_id = $orderId");
                } else {
                    insertData('kitchen_workflows', [
                        'vendor_id' => $vendorId,
                        'order_id' => $orderId,
                        'workflow_status' => 'in_progress',
                        'assigned_chef' => $_SESSION['user_id'],
                        'actual_start_time' => date('Y-m-d H:i:s')
                    ]);
                }
                
                // Add to status history
                insertData('order_status_history', [
                    'order_id' => $orderId,
                    'status' => 'preparing',
                    'notes' => 'Started by kitchen staff',
                    'changed_by' => $_SESSION['user_id']
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Order started successfully']);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'complete_order':
            try {
                $orderId = (int)($_POST['order_id'] ?? 0);
                
                if (!$orderId) {
                    throw new Exception('Order ID is required');
                }
                
                // Update order status to ready
                updateData('orders', [
                    'status' => 'ready',
                    'updated_at' => date('Y-m-d H:i:s')
                ], "id = $orderId");
                
                // Update kitchen workflow
                updateData('kitchen_workflows', [
                    'workflow_status' => 'completed',
                    'actual_completion_time' => date('Y-m-d H:i:s')
                ], "order_id = $orderId");
                
                // Add to status history
                insertData('order_status_history', [
                    'order_id' => $orderId,
                    'status' => 'ready',
                    'notes' => 'Completed by kitchen staff',
                    'changed_by' => $_SESSION['user_id']
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Order completed successfully']);
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
    <title>Kitchen Staff - <?= htmlspecialchars($siteName) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #f97316;
            --ordivo-secondary: #fb923c;
            --ordivo-success: #28a745;
            --ordivo-warning: #ffc107;
            --ordivo-danger: #dc3545;
            --ordivo-info: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 0;
        }

        .header {
            background: #10b981;);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header .navbar-brand {
            font-size: 1.3rem;
            font-weight: 700;
            color: white !important;
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .header .navbar-brand img {
            height: 35px;
            margin-right: 10px;
        }

        .header .navbar-brand i {
            font-size: 1.5rem;
            margin-right: 10px;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px #e5e7eb;
            border-left: 4px solid var(--ordivo-primary);
            transition: all 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px #e5e7eb;
        }

        .order-card.urgent {
            border-left-color: var(--ordivo-danger);
        }

        .order-card.high {
            border-left-color: var(--ordivo-warning);
        }

        .order-card.preparing {
            border-left-color: var(--ordivo-info);
        }

        .order-card.ready {
            border-left-color: var(--ordivo-success);
        }

        .order-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .order-number {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--ordivo-primary);
        }

        .order-time {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .order-items {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .loading {
            text-align: center;
            padding: 3rem;
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

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .order-card {
                padding: 1rem;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <a class="navbar-brand" href="staff.php">
                    <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                        <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>">
                    <?php else: ?>
                        <i class="fas fa-utensils"></i>
                    <?php endif; ?>
                    Kitchen Staff - <?= htmlspecialchars($vendorName) ?>
                </a>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                        <div class="fw-bold"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                        <small class="opacity-75"><?= ucfirst($_SESSION['user_role']) ?></small>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <?php if ($_SESSION['user_role'] === 'kitchen_manager'): ?>
                                <li><a class="dropdown-item" href="dashboard.php">Manager Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="../customer/profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">
                        <i class="fas fa-clipboard-list text-primary me-2"></i>
                        My Orders
                    </h4>
                    <button class="btn btn-primary" onclick="loadMyOrders()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                </div>
                
                <div id="ordersContainer">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p class="mt-2">Loading your orders...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Load orders on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadMyOrders();
            
            // Auto-refresh every 30 seconds
            setInterval(loadMyOrders, 30000);
        });

        async function loadMyOrders() {
            try {
                const response = await fetch('?ajax=my_orders');
                const data = await response.json();
                
                if (data.error) {
                    console.error('Orders error:', data.error);
                    return;
                }
                
                renderOrders(data.orders);
                
            } catch (error) {
                console.error('Failed to load orders:', error);
            }
        }

        function renderOrders(orders) {
            const container = document.getElementById('ordersContainer');
            
            if (!orders || orders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <h5>No orders assigned</h5>
                        <p>You don't have any orders to prepare at the moment.</p>
                    </div>
                `;
                return;
            }
            
            const ordersHtml = orders.map(order => {
                const priorityClass = order.priority || 'normal';
                const statusClass = order.status;
                const elapsedTime = order.elapsed_time;
                const isAssignedToMe = order.assigned_chef == <?= $_SESSION['user_id'] ?>;
                
                let actionButtons = '';
                if (order.status === 'confirmed' && !isAssignedToMe) {
                    actionButtons = `
                        <button class="btn btn-success btn-action" onclick="startOrder(${order.id})">
                            <i class="fas fa-play me-2"></i>Start Cooking
                        </button>
                    `;
                } else if (order.status === 'preparing' && isAssignedToMe) {
                    actionButtons = `
                        <button class="btn btn-primary btn-action" onclick="completeOrder(${order.id})">
                            <i class="fas fa-check me-2"></i>Mark Ready
                        </button>
                    `;
                } else if (order.status === 'ready') {
                    actionButtons = `
                        <span class="badge bg-success fs-6">
                            <i class="fas fa-check-circle me-1"></i>Ready for Delivery
                        </span>
                    `;
                }
                
                return `
                    <div class="order-card ${priorityClass} ${statusClass}">
                        <div class="order-header">
                            <div>
                                <div class="order-number">Order #${order.order_number}</div>
                                <div class="order-time">
                                    <i class="fas fa-clock me-1"></i>${elapsedTime} minutes ago
                                    • <i class="fas fa-user me-1"></i>${order.customer_name}
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="mb-2">
                                    <span class="badge bg-${priorityClass === 'urgent' ? 'danger' : priorityClass === 'high' ? 'warning' : 'info'}">
                                        ${(priorityClass || 'normal').toUpperCase()}
                                    </span>
                                </div>
                                <div class="fw-bold text-success">৳${parseFloat(order.total_amount).toFixed(2)}</div>
                            </div>
                        </div>
                        
                        <div class="order-items">
                            <h6 class="mb-2">
                                <i class="fas fa-utensils me-2"></i>Items to Prepare:
                            </h6>
                            <p class="mb-0">${order.items}</p>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-${statusClass === 'confirmed' ? 'warning' : statusClass === 'preparing' ? 'info' : 'success'}">
                                    ${statusClass.replace('_', ' ').toUpperCase()}
                                </span>
                                ${isAssignedToMe ? '<small class="text-muted ms-2"><i class="fas fa-user-check me-1"></i>Assigned to you</small>' : ''}
                            </div>
                            <div>
                                ${actionButtons}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            container.innerHTML = ordersHtml;
        }

        async function startOrder(orderId) {
            if (!confirm('Are you sure you want to start preparing this order?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('order_id', orderId);
                
                const response = await fetch('?ajax=start_order', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.error) {
                    alert('Error: ' + result.error);
                } else {
                    showNotification('Order started successfully!', 'success');
                    loadMyOrders();
                }
            } catch (error) {
                console.error('Error starting order:', error);
                alert('Failed to start order');
            }
        }

        async function completeOrder(orderId) {
            if (!confirm('Are you sure this order is ready for delivery?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('order_id', orderId);
                
                const response = await fetch('?ajax=complete_order', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.error) {
                    alert('Error: ' + result.error);
                } else {
                    showNotification('Order completed successfully!', 'success');
                    loadMyOrders();
                }
            } catch (error) {
                console.error('Error completing order:', error);
                alert('Failed to complete order');
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