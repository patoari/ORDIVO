<?php
/**
 * ORDIVO - Vendor Profile Page
 * Display vendor details and their products
 */

require_once '../config/db_connection.php';

// Get vendor ID from URL
$vendorId = (int)($_GET['id'] ?? 0);

if (!$vendorId) {
    header('Location: index.php?error=vendor_not_found');
    exit;
}

// Get site settings
$siteSettings = fetchRow("SELECT * FROM site_settings WHERE id = 1") ?? [];
$siteLogo = $siteSettings['logo_url'] ?? '';
$siteName = $siteSettings['site_name'] ?? 'ORDIVO';

// Fix logo path
if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️') {
    if (strpos($siteLogo, 'uploads/') === 0) {
        $siteLogo = '../' . $siteLogo;
    }
    elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $siteLogo)) {
        $siteLogo = '../' . $siteLogo;
    }
}

// Get vendor details
try {
    $vendor = fetchRow("SELECT * FROM users WHERE id = ? AND role = 'vendor'", [$vendorId]);
    if (!$vendor) {
        header('Location: index.php?error=vendor_not_found');
        exit;
    }
    
    // Get vendor profile data (logo and banner) - handle missing table gracefully
    $vendorProfile = null;
    try {
        $vendorProfile = fetchRow("SELECT logo, banner_image, name as business_name, description FROM vendors WHERE owner_id = ?", [$vendorId]);
    } catch (Exception $e) {
        // If vendors table doesn't exist, use defaults
        error_log("Vendor profile query error: " . $e->getMessage());
        $vendorProfile = [
            'logo' => $vendor['avatar'] ?? null,
            'banner_image' => $vendor['cover_photo'] ?? null,
            'business_name' => $vendor['name'],
            'description' => null
        ];
    }
    
    // Ensure we have some default values
    if (!$vendorProfile) {
        $vendorProfile = [
            'logo' => $vendor['avatar'] ?? null,
            'banner_image' => $vendor['cover_photo'] ?? null,
            'business_name' => $vendor['name'],
            'description' => null
        ];
    }
    
    // Fix image paths - add ../ prefix if needed
    if (!empty($vendorProfile['logo'])) {
        if (strpos($vendorProfile['logo'], 'uploads/') === 0) {
            $vendorProfile['logo'] = '../' . $vendorProfile['logo'];
        } elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $vendorProfile['logo'])) {
            $vendorProfile['logo'] = '../' . $vendorProfile['logo'];
        }
    }
    
    if (!empty($vendorProfile['banner_image'])) {
        if (strpos($vendorProfile['banner_image'], 'uploads/') === 0) {
            $vendorProfile['banner_image'] = '../' . $vendorProfile['banner_image'];
        } elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $vendorProfile['banner_image'])) {
            $vendorProfile['banner_image'] = '../' . $vendorProfile['banner_image'];
        }
    }
    
    // Get vendor's products
    $products = fetchAll("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.vendor_id = ? AND p.is_available = 1
        ORDER BY p.is_featured DESC, p.is_trending DESC, c.name, p.name
    ", [$vendorId]);
    
    // Group products by category
    $productsByCategory = [];
    foreach ($products as $product) {
        $categoryName = $product['category_name'] ?? 'Other';
        $productsByCategory[$categoryName][] = $product;
    }
    
} catch (Exception $e) {
    error_log("Vendor profile page error: " . $e->getMessage());
    header('Location: index.php?error=vendor_not_found');
    exit;
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $productId = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
    
    $success = 'Product added to cart!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($vendor['name']) ?> - ORDIVO</title>
    
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
            --ordivo-success: #28a745;
            --ordivo-warning: #ffc107;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }

        .header {
            background: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* Logo Animations */
        .logo-img {
            height: 100px;
            width: auto;
            margin-right: 12px;
            object-fit: contain;
            animation: logoFloat 3s ease-in-out infinite;
            transition: all 0.3s ease;
        }

        .logo-img:hover {
            transform: scale(1.15) rotate(5deg);
        }

        @keyframes logoFloat {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            25% {
                transform: translateY(-3px) rotate(1deg);
            }
            50% {
                transform: translateY(-5px) rotate(0deg);
            }
            75% {
                transform: translateY(-3px) rotate(-1deg);
            }
        }

        .logo-icon {
            color: var(--ordivo-primary);
            font-size: 2rem !important;
            animation: logoPulse 2s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        .brand-text {
            color: var(--ordivo-primary);
            font-size: 1.8rem;
            font-weight: 700;
            animation: brandGlow 4s ease-in-out infinite;
        }

        @keyframes brandGlow {
            0%, 100% {
                
            }
            50% {
                
            }
        }

        .vendor-hero {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 3rem 0;
            position: relative;
            overflow: hidden;
            min-height: 250px;
        }

        .vendor-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }

        .vendor-hero-content {
            position: relative;
            z-index: 2;
        }
        
        .vendor-hero h1,
        .vendor-hero .display-4 {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            font-weight: 700;
        }
        
        .vendor-hero .d-flex span,
        .vendor-hero .d-flex i {
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .vendor-cover-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }

        .vendor-profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            background: white;
        }

        .vendor-profile-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ordivo-primary);
            font-size: 2.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        }

        .vendor-info {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid var(--ordivo-primary);
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px #e5e7eb;
            border-color: var(--ordivo-secondary);
        }

        .product-image {
            height: 200px;
            background: linear-gradient(135deg, var(--ordivo-light) 0%, #f8f9fa 100%);
            position: relative;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .product-badge.featured {
            background: var(--ordivo-primary);
            color: white;
        }

        .product-badge.new {
            background: var(--ordivo-success);
            color: white;
        }

        .product-info {
            padding: 1rem;
        }

        .product-name {
            font-weight: 700;
            color: var(--ordivo-dark);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            line-height: 1.3;
        }

        .product-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--ordivo-primary);
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .rating-star {
            color: var(--ordivo-warning);
        }

        .add-to-cart-btn {
            background: var(--ordivo-primary);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: var(--ordivo-secondary);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--ordivo-primary);
            border: none;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
        }

        .category-section {
            margin-bottom: 3rem;
        }

        .category-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--ordivo-primary);
            border-bottom: 2px solid var(--ordivo-primary);
            padding-bottom: 0.5rem;
        }

        .cart-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--ordivo-primary);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 20px;
            box-shadow: 0 2px 8px #e5e7eb;
            z-index: 1000;
        }

        .cart-btn:hover {
            background: var(--ordivo-secondary);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="text-decoration-none">
                    <div class="d-flex align-items-center">
                        <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="logo-img">
                        <?php else: ?>
                            <i class="fas fa-utensils logo-icon"></i>
                        <?php endif; ?>
                    </div>
                </a>
                
                <div class="d-flex align-items-center gap-3">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                    <a href="cart.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Cart
                        <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                            <span class="badge bg-light text-dark"><?= array_sum($_SESSION['cart']) ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Vendor Hero -->
    <div class="vendor-hero">
        <?php if (!empty($vendorProfile['banner_image'])): ?>
            <img src="<?= htmlspecialchars($vendorProfile['banner_image']) ?>" 
                 alt="<?= htmlspecialchars($vendor['name']) ?> Cover" 
                 class="vendor-cover-image"
                 onerror="this.style.display='none'">
        <?php endif; ?>
        
        <div class="container vendor-hero-content">
            <div class="row align-items-center">
                <div class="col-md-2">
                    <?php if (!empty($vendorProfile['logo'])): ?>
                        <img src="<?= htmlspecialchars($vendorProfile['logo']) ?>" 
                             alt="<?= htmlspecialchars($vendor['name']) ?>" 
                             class="vendor-profile-image"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="vendor-profile-placeholder" style="display: none;">
                            <i class="fas fa-store"></i>
                        </div>
                    <?php else: ?>
                        <div class="vendor-profile-placeholder">
                            <i class="fas fa-store"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-10">
                    <h1 class="display-4 mb-3"><?= htmlspecialchars($vendorProfile['business_name'] ?? $vendor['name']) ?></h1>
                    <div class="d-flex align-items-center gap-4 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-star text-warning me-1"></i>
                            <span>4.5 (1,200+ reviews)</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock me-1"></i>
                            <span>25-40 min</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-motorcycle me-1"></i>
                            <span>Free delivery</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Info -->
    <div class="container">
        <div class="vendor-info">
            <div class="row">
                <div class="col-md-8">
                    <h3>About <?= htmlspecialchars($vendorProfile['business_name'] ?? $vendor['name']) ?></h3>
                    <p class="text-muted"><?= htmlspecialchars($vendorProfile['description'] ?? 'Delicious food delivered fresh to your door. We pride ourselves on quality ingredients and exceptional service.') ?></p>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-phone text-primary me-2"></i>
                        <span><?= htmlspecialchars($vendor['phone'] ?? 'Not available') ?></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-envelope text-primary me-2"></i>
                        <span><?= htmlspecialchars($vendor['email']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Products by Category -->
        <?php if (empty($productsByCategory)): ?>
            <div class="text-center py-5">
                <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No products available</h4>
                <p class="text-muted">This restaurant is currently updating their menu.</p>
            </div>
        <?php else: ?>
            <?php foreach ($productsByCategory as $categoryName => $categoryProducts): ?>
                <div class="category-section">
                    <h2 class="category-title"><?= htmlspecialchars($categoryName) ?></h2>
                    <div class="row">
                        <?php foreach ($categoryProducts as $product): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="product-card" onclick="window.location.href='product_details.php?id=<?= $product['id'] ?>'" style="cursor: pointer;">
                                    <div class="product-image">
                                        <?php if (!empty($product['image'])): ?>
                                            <?php if (strpos($product['image'], 'http') === 0): ?>
                                                <!-- External image URL -->
                                                <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='../uploads/images/placeholder-food.svg';">
                                            <?php else: ?>
                                                <!-- Local image file -->
                                                <img src="../uploads/images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='../uploads/images/placeholder-food.svg';">
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <img src="../uploads/images/placeholder-food.svg" alt="<?= htmlspecialchars($product['name']) ?>">
                                        <?php endif; ?>
                                        
                                        <!-- Product badges -->
                                        <?php if (!empty($product['is_featured']) && $product['is_featured'] == 1): ?>
                                            <div class="product-badge featured">Featured</div>
                                        <?php elseif (!empty($product['is_trending']) && $product['is_trending'] == 1): ?>
                                            <div class="product-badge new">Trending</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-info">
                                        <h5 class="product-name"><?= htmlspecialchars($product['name']) ?></h5>
                                        <p class="product-description"><?= htmlspecialchars($product['description'] ?? 'Delicious and freshly prepared with quality ingredients.') ?></p>
                                        
                                        <div class="product-meta">
                                            <span class="product-price">৳<?= number_format($product['price'], 0) ?></span>
                                            <div class="product-rating">
                                                <i class="fas fa-star rating-star"></i>
                                                <span><?= number_format($product['rating'] ?? 4.5, 1) ?></span>
                                                <span>(<?= $product['total_reviews'] ?? 0 ?>)</span>
                                            </div>
                                        </div>
                                        
                                        <form method="POST" class="d-inline w-100">
                                            <input type="hidden" name="action" value="add_to_cart">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="add-to-cart-btn" onclick="event.stopPropagation();">
                                                <i class="fas fa-plus me-2"></i>Add to Cart
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Floating Cart Button -->
    <a href="cart.php" class="cart-btn">
        <i class="fas fa-shopping-cart me-2"></i>
        Cart
        <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
            <span class="badge bg-light text-dark ms-2"><?= array_sum($_SESSION['cart']) ?></span>
        <?php endif; ?>
    </a>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>