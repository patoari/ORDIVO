<?php
/**
 * ORDIVO - Product Details Page
 * Detailed view of a single product with all information
 */

require_once '../config/db_connection.php';

// Get product ID from URL
$productId = (int)($_GET['id'] ?? 0);

if (!$productId) {
    header('Location: products.php?error=invalid_product');
    exit;
}

// Get site settings
try {
    $siteSettings = fetchRow("SELECT * FROM site_settings WHERE id = 1") ?? [];
    $siteLogo = $siteSettings['logo_url'] ?? '';
    $siteName = $siteSettings['site_name'] ?? 'ORDIVO';
    
    // Fix logo path for customer directory
    if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️') {
        if (strpos($siteLogo, 'uploads/') === 0) {
            $siteLogo = '../' . $siteLogo;
        }
        elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $siteLogo)) {
            $siteLogo = '../' . $siteLogo;
        }
    }
} catch (Exception $e) {
    $siteLogo = '';
    $siteName = 'ORDIVO';
}

// Get product details with vendor information
try {
    $product = fetchRow("
        SELECT p.*, 
               v.name as vendor_name,
               v.id as vendor_id,
               v.phone as vendor_phone,
               v.email as vendor_email,
               c.name as category_name,
               c.id as category_id
        FROM products p
        INNER JOIN users v ON p.vendor_id = v.id AND v.role = 'vendor' AND v.status = 'active'
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ", [$productId]);

    if (!$product) {
        // Product not found, show error page instead of redirecting
        $error_message = "Product not found or no longer available.";
    } else {
        // Get vendor profile information
        $vendorProfile = fetchRow("
            SELECT * FROM vendors WHERE owner_id = ?
        ", [$product['vendor_id']]) ?? [];

        // Get related products from same vendor
        $relatedProducts = fetchAll("
            SELECT p.*, v.name as vendor_name
            FROM products p
            INNER JOIN users v ON p.vendor_id = v.id AND v.role = 'vendor' AND v.status = 'active'
            WHERE p.vendor_id = ? AND p.id != ? AND p.is_available = 1
            ORDER BY RAND()
            LIMIT 6
        ", [$product['vendor_id'], $productId]);

        // Get products from same category
        $categoryProducts = fetchAll("
            SELECT p.*, v.name as vendor_name
            FROM products p
            INNER JOIN users v ON p.vendor_id = v.id AND v.role = 'vendor' AND v.status = 'active'
            WHERE p.category_id = ? AND p.id != ? AND p.is_available = 1
            ORDER BY RAND()
            LIMIT 6
        ", [$product['category_id'], $productId]);

        // Get reviews from database (if reviews table exists)
        $reviews = [];
        try {
            $reviews = fetchAll("
                SELECT r.*, u.name as customer_name 
                FROM reviews r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = ? 
                ORDER BY r.created_at DESC
            ", [$productId]);
        } catch (Exception $e) {
            // Reviews table doesn't exist or has different structure
            error_log("Reviews query failed: " . $e->getMessage());
            $reviews = [];
        }

        // Calculate average rating
        $totalRating = array_sum(array_column($reviews, 'rating'));
        $averageRating = count($reviews) > 0 ? $totalRating / count($reviews) : 0;
    }

} catch (Exception $e) {
    error_log("Product details error: " . $e->getMessage());
    $error_message = "Failed to load product details. Please try again.";
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $quantity = (int)($_POST['quantity'] ?? 1);
    $customizations = $_POST['customizations'] ?? '';
    
    // In a real application, you would add to database cart
    // For now, we'll use JavaScript to add to localStorage cart
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            addToCart({$productId}, '{$product['name']}', {$product['price']}, {$quantity}, '{$customizations}');
            showSuccess('Product added to cart successfully!');
        });
    </script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($error_message) ? 'Product Not Found' : htmlspecialchars($product['name']) ?> - <?= htmlspecialchars($siteName) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
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
            padding-top: 100px;
        }

        /* Header */
        .header {
            background: white;
            box-shadow: 0 2px 10px #e5e7eb;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 1rem 0;
        }

        .header .navbar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--ordivo-primary);
            text-decoration: none;
        }

        /* Breadcrumb */
        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
        }

        .breadcrumb-item a {
            color: var(--ordivo-primary);
            text-decoration: none;
        }

        .breadcrumb-item a:hover {
            text-decoration: underline;
        }

        .breadcrumb-item.active {
            color: #6c757d;
        }

        /* Product Image Gallery */
        .product-gallery {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            background: white;
            box-shadow: 0 4px 20px #e5e7eb;
        }

        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            cursor: zoom-in;
        }

        .image-thumbnails {
            display: flex;
            gap: 0.5rem;
            padding: 1rem;
            overflow-x: auto;
        }

        .thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .thumbnail.active,
        .thumbnail:hover {
            border-color: var(--ordivo-primary);
        }

        /* Product Info */
        .product-info {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px #e5e7eb;
            height: fit-content;
            position: sticky;
            top: 120px;
        }

        .product-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin-bottom: 1rem;
        }

        .product-price {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--ordivo-primary);
            margin-bottom: 1rem;
        }

        .vendor-info {
            background: var(--ordivo-light);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .vendor-name {
            font-weight: 600;
            color: var(--ordivo-dark);
            margin-bottom: 0.5rem;
        }

        .rating-section {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .stars {
            color: #ffc107;
        }

        .rating-text {
            color: #666;
            font-size: 0.9rem;
        }

        /* Quantity Selector */
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
        }

        .quantity-btn {
            background: none;
            border: none;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .quantity-btn:hover {
            background-color: #f8f9fa;
        }

        .quantity-input {
            border: none;
            text-align: center;
            width: 60px;
            padding: 0.5rem;
            font-weight: 600;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-primary {
            background: #10b981; 100%);
            border: none;
            border-radius: 10px;
            padding: 1rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px #f97316;
        }

        .btn-outline-primary {
            border: 2px solid var(--ordivo-primary);
            color: var(--ordivo-primary);
            border-radius: 10px;
            padding: 1rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--ordivo-primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Product Details Tabs */
        .product-details {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px #e5e7eb;
            margin-top: 2rem;
        }

        .nav-tabs {
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 2rem;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #666;
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-radius: 0;
            border-bottom: 3px solid transparent;
        }

        .nav-tabs .nav-link.active {
            color: var(--ordivo-primary);
            border-bottom-color: var(--ordivo-primary);
            background: none;
        }

        /* Reviews */
        .review-item {
            border-bottom: 1px solid #e9ecef;
            padding: 1.5rem 0;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .reviewer-name {
            font-weight: 600;
            color: var(--ordivo-dark);
        }

        .review-date {
            color: #666;
            font-size: 0.9rem;
        }

        .verified-badge {
            background: #28a745;
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            margin-left: 0.5rem;
        }

        /* Related Products */
        .related-products {
            margin-top: 3rem;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px #e5e7eb;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .product-card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-card-body {
            padding: 1rem;
        }

        .product-card-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--ordivo-dark);
        }

        .product-card-price {
            color: var(--ordivo-primary);
            font-weight: 700;
            font-size: 1.1rem;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            body {
                padding-top: 80px;
            }

            .product-title {
                font-size: 1.5rem;
            }

            .product-price {
                font-size: 2rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .product-info {
                position: static;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="navbar-brand">
                    <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                        <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" height="40" style="max-width: 120px;">
                    <?php else: ?>
                        <i class="fas fa-utensils" style="font-size: 2rem; color: #10b981;"></i>
                    <?php endif; ?>
                </a>
                
                <div class="d-flex align-items-center gap-3">
                    <a href="products.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Products
                    </a>
                    <a href="cart.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-shopping-cart me-1"></i>Cart (<span id="cartCount">0</span>)
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container my-4">
        <?php if (isset($error_message)): ?>
            <!-- Error Page -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card text-center">
                        <div class="card-body py-5">
                            <i class="fas fa-exclamation-triangle fa-4x text-warning mb-4"></i>
                            <h2 class="card-title">Product Not Found</h2>
                            <p class="card-text text-muted mb-4"><?= htmlspecialchars($error_message) ?></p>
                            <div class="d-flex gap-3 justify-content-center">
                                <a href="products.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Products
                                </a>
                                <a href="index.php" class="btn btn-outline-primary">
                                    <i class="fas fa-home me-2"></i>Go to Homepage
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                <?php if (!empty($product['category_name'])): ?>
                    <li class="breadcrumb-item">
                        <a href="products.php?category=<?= urlencode($product['category_name']) ?>">
                            <?= htmlspecialchars($product['category_name']) ?>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
            </ol>
        </nav>
        <div class="row">
            <!-- Product Images -->
            <div class="col-lg-6 mb-4">
                <div class="product-gallery">
                    <?php if (!empty($product['image'])): ?>
                        <?php 
                        // Check if image is a URL or local file
                        if (strpos($product['image'], 'http') === 0) {
                            $imageSrc = $product['image'];
                        } else {
                            $imageSrc = '../uploads/images/' . $product['image'];
                        }
                        ?>
                        <img src="<?= htmlspecialchars($imageSrc) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             class="main-image" id="mainImage"
                             onerror="this.src='../uploads/images/placeholder-food.svg';">
                    <?php else: ?>
                        <img src="../uploads/images/placeholder-food.svg" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             class="main-image" id="mainImage">
                    <?php endif; ?>
                    
                    <!-- Thumbnail images -->
                    <div class="image-thumbnails">
                        <?php if (!empty($product['image'])): ?>
                            <?php 
                            // Check if image is a URL or local file
                            if (strpos($product['image'], 'http') === 0) {
                                $thumbSrc = $product['image'];
                            } else {
                                $thumbSrc = '../uploads/images/' . $product['image'];
                            }
                            ?>
                            <img src="<?= htmlspecialchars($thumbSrc) ?>" 
                                 alt="Thumbnail 1" class="thumbnail active" 
                                 onclick="changeMainImage('<?= htmlspecialchars($thumbSrc) ?>')"
                                 onerror="this.src='../uploads/images/placeholder-food.svg';">
                        <?php else: ?>
                            <img src="../uploads/images/placeholder-food.svg" 
                                 alt="Thumbnail 1" class="thumbnail active" 
                                 onclick="changeMainImage('../uploads/images/placeholder-food.svg')">
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Product Information -->
            <div class="col-lg-6">
                <div class="product-info">
                    <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    
                    <div class="product-price">৳<?= number_format($product['price'], 0) ?></div>
                    
                    <!-- Vendor Information -->
                    <div class="vendor-info">
                        <div class="vendor-name">
                            <i class="fas fa-store me-2"></i><?= htmlspecialchars($product['vendor_name']) ?>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                        </small>
                    </div>

                    <!-- Rating -->
                    <div class="rating-section">
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i <= $averageRating ? '' : '-o' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text">
                            <?= number_format($averageRating, 1) ?> (<?= count($reviews) ?> reviews)
                        </span>
                    </div>

                    <!-- Quantity Selector -->
                    <div class="quantity-selector">
                        <label class="fw-bold">Quantity:</label>
                        <div class="quantity-controls">
                            <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="quantity-input" id="quantity" value="1" min="1" max="10">
                            <button type="button" class="quantity-btn" onclick="changeQuantity(1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Special Instructions -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Special Instructions (Optional):</label>
                        <textarea class="form-control" id="specialInstructions" rows="3" 
                                  placeholder="Any special requests or customizations..."></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button class="btn btn-primary flex-fill" onclick="addToCartFromDetails()">
                            <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                        </button>
                        <button class="btn btn-outline-primary" onclick="addToFavorites(<?= $productId ?>)">
                            <i class="fas fa-heart"></i>
                        </button>
                        <button class="btn btn-outline-secondary" onclick="shareProduct()">
                            <i class="fas fa-share-alt"></i>
                        </button>
                    </div>

                    <!-- Product Features -->
                    <div class="product-features">
                        <div class="row text-center">
                            <div class="col-4">
                                <i class="fas fa-shipping-fast text-success fa-2x mb-2"></i>
                                <div class="small">Fast Delivery</div>
                            </div>
                            <div class="col-4">
                                <i class="fas fa-shield-alt text-primary fa-2x mb-2"></i>
                                <div class="small">Quality Assured</div>
                            </div>
                            <div class="col-4">
                                <i class="fas fa-undo text-warning fa-2x mb-2"></i>
                                <div class="small">Easy Return</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Details Tabs -->
        <div class="product-details">
            <ul class="nav nav-tabs" id="productTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab" 
                            data-bs-target="#description" type="button" role="tab">
                        Description
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" 
                            data-bs-target="#reviews" type="button" role="tab">
                        Reviews (<?= count($reviews) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="vendor-tab" data-bs-toggle="tab" 
                            data-bs-target="#vendor" type="button" role="tab">
                        Vendor Info
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="productTabsContent">
                <!-- Description Tab -->
                <div class="tab-pane fade show active" id="description" role="tabpanel">
                    <h5>Product Description</h5>
                    <p><?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?></p>
                    
                    <?php if (!empty($product['ingredients'])): ?>
                        <h6 class="mt-4">Ingredients</h6>
                        <p><?= nl2br(htmlspecialchars($product['ingredients'])) ?></p>
                    <?php endif; ?>
                    
                    <h6 class="mt-4">Product Details</h6>
                    <ul class="list-unstyled">
                        <li><strong>SKU:</strong> <?= htmlspecialchars($product['sku'] ?? 'N/A') ?></li>
                        <li><strong>Category:</strong> <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></li>
                        <li><strong>Availability:</strong> 
                            <span class="badge bg-success">In Stock</span>
                        </li>
                        <li><strong>Added:</strong> <?= date('F j, Y', strtotime($product['created_at'])) ?></li>
                    </ul>
                </div>

                <!-- Reviews Tab -->
                <div class="tab-pane fade" id="reviews" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5>Customer Reviews</h5>
                        <button class="btn btn-outline-primary btn-sm">Write a Review</button>
                    </div>
                    
                    <!-- Overall Rating -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="display-4 fw-bold text-primary"><?= number_format($averageRating, 1) ?></div>
                                <div class="stars mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?= $i <= $averageRating ? '' : '-o' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="text-muted"><?= count($reviews) ?> reviews</div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <!-- Rating breakdown can be added here -->
                        </div>
                    </div>

                    <!-- Individual Reviews -->
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div>
                                    <span class="reviewer-name"><?= htmlspecialchars($review['customer_name']) ?></span>
                                    <?php if ($review['verified']): ?>
                                        <span class="verified-badge">Verified Purchase</span>
                                    <?php endif; ?>
                                </div>
                                <span class="review-date"><?= date('M j, Y', strtotime($review['date'])) ?></span>
                            </div>
                            <div class="stars mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-o' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="mb-0"><?= htmlspecialchars($review['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Vendor Tab -->
                <div class="tab-pane fade" id="vendor" role="tabpanel">
                    <h5><?= htmlspecialchars($product['vendor_name']) ?></h5>
                    <div class="row">
                        <div class="col-md-8">
                            <p><?= htmlspecialchars($vendorProfile['description'] ?? 'No description available.') ?></p>
                            
                            <h6>Contact Information</h6>
                            <ul class="list-unstyled">
                                <?php if (!empty($product['vendor_phone'])): ?>
                                    <li><i class="fas fa-phone me-2"></i><?= htmlspecialchars($product['vendor_phone']) ?></li>
                                <?php endif; ?>
                                <?php if (!empty($product['vendor_email'])): ?>
                                    <li><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($product['vendor_email']) ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <button class="btn btn-outline-primary mb-2 w-100" 
                                        onclick="window.location.href='vendor_profile.php?vendor=<?= $product['vendor_id'] ?>'">
                                    View All Products
                                </button>
                                <button class="btn btn-outline-secondary w-100">Contact Vendor</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="related-products">
                <h3 class="mb-4">More from <?= htmlspecialchars($product['vendor_name']) ?></h3>
                <div class="row">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <div class="product-card" onclick="window.location.href='product_details.php?id=<?= $relatedProduct['id'] ?>'">
                                <?php if (!empty($relatedProduct['image'])): ?>
                                    <?php 
                                    if (strpos($relatedProduct['image'], 'http') === 0) {
                                        $relatedImageSrc = $relatedProduct['image'];
                                    } else {
                                        $relatedImageSrc = '../uploads/images/' . $relatedProduct['image'];
                                    }
                                    ?>
                                    <img src="<?= htmlspecialchars($relatedImageSrc) ?>" 
                                         alt="<?= htmlspecialchars($relatedProduct['name']) ?>" 
                                         class="product-card-image"
                                         onerror="this.src='../uploads/images/placeholder-food.svg';">
                                <?php else: ?>
                                    <img src="../uploads/images/placeholder-food.svg" 
                                         alt="<?= htmlspecialchars($relatedProduct['name']) ?>" 
                                         class="product-card-image">
                                <?php endif; ?>
                                <div class="product-card-body">
                                    <div class="product-card-title"><?= htmlspecialchars($relatedProduct['name']) ?></div>
                                    <div class="product-card-price">৳<?= number_format($relatedProduct['price'], 0) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Similar Products -->
        <?php if (!empty($categoryProducts)): ?>
            <div class="related-products">
                <h3 class="mb-4">Similar Products</h3>
                <div class="row">
                    <?php foreach ($categoryProducts as $categoryProduct): ?>
                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <div class="product-card" onclick="window.location.href='product_details.php?id=<?= $categoryProduct['id'] ?>'">
                                <?php if (!empty($categoryProduct['image'])): ?>
                                    <?php 
                                    if (strpos($categoryProduct['image'], 'http') === 0) {
                                        $categoryImageSrc = $categoryProduct['image'];
                                    } else {
                                        $categoryImageSrc = '../uploads/images/' . $categoryProduct['image'];
                                    }
                                    ?>
                                    <img src="<?= htmlspecialchars($categoryImageSrc) ?>" 
                                         alt="<?= htmlspecialchars($categoryProduct['name']) ?>" 
                                         class="product-card-image"
                                         onerror="this.src='../uploads/images/placeholder-food.svg';">
                                <?php else: ?>
                                    <img src="../uploads/images/placeholder-food.svg" 
                                         alt="<?= htmlspecialchars($categoryProduct['name']) ?>" 
                                         class="product-card-image">
                                <?php endif; ?>
                                <div class="product-card-body">
                                    <div class="product-card-title"><?= htmlspecialchars($categoryProduct['name']) ?></div>
                                    <div class="product-card-price">৳<?= number_format($categoryProduct['price'], 0) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Sweet Alerts Configuration -->
    <script src="../assets/js/sweet-alerts.js"></script>

    <script>
        <?php if (!isset($error_message)): ?>
        // Product Details JavaScript
        let currentQuantity = 1;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });

        // Change main image
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Change quantity
        function changeQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            let newQuantity = parseInt(quantityInput.value) + change;
            
            if (newQuantity < 1) newQuantity = 1;
            if (newQuantity > 10) newQuantity = 10;
            
            quantityInput.value = newQuantity;
            currentQuantity = newQuantity;
        }

        // Add to cart from details page
        function addToCartFromDetails() {
            const quantity = parseInt(document.getElementById('quantity').value);
            const specialInstructions = document.getElementById('specialInstructions').value;
            
            const product = {
                id: <?= $productId ?>,
                name: '<?= addslashes($product['name']) ?>',
                price: <?= $product['price'] ?>,
                vendor_name: '<?= addslashes($product['vendor_name']) ?>',
                image: '<?= addslashes($product['image'] ?? '') ?>',
                specialInstructions: specialInstructions
            };

            addToCart(product.id, product.name, product.price, quantity, specialInstructions);
            showSuccess('Product added to cart successfully!');
        }

        // Add to favorites
        function addToFavorites(productId) {
            // Add to localStorage favorites
            let favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
            
            if (!favorites.includes(productId)) {
                favorites.push(productId);
                localStorage.setItem('favorites', JSON.stringify(favorites));
                showSuccess('Product added to favorites!');
            } else {
                showInfo('Product is already in favorites!');
            }
        }

        // Share product
        function shareProduct() {
            const productName = '<?= addslashes($product['name']) ?>';
            const productUrl = window.location.href;
            
            if (navigator.share) {
                // Use native sharing if available
                navigator.share({
                    title: productName + ' - ORDIVO',
                    text: 'Check out this amazing product on ORDIVO!',
                    url: productUrl
                }).catch(err => console.log('Error sharing:', err));
            } else {
                // Fallback to copying URL
                navigator.clipboard.writeText(productUrl).then(() => {
                    showSuccess('Product link copied to clipboard!');
                }).catch(() => {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = productUrl;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showSuccess('Product link copied to clipboard!');
                });
            }
        }

        // Cart management functions
        function addToCart(productId, productName, price, quantity = 1, specialInstructions = '') {
            try {
                let cart = JSON.parse(localStorage.getItem('ordivo_cart') || '[]');
                
                // Check if product already exists in cart
                const existingItemIndex = cart.findIndex(item => 
                    item.id === productId && item.specialInstructions === specialInstructions
                );
                
                if (existingItemIndex >= 0) {
                    // Update quantity
                    cart[existingItemIndex].quantity += quantity;
                } else {
                    // Add new item
                    cart.push({
                        id: productId,
                        name: productName,
                        price: price,
                        quantity: quantity,
                        specialInstructions: specialInstructions,
                        vendor_name: '<?= addslashes($product['vendor_name']) ?>',
                        image: '<?= addslashes($product['image'] ?? '') ?>'
                    });
                }
                
                localStorage.setItem('ordivo_cart', JSON.stringify(cart));
                updateCartCount();
                
            } catch (error) {
                console.error('Error adding to cart:', error);
                showError('Failed to add product to cart');
            }
        }

        function updateCartCount() {
            try {
                const cart = JSON.parse(localStorage.getItem('ordivo_cart') || '[]');
                const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                document.getElementById('cartCount').textContent = totalItems;
            } catch (error) {
                console.error('Error updating cart count:', error);
            }
        }

        // Image zoom functionality
        document.getElementById('mainImage').addEventListener('click', function() {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= addslashes($product['name']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="${this.src}" alt="<?= addslashes($product['name']) ?>" class="img-fluid">
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.removeChild(modal);
            });
        });

        // Quantity input validation
        document.getElementById('quantity').addEventListener('change', function() {
            let value = parseInt(this.value);
            if (isNaN(value) || value < 1) value = 1;
            if (value > 10) value = 10;
            this.value = value;
            currentQuantity = value;
        });
        <?php endif; ?>
    </script>
</body>
</html>