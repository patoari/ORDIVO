<?php
/**
 * ORDIVO Payment Success Page
 */

session_start();
require_once '../config/db_connection.php';

$transactionId = $_GET['transaction_id'] ?? '';
$paymentId = $_GET['payment_id'] ?? '';

// Get transaction details
$transaction = null;
if ($transactionId) {
    $transaction = fetchRow("SELECT pt.*, o.order_number, o.total_amount 
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
    <title>Payment Successful - ORDIVO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .success-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .success-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: scaleIn 0.5s ease-out;
        }
        .success-icon i {
            font-size: 50px;
            color: white;
        }
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        .transaction-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <h2 class="mb-3">Payment Successful!</h2>
            <p class="text-muted mb-4">Your payment has been processed successfully.</p>
            
            <?php if ($transaction): ?>
            <div class="transaction-details">
                <div class="detail-row">
                    <span class="text-muted">Order Number:</span>
                    <strong><?= htmlspecialchars($transaction['order_number']) ?></strong>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Transaction ID:</span>
                    <strong><?= htmlspecialchars($transaction['transaction_id']) ?></strong>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Payment Method:</span>
                    <strong><?= ucfirst($transaction['payment_method']) ?></strong>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Amount Paid:</span>
                    <strong class="text-success">৳<?= number_format($transaction['amount'], 2) ?></strong>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Status:</span>
                    <span class="badge bg-success">Completed</span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="d-grid gap-2">
                <a href="orders.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-receipt me-2"></i>View My Orders
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
