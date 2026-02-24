<?php
/**
 * ORDIVO - Checkout Page
 * Complete order placement with delivery details
 */

require_once '../config/db_connection.php';
require_once '../config/payment_config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in - if not, redirect to login
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    // Store current URL to redirect back after login
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: ../auth/login.php?redirect=checkout');
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

// Get user wallet balance if logged in
$walletBalance = 0;
$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    $wallet = fetchRow("SELECT balance FROM wallets WHERE user_id = ? AND is_active = 1", [$userId]);
    $walletBalance = $wallet['balance'] ?? 0;
}

// Handle order placement
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $customerName = sanitizeInput($_POST['customer_name'] ?? '');
    $customerPhone = sanitizeInput($_POST['customer_phone'] ?? '');
    $customerEmail = sanitizeInput($_POST['customer_email'] ?? '');
    $deliveryAddress = sanitizeInput($_POST['delivery_address'] ?? '');
    $paymentMethod = sanitizeInput($_POST['payment_method'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $cartData = json_decode($_POST['cart_data'] ?? '[]', true);
    
    // Validation
    if (empty($customerName) || empty($customerPhone) || empty($deliveryAddress) || empty($paymentMethod)) {
        $error = 'Please fill in all required fields.';
    } elseif (empty($cartData)) {
        $error = 'Your cart is empty.';
    } else {
        try {
            // Calculate totals
            $subtotal = (float)($_POST['subtotal'] ?? 0);
            $deliveryFee = (float)($_POST['delivery_fee'] ?? 0);
            $totalAmount = $subtotal + $deliveryFee;
            
            // Get vendor ID from first product
            $firstProduct = fetchRow("SELECT vendor_id FROM products WHERE id = ?", [$cartData[0]['id']]);
            $vendorId = $firstProduct['vendor_id'] ?? 1;
            
            // Generate order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Prepare delivery address JSON
            $deliveryAddressJson = json_encode([
                'name' => $customerName,
                'phone' => $customerPhone,
                'email' => $customerEmail,
                'address' => $deliveryAddress
            ]);
            
            // For wallet payment, check balance
            if ($paymentMethod === 'wallet') {
                if (!$userId) {
                    $error = 'Please login to use wallet payment.';
                } elseif ($walletBalance < $totalAmount) {
                    $error = 'Insufficient wallet balance. Please recharge your wallet or choose another payment method.';
                } else {
                    // Process wallet payment
                    // Insert order
                    $orderId = insertData('orders', [
                        'order_number' => $orderNumber,
                        'customer_id' => $userId,
                        'vendor_id' => $vendorId,
                        'delivery_address' => $deliveryAddressJson,
                        'subtotal' => $subtotal,
                        'delivery_fee' => $deliveryFee,
                        'total_amount' => $totalAmount,
                        'payment_method' => $paymentMethod,
                        'payment_status' => 'paid',
                        'status' => 'pending',
                        'special_instructions' => $notes
                    ]);
                    
                    if ($orderId) {
                        // Insert order items
                        foreach ($cartData as $item) {
                            $product = fetchRow("SELECT name FROM products WHERE id = ?", [$item['id']]);
                            insertData('order_items', [
                                'order_id' => $orderId,
                                'product_id' => $item['id'],
                                'product_name' => $product['name'],
                                'quantity' => $item['quantity'],
                                'unit_price' => $item['price'],
                                'total_price' => $item['price'] * $item['quantity']
                            ]);
                        }
                        
                        // Deduct from wallet
                        $walletRecord = fetchRow("SELECT id, total_spent FROM wallets WHERE user_id = ?", [$userId]);
                        $walletId = $walletRecord['id'];
                        $currentTotalSpent = (float)($walletRecord['total_spent'] ?? 0);
                        $newBalance = $walletBalance - $totalAmount;
                        $newTotalSpent = $currentTotalSpent + $totalAmount;
                        
                        updateData('wallets', [
                            'balance' => $newBalance,
                            'total_spent' => $newTotalSpent
                        ], 'id = ?', [$walletId]);
                        
                        // Record wallet transaction
                        insertData('wallet_transactions', [
                            'wallet_id' => $walletId,
                            'transaction_type' => 'debit',
                            'amount' => $totalAmount,
                            'balance_before' => $walletBalance,
                            'balance_after' => $newBalance,
                            'reference_type' => 'order',
                            'reference_id' => $orderId,
                            'description' => 'Order payment #' . $orderNumber,
                            'payment_method' => 'wallet',
                            'status' => 'completed',
                            'processed_at' => date('Y-m-d H:i:s')
                        ]);
                        
                        // Redirect to success page
                        header("Location: order_success.php?order_id=$orderId");
                        exit;
                    }
                }
            } elseif ($paymentMethod === 'cash_on_delivery') {
                // Insert order for COD
                $orderId = insertData('orders', [
                    'order_number' => $orderNumber,
                    'customer_id' => $userId,
                    'vendor_id' => $vendorId,
                    'delivery_address' => $deliveryAddressJson,
                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'total_amount' => $totalAmount,
                    'payment_method' => 'cash',
                    'payment_status' => 'pending',
                    'status' => 'pending',
                    'special_instructions' => $notes
                ]);
                
                if ($orderId) {
                    // Insert order items
                    foreach ($cartData as $item) {
                        $product = fetchRow("SELECT name FROM products WHERE id = ?", [$item['id']]);
                        insertData('order_items', [
                            'order_id' => $orderId,
                            'product_id' => $item['id'],
                            'product_name' => $product['name'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['price'],
                            'total_price' => $item['price'] * $item['quantity']
                        ]);
                    }
                    
                    // Redirect to success page
                    header("Location: order_success.php?order_id=$orderId");
                    exit;
                }
            } else {
                // For online payment methods (bKash, Nagad, Rocket, Upay, Card)
                // Insert order first
                $orderId = insertData('orders', [
                    'order_number' => $orderNumber,
                    'customer_id' => $userId,
                    'vendor_id' => $vendorId,
                    'delivery_address' => $deliveryAddressJson,
                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'total_amount' => $totalAmount,
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'pending',
                    'status' => 'pending',
                    'special_instructions' => $notes
                ]);
                
                if ($orderId) {
                    // Insert order items
                    foreach ($cartData as $item) {
                        $product = fetchRow("SELECT name FROM products WHERE id = ?", [$item['id']]);
                        insertData('order_items', [
                            'order_id' => $orderId,
                            'product_id' => $item['id'],
                            'product_name' => $product['name'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['price'],
                            'total_price' => $item['price'] * $item['quantity']
                        ]);
                    }
                    
                    // Redirect to payment processing page
                    header("Location: process_payment.php?order_id=$orderId&method=$paymentMethod");
                    exit;
                }
            }
            
            if (!isset($orderId) || !$orderId) {
                $error = 'Failed to place order. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'Error placing order: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ORDIVO</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Logo Animations CSS -->
    <link href="../assets/logo-animations.css" rel="stylesheet">
    <!-- Homepage CSS (includes footer styles) -->
    <link href="../assets/css/homepage.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            padding-top: 160px; /* Header (100px) + Nav tabs (60px) */
        }

        /* Hide navigation tabs on checkout page */
        .nav-tabs-container {
            display: none !important;
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
            animation: logoFloat 3s ease-in-out infinite, logoColorShift 6s ease-in-out infinite;
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
                transform: translateY(-4px) rotate(1deg);
            }
            50% {
                transform: translateY(-6px) rotate(0deg);
            }
            75% {
                transform: translateY(-4px) rotate(-1deg);
            }
        }

        @keyframes logoColorShift {
            0%, 100% {
                
            }
            25% {
                
            }
            50% {
                
            }
            75% {
                
            }
        }

        .logo-icon {
            color: var(--ordivo-primary);
            font-size: 2.5rem !important; /* Increased size */
            animation: logoPulse 2s ease-in-out infinite, logoColorShift 6s ease-in-out infinite;
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

        .checkout-header {
            background: #10b981;
            color: white;
            padding: 2rem 0;
        }

        .checkout-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px #e5e7eb;
        }

        .btn-primary {
            background: var(--ordivo-primary);
            border: none;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
        }

        .order-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .payment-option {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            border-color: var(--ordivo-primary);
        }

        .payment-option.selected {
            border-color: var(--ordivo-primary);
            background: #f0fdf4;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            body {
                padding-top: 114px; /* Header only (114px) - no nav tabs */
            }

            .checkout-header {
                padding: 1.5rem 0;
            }

            .checkout-header h1,
            .checkout-header .display-5 {
                font-size: 1.5rem !important;
            }

            .checkout-section {
                padding: 1rem;
                margin-bottom: 1rem;
                border-radius: 8px;
            }

            .checkout-section h4 {
                font-size: 1.1rem;
                margin-bottom: 1rem;
            }

            .checkout-section h5 {
                font-size: 1rem;
            }

            .form-label {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }

            .form-control,
            .form-select {
                font-size: 0.9rem;
                padding: 0.5rem 0.75rem;
            }

            textarea.form-control {
                min-height: 80px;
            }

            .order-item {
                padding: 0.75rem 0;
            }

            .order-item h6 {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }

            .order-item .text-muted {
                font-size: 0.8rem;
            }

            .order-item .fw-bold {
                font-size: 0.95rem;
            }

            .payment-option {
                padding: 0.75rem;
                margin-bottom: 0.75rem;
            }

            .payment-option h6 {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }

            .payment-option small,
            .payment-option .text-muted {
                font-size: 0.75rem;
            }

            .payment-option i {
                font-size: 1.2rem;
            }

            .btn-primary,
            .btn-success {
                padding: 0.75rem 1rem;
                font-size: 0.95rem;
            }

            .btn-lg {
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
            }

            /* Order summary on mobile */
            .order-summary {
                position: relative;
                margin-top: 1rem;
            }

            .order-summary .h5 {
                font-size: 1rem;
            }

            .order-summary .h4 {
                font-size: 1.2rem;
            }

            /* Stack form columns on mobile */
            .row .col-md-6 {
                margin-bottom: 0.75rem;
            }

            /* Alert messages */
            .alert {
                font-size: 0.9rem;
                padding: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .checkout-header h1,
            .checkout-header .display-5 {
                font-size: 1.3rem !important;
            }

            .checkout-section {
                padding: 0.75rem;
            }

            .checkout-section h4 {
                font-size: 1rem;
            }

            .order-item h6 {
                font-size: 0.85rem;
            }

            .payment-option {
                padding: 0.6rem;
            }

            .btn-lg {
                padding: 0.65rem 1.25rem;
                font-size: 0.95rem;
            }
        }
        
        /* Hide any injected filter elements */
        [class*="filter"],
        [id*="filter"],
        [class*="Filter"],
        [id*="Filter"] {
            display: none !important;
        }
        
        /* But show our payment options */
        .payment-option,
        .payment-options {
            display: block !important;
        }
    </style>
</head>
<body>
    <?php 
    $userLocation = $_SESSION['user_location'] ?? 'Dhaka, Bangladesh';
    include 'includes/header_with_nav.php'; 
    ?>

    <!-- Checkout Header -->
    <div class="checkout-header">
        <div class="container">
            <h1 class="display-5 mb-0">
                <i class="fas fa-credit-card me-3"></i>Checkout
            </h1>
        </div>
    </div>

    <div class="container my-4">
        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Loading State -->
        <div class="text-center py-5" id="loadingState">
            <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
            <p class="text-muted">Loading checkout...</p>
        </div>

        <!-- Checkout Form -->
        <form method="POST" id="checkoutForm" class="d-none">
            <div class="row">
                <!-- Checkout Form -->
                <div class="col-lg-8">
                    <!-- Delivery Information -->
                    <div class="checkout-section">
                        <h4 class="mb-3">
                            <i class="fas fa-map-marker-alt text-primary me-2"></i>Delivery Information
                        </h4>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="customer_phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="customer_phone" name="customer_phone" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customer_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email">
                        </div>
                        
                        <div class="mb-3">
                            <label for="delivery_address" class="form-label">Delivery Address *</label>
                            
                            <!-- Use Current Location Button -->
                            <button type="button" class="btn btn-outline-success w-100 mb-2" id="useLocationBtn" onclick="detectLocation()">
                                <i class="fas fa-crosshairs me-2"></i>
                                <span id="locationBtnText">Use Current Location</span>
                            </button>
                            
                            <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" required placeholder="Enter your complete delivery address"></textarea>
                            <small class="text-muted">Click "Use Current Location" to auto-fill your address</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Special Instructions</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any special instructions for delivery or preparation"></textarea>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-section">
                        <h4 class="mb-3">
                            <i class="fas fa-credit-card text-primary me-2"></i>Payment Method
                        </h4>
                        
                        <div class="payment-options">
                            <!-- Cash on Delivery -->
                            <div class="payment-option" onclick="selectPayment('cash_on_delivery')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="cash_on_delivery" id="cash_on_delivery" class="me-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Cash on Delivery</h6>
                                        <small class="text-muted">Pay when you receive your order</small>
                                    </div>
                                    <i class="fas fa-money-bill-wave ms-auto text-success fa-2x"></i>
                                </div>
                            </div>
                            
                            <!-- bKash -->
                            <div class="payment-option" onclick="selectPayment('bkash')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="bkash" id="bkash" class="me-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">bKash</h6>
                                        <small class="text-muted">Pay securely with bKash</small>
                                    </div>
                                    <div class="payment-logo ms-auto">
                                        <span class="badge bg-danger fs-6">bKash</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Nagad -->
                            <div class="payment-option" onclick="selectPayment('nagad')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="nagad" id="nagad" class="me-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Nagad</h6>
                                        <small class="text-muted">Pay securely with Nagad</small>
                                    </div>
                                    <div class="payment-logo ms-auto">
                                        <span class="badge bg-warning text-dark fs-6">Nagad</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Rocket -->
                            <div class="payment-option" onclick="selectPayment('rocket')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="rocket" id="rocket" class="me-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Rocket</h6>
                                        <small class="text-muted">Pay securely with Rocket</small>
                                    </div>
                                    <div class="payment-logo ms-auto">
                                        <span class="badge bg-info fs-6">Rocket</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Upay -->
                            <div class="payment-option" onclick="selectPayment('upay')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="upay" id="upay" class="me-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Upay</h6>
                                        <small class="text-muted">Pay securely with Upay</small>
                                    </div>
                                    <div class="payment-logo ms-auto">
                                        <span class="badge bg-primary fs-6">Upay</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Credit/Debit Card -->
                            <div class="payment-option" onclick="selectPayment('card')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="card" id="card" class="me-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Credit/Debit Card</h6>
                                        <small class="text-muted">Visa, Mastercard, DBBL</small>
                                    </div>
                                    <i class="fas fa-credit-card ms-auto text-primary fa-2x"></i>
                                </div>
                            </div>
                            
                            <!-- ORDIVO Wallet -->
                            <?php if ($userId): ?>
                            <div class="payment-option" onclick="selectPayment('wallet')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="wallet" id="wallet" class="me-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">ORDIVO Wallet</h6>
                                        <small class="text-muted">Balance: ৳<?= number_format($walletBalance, 2) ?></small>
                                    </div>
                                    <i class="fas fa-wallet ms-auto text-success fa-2x"></i>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="checkout-section">
                        <h4 class="mb-3">Order Summary</h4>
                        
                        <!-- Order Items -->
                        <div id="orderItemsList">
                            <!-- Items will be loaded here -->
                        </div>
                        
                        <hr>
                        
                        <!-- Totals -->
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="checkoutSubtotal">৳0</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Delivery Fee:</span>
                            <span id="checkoutDeliveryFee">৳50</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total:</strong>
                            <strong class="text-primary" id="checkoutTotal">৳50</strong>
                        </div>
                        
                        <!-- Hidden fields for form submission -->
                        <input type="hidden" name="cart_data" id="cartDataInput">
                        <input type="hidden" name="subtotal" id="subtotalInput">
                        <input type="hidden" name="delivery_fee" id="deliveryFeeInput">
                        <input type="hidden" name="place_order" value="1">
                        
                        <button type="submit" class="btn btn-primary w-100 btn-lg">
                            <i class="fas fa-check me-2"></i>Place Order
                        </button>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Your order is secure and encrypted
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Location Tracker -->
    <?php include 'includes/modals.php'; ?>
    <script src="../assets/js/location-tracker.js"></script>
    
    <script>
        let checkoutData = null;
        let selectedPaymentMethod = null;
        const walletBalance = <?= $walletBalance ?>;
        const isLoggedIn = <?= $userId ? 'true' : 'false' ?>;

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Checkout page loaded');
            loadCheckoutData();
            loadSavedLocation();
        });

        // Load saved location on page load
        function loadSavedLocation() {
            const savedLocation = localStorage.getItem('user_location');
            if (savedLocation) {
                try {
                    const location = JSON.parse(savedLocation);
                    const timestamp = location.timestamp;
                    const now = Date.now();
                    const hoursSinceUpdate = (now - timestamp) / (1000 * 60 * 60);
                    
                    // If location is less than 24 hours old, use it
                    if (hoursSinceUpdate < 24 && location.address) {
                        document.getElementById('delivery_address').value = location.address;
                    }
                } catch (e) {
                    console.error('Error loading saved location:', e);
                }
            }
        }

        // Detect current location
        function detectLocation() {
            const btn = document.getElementById('useLocationBtn');
            const btnText = document.getElementById('locationBtnText');
            const originalText = btnText.innerHTML;
            
            // Show loading state
            btn.disabled = true;
            btnText.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Detecting location...';
            
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser');
                btn.disabled = false;
                btnText.innerHTML = originalText;
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                async function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    
                    btnText.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Getting address...';
                    
                    try {
                        // Reverse geocoding using OpenStreetMap Nominatim
                        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`);
                        const data = await response.json();
                        
                        if (data && data.display_name) {
                            const address = data.display_name;
                            
                            // Update delivery address field
                            document.getElementById('delivery_address').value = address;
                            
                            // Save to localStorage
                            const locationData = {
                                latitude: lat,
                                longitude: lon,
                                address: address,
                                timestamp: Date.now()
                            };
                            localStorage.setItem('user_location', JSON.stringify(locationData));
                            
                            // Update button to show success
                            btn.classList.remove('btn-outline-success');
                            btn.classList.add('btn-success');
                            btnText.innerHTML = '<i class="fas fa-check-circle me-2"></i>Location detected';
                            
                            setTimeout(() => {
                                btn.classList.remove('btn-success');
                                btn.classList.add('btn-outline-success');
                                btnText.innerHTML = originalText;
                                btn.disabled = false;
                            }, 2000);
                        } else {
                            throw new Error('Could not get address');
                        }
                    } catch (error) {
                        console.error('Geocoding error:', error);
                        alert('Could not get your address. Please enter it manually.');
                        btn.disabled = false;
                        btnText.innerHTML = originalText;
                    }
                },
                function(error) {
                    console.error('Geolocation error:', error);
                    let errorMessage = 'Could not get your location. ';
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage += 'Please allow location access.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage += 'Location information unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage += 'Location request timed out.';
                            break;
                        default:
                            errorMessage += 'An unknown error occurred.';
                    }
                    
                    alert(errorMessage);
                    btn.disabled = false;
                    btnText.innerHTML = originalText;
                }
            );
        }

        function loadCheckoutData() {
            console.log('Loading checkout data...');
            
            // Get cart data from localStorage
            const cartDataStr = localStorage.getItem('checkout_cart_data');
            console.log('Cart data from localStorage:', cartDataStr);
            
            const cartData = JSON.parse(cartDataStr || 'null');
            const paymentMethod = localStorage.getItem('checkout_payment_method');
            
            console.log('Parsed cart data:', cartData);
            console.log('Payment method:', paymentMethod);

            if (!cartData || !cartData.items || cartData.items.length === 0) {
                console.log('No cart data found, redirecting to cart');
                // Redirect to cart if no data
                alert('No checkout data found. Please add items to cart first.');
                window.location.href = 'cart.php';
                return;
            }

            checkoutData = cartData;
            selectedPaymentMethod = paymentMethod;

            console.log('Rendering order items...');
            // Render order items
            renderOrderItems(cartData.items);
            
            console.log('Updating totals...');
            // Update totals
            updateTotals(cartData);
            
            // Set payment method
            if (paymentMethod) {
                selectPayment(paymentMethod);
            } else {
                selectPayment('cash_on_delivery'); // Default
            }

            console.log('Showing form...');
            // Show form, hide loading
            document.getElementById('loadingState').classList.add('d-none');
            document.getElementById('checkoutForm').classList.remove('d-none');
        }

        function renderOrderItems(items) {
            const container = document.getElementById('orderItemsList');
            
            const itemsHtml = items.map(item => `
                <div class="order-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${item.product.name}</h6>
                            <small class="text-muted">${item.product.vendor_name || 'Restaurant'}</small>
                            <div class="text-muted small">Qty: ${item.quantity} × ৳${parseFloat(item.product.price).toFixed(0)}</div>
                        </div>
                        <div class="text-end">
                            <strong>৳${parseFloat(item.subtotal).toFixed(0)}</strong>
                        </div>
                    </div>
                </div>
            `).join('');

            container.innerHTML = itemsHtml;
        }

        function updateTotals(data) {
            document.getElementById('checkoutSubtotal').textContent = `৳${parseFloat(data.subtotal).toFixed(0)}`;
            document.getElementById('checkoutDeliveryFee').textContent = data.delivery_fee === 0 ? 'FREE' : `৳${parseFloat(data.delivery_fee).toFixed(0)}`;
            document.getElementById('checkoutTotal').textContent = `৳${parseFloat(data.total).toFixed(0)}`;

            // Set hidden form fields
            document.getElementById('cartDataInput').value = JSON.stringify(checkoutData.items.map(item => ({
                id: item.product.id,
                quantity: item.quantity,
                price: item.product.price
            })));
            document.getElementById('subtotalInput').value = data.subtotal;
            document.getElementById('deliveryFeeInput').value = data.delivery_fee;
        }

        function selectPayment(method) {
            // Check wallet balance for wallet payment
            if (method === 'wallet') {
                if (!isLoggedIn) {
                    alert('Please login to use wallet payment');
                    return;
                }
                
                const total = checkoutData.total;
                if (walletBalance < total) {
                    alert(`Insufficient wallet balance. Your balance: ৳${walletBalance.toFixed(2)}, Required: ৳${total.toFixed(2)}`);
                    return;
                }
            }
            
            // Remove selected class from all options
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            const clickedOption = document.querySelector(`#${method}`);
            if (clickedOption) {
                const paymentOption = clickedOption.closest('.payment-option');
                if (paymentOption) {
                    paymentOption.classList.add('selected');
                }
            }
            
            // Check the radio button
            const radioButton = document.getElementById(method);
            if (radioButton) {
                radioButton.checked = true;
                selectedPaymentMethod = method;
            }
        }

        // Form submission handler
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            if (!selectedPaymentMethod) {
                e.preventDefault();
                alert('Please select a payment method');
                return false;
            }

            // For wallet payment, double-check balance
            if (selectedPaymentMethod === 'wallet') {
                const total = checkoutData.total;
                if (walletBalance < total) {
                    e.preventDefault();
                    alert(`Insufficient wallet balance. Your balance: ৳${walletBalance.toFixed(2)}, Required: ৳${total.toFixed(2)}`);
                    return false;
                }
            }

            // Don't clear localStorage here - let the server-side redirect handle it
            // The localStorage will be cleared after successful order placement
            console.log('Form submitting with payment method:', selectedPaymentMethod);
        });
    </script>

    <?php include 'includes/modals.php'; ?>
    <?php include 'includes/footer.php'; ?>
</body>
</html>