<?php
/**
 * ORDIVO - Product Management System
 * Complete product catalog management
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle product actions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    
    switch ($action) {
        case 'approve_product':
            if ($productId) {
                try {
                    // Check if status column exists
                    $statusColumnExists = fetchValue("SHOW COLUMNS FROM products LIKE 'status'");
                    if ($statusColumnExists) {
                        updateData('products', ['status' => 'active'], 'id = ?', [$productId]);
                        $success = 'Product approved successfully!';
                    } else {
                        $success = 'Product status feature not available - status column missing.';
                    }
                } catch (Exception $e) {
                    $error = 'Failed to approve product: ' . $e->getMessage();
                }
            }
            break;
            
        case 'reject_product':
            if ($productId) {
                try {
                    // Check if status column exists
                    $statusColumnExists = fetchValue("SHOW COLUMNS FROM products LIKE 'status'");
                    if ($statusColumnExists) {
                        updateData('products', ['status' => 'inactive'], 'id = ?', [$productId]);
                        $success = 'Product rejected successfully!';
                    } else {
                        $success = 'Product status feature not available - status column missing.';
                    }
                } catch (Exception $e) {
                    $error = 'Failed to reject product: ' . $e->getMessage();
                }
            }
            break;
            
        case 'delete_product':
            if ($productId) {
                try {
                    deleteData('products', 'id = ?', [$productId]);
                    $success = 'Product deleted successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to delete product: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Get products with filters
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$categoryFilter = sanitizeInput($_GET['category'] ?? '');
$vendorFilter = sanitizeInput($_GET['vendor_id'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');

$whereConditions = [];
$params = [];

// Check if status column exists
$statusColumnExists = false;
try {
    $statusColumnExists = fetchValue("SHOW COLUMNS FROM products LIKE 'status'");
} catch (Exception $e) {
    // Column doesn't exist
}

// Only add status filter if status column exists and status filter is provided
if ($statusFilter && $statusColumnExists) {
    $whereConditions[] = "p.status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter) {
    $whereConditions[] = "p.category_id = ?";
    $params[] = $categoryFilter;
}

if ($vendorFilter) {
    $whereConditions[] = "p.vendor_id = ?";
    $params[] = $vendorFilter;
}

if ($search) {
    $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ? OR v.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    // Check if products table exists
    $tableExists = fetchValue("SHOW TABLES LIKE 'products'");
    
    if ($tableExists) {
        // Check if status column exists
        $statusColumnExists = fetchValue("SHOW COLUMNS FROM products LIKE 'status'");
        
        if ($statusColumnExists) {
            $products = fetchAll("
                SELECT p.id, p.name, p.description, p.price, p.status, p.created_at,
                       v.name as vendor_name,
                       c.name as category_name
                FROM products p
                LEFT JOIN users v ON p.vendor_id = v.id
                LEFT JOIN categories c ON p.category_id = c.id
                $whereClause
                ORDER BY p.created_at DESC
                LIMIT 50
            ", $params);
            
            // Get statistics
            $stats = [
                'total' => fetchValue("SELECT COUNT(*) FROM products") ?: 0,
                'active' => fetchValue("SELECT COUNT(*) FROM products WHERE status = 'active'") ?: 0,
                'pending' => fetchValue("SELECT COUNT(*) FROM products WHERE status = 'pending'") ?: 0,
                'inactive' => fetchValue("SELECT COUNT(*) FROM products WHERE status = 'inactive'") ?: 0
            ];
        } else {
            // Fallback query without status column
            $products = fetchAll("
                SELECT p.id, p.name, p.description, p.price, 'active' as status, p.created_at,
                       v.name as vendor_name,
                       c.name as category_name
                FROM products p
                LEFT JOIN users v ON p.vendor_id = v.id
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.created_at DESC
                LIMIT 50
            ");
            
            // Get statistics without status
            $stats = [
                'total' => fetchValue("SELECT COUNT(*) FROM products") ?: 0,
                'active' => fetchValue("SELECT COUNT(*) FROM products") ?: 0,
                'pending' => 0,
                'inactive' => 0
            ];
        }
        
        // Get categories for filter
        try {
            $categoriesTableExists = fetchValue("SHOW TABLES LIKE 'categories'");
            if ($categoriesTableExists) {
                $statusColumnInCategories = fetchValue("SHOW COLUMNS FROM categories LIKE 'status'");
                if ($statusColumnInCategories) {
                    $categories = fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
                } else {
                    $categories = fetchAll("SELECT id, name FROM categories ORDER BY name");
                }
            } else {
                $categories = [];
            }
        } catch (Exception $e) {
            $categories = [];
        }
    } else {
        $products = [];
        $categories = [];
        $stats = ['total' => 0, 'active' => 0, 'pending' => 0, 'inactive' => 0];
    }
} catch (Exception $e) {
    $products = [];
    $categories = [];
    $stats = ['total' => 0, 'active' => 0, 'pending' => 0, 'inactive' => 0];
    $error = 'Failed to load products: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - ORDIVO Admin</title>
    
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

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px #f97316;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px #e5e7eb;
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

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            border-top: none;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }

        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }

        .price {
            font-weight: 700;
            color: var(--ordivo-primary);
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
                        <i class="fas fa-box me-3"></i>Product Management
                    </h1>
                    <p class="mb-0 opacity-75">Manage product catalog and vendor listings</p>
                </div>
                <a href="dashboard.php" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container">
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
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-primary"><?= number_format($stats['total']) ?></div>
                    <div class="stat-label">Total Products</div>
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
                    <div class="stat-value text-warning"><?= number_format($stats['pending']) ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-secondary"><?= number_format($stats['inactive']) ?></div>
                    <div class="stat-label">Inactive</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search Products</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Product name, description, or vendor..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="vendor_id" class="form-label">Vendor</label>
                        <select class="form-select" id="vendor_id" name="vendor_id">
                            <option value="">All Vendors</option>
                            <?php
                            try {
                                $vendors = fetchAll("SELECT v.id, v.name FROM vendors v INNER JOIN users u ON v.owner_id = u.id WHERE u.status = 'active' ORDER BY v.name");
                                foreach ($vendors as $vendor) {
                                    $selected = $vendorFilter == $vendor['id'] ? 'selected' : '';
                                    echo "<option value='{$vendor['id']}' $selected>" . htmlspecialchars($vendor['name']) . "</option>";
                                }
                            } catch (Exception $e) {
                                // Vendors table might not exist or have issues
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $categoryFilter == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-box me-2"></i>Products
                </h5>
                <div>
                    <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Vendor</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fas fa-box fa-2x mb-3"></i><br>
                                        <?= $tableExists ? 'No products found' : 'Products table not created yet. Products will appear here once vendors start adding items.' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
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
                                                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="Product" class="product-image me-3" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div class="product-image me-3" style="display: none;">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="product-image me-3">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                                    <?php if ($product['description']): ?>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars(substr($product['description'], 0, 50)) ?>
                                                            <?= strlen($product['description']) > 50 ? '...' : '' ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($product['vendor_name'] ?? 'Unknown') ?>
                                        </td>
                                        <td>
                                            <?php if ($product['category_name']): ?>
                                                <span class="badge bg-light text-dark">
                                                    <?= htmlspecialchars($product['category_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Uncategorized</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="price">৳<?= number_format($product['price'], 0) ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'active' => 'success',
                                                'pending' => 'warning',
                                                'inactive' => 'secondary'
                                            ];
                                            $statusColor = $statusColors[$product['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $statusColor ?>">
                                                <?= ucfirst($product['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= date('M j, Y', strtotime($product['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($product['status'] === 'pending'): ?>
                                                    <button class="btn btn-success" 
                                                            onclick="approveProduct(<?= $product['id'] ?>)"
                                                            title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-danger" 
                                                            onclick="rejectProduct(<?= $product['id'] ?>)"
                                                            title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-outline-primary" 
                                                        onclick="viewProductDetails(<?= $product['id'] ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <button class="btn btn-outline-danger" 
                                                        onclick="deleteProduct(<?= $product['id'] ?>)"
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function approveProduct(productId) {
            if (confirm('Are you sure you want to approve this product?')) {
                submitAction('approve_product', productId);
            }
        }

        function rejectProduct(productId) {
            if (confirm('Are you sure you want to reject this product?')) {
                submitAction('reject_product', productId);
            }
        }

        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                submitAction('delete_product', productId);
            }
        }

        function viewProductDetails(productId) {
            // This could open a modal or redirect to a detailed view
            alert('Product details view - Feature coming soon!');
        }

        function submitAction(action, productId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="${action}">
                <input type="hidden" name="product_id" value="${productId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>