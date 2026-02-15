<?php
/**
 * ORDIVO - Vendor Categories Management
 * Manage product categories for vendor
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is vendor
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'vendor') {
    header('Location: ../auth/login.php');
    exit;
}

$vendorId = $_SESSION['user_id'];

// Get vendor business information
try {
    global $pdo;
    $stmt = $pdo->prepare("SELECT v.name, v.logo FROM vendors v WHERE v.owner_id = ? LIMIT 1");
    $stmt->execute([$vendorId]);
    $vendorInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $vendorBusinessName = $vendorInfo['name'] ?? 'My Business';
    $vendorLogo = $vendorInfo['logo'] ?? '';
    
    // Fix logo path for vendor directory - add ../ prefix if it's a relative path
    if (!empty($vendorLogo) && $vendorLogo !== '🍔' && $vendorLogo !== '🍽️') {
        if (strpos($vendorLogo, 'uploads/') === 0) {
            $vendorLogo = '../' . $vendorLogo;
        }
        elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $vendorLogo)) {
            $vendorLogo = '../' . $vendorLogo;
        }
    }
} catch (Exception $e) {
    error_log("Error loading vendor info in vendor categories: " . $e->getMessage());
    $vendorBusinessName = 'My Business';
    $vendorLogo = '';
}

$success = $error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_category':
            try {
                global $pdo;
                
                // Check what columns exist in categories table
                $stmt = $pdo->query("SHOW COLUMNS FROM categories");
                $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Generate slug from name
                $name = sanitizeInput($_POST['name']);
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
                
                // Make slug unique by adding number if needed
                $originalSlug = $slug;
                $counter = 1;
                while (true) {
                    $existingSlug = fetchRow("SELECT id FROM categories WHERE slug = ?", [$slug]);
                    if (!$existingSlug) {
                        break;
                    }
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                $data = [
                    'name' => $name,
                    'description' => sanitizeInput($_POST['description']),
                    'is_active' => 1
                ];
                
                // Add slug if column exists
                if (in_array('slug', $existingColumns)) {
                    $data['slug'] = $slug;
                }
                
                // Add vendor_id if column exists
                if (in_array('vendor_id', $existingColumns)) {
                    $data['vendor_id'] = $vendorId;
                }
                
                // Add created_at if column exists
                if (in_array('created_at', $existingColumns)) {
                    $data['created_at'] = date('Y-m-d H:i:s');
                }
                
                insertData('categories', $data);
                $success = 'Category added successfully!';
            } catch (Exception $e) {
                $error = 'Failed to add category: ' . $e->getMessage();
            }
            break;
            
        case 'update_category':
            try {
                global $pdo;
                
                $categoryId = (int)$_POST['category_id'];
                
                // Check what columns exist in categories table
                $stmt = $pdo->query("SHOW COLUMNS FROM categories");
                $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $name = sanitizeInput($_POST['name']);
                $data = [
                    'name' => $name,
                    'description' => sanitizeInput($_POST['description'])
                ];
                
                // Update slug if column exists and name changed
                if (in_array('slug', $existingColumns)) {
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
                    
                    // Make slug unique by adding number if needed (excluding current category)
                    $originalSlug = $slug;
                    $counter = 1;
                    while (true) {
                        $existingSlug = fetchRow("SELECT id FROM categories WHERE slug = ? AND id != ?", [$slug, $categoryId]);
                        if (!$existingSlug) {
                            break;
                        }
                        $slug = $originalSlug . '-' . $counter;
                        $counter++;
                    }
                    
                    $data['slug'] = $slug;
                }
                
                // Add updated_at if column exists
                if (in_array('updated_at', $existingColumns)) {
                    $data['updated_at'] = date('Y-m-d H:i:s');
                }
                
                // Check if vendor_id column exists for WHERE clause
                if (in_array('vendor_id', $existingColumns)) {
                    updateData('categories', $data, "id = ? AND vendor_id = ?", [$categoryId, $vendorId]);
                } else {
                    updateData('categories', $data, "id = ?", [$categoryId]);
                }
                
                $success = 'Category updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update category: ' . $e->getMessage();
            }
            break;
            
        case 'toggle_status':
            try {
                $categoryId = (int)$_POST['category_id'];
                $newStatus = (int)$_POST['status'];
                
                // Check if vendor_id column exists for WHERE clause
                $vendorIdExists = false;
                try {
                    $result = $pdo->query("SHOW COLUMNS FROM categories LIKE 'vendor_id'");
                    $vendorIdExists = $result->rowCount() > 0;
                } catch (Exception $e) {
                    // Column check failed, assume it doesn't exist
                }
                
                if ($vendorIdExists) {
                    updateData('categories', ['is_active' => $newStatus], "id = ? AND vendor_id = ?", [$categoryId, $vendorId]);
                } else {
                    updateData('categories', ['is_active' => $newStatus], "id = ?", [$categoryId]);
                }
                
                $success = 'Category status updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update category status: ' . $e->getMessage();
            }
            break;
            
        case 'delete_category':
            try {
                $categoryId = (int)$_POST['category_id'];
                
                // Check if category has products
                $productCountResult = fetchRow("SELECT COUNT(*) as count FROM products WHERE category_id = ?", [$categoryId]);
                $productCount = $productCountResult ? $productCountResult['count'] : 0;
                
                if ($productCount > 0) {
                    $error = 'Cannot delete category. It has ' . $productCount . ' products associated with it.';
                } else {
                    // Check if vendor_id column exists for WHERE clause
                    $vendorIdExists = false;
                    try {
                        $result = $pdo->query("SHOW COLUMNS FROM categories LIKE 'vendor_id'");
                        $vendorIdExists = $result->rowCount() > 0;
                    } catch (Exception $e) {
                        // Column check failed, assume it doesn't exist
                    }
                    
                    if ($vendorIdExists) {
                        deleteData('categories', "id = ? AND vendor_id = ?", [$categoryId, $vendorId]);
                    } else {
                        deleteData('categories', "id = ?", [$categoryId]);
                    }
                    
                    $success = 'Category deleted successfully!';
                }
            } catch (Exception $e) {
                $error = 'Failed to delete category: ' . $e->getMessage();
            }
            break;
    }
}

// Get categories with product counts
try {
    // First check if vendor_id column exists
    $vendorIdExists = false;
    try {
        $result = $pdo->query("SHOW COLUMNS FROM categories LIKE 'vendor_id'");
        $vendorIdExists = $result->rowCount() > 0;
    } catch (Exception $e) {
        // Column check failed, assume it doesn't exist
    }
    
    if ($vendorIdExists) {
        // Use vendor-specific query
        $categories = fetchAll("
            SELECT c.*, 
                   COUNT(p.id) as product_count,
                   COUNT(p.id) as active_products
            FROM categories c 
            LEFT JOIN products p ON c.id = p.category_id 
            WHERE c.vendor_id = ? 
            GROUP BY c.id 
            ORDER BY COALESCE(c.created_at, c.id) DESC
        ", [$vendorId]);
    } else {
        // Fallback: get all categories (for backward compatibility)
        $categories = fetchAll("
            SELECT c.*, 
                   COUNT(p.id) as product_count,
                   COUNT(p.id) as active_products,
                   1 as is_active,
                   NOW() as created_at
            FROM categories c 
            LEFT JOIN products p ON c.id = p.category_id 
            GROUP BY c.id 
            ORDER BY c.id DESC
        ");
    }
    
    // Get statistics
    $stats = [
        'total_categories' => count($categories),
        'active_categories' => count(array_filter($categories, fn($c) => ($c['is_active'] ?? 1) == 1)),
        'total_products' => array_sum(array_column($categories, 'product_count')),
        'categories_with_products' => count(array_filter($categories, fn($c) => $c['product_count'] > 0))
    ];
    
} catch (Exception $e) {
    $categories = [];
    $stats = ['total_categories' => 0, 'active_categories' => 0, 'total_products' => 0, 'categories_with_products' => 0];
    $error = 'Failed to load categories: ' . $e->getMessage() . ' - Please run fix_categories_table.php to update the database structure.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - ORDIVO Vendor</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/ordivo-responsive.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-accent: #f97316;
            --ordivo-light: #f0fdf4;
            --ordivo-dark: #1a1a1a;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }

        /* Mobile First - Sidebar hidden by default on mobile */
        .sidebar {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            width: 250px;
            position: fixed;
            left: -250px; /* Hidden by default on mobile */
            top: 0;
            z-index: 1000;
            transition: left 0.3s ease;
            overflow-y: auto;
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

        .sidebar .nav-link {
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #ffffff;
            color: #10b981;
            border-radius: 0.5rem;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active i {
            color: #10b981;
        }

        .main-content {
            margin-left: 0; /* No margin on mobile */
            padding: 0.625rem 1rem 1rem; /* 10px top, 1rem sides and bottom */
            transition: all 0.3s ease;
        }

        .header-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .header-card-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* Inline hamburger button for mobile - positioned in header card */
        .sidebar-toggle-inline {
            display: block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.3);
            border: 2px solid white;
            border-radius: 8px;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .sidebar-toggle-inline:hover {
            background: rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }

        .header-info {
            flex: 1;
            min-width: 0;
        }

        .header-info h1 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .header-info p {
            font-size: 0.875rem;
            margin-bottom: 0;
        }

        @media (max-width: 576px) {
            .header-card-content {
                flex-direction: column;
                align-items: stretch;
            }

            .sidebar-toggle-inline {
                width: 100%;
            }

            .header-info {
                width: 100%;
                text-align: center;
            }

            .header-card-content .btn {
                width: 100%;
            }
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px #e5e7eb;
            transition: all 0.3s ease;
            border: none;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px #e5e7eb;
        }

        .stat-value {
            font-size: 2.5rem;
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

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px #e5e7eb;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 15px 35px #e5e7eb;
        }

        .btn-primary {
            background: #10b981; 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px #e5e7eb;
        }

        .category-card {
            border-left: 4px solid var(--ordivo-primary);
            transition: all 0.3s ease;
        }

        .category-card:hover {
            border-left-width: 6px;
            transform: translateX(5px);
        }

        .category-card.inactive {
            opacity: 0.7;
            border-left-color: #6c757d;
        }

        .badge-status {
            font-size: 0.75rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }

        .table th {
            border: none;
            background: #f8f9fa;
            font-weight: 600;
            color: var(--ordivo-dark);
            padding: 1rem;
        }

        .table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }

        .table tbody tr {
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
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
                margin-left: 250px;
                padding: 1.5rem;
            }

            .header-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }

            .header-info h1 {
                font-size: 1.8rem;
            }

            .header-info p {
                font-size: 1rem;
            }
        }

        /* Desktop */
        @media (min-width: 1200px) {
            .main-content {
                padding: 20px;
            }
        }

        /* DataTable Controls - One Line Layout */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            display: inline-block;
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .row:first-child {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem;
        }

        .dataTables_wrapper .row:first-child > div {
            flex: 0 0 auto;
        }

        @media (min-width: 576px) {
            .dataTables_wrapper .dataTables_length {
                margin-right: auto;
            }
            
            .dataTables_wrapper .dataTables_filter {
                margin-left: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="p-4">
            <div class="d-flex align-items-center mb-4">
                <?php if (!empty($vendorLogo)): ?>
                    <img src="<?= htmlspecialchars($vendorLogo) ?>" alt="<?= htmlspecialchars($vendorBusinessName) ?>" 
                         style="height: 60px; width: 60px; object-fit: cover; border-radius: 10px; margin-right: 12px; background: white; padding: 5px;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="display: none; width: 60px; height: 60px; background: #ffffff; border-radius: 10px; align-items: center; justify-content: center; margin-right: 12px;">
                        <i class="fas fa-store fa-2x text-white"></i>
                    </div>
                <?php else: ?>
                    <div style="display: flex; width: 60px; height: 60px; background: #ffffff; border-radius: 10px; align-items: center; justify-content: center; margin-right: 12px;">
                        <i class="fas fa-store fa-2x text-white"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <h5 class="text-white mb-0" style="font-size: 1.1rem; font-weight: 600;"><?= htmlspecialchars($vendorBusinessName) ?></h5>
                    <small class="text-white-50">Vendor Portal</small>
                </div>
            </div>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="products.php">
                    <i class="fas fa-box me-2"></i>Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i>Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="inventory.php">
                    <i class="fas fa-boxes me-2"></i>Inventory
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="categories.php">
                    <i class="fas fa-tags me-2"></i>Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../kitchen/dashboard.php">
                    <i class="fas fa-utensils me-2"></i>Kitchen
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="staff.php">
                    <i class="fas fa-users me-2"></i>Staff
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link" href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header-card">
            <div class="header-card-content">
                <!-- Mobile Hamburger Button -->
                <button class="sidebar-toggle-inline" id="sidebarToggleInline">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="header-info">
                    <h1>
                        <i class="fas fa-tags me-2"></i>Categories Management
                    </h1>
                    <p class="opacity-75">Organize your products with categories</p>
                </div>
                
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-2"></i>Add Category
                </button>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-primary"><?= number_format($stats['total_categories']) ?></div>
                    <div class="stat-label">Total Categories</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-success"><?= number_format($stats['active_categories']) ?></div>
                    <div class="stat-label">Active Categories</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-info"><?= number_format($stats['total_products']) ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-warning"><?= number_format($stats['categories_with_products']) ?></div>
                    <div class="stat-label">Categories with Products</div>
                </div>
            </div>
        </div>

        <!-- Categories List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Your Categories
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($categories)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Categories Yet</h4>
                        <p class="text-muted mb-4">Start organizing your products by creating categories</p>
                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i>Create Your First Category
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table" id="categoriesTable">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-tag text-white"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($category['name']) ?></h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted"><?= htmlspecialchars($category['description'] ?: 'No description') ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-info me-2"><?= $category['product_count'] ?> Total</span>
                                                <span class="badge bg-success"><?= $category['active_products'] ?> Active</span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (isset($category['is_active'])): ?>
                                                <?php if ($category['is_active']): ?>
                                                    <span class="badge bg-success badge-status">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary badge-status">Inactive</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-success badge-status">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php if (isset($category['created_at']) && $category['created_at']): ?>
                                                    <?= date('M j, Y', strtotime($category['created_at'])) ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>', '<?= htmlspecialchars($category['description'] ?? '') ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (isset($category['is_active'])): ?>
                                                    <button class="btn btn-outline-<?= $category['is_active'] ? 'warning' : 'success' ?>" onclick="toggleStatus(<?= $category['id'] ?>, <?= $category['is_active'] ? 0 : 1 ?>)">
                                                        <i class="fas fa-<?= $category['is_active'] ? 'pause' : 'play' ?>"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($category['product_count'] == 0): ?>
                                                    <button class="btn btn-outline-danger" onclick="deleteCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_category">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., Fast Food, Beverages, Desserts">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief description of this category"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" id="edit_category_id" name="category_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable with pagination
            <?php if (!empty($categories)): ?>
            $('#categoriesTable').DataTable({
                pageLength: 10,
                order: [[4, 'desc']], // Sort by Created date descending
                language: {
                    search: "Search categories:",
                    lengthMenu: "Show _MENU_ categories per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ categories",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                columnDefs: [
                    { orderable: false, targets: [5] } // Disable sorting on Actions column
                ]
            });
            <?php endif; ?>

            // Mobile menu toggle
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebarToggleInline = document.getElementById('sidebarToggleInline');

            function toggleSidebar() {
                if (sidebar && sidebarOverlay) {
                    sidebar.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                }
            }

            if (sidebarToggleInline) {
                sidebarToggleInline.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleSidebar();
                });
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleSidebar();
                });
            }
        });

        function editCategory(id, name, description) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            
            const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            modal.show();
        }

        function toggleStatus(categoryId, newStatus) {
            const statusText = newStatus ? 'activate' : 'deactivate';
            
            if (confirm(`Are you sure you want to ${statusText} this category?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="category_id" value="${categoryId}">
                    <input type="hidden" name="status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteCategory(categoryId, categoryName) {
            if (confirm(`Are you sure you want to delete the category "${categoryName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="category_id" value="${categoryId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>