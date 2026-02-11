<?php
/**
 * ORDIVO - Vendor Analytics & Performance Dashboard
 * Comprehensive business analytics for vendor management
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    // More robust redirect handling
    if (!headers_sent()) {
        header('Location: ../auth/login.php');
        exit;
    } else {
        // If headers already sent, use JavaScript redirect
        echo '<script>window.location.href="../auth/login.php";</script>';
        echo '<meta http-equiv="refresh" content="0;url=../auth/login.php">';
        echo '<p>Redirecting to login page... <a href="../auth/login.php">Click here if not redirected</a></p>';
        exit;
    }
}

$vendorId = (int)($_GET['vendor_id'] ?? 0);

// Get vendor analytics data
try {
    // Overall vendor statistics
    $overallStats = [
        'total_vendors' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'vendor'"),
        'active_vendors' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'vendor' AND status = 'active'"),
        'total_revenue' => fetchValue("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'"),
        'total_orders' => fetchValue("SELECT COUNT(*) FROM orders"),
        'avg_order_value' => fetchValue("SELECT COALESCE(AVG(total_amount), 0) FROM orders WHERE payment_status = 'paid'")
    ];
    
    // Top performing vendors
    $topVendors = fetchAll("
        SELECT v.id, COALESCE(v.name, u.name) as name, v.owner_id,
               COUNT(o.id) as total_orders,
               COALESCE(SUM(o.total_amount), 0) as total_revenue,
               COALESCE(AVG(o.total_amount), 0) as avg_order_value,
               COALESCE(AVG(r.rating), 0) as avg_rating
        FROM vendors v
        LEFT JOIN orders o ON v.id = o.vendor_id AND o.payment_status = 'paid'
        LEFT JOIN reviews r ON v.id = r.vendor_id
        INNER JOIN users u ON v.owner_id = u.id AND u.status = 'active'
        GROUP BY v.id, v.name, v.owner_id, u.name
        ORDER BY total_revenue DESC
        LIMIT 10
    ");
    
    // Monthly revenue trend
    $monthlyRevenue = fetchAll("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as revenue
        FROM orders 
        WHERE payment_status = 'paid' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    
    // Vendor performance metrics
    $vendorMetrics = fetchAll("
        SELECT v.id, COALESCE(v.name, u.name) as name,
               COUNT(DISTINCT p.id) as product_count,
               COUNT(DISTINCT o.id) as order_count,
               COALESCE(SUM(o.total_amount), 0) as revenue,
               COALESCE(AVG(r.rating), 0) as avg_rating,
               COALESCE(v.commission_rate, 15) as commission_rate,
               COALESCE(SUM(o.total_amount * COALESCE(v.commission_rate, 15) / 100), 0) as commission_earned
        FROM vendors v
        LEFT JOIN products p ON v.id = p.vendor_id AND p.is_active = 1
        LEFT JOIN orders o ON v.id = o.vendor_id AND o.payment_status = 'paid'
        LEFT JOIN reviews r ON v.id = r.vendor_id
        INNER JOIN users u ON v.owner_id = u.id AND u.status = 'active'
        GROUP BY v.id, v.name, v.commission_rate, u.name
        ORDER BY revenue DESC
    ");
    
    // Category performance
    $categoryPerformance = fetchAll("
        SELECT c.name as category_name,
               COUNT(DISTINCT p.id) as product_count,
               COUNT(DISTINCT o.id) as order_count,
               COALESCE(SUM(oi.quantity * oi.price), 0) as revenue
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
        WHERE c.is_active = 1
        GROUP BY c.id, c.name
        ORDER BY revenue DESC
        LIMIT 10
    ");
    
} catch (Exception $e) {
    $overallStats = ['total_vendors' => 0, 'active_vendors' => 0, 'total_revenue' => 0, 'total_orders' => 0, 'avg_order_value' => 0];
    $topVendors = [];
    $monthlyRevenue = [];
    $vendorMetrics = [];
    $categoryPerformance = [];
    $error = 'Failed to load analytics data: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Analytics - ORDIVO Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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

        .header {
            background: #10b981; 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .btn-primary {
            background: #10b981; 100%);
            border: none;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px #e5e7eb;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px #e5e7eb;
            transition: all 0.3s ease;
            text-align: center;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }

        .vendor-rank {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .vendor-rank:hover {
            background: var(--ordivo-light);
            transform: translateX(5px);
        }

        .rank-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--ordivo-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 1rem;
        }

        .rank-number.gold {
            background: #ffd700;
            color: #000;
        }

        .rank-number.silver {
            background: #c0c0c0;
            color: #000;
        }

        .rank-number.bronze {
            background: #cd7f32;
            color: white;
        }

        .performance-metric {
            text-align: center;
            padding: 1rem;
            border-radius: 8px;
            background: #f8f9fa;
            margin-bottom: 1rem;
        }

        .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--ordivo-primary);
        }

        .metric-label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1">
                        <i class="fas fa-chart-line me-3"></i>Vendor Analytics
                    </h1>
                    <p class="mb-0 opacity-75">Business performance and vendor insights</p>
                </div>
                <a href="vendors.php" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to Vendors
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Overall Statistics -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-value text-primary"><?= number_format($overallStats['total_vendors']) ?></div>
                    <div class="stat-label">Total Vendors</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-value text-success"><?= number_format($overallStats['active_vendors']) ?></div>
                    <div class="stat-label">Active Vendors</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-success">৳<?= number_format($overallStats['total_revenue']) ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-value text-info"><?= number_format($overallStats['total_orders']) ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-warning">৳<?= number_format($overallStats['avg_order_value']) ?></div>
                    <div class="stat-label">Avg Order Value</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Revenue Trend Chart -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-area me-2"></i>Monthly Revenue Trend
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Vendors -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>Top Performing Vendors
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($topVendors)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                <p>No vendor data available</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($topVendors as $index => $vendor): ?>
                                <div class="vendor-rank">
                                    <div class="rank-number <?= $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : '')) ?>">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?= htmlspecialchars($vendor['name']) ?></div>
                                        <small class="text-muted">
                                            ৳<?= number_format($vendor['total_revenue']) ?> • 
                                            <?= number_format($vendor['total_orders']) ?> orders • 
                                            <?= number_format($vendor['avg_rating'], 1) ?>★
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Vendor Performance Table -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2"></i>Vendor Performance Metrics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Vendor</th>
                                        <th>Products</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                        <th>Rating</th>
                                        <th>Commission</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($vendorMetrics)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                No vendor metrics available
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($vendorMetrics as $metric): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($metric['name']) ?></strong>
                                                </td>
                                                <td><?= number_format($metric['product_count']) ?></td>
                                                <td><?= number_format($metric['order_count']) ?></td>
                                                <td>৳<?= number_format($metric['revenue']) ?></td>
                                                <td>
                                                    <?= number_format($metric['avg_rating'], 1) ?>★
                                                </td>
                                                <td>
                                                    <?= $metric['commission_rate'] ?>% 
                                                    <small class="text-muted">(৳<?= number_format($metric['commission_earned']) ?>)</small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Performance -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tags me-2"></i>Category Performance
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categoryPerformance)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-tags fa-2x mb-2"></i>
                                <p>No category data available</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($categoryPerformance as $category): ?>
                                <div class="performance-metric">
                                    <div class="metric-value">৳<?= number_format($category['revenue']) ?></div>
                                    <div class="metric-label"><?= htmlspecialchars($category['category_name']) ?></div>
                                    <small class="text-muted">
                                        <?= number_format($category['product_count']) ?> products • 
                                        <?= number_format($category['order_count']) ?> orders
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Revenue Trend Chart
        const revenueData = <?= json_encode($monthlyRevenue) ?>;
        const revenueLabels = revenueData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        });
        const revenueValues = revenueData.map(item => parseFloat(item.revenue));

        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Revenue (৳)',
                    data: revenueValues,
                    borderColor: '#ff6b35',
                    backgroundColor: 'rgba(255, 107, 53, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '৳' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>