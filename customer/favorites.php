<?php
/**
 * ORDIVO - Customer Favorites
 * Display customer's favorite restaurants and products
 */

require_once '../config/db_connection.php';

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

// For demo purposes, get some featured restaurants and products
try {
    $favoriteRestaurants = fetchAll("
        SELECT u.*, 
               COALESCE(u.business_name, u.name) as restaurant_name,
               u.rating,
               u.total_reviews,
               u.avg_delivery_time
        FROM users u 
        WHERE u.role = 'vendor' AND u.status = 'active' 
        ORDER BY u.featured DESC, u.rating DESC 
        LIMIT 6
    ");
    
    $favoriteProducts = fetchAll("
        SELECT p.*, 
               u.business_name as vendor_name,
               c.name as category_name
        FROM products p 
        INNER JOIN users u ON p.vendor_id = u.id AND u.role = 'vendor' AND u.status = 'active'
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.is_available = 1 
        ORDER BY RAND() 
        LIMIT 8
    ");
} catch (Exception $e) {
    $favoriteRestaurants = [];
    $favoriteProducts = [];
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
    <title>My Favorites - ORDIVO</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-accent: #f97316;
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
            height: 60px;
            margin-right: 12px;
            animation: logoFloat 3s ease-in-out infinite;
            transition: all 0.3s ease;
        }

        .logo-img:hover {
            transform: scale(1.1) rotate(5deg) !important;
            filter: brightness(1.1);
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

        .favorites-header {
            background: #10b981;);
            color: white;
            padding: 2rem 0;
        }

        .restaurant-card, .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px #e5e7eb;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            cursor: pointer;
        }

        .restaurant-card:hover, .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .restaurant-image, .product-image {
            height: 180px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            position: relative;
        }

        .favorite-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ffffff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ordivo-primary);
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .favorite-btn:hover {
            background: var(--ordivo-primary);
            color: white;
        }

        .favorite-btn.active {
            background: var(--ordivo-primary);
            color: white;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: #ffc107;
        }

        .btn-primary {
            background: var(--ordivo-primary);
            border: none;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
        }

        .empty-favorites {
            text-align: center;
            padding: 4rem 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            color: var(--ordivo-primary);
            border-bottom: 2px solid var(--ordivo-primary);
            padding-bottom: 0.5rem;
            display: inline-block;
        }

        .delivery-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .delivery-info span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
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
                            <i class="fas fa-utensils me-2 logo-icon"></i>
                        <?php endif; ?>
                        <span class="brand-text"><?= htmlspecialchars($siteName) ?></span>
                    </div>
                </a>
                
                <div class="d-flex align-items-center gap-3">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-home me-2"></i>Home
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

    <!-- Favorites Header -->
    <div class="favorites-header">
        <div class="container">
            <h1 class="display-5 mb-0">
                <i class="fas fa-heart me-3"></i>My Favorites
            </h1>
        </div>
    </div>

    <div class="container my-4">
        <!-- Success Message -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($favoriteRestaurants) && empty($favoriteProducts)): ?>
            <!-- Empty Favorites -->
            <div class="empty-favorites">
                <i class="fas fa-heart fa-4x text-muted mb-4"></i>
                <h3 class="text-muted mb-3">No favorites yet</h3>
                <p class="text-muted mb-4">Start adding your favorite restaurants and dishes to see them here!</p>
                <a href="index.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-utensils me-2"></i>Explore Restaurants
                </a>
            </div>
        <?php else: ?>
            <!-- Favorite Restaurants -->
            <?php if (!empty($favoriteRestaurants)): ?>
                <div class="mb-5">
                    <h2 class="section-title">Favorite Restaurants</h2>
                    <div class="row">
                        <?php foreach ($favoriteRestaurants as $restaurant): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="restaurant-card" onclick="window.location.href='vendor_profile.php?id=<?= $restaurant['id'] ?>'">
                                    <div class="restaurant-image">
                                        <button class="favorite-btn active" onclick="event.stopPropagation(); toggleFavorite('restaurant', <?= $restaurant['id'] ?>)">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                        <i class="fas fa-store fa-3x"></i>
                                    </div>
                                    <div class="p-3">
                                        <h5 class="mb-2"><?= htmlspecialchars($restaurant['restaurant_name']) ?></h5>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="rating">
                                                <i class="fas fa-star"></i>
                                                <span><?= number_format($restaurant['rating'] ?? 4.5, 1) ?></span>
                                                <small class="text-muted">(<?= $restaurant['total_reviews'] ?? 100 ?>+)</small>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= $restaurant['avg_delivery_time'] ?? 30 ?> min
                                            </small>
                                        </div>
                                        
                                        <div class="delivery-info">
                                            <span>
                                                <i class="fas fa-motorcycle"></i>
                                                ৳<?= number_format($restaurant['delivery_fee'] ?? 50, 0) ?> delivery
                                            </span>
                                            <span>
                                                <i class="fas fa-shopping-bag"></i>
                                                Min ৳<?= number_format($restaurant['min_order_amount'] ?? 100, 0) ?>
                                            </span>
                                        </div>
                                        
                                        <?php if ($restaurant['featured']): ?>
                                            <div class="mt-2">
                                                <span class="badge bg-primary">Featured</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Favorite Products -->
            <?php if (!empty($favoriteProducts)): ?>
                <div class="mb-5">
                    <h2 class="section-title">Favorite Dishes</h2>
                    <div class="row">
                        <?php foreach ($favoriteProducts as $product): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="product-card">
                                    <div class="product-image">
                                        <button class="favorite-btn active" onclick="toggleFavorite('product', <?= $product['id'] ?>)">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="../uploads/images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 100%; height: 180px; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="fas fa-utensils fa-3x"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-3">
                                        <h6 class="mb-2"><?= htmlspecialchars($product['name']) ?></h6>
                                        <small class="text-muted d-block mb-2"><?= htmlspecialchars($product['vendor_name'] ?? 'Restaurant') ?></small>
                                        <p class="text-muted small mb-2"><?= htmlspecialchars(substr($product['description'] ?? '', 0, 60)) ?>...</p>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="h6 mb-0 text-primary">৳<?= number_format($product['price'], 0) ?></span>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="add_to_cart">
                                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-plus me-1"></i>Add
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <?php if ($product['category_name']): ?>
                                            <div class="mt-2">
                                                <span class="badge bg-light text-dark"><?= htmlspecialchars($product['category_name']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleFavorite(type, id) {
            const btn = event.currentTarget;
            const icon = btn.querySelector('i');
            
            if (btn.classList.contains('active')) {
                // Remove from favorites
                btn.classList.remove('active');
                icon.classList.remove('fas');
                icon.classList.add('far');
                
                // In a real app, this would make an AJAX call to remove from favorites
                console.log(`Removed ${type} ${id} from favorites`);
                
                // Show notification
                showNotification(`Removed from favorites`, 'info');
                
                // Remove the card after animation
                setTimeout(() => {
                    btn.closest('.restaurant-card, .product-card').style.opacity = '0.5';
                }, 100);
                
            } else {
                // Add to favorites
                btn.classList.add('active');
                icon.classList.remove('far');
                icon.classList.add('fas');
                
                // In a real app, this would make an AJAX call to add to favorites
                console.log(`Added ${type} ${id} to favorites`);
                
                // Show notification
                showNotification(`Added to favorites`, 'success');
            }
        }
        
        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 250px;';
            notification.innerHTML = `
                <i class="fas fa-heart me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }
        
        // Initialize favorite states (in a real app, this would come from the database)
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Favorites page loaded');
        });
    </script>
</body>
</html>