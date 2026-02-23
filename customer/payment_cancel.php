<?php
/**
 * ORDIVO Payment Cancelled Page
 */

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - ORDIVO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .cancel-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        }
        .cancel-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .cancel-icon {
            width: 100px;
            height: 100px;
            background: #f59e0b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }
        .cancel-icon i {
            font-size: 50px;
            color: white;
        }
    </style>
</head>
<body>
    <div class="cancel-container">
        <div class="cancel-card">
            <div class="cancel-icon">
                <i class="fas fa-ban"></i>
            </div>
            
            <h2 class="mb-3">Payment Cancelled</h2>
            <p class="text-muted mb-4">You have cancelled the payment process.</p>
            
            <div class="alert alert-warning">
                <i class="fas fa-info-circle me-2"></i>
                Your order has not been placed. You can try again anytime.
            </div>
            
            <div class="d-grid gap-2">
                <a href="checkout.php" class="btn btn-warning btn-lg">
                    <i class="fas fa-redo me-2"></i>Try Again
                </a>
                <a href="cart.php" class="btn btn-outline-secondary">
                    <i class="fas fa-shopping-cart me-2"></i>Back to Cart
                </a>
                <a href="index.php" class="btn btn-link">
                    <i class="fas fa-home me-2"></i>Continue Shopping
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
