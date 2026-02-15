<?php
/**
 * ORDIVO - Vendor Product Management
 * Clean implementation without vendor_slug issues
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
    error_log("Error loading vendor info in vendor products: " . $e->getMessage());
    $vendorBusinessName = 'My Business';
    $vendorLogo = '';
}

// Get site settings for branding
$siteSettings = fetchRow("SELECT * FROM site_settings WHERE id = 1") ?? [];
$siteLogo = $siteSettings['logo_url'] ?? '';
$siteName = $siteSettings['site_name'] ?? 'ORDIVO';

// Fix logo path for vendor directory
if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️') {
    if (strpos($siteLogo, 'uploads/') === 0) {
        $siteLogo = '../' . $siteLogo;
    }
    elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $siteLogo)) {
        $siteLogo = '../' . $siteLogo;
    }
}

// Handle form submissions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_product':
            try {
                global $pdo;
                
                // Validate required fields
                if (empty($_POST['name']) || empty($_POST['price'])) {
                    throw new Exception('Product name and price are required');
                }
                
                // Handle image upload
                $imagePath = null;
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $imagePath = handleImageUpload($_FILES['product_image']);
                }
                
                // Prepare data
                $name = trim($_POST['name']);
                $description = trim($_POST['description'] ?? '');
                $price = (float)$_POST['price'];
                $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
                $sku = trim($_POST['sku'] ?? '');
                
                // Generate unique slug
                $baseSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
                if (empty($baseSlug)) {
                    $baseSlug = 'product';
                }
                
                // Make slug unique by adding timestamp
                $slug = $baseSlug . '-' . time();
                
                // Check if slug exists for this vendor, if so add random suffix
                $existingSlug = fetchValue("SELECT COUNT(*) FROM products WHERE vendor_id = ? AND slug = ?", [$vendorId, $slug]);
                if ($existingSlug > 0) {
                    $slug = $baseSlug . '-' . time() . '-' . rand(1000, 9999);
                }
                
                // Simple direct SQL insertion with slug
                $sql = "INSERT INTO products (vendor_id, name, slug, description, price, category_id, sku, image, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $vendorId,
                    $name,
                    $slug,
                    $description,
                    $price,
                    $categoryId,
                    $sku,
                    $imagePath
                ]);
                
                if ($result) {
                    $success = 'Product added successfully!';
                } else {
                    $error = 'Failed to add product';
                }
                
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
            break;
            
        case 'delete_product':
            try {
                global $pdo;
                
                $productId = (int)$_POST['product_id'];
                
                // Get product to delete image
                $product = fetchRow("SELECT image FROM products WHERE id = ? AND vendor_id = ?", [$productId, $vendorId]);
                
                // Delete product
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND vendor_id = ?");
                $result = $stmt->execute([$productId, $vendorId]);
                
                if ($result) {
                    // Delete image file if exists
                    if ($product && $product['image'] && file_exists('../uploads/images/' . $product['image'])) {
                        unlink('../uploads/images/' . $product['image']);
                    }
                    $success = 'Product deleted successfully!';
                } else {
                    $error = 'Failed to delete product';
                }
                
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
            break;
            
        case 'edit_product':
            try {
                global $pdo;
                
                $productId = (int)$_POST['product_id'];
                
                // Verify product belongs to this vendor
                $existingProduct = fetchRow("SELECT * FROM products WHERE id = ? AND vendor_id = ?", [$productId, $vendorId]);
                if (!$existingProduct) {
                    throw new Exception('Product not found or access denied');
                }
                
                // Validate required fields
                if (empty($_POST['name']) || empty($_POST['price'])) {
                    throw new Exception('Product name and price are required');
                }
                
                // Handle image upload
                $imagePath = $existingProduct['image']; // Keep existing image by default
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    // Delete old image if exists
                    if ($existingProduct['image'] && file_exists('../uploads/images/' . $existingProduct['image'])) {
                        unlink('../uploads/images/' . $existingProduct['image']);
                    }
                    $imagePath = handleImageUpload($_FILES['product_image']);
                }
                
                // Prepare data
                $name = trim($_POST['name']);
                $description = trim($_POST['description'] ?? '');
                $price = (float)$_POST['price'];
                $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
                $sku = trim($_POST['sku'] ?? '');
                $isAvailable = isset($_POST['is_available']) ? 1 : 0;
                $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
                
                // Update product
                $stmt = $pdo->prepare("
                    UPDATE products SET 
                        name = ?,
                        description = ?,
                        price = ?,
                        category_id = ?,
                        sku = ?,
                        image = ?,
                        is_available = ?,
                        is_featured = ?,
                        updated_at = NOW()
                    WHERE id = ? AND vendor_id = ?
                ");
                
                $result = $stmt->execute([
                    $name,
                    $description,
                    $price,
                    $categoryId,
                    $sku,
                    $imagePath,
                    $isAvailable,
                    $isFeatured,
                    $productId,
                    $vendorId
                ]);
                
                if ($result) {
                    $success = 'Product updated successfully!';
                } else {
                    throw new Exception('Failed to update product');
                }
                
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
            break;
    }
}

// Handle image upload
function handleImageUpload($file) {
    $uploadDir = '../uploads/images/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('File size too large. Maximum 5MB allowed.');
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'product_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    } else {
        throw new Exception('Failed to upload image.');
    }
}

// Get products for this vendor
try {
    $products = fetchAll("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.vendor_id = ? 
        ORDER BY p.created_at DESC
    ", [$vendorId]);
} catch (Exception $e) {
    $products = [];
    $error = 'Failed to load products: ' . $e->getMessage();
}

// Get categories for dropdown
try {
    $categories = fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
} catch (Exception $e) {
    $categories = [];
}

// Calculate statistics
$stats = [
    'total' => count($products),
    'active' => count($products), // Assuming all are active for now
    'pending' => 0,
    'low_stock' => 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - ORDIVO Vendor</title>
    
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
        }

        .header-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: relative;
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
            color: var(--ordivo-primary);
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
        }

        .btn-primary {
            background: #10b981; 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px #e5e7eb;
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .table th {
            border: none;
            background: #f8f9fa;
            font-weight: 600;
            padding: 1rem;
        }

        .table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }

        .table tbody tr {
            border-bottom: 1px solid #eee;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
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
                <a class="nav-link active" href="products.php">
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
                <a class="nav-link" href="categories.php">
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
                        <i class="fas fa-box me-2"></i>Product Management
                    </h1>
                    <p class="opacity-75">Manage your product catalog and inventory</p>
                </div>
                
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-2"></i>Add Product
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
                    <div class="stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['active']) ?></div>
                    <div class="stat-label">Active Products</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['pending']) ?></div>
                    <div class="stat-label">Pending Approval</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['low_stock']) ?></div>
                    <div class="stat-label">Low Stock</div>
                </div>
            </div>
        </div>

        <!-- Products List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Your Products
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Products Found</h4>
                        <p class="text-muted mb-4">Start building your product catalog</p>
                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus me-2"></i>Add Your First Product
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($product['image']): ?>
                                                    <img src="../uploads/images/<?= htmlspecialchars($product['image']) ?>" alt="Product" class="product-image me-3">
                                                <?php else: ?>
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($product['name']) ?></h6>
                                                    <?php if ($product['sku']): ?>
                                                        <small class="text-muted">SKU: <?= htmlspecialchars($product['sku']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></span>
                                        </td>
                                        <td>
                                            <strong>৳<?= number_format($product['price'], 0) ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= date('M j, Y', strtotime($product['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-primary btn-sm me-1" onclick="editProduct(<?= $product['id'] ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" onclick="deleteProduct(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
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

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_product">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sku" class="form-label">SKU</label>
                                <input type="text" class="form-control" id="sku" name="sku">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price (৳) *</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="product_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                            <small class="text-muted">Max 5MB. JPEG, PNG, GIF, WebP allowed.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="editProductForm">
                    <input type="hidden" name="action" value="edit_product">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_sku" class="form-label">SKU</label>
                                <input type="text" class="form-control" id="edit_sku" name="sku">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_category_id" class="form-label">Category</label>
                                <select class="form-select" id="edit_category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_price" class="form-label">Price (৳) *</label>
                                <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <div id="current_image_preview" class="mb-2"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_product_image" class="form-label">Change Product Image</label>
                            <input type="file" class="form-control" id="edit_product_image" name="product_image" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image. Max 5MB. JPEG, PNG, GIF, WebP allowed.</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_available" name="is_available" value="1">
                                    <label class="form-check-label" for="edit_is_available">
                                        Available for Sale
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_featured" name="is_featured" value="1">
                                    <label class="form-check-label" for="edit_is_featured">
                                        Featured Product
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
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

        // Product data for editing
        const productsData = <?= json_encode($products) ?>;
        
        function editProduct(productId) {
            const product = productsData.find(p => p.id == productId);
            if (!product) return;
            
            // Fill form fields
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_sku').value = product.sku || '';
            document.getElementById('edit_description').value = product.description || '';
            document.getElementById('edit_category_id').value = product.category_id || '';
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_is_available').checked = product.is_available == 1;
            document.getElementById('edit_is_featured').checked = product.is_featured == 1;
            
            // Show current image
            const imagePreview = document.getElementById('current_image_preview');
            if (product.image) {
                imagePreview.innerHTML = `<img src="../uploads/images/${product.image}" alt="Current Image" style="max-width: 200px; max-height: 200px;" class="img-thumbnail">`;
            } else {
                imagePreview.innerHTML = '<p class="text-muted">No image uploaded</p>';
            }
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
            modal.show();
        }
        
        function deleteProduct(productId, productName) {
            if (confirm(`Are you sure you want to delete "${productName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_product">
                    <input type="hidden" name="product_id" value="${productId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>