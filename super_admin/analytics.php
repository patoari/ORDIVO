<?php
/**
 * ORDIVO - Analytics Dashboard
 * Comprehensive analytics and reporting system
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Get date range from query parameters
$startDate = sanitizeInput($_GET['start_date'] ?? date('Y-m-01')); // First day of current month
$endDate = sanitizeInput($_GET['end_date'] ?? date('Y-m-d')); // Today

try {
    // Check if tables exist
    $ordersTableExists = fetchValue("SHOW TABLES LIKE 'orders'");
    $productsTableExists = fetchValue("SHOW TABLES LIKE 'products'");
    
    if ($ordersTableExists) {
        // Revenue Analytics
        $revenueData = fetchAll("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders,
                SUM(total_amount) as revenue
            FROM orders 
            WHERE DATE(created_at) BETWEEN ? AND ?
            AND payment_status = 'paid'
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$startDate, $endDate]);
        
        // Monthly Revenue Trend
        $monthlyRevenue = fetchAll("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as orders,
                SUM(total_amount) as revenue
            FROM orders 
            WHERE payment_status = 'paid'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        
        // Top Vendors by Revenue
        $topVendors = fetchAll("
            SELECT 
                v.name as vendor_name,
                COUNT(o.id) as total_orders,
                SUM(o.total_amount) as total_revenue
            FROM orders o
            JOIN users v ON o.vendor_id = v.id
            WHERE o.payment_status = 'paid'
            AND DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY o.vendor_id, v.name
            ORDER BY total_revenue DESC
            LIMIT 10
        ", [$startDate, $endDate]);
        
        // Order Status Distribution
        $orderStatus = fetchAll("
            SELECT 
                status,
                COUNT(*) as count
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY status
        ", [$startDate, $endDate]);
        
    } else {
        $revenueData = $monthlyRevenue = $topVendors = $orderStatus = [];
    }
    
    // User Growth Analytics
    $userGrowth = fetchAll("
        SELECT 
            DATE(created_at) as date,
            role,
            COUNT(*) as count
        FROM users
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at), role
        ORDER BY date ASC
    ", [$startDate, $endDate]);
    
    // Summary Statistics
    $stats = [
        'total_revenue' => $ordersTableExists ? (fetchValue("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid' AND DATE(created_at) BETWEEN ? AND ?", [$startDate, $endDate]) ?: 0) : 0,
        'total_orders' => $ordersTableExists ? (fetchValue("SELECT COUNT(*) FROM orders WHERE DATE(created_at) BETWEEN ? AND ?", [$startDate, $endDate]) ?: 0) : 0,
        'new_users' => fetchValue("SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN ? AND ?", [$startDate, $endDate]) ?: 0,
        'new_vendors' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'vendor' AND DATE(created_at) BETWEEN ? AND ?", [$startDate, $endDate]) ?: 0,
        'avg_order_value' => 0
    ];
    
    if ($stats['total_orders'] > 0) {
        $stats['avg_order_value'] = $stats['total_revenue'] / $stats['total_orders'];
    }
    
} catch (Exception $e) {
    $revenueData = $monthlyRevenue = $topVendors = $orderStatus = $userGrowth = [];
    $stats = ['total_revenue' => 0, 'total_orders' => 0, 'new_users' => 0, 'new_vendors' => 0, 'avg_order_value' => 0];
    $error = 'Failed to load analytics: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - ORDIVO Admin</title>
    
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
            --ordivo-dark: #374151;
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        /* Mobile First - Sidebar hidden by default on mobile */
        .sidebar {
            position: fixed;
            top: 0;
            left: -280px; /* Hidden by default on mobile */
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: left 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar.show {
            left: 0;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        /* Mobile toggle button - Hidden, using inline version */
        .sidebar-toggle {
            display: none;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            text-align: center;
        }

        .sidebar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: white;
            text-decoration: none;
            display: block;
        }

        .sidebar-subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            color: #ffffff;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            transform: translateX(5px);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 0; /* No margin on mobile */
            min-height: 100vh;
            padding: 0.625rem 1rem 1rem; /* 10px top, 1rem sides and bottom */
        }

        .page-header {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-top: 4px solid #10b981;
            display: flex;
            flex-direction: row;
            gap: 0.75rem;
            align-items: center;
        }

        /* Inline hamburger button for mobile */
        .sidebar-toggle-inline {
            display: block;
            width: 40px;
            height: 40px;
            background: #10b981;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }

        .sidebar-toggle-inline:hover {
            background: #059669;
            transform: scale(1.05);
        }

        .page-header-content {
            flex: 1;
            min-width: 0;
        }

        .page-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .page-subtitle {
            font-size: 0.75rem;
            color: #6c757d;
            margin: 0;
            display: none; /* Hide subtitle on mobile */
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px #e5e7eb;
            transition: all 0.3s ease;
            text-align: center;
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
            height: 300px;
            margin-top: 1rem;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            border-top: none;
        }

        .progress {
            height: 8px;
        }

        /* Tablet and up */
        @media (min-width: 768px) {
            .sidebar-toggle-inline {
                display: none; /* Hide inline hamburger on tablet+ */
            }

            .sidebar {
                left: 0; /* Always visible on tablet+ */
            }

            .sidebar-overlay {
                display: none !important;
            }

            .main-content {
                margin-left: var(--sidebar-width);
                padding: 1.5rem;
            }

            .page-header {
                padding: 1.5rem;
                margin-bottom: 2rem;
            }

            .page-title {
                font-size: 1.8rem;
                white-space: normal;
            }

            .page-subtitle {
                font-size: 1rem;
                display: block; /* Show subtitle on tablet+ */
            }
        }

        /* Desktop */
        @media (min-width: 1200px) {
            .main-content {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <?php 
                $settings = fetchRow("SELECT * FROM site_settings LIMIT 1");
                $sidebarLogoUrl = $settings['logo_url'] ?? '';
                
                // Fix path for super_admin directory
                if (!empty($sidebarLogoUrl)) {
                    if (strpos($sidebarLogoUrl, 'uploads/') === 0) {
                        $sidebarLogoUrl = '../' . $sidebarLogoUrl;
                    }
                }
                ?>
                
                <?php if (!empty($sidebarLogoUrl)): ?>
                    <img src="<?= htmlspecialchars($sidebarLogoUrl) ?>" alt="ORDIVO" 
                         style="height: 90px; width: auto; vertical-align: middle;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                    <i class="fas fa-utensils" style="display: none; font-size: 2rem;"></i>
                <?php else: ?>
                    <i class="fas fa-utensils" style="font-size: 2rem;"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-subtitle">Super Admin Panel</div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>User Management
                </a>
            </div>
            <div class="nav-item">
                <a href="vendors.php" class="nav-link">
                    <i class="fas fa-store"></i>Vendor Management
                </a>
            </div>
            <div class="nav-item">
                <a href="products_featured.php" class="nav-link">
                    <i class="fas fa-star"></i>Featured Products
                </a>
            </div>
            <div class="nav-item">
                <a href="categories.php" class="nav-link">
                    <i class="fas fa-tags"></i>Categories
                </a>
            </div>
            <div class="nav-item">
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>Orders
                </a>
            </div>
            <div class="nav-item">
                <a href="analytics.php" class="nav-link active">
                    <i class="fas fa-chart-bar"></i>Analytics
                </a>
            </div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>Settings
                </a>
            </div>
            <div class="nav-item mt-4">
                <a href="../auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <button class="sidebar-toggle-inline" id="sidebarToggleInline">
                <i class="fas fa-bars"></i>
            </button>
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-chart-bar me-2"></i>Analytics Dashboard
                </h1>
                <p class="page-subtitle">Comprehensive business insights and performance metrics</p>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $startDate ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $endDate ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Apply Filter
                        </button>
                        <button type="button" class="btn btn-outline-secondary ms-2" onclick="resetDateRange()">
                            <i class="fas fa-undo me-2"></i>Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-6 col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-success">৳<?= number_format($stats['total_revenue'], 0) ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-primary"><?= number_format($stats['total_orders']) ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-info"><?= number_format($stats['new_users']) ?></div>
                    <div class="stat-label">New Users</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-warning">৳<?= number_format($stats['avg_order_value'], 0) ?></div>
                    <div class="stat-label">Avg Order Value</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Revenue Chart -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Revenue Trend
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Status Chart -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Order Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Revenue & Top Vendors -->
        <div class="row mb-4">
            <!-- Monthly Revenue -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Monthly Revenue (Last 12 Months)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Vendors -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>Top Vendors
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($topVendors)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-store fa-2x mb-3"></i><br>
                                No vendor data available
                            </div>
                        <?php else: ?>
                            <?php foreach ($topVendors as $index => $vendor): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($vendor['vendor_name']) ?></div>
                                        <small class="text-muted"><?= $vendor['total_orders'] ?> orders</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success">৳<?= number_format($vendor['total_revenue'], 0) ?></div>
                                        <div class="progress" style="width: 100px;">
                                            <div class="progress-bar bg-success" 
                                                 style="width: <?= $index === 0 ? 100 : (($vendor['total_revenue'] / $topVendors[0]['total_revenue']) * 100) ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Growth -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>User Growth
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Mobile menu toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarToggleInline = document.getElementById('sidebarToggleInline');

        function toggleSidebar() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        }

        if (sidebarToggleInline) {
            sidebarToggleInline.addEventListener('click', toggleSidebar);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = <?= json_encode($revenueData) ?>;
        
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueData.map(item => item.date),
                datasets: [{
                    label: 'Revenue (৳)',
                    data: revenueData.map(item => parseFloat(item.revenue)),
                    borderColor: '#f97316',
                    backgroundColor: '#f97316',
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

        // Order Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusData = <?= json_encode($orderStatus) ?>;
        
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1)),
                datasets: [{
                    data: statusData.map(item => parseInt(item.count)),
                    backgroundColor: [
                        '#ffc107', // pending
                        '#17a2b8', // confirmed
                        '#007bff', // preparing
                        '#28a745', // ready/delivered
                        '#dc3545'  // cancelled
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Monthly Revenue Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?= json_encode($monthlyRevenue) ?>;
        
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: monthlyData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Revenue (৳)',
                    data: monthlyData.map(item => parseFloat(item.revenue)),
                    backgroundColor: '#f97316',
                    borderColor: '#f97316',
                    borderWidth: 1
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

        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        const userGrowthData = <?= json_encode($userGrowth) ?>;
        
        // Process data for chart
        const dates = [...new Set(userGrowthData.map(item => item.date))];
        const roles = [...new Set(userGrowthData.map(item => item.role))];
        
        const datasets = roles.map((role, index) => {
            const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8'];
            return {
                label: role.charAt(0).toUpperCase() + role.slice(1),
                data: dates.map(date => {
                    const item = userGrowthData.find(d => d.date === date && d.role === role);
                    return item ? parseInt(item.count) : 0;
                }),
                borderColor: colors[index % colors.length],
                backgroundColor: colors[index % colors.length] + '20',
                borderWidth: 2,
                fill: false
            };
        });
        
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        function resetDateRange() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            
            document.getElementById('start_date').value = firstDay.toISOString().split('T')[0];
            document.getElementById('end_date').value = today.toISOString().split('T')[0];
        }
    </script>
    </div><!-- End Main Content -->
</body>
</html>