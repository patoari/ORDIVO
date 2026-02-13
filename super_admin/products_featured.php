<?php
/**
 * ORDIVO - Featured Products Management
 * Super Admin can set/unset featured products
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle actions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'toggle_featured':
            try {
                $productId = (int)$_POST['product_id'];
                $currentStatus = (int)$_POST['current_status'];
                $newStatus = $currentStatus ? 0 : 1;
                
                updateData('products', ['is_featured' => $newStatus], 'id = ?', [$productId]);
                
                $success = $newStatus ? 'Product marked as featured!' : 'Product removed from featured!';
            } catch (Exception $e) {
                $error = 'Failed to update featured status: ' . $e->getMessage();
            }
            break;
            
        case 'bulk_featured':
            try {
                $productIds = $_POST['product_ids'] ?? [];
                $action_type = $_POST['bulk_action'] ?? '';
                
                if (empty($productIds)) {
                    throw new Exception('No products selected');
                }
                
                $newStatus = ($action_type === 'set_featured') ? 1 : 0;
                
                global $pdo;
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                $stmt = $pdo->prepare("UPDATE products SET is_featured = ? WHERE id IN ($placeholders)");
                $stmt->execute(array_merge([$newStatus], $productIds));
                
                $success = count($productIds) . ' products updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update products: ' . $e->getMessage();
            }
            break;
    }
}

// Get filters
$search = sanitizeInput($_GET['search'] ?? '');
$vendorFilter = (int)($_GET['vendor'] ?? 0);
$categoryFilter = (int)($_GET['category'] ?? 0);
$featuredFilter = sanitizeInput($_GET['featured'] ?? '');

// Pagination settings
$itemsPerPage = 20;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Build query
$whereConditions = ["1=1"];
$params = [];

if ($search) {
    $whereConditions[] = "p.name LIKE ?";
    $params[] = "%$search%";
}

if ($vendorFilter) {
    $whereConditions[] = "p.vendor_id = ?";
    $params[] = $vendorFilter;
}

if ($categoryFilter) {
    $whereConditions[] = "p.category_id = ?";
    $params[] = $categoryFilter;
}

if ($featuredFilter !== '') {
    $whereConditions[] = "p.is_featured = ?";
    $params[] = (int)$featuredFilter;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

try {
    // Get total count for pagination
    $totalProducts = fetchValue("SELECT COUNT(*) FROM products p $whereClause", $params);
    $totalPages = ceil($totalProducts / $itemsPerPage);
    
    // Add pagination parameters
    $paginationParams = array_merge($params, [$itemsPerPage, $offset]);
    
    $products = fetchAll("
        SELECT 
            p.*,
            v.name as vendor_name,
            c.name as category_name,
            u.name as owner_name
        FROM products p
        LEFT JOIN vendors v ON p.vendor_id = v.id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON v.owner_id = u.id
        $whereClause
        ORDER BY p.is_featured DESC, p.created_at DESC
        LIMIT ? OFFSET ?
    ", $paginationParams);
    
    // Get statistics
    $stats = [
        'total' => fetchValue("SELECT COUNT(*) FROM products"),
        'featured' => fetchValue("SELECT COUNT(*) FROM products WHERE is_featured = 1"),
        'available' => fetchValue("SELECT COUNT(*) FROM products WHERE is_available = 1"),
        'trending' => fetchValue("SELECT COUNT(*) FROM products WHERE is_trending = 1")
    ];
    
    // Get vendors for filter
    $vendors = fetchAll("SELECT v.id, v.name FROM vendors v ORDER BY v.name");
    
    // Get categories for filter
    $categories = fetchAll("SELECT id, name FROM categories ORDER BY name");
    
} catch (Exception $e) {
    error_log("Featured Products Error: " . $e->getMessage());
    $products = [];
    $stats = ['total' => 0, 'featured' => 0, 'available' => 0, 'trending' => 0];
    $vendors = [];
    $categories = [];
    $totalPages = 0;
    $currentPage = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Products - ORDIVO Super Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-light: #f0fdf4;
            --ordivo-dark: #374151;
            --ordivo-accent: #f97316;
            --sidebar-width: 280px;
            --primary: #10b981;
            --secondary: #059669;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Mobile First - Sidebar hidden by default on mobile */
        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
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

        /* Main Content - Mobile First */
        .main-content {
            margin-left: 0;
            min-height: 100vh;
            padding: 0.625rem 1rem 1rem;
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
            display: none;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin: 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 10px #e5e7eb;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px #e5e7eb;
        }

        .card-header {
            padding: 0.75rem;
            flex-wrap: nowrap !important;
        }

        .card-header h5 {
            font-size: 0.85rem;
            white-space: nowrap;
            margin-right: 0.5rem;
        }

        .card-header .btn {
            font-size: 0.7rem;
            padding: 0.25rem 0.4rem;
            white-space: nowrap;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        /* Tablet and up */
        @media (min-width: 768px) {
            .card-header {
                padding: 1rem;
            }

            .card-header h5 {
                font-size: 1.25rem;
            }

            .card-header .btn {
                font-size: 0.875rem;
                padding: 0.375rem 0.75rem;
            }

            .sidebar-toggle-inline {
                display: none;
            }

            .sidebar {
                left: 0;
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
                margin-bottom: 1.5rem;
            }

            .page-title {
                font-size: 1.8rem;
                white-space: normal;
            }

            .page-subtitle {
                display: block;
                font-size: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-value {
                font-size: 2.5rem;
            }

            .stat-label {
                font-size: 0.9rem;
            }
        }

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
                <a href="products_featured.php" class="nav-link active">
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
                <a href="analytics.php" class="nav-link">
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
                <h1 class="page-title"><i class="fas fa-star me-2"></i>Featured Products Management</h1>
                <p class="page-subtitle">Set and manage featured products for homepage display</p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-3">
                <div class="stat-card" style="border-left-color: #ffd700;">
                    <div class="stat-value" style="color: #ffd700;"><?= number_format($stats['featured']) ?></div>
                    <div class="stat-label">Featured Products</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-3">
                <div class="stat-card" style="border-left-color: #28a745;">
                    <div class="stat-value" style="color: #28a745;"><?= number_format($stats['available']) ?></div>
                    <div class="stat-label">Available</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-3">
                <div class="stat-card" style="border-left-color: #ff6b6b;">
                    <div class="stat-value" style="color: #ff6b6b;"><?= number_format($stats['trending']) ?></div>
                    <div class="stat-label">Trending</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Product name...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Vendor</label>
                        <select class="form-select" name="vendor">
                            <option value="">All Vendors</option>
                            <?php foreach ($vendors as $vendor): ?>
                                <option value="<?= $vendor['id'] ?>" <?= $vendorFilter == $vendor['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($vendor['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $categoryFilter == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Featured</label>
                        <select class="form-select" name="featured">
                            <option value="">All</option>
                            <option value="1" <?= $featuredFilter === '1' ? 'selected' : '' ?>>Featured Only</option>
                            <option value="0" <?= $featuredFilter === '0' ? 'selected' : '' ?>>Non-Featured</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Products (<?= count($products) ?>)</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-warning" onclick="bulkAction('set_featured')">
                        <i class="fas fa-star me-1"></i>Set Featured
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="bulkAction('remove_featured')">
                        <i class="fas fa-star-half-alt me-1"></i>Remove Featured
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <form id="bulkForm" method="POST">
                    <input type="hidden" name="action" value="bulk_featured">
                    <input type="hidden" name="bulk_action" id="bulk_action">
                    
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectAll" onclick="toggleAll(this)">
                                    </th>
                                    <th>Image</th>
                                    <th>Product Name</th>
                                    <th>Vendor</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Featured</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No products found</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="product_ids[]" value="<?= $product['id'] ?>" class="product-checkbox">
                                            </td>
                                            <td>
                                                <?php if (!empty($product['image'])): ?>
                                                    <?php 
                                                    // Fix image path for super_admin directory
                                                    $imagePath = $product['image'];
                                                    if (strpos($imagePath, 'uploads/') === 0) {
                                                        $imagePath = '../' . $imagePath;
                                                    } elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $imagePath)) {
                                                        $imagePath = '../uploads/images/' . $imagePath;
                                                    }
                                                    ?>
                                                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="Product" class="product-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div class="product-image bg-light d-flex align-items-center justify-content-center" style="display: none;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                                                <small class="text-muted">SKU: <?= htmlspecialchars($product['sku'] ?? 'N/A') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($product['vendor_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($product['category_name'] ?? 'N/A') ?></td>
                                            <td>
                                                <strong>৳<?= number_format($product['price'], 2) ?></strong>
                                                <?php if ($product['discounted_price']): ?>
                                                    <br><small class="text-success">৳<?= number_format($product['discounted_price'], 2) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($product['is_available']): ?>
                                                    <span class="badge bg-success">Available</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Unavailable</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($product['is_featured']): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-star"></i> Featured
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark">Not Featured</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="toggle_featured">
                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                    <input type="hidden" name="current_status" value="<?= $product['is_featured'] ?>">
                                                    <button type="submit" class="btn btn-sm <?= $product['is_featured'] ? 'btn-warning' : 'btn-outline-warning' ?>">
                                                        <i class="fas fa-star"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Products pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- Previous Button -->
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $vendorFilter ? '&vendor=' . $vendorFilter : '' ?><?= $categoryFilter ? '&category=' . $categoryFilter : '' ?><?= $featuredFilter !== '' ? '&featured=' . $featuredFilter : '' ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <!-- Page Numbers -->
                    <?php
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    
                    if ($startPage > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=1<?= $search ? '&search=' . urlencode($search) : '' ?><?= $vendorFilter ? '&vendor=' . $vendorFilter : '' ?><?= $categoryFilter ? '&category=' . $categoryFilter : '' ?><?= $featuredFilter !== '' ? '&featured=' . $featuredFilter : '' ?>">1</a></li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $vendorFilter ? '&vendor=' . $vendorFilter : '' ?><?= $categoryFilter ? '&category=' . $categoryFilter : '' ?><?= $featuredFilter !== '' ? '&featured=' . $featuredFilter : '' ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $totalPages ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $vendorFilter ? '&vendor=' . $vendorFilter : '' ?><?= $categoryFilter ? '&category=' . $categoryFilter : '' ?><?= $featuredFilter !== '' ? '&featured=' . $featuredFilter : '' ?>"><?= $totalPages ?></a></li>
                    <?php endif; ?>

                    <!-- Next Button -->
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $vendorFilter ? '&vendor=' . $vendorFilter : '' ?><?= $categoryFilter ? '&category=' . $categoryFilter : '' ?><?= $featuredFilter !== '' ? '&featured=' . $featuredFilter : '' ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
                <div class="text-center text-muted mt-2">
                    <small>Showing <?= count($products) ?> of <?= $totalProducts ?> products (Page <?= $currentPage ?> of <?= $totalPages ?>)</small>
                </div>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile menu toggle
        const sidebarToggleInline = document.getElementById('sidebarToggleInline');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (sidebarToggleInline) {
            sidebarToggleInline.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
        }

        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                }
            });
        });

        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = source.checked);
        }
        
        function bulkAction(action) {
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one product');
                return;
            }
            
            if (confirm(`${action === 'set_featured' ? 'Set' : 'Remove'} featured status for ${checkboxes.length} product(s)?`)) {
                document.getElementById('bulk_action').value = action;
                document.getElementById('bulkForm').submit();
            }
        }
    </script>
    </div><!-- End Main Content -->
</body>
</html>
