<?php
/**
 * ORDIVO - Checkout Page
 * Complete order placement with delivery details
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
            $vendorId = 1; // Default vendor, you might want to handle multiple vendors
            
            // Insert order
            $orderId = insertData('orders', [
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_email' => $customerEmail,
                'delivery_address' => $deliveryAddress,
                'vendor_id' => $vendorId,
                'total_amount' => $totalAmount,
                'delivery_fee' => $deliveryFee,
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'notes' => $notes
            ]);
            
            if ($orderId) {
                // Insert order items
                foreach ($cartData as $item) {
                    insertData('order_items', [
                        'order_id' => $orderId,
                        'product_id' => $item['id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $item['price'] * $item['quantity']
                    ]);
                }
                
                // Redirect to success page
                header("Location: order_success.php?order_id=$orderId");
                exit;
            } else {
                $error = 'Failed to place order. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'Error placing order: ' . $e->getMessage();
        }
    }
}
        
        $cartItems[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
} catch (Exception $e) {
    header('Location: cart.php?error=cart_error');
    exit;
}

$deliveryFee = $totalAmount > 500 ? 0 : 50;
$finalTotal = $totalAmount + $deliveryFee;

// Handle order placement
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        global $pdo;
        
        // Validate required fields
        $customerName = trim($_POST['customer_name'] ?? '');
        $customerPhone = trim($_POST['customer_phone'] ?? '');
        $deliveryAddress = trim($_POST['delivery_address'] ?? '');
        $paymentMethod = $_POST['payment_method'] ?? '';
        
        if (empty($customerName) || empty($customerPhone) || empty($deliveryAddress) || empty($paymentMethod)) {
            throw new Exception('Please fill in all required fields');
        }
        
        // Create order
        $orderId = insertData('orders', [
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_email' => trim($_POST['customer_email'] ?? ''),
            'delivery_address' => $deliveryAddress,
            'vendor_id' => $vendorId,
            'total_amount' => $finalTotal,
            'delivery_fee' => $deliveryFee,
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'notes' => trim($_POST['notes'] ?? ''),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Add order items
        foreach ($cartItems as $item) {
            insertData('order_items', [
                'order_id' => $orderId,
                'product_id' => $item['product']['id'],
                'quantity' => $item['quantity'],
                'price' => $item['product']['price'],
                'subtotal' => $item['subtotal']
            ]);
        }
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        // Redirect to success page
        header("Location: order_success.php?order_id=$orderId");
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
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
            background: #10b981;);
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
            background: #f97316;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="text-decoration-none">
                    <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                        <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="logo-img">
                    <?php else: ?>
                        <i class="fas fa-utensils logo-icon"></i>
                    <?php endif; ?>
                </a>
                
                <a href="cart.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Cart
                </a>
            </div>
        </div>
    </header>

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
                            <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" required placeholder="Enter your complete delivery address"></textarea>
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
                            <div class="payment-option" onclick="selectPayment('mobile_banking')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="mobile_banking" id="mobile_banking" class="me-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Mobile Banking</h6>
                                        <small class="text-muted">bKash, Nagad, Rocket</small>
                                    </div>
                                    <i class="fas fa-mobile-alt ms-auto text-success fa-lg"></i>
                                </div>
                            </div>
                            
                            <div class="payment-option" onclick="selectPayment('bank_card')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="bank_card" id="bank_card" class="me-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Bank Card</h6>
                                        <small class="text-muted">Visa, Mastercard, DBBL</small>
                                    </div>
                                    <i class="fas fa-credit-card ms-auto text-primary fa-lg"></i>
                                </div>
                            </div>
                            
                            <div class="payment-option" onclick="selectPayment('cash_on_delivery')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="cash_on_delivery" id="cash_on_delivery" class="me-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Cash on Delivery</h6>
                                        <small class="text-muted">Pay when you receive</small>
                                    </div>
                                    <i class="fas fa-money-bill-wave ms-auto text-warning fa-lg"></i>
                                </div>
                            </div>
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
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-primary">৳<?= number_format($finalTotal, 0) ?></strong>
                        </div>
                        
                        <button type="submit" name="place_order" class="btn btn-primary w-100 btn-lg">
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
    
    <script>
        let checkoutData = null;
        let selectedPaymentMethod = null;

        document.addEventListener('DOMContentLoaded', function() {
            loadCheckoutData();
        });

        function loadCheckoutData() {
            // Get cart data from localStorage
            const cartData = JSON.parse(localStorage.getItem('checkout_cart_data') || 'null');
            const paymentMethod = localStorage.getItem('checkout_payment_method');

            if (!cartData || !cartData.items || cartData.items.length === 0) {
                // Redirect to cart if no data
                window.location.href = 'cart.php';
                return;
            }

            checkoutData = cartData;
            selectedPaymentMethod = paymentMethod;

            // Render order items
            renderOrderItems(cartData.items);
            
            // Update totals
            updateTotals(cartData);
            
            // Set payment method
            if (paymentMethod) {
                selectPayment(paymentMethod);
            } else {
                selectPayment('mobile_banking'); // Default
            }

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
            // Remove selected class from all options
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            const clickedOption = document.querySelector(`#${method}`).closest('.payment-option');
            if (clickedOption) {
                clickedOption.classList.add('selected');
            }
            
            // Check the radio button
            document.getElementById(method).checked = true;
            selectedPaymentMethod = method;
        }

        // Form submission handler
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            if (!selectedPaymentMethod) {
                e.preventDefault();
                alert('Please select a payment method');
                return false;
            }

            // Clear localStorage after successful submission
            localStorage.removeItem('checkout_cart_data');
            localStorage.removeItem('checkout_payment_method');
            localStorage.removeItem('ordivo_cart'); // Clear cart
        });
    </script>
</body>
</html>