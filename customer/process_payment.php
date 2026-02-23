<?php
/**
 * ORDIVO Payment Processing
 * Handles payment initiation and processing
 */

session_start();
require_once '../config/db_connection.php';
require_once '../config/payment_config.php';
require_once '../config/PaymentGateway.php';

// Get order ID and payment method from URL
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$paymentMethod = isset($_GET['method']) ? sanitizeInput($_GET['method']) : '';

// Validate inputs
if (!$orderId || !$paymentMethod) {
    header('Location: checkout.php?error=invalid_payment');
    exit;
}

// Get order details
$order = fetchRow("SELECT * FROM orders WHERE id = ?", [$orderId]);

if (!$order) {
    header('Location: checkout.php?error=order_not_found');
    exit;
}

// Validate payment method
if (!isPaymentMethodEnabled($paymentMethod)) {
    header('Location: checkout.php?error=invalid_payment_method');
    exit;
}

// Prepare customer info
$customerInfo = [
    'user_id' => $_SESSION['user_id'] ?? 0,
    'name' => $order['customer_name'] ?? '',
    'email' => $order['customer_email'] ?? '',
    'phone' => $order['customer_phone'] ?? ''
];

// Decode delivery address if it's JSON
if (is_string($order['delivery_address'])) {
    $deliveryData = json_decode($order['delivery_address'], true);
    if ($deliveryData && is_array($deliveryData)) {
        $customerInfo['name'] = $deliveryData['name'] ?? $customerInfo['name'];
        $customerInfo['email'] = $deliveryData['email'] ?? $customerInfo['email'];
        $customerInfo['phone'] = $deliveryData['phone'] ?? $customerInfo['phone'];
    }
}

// Create payment gateway instance
try {
    $gateway = PaymentGatewayFactory::create($paymentMethod, $orderId, $order['total_amount'], $customerInfo);
    
    // Initiate payment
    $result = $gateway->initiatePayment();
    
    if ($result['success']) {
        // Create payment transaction record
        insertData('payment_transactions', [
            'order_id' => $orderId,
            'user_id' => $customerInfo['user_id'],
            'payment_method' => $paymentMethod,
            'amount' => $order['total_amount'],
            'currency' => 'BDT',
            'gateway_transaction_id' => $result['transaction_id'] ?? null,
            'status' => 'pending',
            'gateway_response' => json_encode($result)
        ]);
        
        // Update order payment status
        updateData('orders', ['payment_status' => 'processing'], 'id = ?', [$orderId]);
        
        // Redirect to payment gateway
        if (isset($result['redirect_url'])) {
            header('Location: ' . $result['redirect_url']);
            exit;
        } else {
            // Show payment page
            $paymentUrl = $result['payment_url'] ?? '';
            $transactionId = $result['transaction_id'] ?? '';
        }
    } else {
        // Payment initiation failed
        header('Location: payment_fail.php?order_id=' . $orderId . '&reason=' . urlencode($result['message'] ?? 'Payment initiation failed'));
        exit;
    }
    
} catch (Exception $e) {
    header('Location: payment_fail.php?order_id=' . $orderId . '&reason=' . urlencode($e->getMessage()));
    exit;
}

// Handle AJAX payment initiation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'initiate_payment') {
    header('Content-Type: application/json');
    
    try {
        $orderId = sanitizeInput($_POST['order_id']);
        $paymentMethod = sanitizeInput($_POST['payment_method']);
        $amount = floatval($_POST['amount']);
        
        // Validate payment method
        if (!isPaymentMethodEnabled($paymentMethod)) {
            throw new Exception('Invalid or disabled payment method');
        }
        
        // Validate amount
        if ($amount < MIN_ORDER_AMOUNT || $amount > MAX_ORDER_AMOUNT) {
            throw new Exception('Invalid order amount');
        }
        
        // Get order details
        $order = fetchRow("SELECT * FROM orders WHERE id = ?", [$orderId]);
        
        if (!$order) {
            throw new Exception('Order not found');
        }
        
        // Prepare customer info
        $customerInfo = [
            'user_id' => $_SESSION['user_id'] ?? 0,
            'name' => $order['customer_name'] ?? 'Customer',
            'email' => $order['customer_email'] ?? '',
            'phone' => $order['customer_phone'] ?? ''
        ];
        
        // Decode delivery address if it's JSON
        if (isset($order['delivery_address']) && is_string($order['delivery_address'])) {
            $deliveryData = json_decode($order['delivery_address'], true);
            if ($deliveryData && is_array($deliveryData)) {
                $customerInfo['name'] = $deliveryData['name'] ?? $customerInfo['name'];
                $customerInfo['email'] = $deliveryData['email'] ?? $customerInfo['email'];
                $customerInfo['phone'] = $deliveryData['phone'] ?? $customerInfo['phone'];
            }
        }
        
        // Create payment gateway instance
        $gateway = PaymentGatewayFactory::create($paymentMethod, $orderId, $amount, $customerInfo);
        
        // Initiate payment
        $result = $gateway->initiatePayment();
        
        if ($result['success']) {
            // Update order payment status
            executeQuery("UPDATE orders SET payment_status = 'processing' WHERE id = ?", [$orderId]);
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message'] ?? 'Payment initiation failed'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    exit;
}

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['verify'])) {
    header('Content-Type: application/json');
    
    try {
        $paymentId = sanitizeInput($_GET['payment_id']);
        $method = sanitizeInput($_GET['method']);
        
        // Get transaction details
        $transaction = fetchRow("SELECT * FROM payment_transactions WHERE gateway_transaction_id = ?", [$paymentId]);
        
        if (!$transaction) {
            throw new Exception('Transaction not found');
        }
        
        // Create gateway instance
        $order = fetchRow("SELECT * FROM orders WHERE id = ?", [$transaction['order_id']]);
        
        $customerInfo = [
            'user_id' => $transaction['user_id'],
            'name' => 'Customer',
            'email' => '',
            'phone' => ''
        ];
        
        // Decode delivery address if it's JSON
        if (isset($order['delivery_address']) && is_string($order['delivery_address'])) {
            $deliveryData = json_decode($order['delivery_address'], true);
            if ($deliveryData && is_array($deliveryData)) {
                $customerInfo['name'] = $deliveryData['name'] ?? $customerInfo['name'];
                $customerInfo['email'] = $deliveryData['email'] ?? $customerInfo['email'];
                $customerInfo['phone'] = $deliveryData['phone'] ?? $customerInfo['phone'];
            }
        }
        
        $gateway = PaymentGatewayFactory::create($method, $transaction['order_id'], $transaction['amount'], $customerInfo);
        
        // Verify payment
        $result = $gateway->verifyPayment($paymentId);
        
        if ($result['success']) {
            // Update transaction status
            executeQuery("UPDATE payment_transactions SET status = 'completed', processed_at = NOW() WHERE id = ?", [$transaction['id']]);
            
            // Update order status
            executeQuery("UPDATE orders SET payment_status = 'paid', status = 'confirmed' WHERE id = ?", [$transaction['order_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment verified successfully'
            ]);
        } else {
            // Update transaction as failed
            executeQuery("UPDATE payment_transactions SET status = 'failed', failure_reason = ? WHERE id = ?", 
                [$result['message'], $transaction['id']]);
            
            echo json_encode([
                'success' => false,
                'message' => $result['message']
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment - ORDIVO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .payment-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .spinner {
            width: 80px;
            height: 80px;
            border: 8px solid #f3f3f3;
            border-top: 8px solid #10b981;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 2rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .payment-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="payment-card">
        <div class="spinner"></div>
        <h2 class="mb-3">Processing Your Payment</h2>
        <p class="text-muted mb-4">Please wait while we redirect you to the payment gateway...</p>
        
        <div class="payment-info">
            <div class="d-flex justify-content-between mb-2">
                <span>Order ID:</span>
                <strong>#<?= $orderId ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Amount:</span>
                <strong>৳<?= number_format($order['total_amount'], 2) ?></strong>
            </div>
            <div class="d-flex justify-content-between">
                <span>Payment Method:</span>
                <strong><?= ucfirst(str_replace('_', ' ', $paymentMethod)) ?></strong>
            </div>
        </div>
        
        <div class="mt-4">
            <small class="text-muted">
                <i class="fas fa-shield-alt me-1"></i>
                Your payment is secure and encrypted
            </small>
        </div>
    </div>
    
    <?php if (isset($paymentUrl) && $paymentUrl): ?>
    <script>
        // Auto-redirect to payment gateway after 2 seconds
        setTimeout(function() {
            window.location.href = '<?= $paymentUrl ?>';
        }, 2000);
    </script>
    <?php endif; ?>
</body>
</html>
