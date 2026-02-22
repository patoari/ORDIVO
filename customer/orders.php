<?php
/**
 * ORDIVO - Customer Orders History
 * Display customer's order history and status
 */

require_once '../config/db_connection.php';

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

// For demo purposes, we'll show all orders
// In a real app, you'd filter by logged-in customer
try {
    $orders = fetchAll("
        SELECT o.*, u.business_name as vendor_name 
        FROM orders o 
        LEFT JOIN users u ON o.vendor_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 20
    ");
} catch (Exception $e) {
    $orders = [];
}

// Status colors and icons
$statusConfig = [
    'pending' => ['color' => 'warning', 'icon' => 'clock', 'text' => 'Order Placed'],
    'confirmed' => ['color' => 'info', 'icon' => 'check-circle', 'text' => 'Confirmed'],
    'preparing' => ['color' => 'primary', 'icon' => 'utensils', 'text' => 'Preparing'],
    'on_way' => ['color' => 'secondary', 'icon' => 'motorcycle', 'text' => 'On the Way'],
    'delivered' => ['color' => 'success', 'icon' => 'check', 'text' => 'Delivered'],
    'cancelled' => ['color' => 'danger', 'icon' => 'times', 'text' => 'Cancelled']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ORDIVO</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }

        .header {
            background: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* Logo Animations */
        .logo-img {
            height: 100px;
            width: auto;
            margin-right: 12px;
            object-fit: contain;
            animation: logoFloat 3s ease-in-out infinite;
            transition: all 0.3s ease;
        }

        .logo-img:hover {
            transform: scale(1.15) rotate(5deg) !important;
        }

        @keyframes logoFloat {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            25% {
                transform: translateY(-3px) rotate(1deg);
            }
            50% {
                transform: translateY(-5px) rotate(0deg);
            }
            75% {
                transform: translateY(-3px) rotate(-1deg);
            }
        }

        .logo-icon {
            color: var(--ordivo-primary);
            font-size: 2rem !important;
            animation: logoPulse 2s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        .brand-text {
            color: var(--ordivo-primary);
            font-size: 1.8rem;
            font-weight: 700;
            animation: brandGlow 4s ease-in-out infinite;
        }

        @keyframes brandGlow {
            0%, 100% {
                
            }
            50% {
                
            }
        }

        .orders-header {
            background: #10b981;);
            color: white;
            padding: 2rem 0;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px #e5e7eb;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px #e5e7eb;
        }

        .order-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .order-id {
            font-weight: 600;
            color: var(--ordivo-primary);
        }

        .order-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .btn-primary {
            background: var(--ordivo-primary);
            border: none;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
        }

        .empty-orders {
            text-align: center;
            padding: 4rem 2rem;
        }

        .order-timeline {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1rem 0;
        }

        .timeline-step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: #f8f9fa;
            font-size: 0.85rem;
        }

        .timeline-step.active {
            background: var(--ordivo-primary);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="text-decoration-none">
                    <div class="d-flex align-items-center">
                        <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="logo-img">
                        <?php else: ?>
                            <i class="fas fa-utensils logo-icon"></i>
                        <?php endif; ?>
                    </div>
                </a>
                
                <div class="d-flex align-items-center gap-3">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-home me-2"></i>Home
                    </a>
                    <a href="cart.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Cart
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Orders Header -->
    <div class="orders-header">
        <div class="container">
            <h1 class="display-5 mb-0">
                <i class="fas fa-list-alt me-3"></i>My Orders
            </h1>
        </div>
    </div>

    <div class="container my-4">
        <?php if (empty($orders)): ?>
            <!-- Empty Orders -->
            <div class="empty-orders">
                <i class="fas fa-receipt fa-4x text-muted mb-4"></i>
                <h3 class="text-muted mb-3">No orders yet</h3>
                <p class="text-muted mb-4">You haven't placed any orders yet. Start exploring delicious food!</p>
                <a href="index.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-utensils me-2"></i>Start Ordering
                </a>
            </div>
        <?php else: ?>
            <!-- Orders List -->
            <div class="row">
                <div class="col-12">
                    <h4 class="mb-4">Order History (<?= count($orders) ?> orders)</h4>
                    
                    <?php foreach ($orders as $order): ?>
                        <?php 
                        $status = $statusConfig[$order['status']] ?? $statusConfig['pending'];
                        $orderDate = date('M j, Y g:i A', strtotime($order['created_at']));
                        ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="d-flex justify-content-between align-items-center w-100">
                                    <div>
                                        <div class="order-id">Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></div>
                                        <small class="text-muted"><?= $orderDate ?></small>
                                    </div>
                                    <span class="order-status bg-<?= $status['color'] ?> text-white">
                                        <i class="fas fa-<?= $status['icon'] ?> me-1"></i>
                                        <?= $status['text'] ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-2">
                                        <strong>Restaurant:</strong> <?= htmlspecialchars($order['vendor_name'] ?? 'Unknown Restaurant') ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Delivery Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Payment:</strong> <?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?>
                                    </div>
                                    
                                    <?php if ($order['notes']): ?>
                                        <div class="mb-2">
                                            <strong>Notes:</strong> <?= htmlspecialchars($order['notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="text-end">
                                        <div class="mb-2">
                                            <small class="text-muted">Total Amount</small>
                                            <div class="h4 text-primary mb-0">৳<?= number_format($order['total_amount'], 0) ?></div>
                                        </div>
                                        
                                        <?php if ($order['delivery_fee'] > 0): ?>
                                            <small class="text-muted">Delivery: ৳<?= number_format($order['delivery_fee'], 0) ?></small>
                                        <?php else: ?>
                                            <small class="text-success">Free Delivery</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Order Actions -->
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <div class="timeline-step <?= $order['status'] !== 'cancelled' ? 'active' : '' ?>">
                                    <i class="fas fa-<?= $status['icon'] ?>"></i>
                                    <span><?= $status['text'] ?></span>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <a href="order_success.php?order_id=<?= $order['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                    
                                    <?php if ($order['status'] === 'delivered'): ?>
                                        <button class="btn btn-primary btn-sm" onclick="reorder(<?= $order['id'] ?>)">
                                            <i class="fas fa-redo me-1"></i>Reorder
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                        <button class="btn btn-outline-danger btn-sm" onclick="cancelOrder(<?= $order['id'] ?>)">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function reorder(orderId) {
            if (confirm('Add all items from this order to your cart?')) {
                // In a real app, this would add order items to cart
                alert('Items added to cart! (Demo functionality)');
                window.location.href = 'cart.php';
            }
        }
        
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                // In a real app, this would update order status
                alert('Order cancelled! (Demo functionality)');
                location.reload();
            }
        }
        
        // Auto-refresh every 30 seconds to check for status updates
        setInterval(function() {
            // In a real app, this would check for order status updates
            console.log('Checking for order updates...');
        }, 30000);
    </script>
</body>
</html>