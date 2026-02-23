<?php
/**
 * ORDIVO - Order Success Page
 * Confirmation page after successful order placement
 */

require_once '../config/db_connection.php';

// Get order ID from URL
$orderId = (int)($_GET['order_id'] ?? 0);

if (!$orderId) {
    header('Location: index.php');
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

// Get order details
try {
    $order = fetchRow("
        SELECT o.*, u.name as vendor_name, c.name as customer_name, c.email as customer_email, c.phone as customer_phone
        FROM orders o 
        LEFT JOIN users u ON o.vendor_id = u.id 
        LEFT JOIN users c ON o.customer_id = c.id
        WHERE o.id = ?
    ", [$orderId]);
    
    if (!$order) {
        header('Location: index.php');
        exit;
    }
    
    // Decode delivery address JSON
    $deliveryAddressData = json_decode($order['delivery_address'], true);
    $order['delivery_name'] = $deliveryAddressData['name'] ?? $order['customer_name'];
    $order['delivery_phone'] = $deliveryAddressData['phone'] ?? $order['customer_phone'];
    $order['delivery_email'] = $deliveryAddressData['email'] ?? $order['customer_email'];
    $order['delivery_address_text'] = $deliveryAddressData['address'] ?? '';
    
    // Get order items
    $orderItems = fetchAll("
        SELECT oi.*, p.name as product_name 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ", [$orderId]);
    
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}

// Estimated delivery time
$estimatedTime = date('H:i', strtotime('+45 minutes'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - ORDIVO</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #f97316;
            --ordivo-secondary: #fb923c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }

        .header {
            background: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px #e5e7eb;
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

        .success-hero {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }

        .order-details {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-top: -3rem;
            box-shadow: 0 5px 15px #e5e7eb;
            position: relative;
            z-index: 10;
        }

        .status-timeline {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            position: relative;
        }

        .status-step {
            text-align: center;
            flex: 1;
            position: relative;
        }

        .status-step::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            right: -50%;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }

        .status-step:last-child::before {
            display: none;
        }

        .status-step.active::before {
            background: var(--ordivo-primary);
        }

        .status-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            position: relative;
            z-index: 2;
        }

        .status-step.active .status-icon {
            background: var(--ordivo-primary);
            color: white;
        }

        .btn-primary {
            background: var(--ordivo-primary);
            border: none;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
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
                
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </header>

    <!-- Success Hero -->
    <div class="success-hero">
        <div class="container">
            <div class="success-icon">
                <i class="fas fa-check fa-2x"></i>
            </div>
            <h1 class="display-4 mb-3">Order Confirmed!</h1>
            <p class="lead mb-0">Thank you for your order. We're preparing your delicious meal!</p>
        </div>
    </div>

    <div class="container">
        <!-- Order Details -->
        <div class="order-details">
            <div class="row">
                <div class="col-md-8">
                    <h3 class="mb-3">Order Details</h3>
                    
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <strong>Order ID:</strong> #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div class="col-sm-6">
                            <strong>Restaurant:</strong> <?= htmlspecialchars($order['vendor_name']) ?>
                        </div>
                        <div class="col-sm-6 mt-2">
                            <strong>Order Time:</strong> <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
                        </div>
                        <div class="col-sm-6 mt-2">
                            <strong>Estimated Delivery:</strong> <?= $estimatedTime ?>
                        </div>
                    </div>

                    <!-- Status Timeline -->
                    <div class="status-timeline">
                        <div class="status-step active">
                            <div class="status-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <small>Order Placed</small>
                        </div>
                        <div class="status-step">
                            <div class="status-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <small>Preparing</small>
                        </div>
                        <div class="status-step">
                            <div class="status-icon">
                                <i class="fas fa-motorcycle"></i>
                            </div>
                            <small>On the Way</small>
                        </div>
                        <div class="status-step">
                            <div class="status-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <small>Delivered</small>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <h5 class="mb-3">Items Ordered</h5>
                    <?php foreach ($orderItems as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                <small class="text-muted d-block">Qty: <?= $item['quantity'] ?> × ৳<?= number_format($item['unit_price'], 0) ?></small>
                            </div>
                            <strong>৳<?= number_format($item['total_price'], 0) ?></strong>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span>Delivery Fee:</span>
                        <span><?= $order['delivery_fee'] == 0 ? 'FREE' : '৳' . number_format($order['delivery_fee'], 0) ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center py-2 border-top">
                        <strong>Total:</strong>
                        <strong class="text-primary">৳<?= number_format($order['total_amount'], 0) ?></strong>
                    </div>
                </div>

                <!-- Delivery Info -->
                <div class="col-md-4">
                    <div class="bg-light rounded p-3">
                        <h5 class="mb-3">Delivery Information</h5>
                        
                        <div class="mb-3">
                            <strong>Customer:</strong><br>
                            <?= htmlspecialchars($order['delivery_name']) ?><br>
                            <?= htmlspecialchars($order['delivery_phone']) ?>
                            <?php if ($order['delivery_email']): ?>
                                <br><?= htmlspecialchars($order['delivery_email']) ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Delivery Address:</strong><br>
                            <?= nl2br(htmlspecialchars($order['delivery_address_text'])) ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Payment Method:</strong><br>
                            <?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?>
                        </div>
                        
                        <?php if ($order['special_instructions']): ?>
                            <div class="mb-3">
                                <strong>Special Instructions:</strong><br>
                                <?= nl2br(htmlspecialchars($order['special_instructions'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-utensils me-2"></i>Order Again
                </a>
                <a href="orders.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-list me-2"></i>View All Orders
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Clear cart and checkout data from localStorage on success page
        localStorage.removeItem('checkout_cart_data');
        localStorage.removeItem('checkout_payment_method');
        localStorage.removeItem('ordivo_cart');
        
        console.log('Order successful - cart cleared from localStorage');
        
        // Auto-refresh page every 30 seconds to simulate order status updates
        setTimeout(function() {
            // In a real app, this would check for status updates via AJAX
            console.log('Checking for order updates...');
        }, 30000);
    </script>
</body>
</html>