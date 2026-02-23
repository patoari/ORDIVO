<?php
/**
 * ORDIVO - Customer Wallet Page
 * Manage wallet balance and transactions
 */

require_once '../config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get wallet data from database
try {
    // Get or create wallet
    $walletData = fetchRow("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
    
    if (!$walletData) {
        // Create wallet if doesn't exist
        insertData('wallets', [
            'user_id' => $userId,
            'balance' => 0.00,
            'currency' => 'BDT',
            'is_active' => 1
        ]);
        $walletData = fetchRow("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
    }
    
    $walletBalance = $walletData ? (float)$walletData['balance'] : 0.00;
    $totalEarned = $walletData ? (float)$walletData['total_earned'] : 0.00;
    $totalSpent = $walletData ? (float)$walletData['total_spent'] : 0.00;
    $walletId = $walletData['id'];
    
    // Get wallet transactions
    $transactions = fetchAll("
        SELECT 
            wt.*,
            wt.transaction_type as type,
            wt.created_at as date
        FROM wallet_transactions wt
        WHERE wt.wallet_id = ?
        ORDER BY wt.created_at DESC 
        LIMIT 50
    ", [$walletId]);
} catch (Exception $e) {
    error_log("Wallet query failed: " . $e->getMessage());
    $walletBalance = 0.00;
    $totalEarned = 0.00;
    $totalSpent = 0.00;
    $transactions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet - ORDIVO</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-light: #f0fdf4;
            --ordivo-dark: #1a1a1a;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }

        .page-header {
            background: #10b981; 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .back-btn {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: #ffffff;
            text-decoration: none;
        }

        .wallet-card {
            background: #10b981; 100%);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px #f97316;
        }

        .balance-amount {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .wallet-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-wallet {
            background: #ffffff;
            border: 1px solid #ffffff;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-wallet:hover {
            background: white;
            color: var(--ordivo-primary);
            border-color: white;
        }

        .transactions-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px #e5e7eb;
            padding: 2rem;
        }

        .transaction-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .transaction-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 1rem;
        }

        .transaction-icon.credit {
            background: #d4edda;
            color: #155724;
        }

        .transaction-icon.debit {
            background: #f8d7da;
            color: #721c24;
        }

        .transaction-amount.credit {
            color: #28a745;
        }

        .transaction-amount.debit {
            color: #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <a href="index.php" class="back-btn">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col">
                    <h1 class="mb-1">
                        <i class="fas fa-wallet me-2"></i>My Wallet
                    </h1>
                    <p class="mb-0 opacity-75">Manage your wallet balance and transactions</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Wallet Balance Card -->
        <div class="wallet-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="mb-2">Current Balance</h3>
                    <div class="balance-amount">৳<?= number_format($walletBalance, 2) ?></div>
                    <p class="mb-0 opacity-75">Available for orders</p>
                    <div class="mt-3">
                        <small class="opacity-75">
                            Total Earned: ৳<?= number_format($totalEarned, 2) ?> | 
                            Total Spent: ৳<?= number_format($totalSpent, 2) ?>
                        </small>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="wallet-icon" style="font-size: 4rem; opacity: 0.3;">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
            
            <div class="wallet-actions">
                <button class="btn btn-wallet" onclick="addMoney()">
                    <i class="fas fa-plus me-2"></i>Add Money
                </button>
                <button class="btn btn-wallet" onclick="viewHistory()">
                    <i class="fas fa-history me-2"></i>Full History
                </button>
            </div>
        </div>

        <!-- Transactions -->
        <div class="transactions-card">
            <h3 class="mb-4">
                <i class="fas fa-history me-2 text-primary"></i>Recent Transactions
            </h3>

            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h4>No Transactions Yet</h4>
                    <p>Your transaction history will appear here</p>
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <div class="transaction-item">
                        <div class="d-flex align-items-center">
                            <div class="transaction-icon <?= $transaction['type'] ?>">
                                <i class="fas fa-<?= $transaction['type'] === 'credit' ? 'plus' : 'minus' ?>"></i>
                            </div>
                            <div>
                                <h6 class="mb-1"><?= htmlspecialchars($transaction['description']) ?></h6>
                                <small class="text-muted">
                                    <?= date('M d, Y - h:i A', strtotime($transaction['date'])) ?>
                                </small>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="transaction-amount <?= $transaction['type'] ?> fw-bold">
                                <?= $transaction['type'] === 'credit' ? '+' : '-' ?>৳<?= number_format($transaction['amount'], 0) ?>
                            </div>
                            <small class="text-success">
                                <i class="fas fa-check-circle me-1"></i><?= ucfirst($transaction['status']) ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function addMoney() {
            // Redirect to add money page or show modal
            const amount = prompt('Enter amount to add to wallet (৳):');
            if (amount && !isNaN(amount) && parseFloat(amount) > 0) {
                window.location.href = 'process_payment.php?action=add_money&amount=' + amount;
            }
        }

        function viewHistory() {
            // Show all transactions
            alert('Full transaction history - would show paginated list of all transactions');
        }
    </script>
</body>
</html>