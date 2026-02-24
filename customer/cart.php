<?php
/**
 * ORDIVO - Shopping Cart
 * Display cart items and checkout functionality
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

// Handle AJAX requests for cart operations
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'get_cart_items':
            $cartData = json_decode($_POST['cart_data'] ?? '[]', true);
            $cartItems = [];
            $totalAmount = 0;
            
            if (!empty($cartData)) {
                $productIds = array_column($cartData, 'id');
                $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
                
                try {
                    $products = fetchAll("
                        SELECT p.*, u.name as vendor_name 
                        FROM products p 
                        INNER JOIN users u ON p.vendor_id = u.id AND u.role = 'vendor' AND u.status = 'active'
                        WHERE p.id IN ($placeholders)
                    ", $productIds);
                    
                    foreach ($products as $product) {
                        $cartItem = array_filter($cartData, fn($item) => $item['id'] == $product['id']);
                        $cartItem = reset($cartItem);
                        $quantity = $cartItem['quantity'] ?? 1;
                        $subtotal = $product['price'] * $quantity;
                        $totalAmount += $subtotal;
                        
                        $cartItems[] = [
                            'product' => $product,
                            'quantity' => $quantity,
                            'subtotal' => $subtotal
                        ];
                    }
                } catch (Exception $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                    exit;
                }
            }
            
            $deliveryFee = $totalAmount > 500 ? 0 : 50;
            $finalTotal = $totalAmount + $deliveryFee;
            
            echo json_encode([
                'items' => $cartItems,
                'subtotal' => $totalAmount,
                'delivery_fee' => $deliveryFee,
                'total' => $finalTotal
            ]);
            exit;
    }
}

// Initialize empty cart data for server-side rendering
$cartItems = [];
$totalAmount = 0;
$deliveryFee = 50;
$finalTotal = 50;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ORDIVO</title>
    
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
            --ordivo-accent: #f97316;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
            margin: 0;
            padding-top: 160px; /* Header (100px) + Nav tabs (60px) */
        }

        /* Hide navigation tabs on cart page */
        .nav-tabs-container {
            display: none !important;
        }
            text-decoration: none;
        }

        .navbar-brand img {
            height: 100px;
            width: auto;
            margin-right: 12px;
            object-fit: contain;
            animation: logoFloat 3s ease-in-out infinite, logoColorShift 6s ease-in-out infinite;
            transition: all 0.3s ease;
        }

        .navbar-brand img:hover {
            transform: scale(1.15) rotate(5deg);
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-4px) rotate(1deg); }
            50% { transform: translateY(-6px) rotate(0deg); }
            75% { transform: translateY(-4px) rotate(-1deg); }
        }

        @keyframes logoColorShift {
            0%, 100% {  }
            25% {  }
            50% {  }
            75% {  }
        }

        .navbar-brand i.fa-shopping-cart {
            animation: logoPulse 2s ease-in-out infinite, logoColorShift 6s ease-in-out infinite;
            font-size: 2.5rem !important;
            color: var(--ordivo-primary);
        }

        @keyframes logoPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .location-display {
            display: flex;
            align-items: center;
            color: #6c757d;
            font-size: 0.9rem;
            cursor: pointer;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            height: fit-content;
            border: 2px solid #10b981;
            background: white;
        }

        .location-display:hover {
            background: #10b981;
            color: white;
            border-color: #059669;
        }

        .location-display:hover i {
            color: white;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            height: fit-content;
        }

        .btn-user {
            background: white;
            border: 2px solid #10b981;
            color: var(--ordivo-dark);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            height: fit-content;
        }

        .btn-user:hover {
            background: #10b981;
            color: white;
            border-color: #059669;
        }

        .btn-user:hover i {
            color: white;
        }

        /* Navigation Tabs */
        .nav-tabs-container {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 0 1rem;
            height: 60px;
            position: fixed;
            top: 100px;
            left: 0;
            right: 0;
            z-index: 999;
            box-shadow: 0 2px 4px #e5e7eb;
            border-top: 2px solid transparent;
            border-bottom: 2px solid transparent;
            background: #10b981;
            background-origin: border-box;
            background-clip: padding-box, border-box;
            animation: navbarBorderPulse 3s ease-in-out infinite;
        }

        @keyframes navbarBorderPulse {
            0%, 100% {
                background: #10b981;
                background-size: 100% 100%, 200% 200%;
                background-position: 0 0, 0 0;
            }
            25% {
                background: #10b981;
                background-size: 100% 100%, 200% 200%;
                background-position: 0 0, -50% -50%;
            }
            50% {
                background: #10b981;
                background-size: 100% 100%, 200% 200%;
                background-position: 0 0, -100% -100%;
            }
            75% {
                background: #10b981;
                background-size: 100% 100%, 200% 200%;
                background-position: 0 0, -150% -150%;
            }
        }

        .nav-tabs {
            border-bottom: none;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #e5e7eb;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            margin-right: 1rem;
            border: 2px solid transparent;
            border-radius: 8px;
            background: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-tabs .nav-link.active {
            color: #ffffff;
            border: 2px solid #ffffff;
            background: #059669;
            font-weight: 600;
        }

        .nav-tabs .nav-link:hover {
            color: #ffffff;
            border: 2px solid #ffffff;
            background: #059669;
        }

        .cart-header {
            background: #10b981;
            color: white;
            padding: 2rem 0;
            margin-top: 1.5rem;
        }

        .cart-item {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px #e5e7eb;
        }

        .cart-summary {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px #e5e7eb;
            position: sticky;
            top: 100px;
        }

        .btn-primary {
            background: var(--ordivo-primary);
            border: none;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-btn {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .quantity-btn:hover {
            background: #f8f9fa;
        }

        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
        }

        .payment-options .payment-option {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .payment-options .payment-option:hover {
            border-color: var(--ordivo-primary);
            background: #f97316;
        }

        .payment-options .form-check-input:checked + .form-check-label .payment-option,
        .payment-options .form-check-input:checked ~ .payment-option {
            border-color: var(--ordivo-primary);
            background: #f97316;
        }

        .payment-options .form-check-input {
            margin-top: 0.5rem;
        }

        .payment-options .form-check-label {
            cursor: pointer;
        }

        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            body {
                padding-top: 114px; /* Header only (114px) - no nav tabs */
                background: #ffffff;
            }

            .cart-header {
                padding: 1rem 0;
            }

            .cart-header h1,
            .cart-header .display-5 {
                font-size: 1.5rem !important;
            }

            .cart-item {
                padding: 1rem;
                margin-bottom: 0.75rem;
            }

            .cart-item-image {
                width: 60px;
                height: 60px;
            }

            .cart-item h5 {
                font-size: 0.95rem;
                margin-bottom: 0.25rem;
            }

            .cart-item .text-muted {
                font-size: 0.8rem;
            }

            .cart-item .h5 {
                font-size: 1rem;
            }

            .quantity-controls {
                gap: 0.25rem;
            }

            .quantity-controls button {
                width: 28px;
                height: 28px;
                font-size: 0.85rem;
                padding: 0;
            }

            .quantity-controls input {
                width: 40px;
                height: 28px;
                font-size: 0.85rem;
                padding: 0.25rem;
            }

            .btn-remove {
                padding: 0.4rem 0.75rem;
                font-size: 0.85rem;
            }

            .cart-summary {
                padding: 1rem;
                margin-top: 1rem;
            }

            .cart-summary h4 {
                font-size: 1.1rem;
            }

            .cart-summary .h5 {
                font-size: 1rem;
            }

            .cart-summary .h4 {
                font-size: 1.2rem;
            }

            .btn-checkout {
                padding: 0.75rem;
                font-size: 0.95rem;
            }

            .empty-cart {
                padding: 2rem 1rem;
            }

            .empty-cart i {
                font-size: 3rem !important;
            }

            .empty-cart h3 {
                font-size: 1.3rem;
            }

            .empty-cart p {
                font-size: 0.9rem;
            }

            /* Stack cart items vertically on mobile */
            .cart-item .row {
                flex-direction: column;
            }

            .cart-item .col-md-6,
            .cart-item .col-md-3,
            .cart-item .col-md-2 {
                max-width: 100%;
                flex: 0 0 100%;
                margin-bottom: 0.5rem;
            }

            .cart-item .d-flex {
                justify-content: space-between;
                align-items: center;
            }

            /* Payment options on mobile */
            .payment-options .payment-option {
                padding: 0.75rem;
                margin-bottom: 0.5rem;
            }

            .payment-options .payment-option i {
                font-size: 1.2rem;
            }

            .payment-options .payment-option h6 {
                font-size: 0.9rem;
            }

            .payment-options .payment-option small {
                font-size: 0.75rem;
            }

            .notification {
                right: 10px;
                left: 10px;
                min-width: auto;
            }
        }

        @media (max-width: 576px) {
            .cart-header h1,
            .cart-header .display-5 {
                font-size: 1.3rem !important;
            }

            .cart-item-image {
                width: 50px;
                height: 50px;
            }

            .cart-item h5 {
                font-size: 0.9rem;
            }

            .cart-summary {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <?php 
    $userLocation = $_SESSION['user_location'] ?? 'Dhaka, Bangladesh';
    include 'includes/header_with_nav.php'; 
    ?>

    <!-- Cart Header -->
    <div class="cart-header">
        <div class="container">
            <h1 class="display-5 mb-0">
                <i class="fas fa-shopping-cart me-3"></i>Your Cart
            </h1>
        </div>
    </div>

    <div class="container my-4">
        <!-- Cart Items Container -->
        <div class="row" id="cartContainer">
            <!-- Loading State -->
            <div class="col-12 text-center" id="loadingState">
                <div class="py-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                    <p class="text-muted">Loading your cart...</p>
                </div>
            </div>
            
            <!-- Empty Cart State -->
            <div class="col-12 d-none" id="emptyCartState">
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                    <h3 class="text-muted mb-3">Your cart is empty</h3>
                    <p class="text-muted mb-4">Looks like you haven't added anything to your cart yet.</p>
                    <a href="index.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-utensils me-2"></i>Start Shopping
                    </a>
                </div>
            </div>
            
            <!-- Cart Items -->
            <div class="col-lg-8 d-none" id="cartItemsContainer">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Cart Items (<span id="itemCount">0</span>)</h4>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                        <i class="fas fa-trash me-1"></i>Clear Cart
                    </button>
                </div>
                
                <div id="cartItemsList">
                    <!-- Cart items will be loaded here -->
                </div>
            </div>

            <!-- Cart Summary & Payment Options -->
            <div class="col-lg-4 d-none" id="cartSummaryContainer">
                <div class="cart-summary">
                    <h5 class="mb-3">Order Summary</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span id="subtotalAmount">৳0</span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Delivery Fee:</span>
                        <span id="deliveryFeeAmount">৳50</span>
                    </div>
                    
                    <small class="text-muted mb-3 d-block" id="freeDeliveryNote">Free delivery on orders over ৳500</small>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-4">
                        <strong>Total:</strong>
                        <strong class="text-primary" id="totalAmount">৳50</strong>
                    </div>
                    
                    <!-- Payment Options -->
                    <div class="mb-4">
                        <h6 class="mb-3">Payment Method</h6>
                        
                        <div class="payment-options">
                            <div class="form-check mb-3 payment-option">
                                <input class="form-check-input" type="radio" name="payment_method" id="mobile_banking" value="mobile_banking" checked>
                                <label class="form-check-label w-100" for="mobile_banking">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-mobile-alt text-success me-3 fa-lg"></i>
                                        <div>
                                            <div class="fw-bold">Mobile Banking</div>
                                            <small class="text-muted">bKash, Nagad, Rocket</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3 payment-option">
                                <input class="form-check-input" type="radio" name="payment_method" id="bank_card" value="bank_card">
                                <label class="form-check-label w-100" for="bank_card">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-credit-card text-primary me-3 fa-lg"></i>
                                        <div>
                                            <div class="fw-bold">Bank Card</div>
                                            <small class="text-muted">Visa, Mastercard, DBBL</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3 payment-option">
                                <input class="form-check-input" type="radio" name="payment_method" id="cash_on_delivery" value="cash_on_delivery">
                                <label class="form-check-label w-100" for="cash_on_delivery">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-money-bill-wave text-warning me-3 fa-lg"></i>
                                        <div>
                                            <div class="fw-bold">Cash on Delivery</div>
                                            <small class="text-muted">Pay when you receive</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-primary w-100 btn-lg" onclick="proceedToCheckout()">
                        <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                    </button>
                    
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            Secure checkout with SSL encryption
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/location-tracker.js"></script>
    
    <script>
        let cart = JSON.parse(localStorage.getItem('ordivo_cart') || '[]');
        let cartData = null;

        // Load cart on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadCart();
        });

        async function loadCart() {
            const loadingState = document.getElementById('loadingState');
            const emptyCartState = document.getElementById('emptyCartState');
            const cartItemsContainer = document.getElementById('cartItemsContainer');
            const cartSummaryContainer = document.getElementById('cartSummaryContainer');

            if (cart.length === 0) {
                loadingState.classList.add('d-none');
                emptyCartState.classList.remove('d-none');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('cart_data', JSON.stringify(cart));

                const response = await fetch('?ajax=get_cart_items', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.error) {
                    showNotification('Error loading cart: ' + data.error, 'error');
                    return;
                }

                cartData = data;
                renderCartItems(data.items);
                updateCartSummary(data);

                loadingState.classList.add('d-none');
                cartItemsContainer.classList.remove('d-none');
                cartSummaryContainer.classList.remove('d-none');

            } catch (error) {
                console.error('Failed to load cart:', error);
                showNotification('Failed to load cart items', 'error');
                loadingState.classList.add('d-none');
                emptyCartState.classList.remove('d-none');
            }
        }

        function renderCartItems(items) {
            const container = document.getElementById('cartItemsList');
            const itemCount = document.getElementById('itemCount');
            
            itemCount.textContent = items.length;

            const itemsHtml = items.map(item => `
                <div class="cart-item" data-product-id="${item.product.id}">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 80px;">
                                ${item.product.image ? 
                                    `<img src="../uploads/images/${item.product.image}" alt="${item.product.name}" class="cart-item-image">` :
                                    `<i class="fas fa-utensils text-muted"></i>`
                                }
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-1">${item.product.name}</h6>
                            <small class="text-muted">${item.product.vendor_name || 'Restaurant'}</small>
                            <div class="text-primary fw-bold">৳${parseFloat(item.product.price).toFixed(0)}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="updateQuantity(${item.product.id}, ${item.quantity - 1})">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="mx-3 fw-bold">${item.quantity}</span>
                                <button type="button" class="quantity-btn" onclick="updateQuantity(${item.product.id}, ${item.quantity + 1})">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="fw-bold text-primary">৳${parseFloat(item.subtotal).toFixed(0)}</div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(${item.product.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');

            container.innerHTML = itemsHtml;
        }

        function updateCartSummary(data) {
            document.getElementById('subtotalAmount').textContent = `৳${parseFloat(data.subtotal).toFixed(0)}`;
            document.getElementById('deliveryFeeAmount').textContent = data.delivery_fee === 0 ? 'FREE' : `৳${parseFloat(data.delivery_fee).toFixed(0)}`;
            document.getElementById('totalAmount').textContent = `৳${parseFloat(data.total).toFixed(0)}`;
            
            const freeDeliveryNote = document.getElementById('freeDeliveryNote');
            if (data.delivery_fee === 0) {
                freeDeliveryNote.textContent = 'Free delivery applied!';
                freeDeliveryNote.className = 'text-success mb-3 d-block';
            } else {
                freeDeliveryNote.textContent = 'Free delivery on orders over ৳500';
                freeDeliveryNote.className = 'text-muted mb-3 d-block';
            }
        }

        function updateQuantity(productId, newQuantity) {
            if (newQuantity <= 0) {
                removeItem(productId);
                return;
            }

            // Update localStorage
            const itemIndex = cart.findIndex(item => item.id === productId);
            if (itemIndex !== -1) {
                cart[itemIndex].quantity = newQuantity;
                localStorage.setItem('ordivo_cart', JSON.stringify(cart));
                loadCart(); // Reload cart display
                showNotification('Cart updated successfully!', 'success');
            }
        }

        function removeItem(productId) {
            if (confirm('Remove this item from cart?')) {
                // Remove from localStorage
                cart = cart.filter(item => item.id !== productId);
                localStorage.setItem('ordivo_cart', JSON.stringify(cart));
                
                if (cart.length === 0) {
                    // Show empty cart state
                    document.getElementById('cartItemsContainer').classList.add('d-none');
                    document.getElementById('cartSummaryContainer').classList.add('d-none');
                    document.getElementById('emptyCartState').classList.remove('d-none');
                } else {
                    loadCart(); // Reload cart display
                }
                
                showNotification('Item removed from cart!', 'success');
            }
        }

        function clearCart() {
            if (confirm('Clear all items from cart?')) {
                cart = [];
                localStorage.setItem('ordivo_cart', JSON.stringify(cart));
                
                // Show empty cart state
                document.getElementById('cartItemsContainer').classList.add('d-none');
                document.getElementById('cartSummaryContainer').classList.add('d-none');
                document.getElementById('emptyCartState').classList.remove('d-none');
                
                showNotification('Cart cleared!', 'success');
            }
        }

        function proceedToCheckout() {
            if (!cartData || cartData.items.length === 0) {
                showNotification('Your cart is empty!', 'error');
                return;
            }

            const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
            if (!selectedPayment) {
                showNotification('Please select a payment method!', 'error');
                return;
            }

            // Store payment method and cart data for checkout
            localStorage.setItem('checkout_payment_method', selectedPayment.value);
            localStorage.setItem('checkout_cart_data', JSON.stringify(cartData));
            
            // Check if user is logged in (check session via PHP or localStorage)
            // For now, we'll redirect to checkout and let checkout page handle login check
            window.location.href = 'checkout.php';
        }

        function showNotification(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';
            
            const notification = document.createElement('div');
            notification.className = `alert ${alertClass} alert-dismissible fade show notification`;
            notification.innerHTML = `
                <i class="fas fa-${icon} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }
    </script>

    <?php include 'includes/modals.php'; ?>
    <?php include 'includes/footer.php'; ?>
</body>
</html>