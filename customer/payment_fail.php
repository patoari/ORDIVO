<?php
/**
 * ORDIVO Payment Failure Page
 */

session_start();
require_once '../config/db_connection.php';

$transactionId = $_GET['transaction_id'] ?? '';
$reason = $_GET['reason'] ?? 'Payment processing failed';

// Get transaction details
$transaction = null;
if ($transactionId) {
    $transaction = fetchRow("SELECT pt.*, o.order_number 
                            FROM payment_transactions pt 
                            JOIN orders o ON pt.order_id = o.id 
                            WHERE pt.transaction_id = ?", [$transactionId]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - ORDIVO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .fail-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .fail-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .fail-icon {
            width: 100px;
            height: 100px;
            background: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: shake 0.5s ease-out;
        }
        .fail-icon i {
            font-size: 50px;
            color: white;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        .error-message {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 1rem;
            border-radius: 8px;
            margin: 2rem 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="fail-container">
        <div class="fail-card">
            <div class="fail-icon">
                <i class="fas fa-times"></i>
            </div>
            
            <h2 class="mb-3">Payment Failed</h2>
            <p class="text-muted mb-4">We couldn't process your payment.</p>
            
            <div class="error-message">
                <strong><i class="fas fa-exclamation-circle me-2"></i>Reason:</strong>
                <p class="mb-0 mt-2"><?= htmlspecialchars($reason) ?></p>
            </div>
            
            <?php if ($transaction): ?>
            <div class="alert alert-info">
                <strong>Order Number:</strong> <?= htmlspecialchars($transaction['order_number']) ?><br>
                <strong>Transaction ID:</strong> <?= htmlspecialchars($transaction['transaction_id']) ?>
            </div>
            <?php endif; ?>
            
            <div class="d-grid gap-2">
                <a href="checkout.php<?= $transaction ? '?order_id=' . $transaction['order_id'] : '' ?>" class="btn btn-danger btn-lg">
                    <i class="fas fa-redo me-2"></i>Try Again
                </a>
                <a href="cart.php" class="btn btn-outline-secondary">
                    <i class="fas fa-shopping-cart me-2"></i>Back to Cart
                </a>
                <a href="help.php" class="btn btn-link">
                    <i class="fas fa-question-circle me-2"></i>Need Help?
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
