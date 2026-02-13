<?php
/**
 * ORDIVO - Categories Management System
 * Complete category management for product organization
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle category actions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_category':
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $icon = sanitizeInput($_POST['icon'] ?? '');
            
            if ($name) {
                try {
                    // Handle image upload
                    $imagePath = null;
                    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../uploads/categories/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        $maxSize = 5 * 1024 * 1024; // 5MB
                        
                        if (!in_array($_FILES['category_image']['type'], $allowedTypes)) {
                            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.');
                        }
                        
                        if ($_FILES['category_image']['size'] > $maxSize) {
                            throw new Exception('File size too large. Maximum 5MB allowed.');
                        }
                        
                        $extension = pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION);
                        $filename = 'category_' . time() . '_' . uniqid() . '.' . $extension;
                        $filepath = $uploadDir . $filename;
                        
                        if (move_uploaded_file($_FILES['category_image']['tmp_name'], $filepath)) {
                            $imagePath = 'uploads/categories/' . $filename;
                        }
                    }
                    
                    // Check if status column exists
                    $statusColumnExists = fetchValue("SHOW COLUMNS FROM categories LIKE 'status'");
                    
                    // Check if image column exists
                    $imageColumnExists = fetchValue("SHOW COLUMNS FROM categories LIKE 'image'");
                    
                    $categoryData = [
                        'name' => $name,
                        'description' => $description,
                        'icon' => $icon,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($statusColumnExists) {
                        $categoryData['status'] = 'active';
                    }
                    
                    if ($imageColumnExists && $imagePath) {
                        $categoryData['image'] = $imagePath;
                    }
                    
                    insertData('categories', $categoryData);
                    $success = 'Category created successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to create category: ' . $e->getMessage();
                }
            } else {
                $error = 'Category name is required.';
            }
            break;
            
        case 'update_category':
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $icon = sanitizeInput($_POST['icon'] ?? '');
            $status = sanitizeInput($_POST['status'] ?? '');
            
            if ($categoryId && $name) {
                try {
                    // Handle image upload
                    $imagePath = null;
                    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../uploads/categories/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        $maxSize = 5 * 1024 * 1024; // 5MB
                        
                        if (!in_array($_FILES['category_image']['type'], $allowedTypes)) {
                            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.');
                        }
                        
                        if ($_FILES['category_image']['size'] > $maxSize) {
                            throw new Exception('File size too large. Maximum 5MB allowed.');
                        }
                        
                        $extension = pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION);
                        $filename = 'category_' . time() . '_' . uniqid() . '.' . $extension;
                        $filepath = $uploadDir . $filename;
                        
                        if (move_uploaded_file($_FILES['category_image']['tmp_name'], $filepath)) {
                            $imagePath = 'uploads/categories/' . $filename;
                            
                            // Delete old image if exists
                            $oldCategory = fetchRow("SELECT image FROM categories WHERE id = ?", [$categoryId]);
                            if ($oldCategory && !empty($oldCategory['image']) && file_exists('../' . $oldCategory['image'])) {
                                unlink('../' . $oldCategory['image']);
                            }
                        }
                    }
                    
                    // Check if status column exists
                    $statusColumnExists = fetchValue("SHOW COLUMNS FROM categories LIKE 'status'");
                    
                    // Check if image column exists
                    $imageColumnExists = fetchValue("SHOW COLUMNS FROM categories LIKE 'image'");
                    
                    $categoryData = [
                        'name' => $name,
                        'description' => $description,
                        'icon' => $icon,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($statusColumnExists) {
                        $categoryData['status'] = $status;
                    }
                    
                    if ($imageColumnExists && $imagePath) {
                        $categoryData['image'] = $imagePath;
                    }
                    
                    updateData('categories', $categoryData, 'id = ?', [$categoryId]);
                    $success = 'Category updated successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to update category: ' . $e->getMessage();
                }
            } else {
                $error = 'Category ID and name are required.';
            }
            break;
            
        case 'delete_category':
            $categoryId = (int)($_POST['category_id'] ?? 0);
            if ($categoryId) {
                try {
                    // Check if category has products
                    $productCount = fetchValue("SELECT COUNT(*) FROM products WHERE category_id = ?", [$categoryId]) ?: 0;
                    
                    if ($productCount > 0) {
                        $error = "Cannot delete category. It has $productCount products associated with it.";
                    } else {
                        deleteData('categories', 'id = ?', [$categoryId]);
                        $success = 'Category deleted successfully!';
                    }
                } catch (Exception $e) {
                    $error = 'Failed to delete category: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Get categories
try {
    // Check if categories table exists and what columns it has
    $categoriesTableExists = fetchValue("SHOW TABLES LIKE 'categories'");
    
    if ($categoriesTableExists) {
        // Check if status column exists
        $statusColumnExists = fetchValue("SHOW COLUMNS FROM categories LIKE 'status'");
        
        if ($statusColumnExists) {
            $categories = fetchAll("
                SELECT c.*, 
                       COUNT(p.id) as product_count
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id
                GROUP BY c.id
                ORDER BY c.created_at DESC
            ");
        } else {
            // Fallback query without status column
            $categories = fetchAll("
                SELECT c.*, 
                       'active' as status,
                       COUNT(p.id) as product_count
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id
                GROUP BY c.id
                ORDER BY c.created_at DESC
            ");
        }
        
        // Get statistics with safe array access
        $stats = [
            'total' => count($categories),
            'active' => count(array_filter($categories, fn($c) => ($c['status'] ?? 'active') === 'active')),
            'inactive' => count(array_filter($categories, fn($c) => ($c['status'] ?? 'active') === 'inactive')),
            'total_products' => array_sum(array_column($categories, 'product_count'))
        ];
    } else {
        $categories = [];
        $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'total_products' => 0];
    }
} catch (Exception $e) {
    $categories = [];
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'total_products' => 0];
    $error = 'Failed to load categories: ' . $e->getMessage();
}

// Popular icons for categories
$iconOptions = [
    'fas fa-utensils' => 'Food',
    'fas fa-pizza-slice' => 'Pizza',
    'fas fa-hamburger' => 'Burger',
    'fas fa-coffee' => 'Coffee',
    'fas fa-ice-cream' => 'Dessert',
    'fas fa-apple-alt' => 'Fruits',
    'fas fa-carrot' => 'Vegetables',
    'fas fa-bread-slice' => 'Bakery',
    'fas fa-fish' => 'Seafood',
    'fas fa-drumstick-bite' => 'Meat',
    'fas fa-cheese' => 'Dairy',
    'fas fa-wine-bottle' => 'Beverages',
    'fas fa-candy-cane' => 'Snacks',
    'fas fa-birthday-cake' => 'Cakes',
    'fas fa-seedling' => 'Organic'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - ORDIVO Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-accent: #f97316;
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

        /* Static Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
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
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 2rem;
        }

        .page-header {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin: 0;
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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

        .category-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .category-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--ordivo-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--ordivo-primary);
            margin: 0 auto 1rem;
        }

        .category-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .category-description {
            color: #6c757d;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .category-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }

        .icon-selector {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .icon-option {
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .icon-option:hover, .icon-option.selected {
            border-color: var(--ordivo-primary);
            background: var(--ordivo-light);
            color: var(--ordivo-primary);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="fas fa-utensils me-2"></i>ORDIVO
            </a>
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
                <a href="categories.php" class="nav-link active">
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
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-tags me-3"></i>Categories Management
                    </h1>
                    <p class="mb-0 text-muted">Organize products with categories and subcategories</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
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
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-success"><?= number_format($stats['active']) ?></div>
                    <div class="stat-label">Active</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-secondary"><?= number_format($stats['inactive']) ?></div>
                    <div class="stat-label">Inactive</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-info"><?= number_format($stats['total_products']) ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
        </div>

        <!-- Categories Grid -->
        <div class="row">
            <?php if (empty($categories)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No categories found</h5>
                            <p class="text-muted">Start by creating your first product category.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                                <i class="fas fa-plus me-2"></i>Create Category
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="category-card">
                            <?php if (!empty($category['image'])): ?>
                                <?php 
                                $categoryImagePath = $category['image'];
                                if (strpos($categoryImagePath, 'uploads/') === 0) {
                                    $categoryImagePath = '../' . $categoryImagePath;
                                }
                                ?>
                                <div class="category-image mb-3">
                                    <img src="<?= htmlspecialchars($categoryImagePath) ?>" alt="<?= htmlspecialchars($category['name']) ?>" style="width: 100%; height: 150px; object-fit: cover; border-radius: 10px;">
                                </div>
                            <?php else: ?>
                                <div class="category-icon">
                                    <i class="<?= htmlspecialchars($category['icon'] ?: 'fas fa-tag') ?>"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="category-name">
                                <?= htmlspecialchars($category['name']) ?>
                            </div>
                            
                            <?php if ($category['description']): ?>
                                <div class="category-description">
                                    <?= htmlspecialchars($category['description']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="category-stats">
                                <span class="badge bg-light text-dark">
                                    <?= $category['product_count'] ?> Products
                                </span>
                                <span class="badge bg-<?= ($category['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($category['status'] ?? 'active') ?>
                                </span>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm flex-fill" 
                                        onclick="editCategory(<?= htmlspecialchars(json_encode($category)) ?>)"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editCategoryModal">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                
                                <?php if ($category['product_count'] == 0): ?>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="deleteCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary btn-sm" 
                                            disabled
                                            title="Cannot delete category with products">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Category Modal -->
    <div class="modal fade" id="createCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_category">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="createName" class="form-label">Category Name *</label>
                                    <input type="text" class="form-control" id="createName" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="createDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="createDescription" name="description" rows="3"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="createImage" class="form-label">Category Image</label>
                                    <input type="file" class="form-control" id="createImage" name="category_image" accept="image/*">
                                    <small class="text-muted">Upload an image for this category (Max 5MB, JPEG/PNG/GIF/WebP)</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Select Icon</label>
                                    <div class="icon-selector">
                                        <?php foreach ($iconOptions as $iconClass => $iconName): ?>
                                            <div class="icon-option" onclick="selectIcon(this, '<?= $iconClass ?>')">
                                                <i class="<?= $iconClass ?>"></i>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="icon" id="createIcon">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_category">
                        <input type="hidden" name="category_id" id="editCategoryId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editName" class="form-label">Category Name *</label>
                                    <input type="text" class="form-control" id="editName" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="editDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="editImage" class="form-label">Category Image</label>
                                    <div id="currentImagePreview" class="mb-2" style="display: none;">
                                        <img id="currentImage" src="" alt="Current" style="max-width: 200px; height: auto; border-radius: 8px;">
                                        <p class="text-muted small mb-0">Current image</p>
                                    </div>
                                    <input type="file" class="form-control" id="editImage" name="category_image" accept="image/*">
                                    <small class="text-muted">Upload a new image to replace the current one (Max 5MB)</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="editStatus" class="form-label">Status</label>
                                    <select class="form-select" id="editStatus" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Select Icon</label>
                                    <div class="icon-selector" id="editIconSelector">
                                        <?php foreach ($iconOptions as $iconClass => $iconName): ?>
                                            <div class="icon-option" onclick="selectEditIcon(this, '<?= $iconClass ?>')">
                                                <i class="<?= $iconClass ?>"></i>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="icon" id="editIcon">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function selectIcon(element, iconClass) {
            // Remove selected class from all icons
            document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));
            // Add selected class to clicked icon
            element.classList.add('selected');
            // Set hidden input value
            document.getElementById('createIcon').value = iconClass;
        }

        function selectEditIcon(element, iconClass) {
            // Remove selected class from all icons in edit modal
            document.querySelectorAll('#editIconSelector .icon-option').forEach(el => el.classList.remove('selected'));
            // Add selected class to clicked icon
            element.classList.add('selected');
            // Set hidden input value
            document.getElementById('editIcon').value = iconClass;
        }

        function editCategory(category) {
            document.getElementById('editCategoryId').value = category.id;
            document.getElementById('editName').value = category.name;
            document.getElementById('editDescription').value = category.description || '';
            document.getElementById('editStatus').value = category.status || 'active';
            document.getElementById('editIcon').value = category.icon || '';
            
            // Show current image if exists
            if (category.image) {
                const imagePath = category.image.startsWith('uploads/') ? '../' + category.image : category.image;
                document.getElementById('currentImage').src = imagePath;
                document.getElementById('currentImagePreview').style.display = 'block';
            } else {
                document.getElementById('currentImagePreview').style.display = 'none';
            }
            
            // Select the current icon
            document.querySelectorAll('#editIconSelector .icon-option').forEach(el => {
                el.classList.remove('selected');
                if (el.onclick.toString().includes(category.icon)) {
                    el.classList.add('selected');
                }
            });
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
    </div><!-- End Main Content -->
</body>
</html>