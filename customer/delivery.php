<?php
/**
 * ORDIVO - Delivery Page
 * Dedicated page for food delivery services
 */

require_once '../config/db_connection.php';

// Get site settings for logo
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
    error_log("Error loading site settings: " . $e->getMessage());
    $siteLogo = '';
    $siteName = 'ORDIVO';
}

// Get parameters
$userLocation = $_SESSION['user_location'] ?? 'New address Road 71 Road 71, Dhaka, Bangladesh Dhaka';
$searchQuery = sanitizeInput($_GET['search'] ?? '');

// Handle AJAX requests for delivery data
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'delivery_restaurants':
            try {
                // Get delivery restaurants with fast delivery times
                $restaurants = fetchAll("
                    SELECT DISTINCT u.id, u.name as vendor_name, u.address, u.phone,
                           COUNT(p.id) as product_count,
                           AVG(p.rating) as avg_rating,
                           MIN(p.price) as min_price
                    FROM users u 
                    LEFT JOIN products p ON u.id = p.vendor_id AND p.is_available = 1
                    WHERE u.role = 'vendor' AND u.status = 'active'
                    GROUP BY u.id, u.name, u.address, u.phone
                    HAVING product_count > 0
                    ORDER BY avg_rating DESC, product_count DESC
                    LIMIT 20
                ");
                
                // Format for frontend
                $deliveryRestaurants = array_map(function($restaurant) {
                    return [
                        'id' => $restaurant['id'],
                        'name' => $restaurant['vendor_name'],
                        'address' => $restaurant['address'] ?? 'Dhaka, Bangladesh',
                        'rating' => round((float)($restaurant['avg_rating'] ?? 4.0) + (rand(1, 9) / 10), 1),
                        'reviews' => rand(100, 2000),
                        'delivery_time' => rand(15, 45) . '-' . rand(45, 60) . ' min',
                        'delivery_fee' => rand(0, 50),
                        'min_order' => rand(100, 300),
                        'image' => 'https://images.pexels.com/photos/958545/pexels-photo-958545.jpeg?w=400&h=300&fit=crop',
                        'badge' => rand(0, 1) ? 'Free Delivery' : 'Fast Delivery',
                        'cuisine_types' => ['Fast Food', 'Asian', 'Italian', 'Bangladeshi'][rand(0, 3)],
                        'product_count' => (int)$restaurant['product_count']
                    ];
                }, $restaurants);
                
                echo json_encode($deliveryRestaurants);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'featured_delivery':
            try {
                // Get featured delivery products
                $featured = fetchAll("
                    SELECT p.*, c.name as category_name, u.name as vendor_name
                    FROM products p 
                    INNER JOIN users u ON p.vendor_id = u.id AND u.role = 'vendor' AND u.status = 'active'
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.is_available = 1 AND p.is_featured = 1
                    ORDER BY p.rating DESC, p.created_at DESC 
                    LIMIT 8
                ");
                
                $featuredDelivery = array_map(function($product) {
                    return [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'description' => $product['description'] ?? $product['short_description'] ?? '',
                        'price' => (float)$product['price'],
                        'image' => !empty($product['image']) ? 
                            (strpos($product['image'], 'http') === 0 ? $product['image'] : '../uploads/images/' . $product['image']) : 
                            'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?w=300&h=200&fit=crop',
                        'vendor_name' => $product['vendor_name'] ?? 'Restaurant',
                        'category' => $product['category_name'] ?? 'Food',
                        'rating' => (float)($product['rating'] ?? 4.0) + (rand(1, 9) / 10),
                        'delivery_time' => rand(20, 40) . ' min'
                    ];
                }, $featured);
                
                echo json_encode($featuredDelivery);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Delivery - ORDIVO</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --foodpanda-pink: #f97316;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            line-height: 1.6;
            margin: 0;
            padding-top: 160px; /* Header (100px) + Nav tabs (60px) */
        }

        /* Header */
        .header {
            background: white;
            padding: 0;
            box-shadow: 0 2px 4px #e5e7eb;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 100px;
            transition: transform 0.3s ease-in-out;
        }

        .header.header-hidden {
            transform: translateY(-100%);
        }

        .header .navbar {
            height: 100px;
            padding: 0 1rem;
        }

        .header .container-fluid {
            height: 100%;
        }

        .header .navbar-expand-lg {
            height: 100%;
            align-items: center;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--foodpanda-pink) !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            height: fit-content;
            margin-right: 2rem;
        }

        .navbar-brand:hover {
            color: var(--foodpanda-pink) !important;
            text-decoration: none;
        }

        .navbar-brand img {
            height: 80px;
            width: auto;
            margin-right: 12px;
            object-fit: contain;
            animation: logoFloat 3s ease-in-out infinite, logoColorShift 6s ease-in-out infinite;
            transition: all 0.3s ease;
            
        }

        .navbar-brand img:hover {
            transform: scale(1.15) rotate(5deg);
            
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-4px) rotate(1deg); }
            50% { transform: translateY(-6px) rotate(0deg); }
            75% { transform: translateY(-4px) rotate(-1deg); }
        }

        @keyframes logoColorShift {
            0%, 100% {  }
            25% {  }
            50% {  }
            75% {  }
        }

        .navbar-brand i.fa-motorcycle {
            animation: logoPulse 2s ease-in-out infinite, logoColorShift 6s ease-in-out infinite;
            font-size: 2.5rem !important;
            color: var(--foodpanda-pink);
        }

        @keyframes logoPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .location-display {
            display: flex;
            align-items: center;
            color: #6c757d;
            font-size: 0.9rem;
            cursor: pointer;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            height: fit-content;
            border: 2px solid #10b981;
            background: white;
        }

        .location-display:hover {
            background: #10b981;
            color: white;
            border-color: #059669;
        }

        .location-display:hover i {
            color: white;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            height: fit-content;
        }

        .btn-user {
            background: white;
            border: 2px solid #10b981;
            color: var(--ordivo-dark);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            height: fit-content;
        }

        .btn-user:hover {
            background: #10b981;
            color: white;
            border-color: #059669;
        }

        .btn-user:hover i {
            color: white;
        }

        /* Navigation Tabs */
        .nav-tabs-container {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 0 1rem;
            height: 60px;
            position: fixed;
            top: 100px;
            left: 0;
            right: 0;
            z-index: 999;
            box-shadow: 0 2px 4px #e5e7eb;
            border-top: 2px solid transparent;
            border-bottom: 2px solid transparent;
            background: #10b981;
            background-origin: border-box;
            background-clip: padding-box, border-box;
            animation: navbarBorderPulse 3s ease-in-out infinite;
            transition: top 0.3s ease-in-out;
        }

        .nav-tabs-container.nav-fixed-top {
            top: 0;
        }

        @keyframes navbarBorderPulse {
            0%, 100% {
                background: #10b981;
                background-size: 100% 100%, 200% 200%;
                background-position: 0 0, 0 0;
            }
            50% {
                background: #10b981;
                background-size: 100% 100%, 200% 200%;
                background-position: 0 0, -100% -100%;
            }
        }

        .nav-tabs {
            border-bottom: none;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #e5e7eb;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            margin-right: 1rem;
            border: 2px solid transparent;
            border-radius: 8px;
            background: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-tabs .nav-link.active {
            color: #ffffff;
            border: 2px solid #ffffff;
            background: #059669;
            font-weight: 600;
        }

        .nav-tabs .nav-link:hover {
            color: #ffffff;
            border: 2px solid #ffffff;
            background: #059669;
        }

        /* Main Content */
        .main-content {
            padding: 2rem 0;
        }

        .hero-section {
            background: #10b981;);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="delivery-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="#ffffff"/></pattern></defs><rect width="100" height="100" fill="url(%23delivery-pattern)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .search-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px #e5e7eb;
            margin-bottom: 3rem;
        }

        .search-input {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1.1rem;
        }

        .search-input:focus {
            border-color: var(--ordivo-primary);
            box-shadow: 0 0 0 0.2rem #f97316;
        }

        .btn-search {
            background: var(--ordivo-primary);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-search:hover {
            background: var(--ordivo-secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px #f97316;
        }

        /* Restaurant Cards */
        .restaurant-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px #e5e7eb;
            transition: all 0.3s;
            margin-bottom: 2rem;
        }

        .restaurant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px #e5e7eb;
        }

        .restaurant-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .restaurant-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--ordivo-primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .restaurant-info {
            padding: 1.5rem;
        }

        .restaurant-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .restaurant-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: #ffc107;
        }

        .delivery-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
        }

        /* Featured Section */
        .featured-section {
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .featured-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 15px #e5e7eb;
            transition: all 0.3s;
        }

        .featured-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .featured-image {
            height: 150px;
            background-size: cover;
            background-position: center;
        }

        .featured-info {
            padding: 1rem;
        }

        .featured-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .featured-price {
            color: var(--ordivo-primary);
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Loading Animation */
        .loading {
            text-align: center;
            padding: 2rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--ordivo-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding-top: 140px;
            }
            
            .nav-tabs-container {
                height: 70px;
            }
            
            .hero-section {
                padding: 2rem 0;
                margin-bottom: 2rem;
            }
            
            .search-section {
                padding: 1.5rem;
                margin-bottom: 2rem;
            }
            
            .featured-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
            }
        }

        /* Location Modal Styles */
        .location-option {
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .location-option:hover {
            background: #f0fdf4;
            border-color: #f97316;
        }

        .location-option.selected {
            background: #f97316;
            color: white;
            border-color: #f97316;
        }

        .location-option.selected i {
            color: white;
        }

        .location-option i {
            width: 20px;
        }

        #locationSearch {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        #locationSearch:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 0.2rem #f97316;
        }

        .modal-header {
            border-bottom: 2px solid #f0fdf4;
        }

        .modal-footer {
            border-top: 2px solid #f0fdf4;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container-fluid">
            <nav class="navbar navbar-expand-lg d-flex justify-content-between align-items-center">
                <a class="navbar-brand" href="index.php">
                    <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                        <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                        <i class="fas fa-motorcycle" style="display: none;"></i>
                    <?php else: ?>
                        <i class="fas fa-motorcycle"></i>
                    <?php endif; ?>
                    
                </a>
                
                <div class="location-display" data-bs-toggle="modal" data-bs-target="#locationModal">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <span id="currentLocation">Dhaka, Bangladesh</span>
                    <i class="fas fa-chevron-down ms-2"></i>
                </div>
                
                <div class="user-menu">
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <div class="dropdown">
                            <button class="btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                                <li><a class="dropdown-item" href="favorites.php">Favorites</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="../auth/logout.php">Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="../auth/login.php" class="btn-user">
                            <i class="fas fa-user me-1"></i>Account
                        </a>
                    <?php endif; ?>
                    
                    <div class="dropdown">
                        <button class="btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-globe me-1"></i>EN
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">English</a></li>
                            <li><a class="dropdown-item" href="#">বাংলা</a></li>
                        </ul>
                    </div>
                    
                    <a href="favorites.php" class="btn-user">
                        <i class="fas fa-heart"></i>
                    </a>
                    <a href="cart.php" class="btn-user">
                        <i class="fas fa-shopping-cart me-1"></i>Cart
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <div class="nav-tabs-container">
        <div class="container-fluid">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home me-2"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="delivery.php">
                        <i class="fas fa-motorcycle me-2"></i>Delivery
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pickup.php">
                        <i class="fas fa-walking me-2"></i>Pick-up
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ordivomart.php">
                        <i class="fas fa-store me-2"></i>ordivomart
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="shops.php">
                        <i class="fas fa-shopping-bag me-2"></i>Shops
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-utensils me-2"></i>All Products
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="main-content">
            <!-- Hero Section -->
            <div class="hero-section">
                <div class="container">
                    <div class="hero-content text-center">
                        <h1 class="display-4 mb-3">Fast Food Delivery</h1>
                        <p class="lead mb-4">Get your favorite meals delivered hot and fresh to your doorstep in minutes!</p>
                        <div class="row justify-content-center">
                            <div class="col-md-3 col-6 text-center mb-3">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <div>Fast Delivery</div>
                                <small>15-45 mins</small>
                            </div>
                            <div class="col-md-3 col-6 text-center mb-3">
                                <i class="fas fa-shield-alt fa-2x mb-2"></i>
                                <div>Safe & Secure</div>
                                <small>Contactless delivery</small>
                            </div>
                            <div class="col-md-3 col-6 text-center mb-3">
                                <i class="fas fa-utensils fa-2x mb-2"></i>
                                <div>Quality Food</div>
                                <small>Fresh & hot</small>
                            </div>
                            <div class="col-md-3 col-6 text-center mb-3">
                                <i class="fas fa-star fa-2x mb-2"></i>
                                <div>Top Rated</div>
                                <small>Best restaurants</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="search-section">
                <div class="container">
                    <h3 class="text-center mb-4">Find Your Favorite Restaurant</h3>
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="text" class="form-control search-input" placeholder="Search for restaurants, cuisines, or dishes..." id="searchInput">
                                <button class="btn btn-search" type="button" onclick="searchRestaurants()">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Delivery Items -->
            <div class="container">
                <div class="featured-section">
                    <h2 class="section-title">
                        <i class="fas fa-fire text-danger me-2"></i>Featured for Delivery
                    </h2>
                    <div class="featured-grid" id="featuredGrid">
                        <div class="loading">
                            <div class="spinner"></div>
                            <p>Loading featured items...</p>
                        </div>
                    </div>
                </div>

                <!-- Delivery Restaurants -->
                <div class="restaurants-section">
                    <h2 class="section-title">
                        <i class="fas fa-motorcycle text-primary me-2"></i>Restaurants Near You
                    </h2>
                    <div class="row" id="restaurantsGrid">
                        <div class="col-12">
                            <div class="loading">
                                <div class="spinner"></div>
                                <p>Loading restaurants...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Selection Modal -->
    <div class="modal fade" id="locationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                        Select Your Location
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Search for your location</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="locationSearch" 
                                   placeholder="Enter your address, area, or landmark">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <button class="btn btn-outline-primary btn-sm" onclick="getCurrentLocation()">
                            <i class="fas fa-crosshairs me-1"></i>
                            Use Current Location
                        </button>
                    </div>
                    
                    <div class="popular-locations">
                        <h6 class="fw-bold mb-3">Popular Locations</h6>
                        <div class="row">
                            <div class="col-12 mb-2">
                                <div class="location-option" onclick="selectLocation('Dhanmondi, Dhaka, Bangladesh')">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    <span>Dhanmondi, Dhaka, Bangladesh</span>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="location-option" onclick="selectLocation('Gulshan, Dhaka, Bangladesh')">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    <span>Gulshan, Dhaka, Bangladesh</span>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="location-option" onclick="selectLocation('Banani, Dhaka, Bangladesh')">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    <span>Banani, Dhaka, Bangladesh</span>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="location-option" onclick="selectLocation('Uttara, Dhaka, Bangladesh')">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    <span>Uttara, Dhaka, Bangladesh</span>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="location-option" onclick="selectLocation('Mirpur, Dhaka, Bangladesh')">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    <span>Mirpur, Dhaka, Bangladesh</span>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="location-option" onclick="selectLocation('Wari, Dhaka, Bangladesh')">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    <span>Wari, Dhaka, Bangladesh</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmLocation()">Confirm Location</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Load featured delivery items
        function loadFeaturedDelivery() {
            fetch('delivery.php?ajax=featured_delivery')
                .then(response => response.json())
                .then(data => {
                    const grid = document.getElementById('featuredGrid');
                    if (data.error) {
                        grid.innerHTML = '<div class="col-12 text-center"><p class="text-muted">Unable to load featured items</p></div>';
                        return;
                    }
                    
                    grid.innerHTML = data.map(item => `
                        <div class="featured-card">
                            <div class="featured-image" style="background-image: url('${item.image}')"></div>
                            <div class="featured-info">
                                <div class="featured-name">${item.name}</div>
                                <div class="text-muted small mb-2">${item.vendor_name} • ${item.category}</div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="featured-price">৳${item.price}</div>
                                    <div class="text-muted small">
                                        <i class="fas fa-star text-warning"></i> ${item.rating}
                                    </div>
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="fas fa-clock"></i> ${item.delivery_time}
                                </div>
                            </div>
                        </div>
                    `).join('');
                })
                .catch(error => {
                    console.error('Error loading featured delivery:', error);
                    document.getElementById('featuredGrid').innerHTML = '<div class="col-12 text-center"><p class="text-muted">Unable to load featured items</p></div>';
                });
        }

        // Load delivery restaurants
        function loadDeliveryRestaurants() {
            fetch('delivery.php?ajax=delivery_restaurants')
                .then(response => response.json())
                .then(data => {
                    const grid = document.getElementById('restaurantsGrid');
                    if (data.error) {
                        grid.innerHTML = '<div class="col-12 text-center"><p class="text-muted">Unable to load restaurants</p></div>';
                        return;
                    }
                    
                    grid.innerHTML = data.map(restaurant => `
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="restaurant-card">
                                <div class="restaurant-image" style="background-image: url('${restaurant.image}')">
                                    <div class="restaurant-badge">${restaurant.badge}</div>
                                </div>
                                <div class="restaurant-info">
                                    <div class="restaurant-name">${restaurant.name}</div>
                                    <div class="restaurant-details">
                                        <div class="rating">
                                            <i class="fas fa-star"></i>
                                            <span>${restaurant.rating}</span>
                                            <span class="text-muted">(${restaurant.reviews})</span>
                                        </div>
                                        <div class="text-muted">${restaurant.cuisine_types}</div>
                                    </div>
                                    <div class="delivery-info">
                                        <span><i class="fas fa-clock"></i> ${restaurant.delivery_time}</span>
                                        <span><i class="fas fa-motorcycle"></i> ৳${restaurant.delivery_fee} delivery</span>
                                    </div>
                                    <div class="delivery-info mt-1">
                                        <span><i class="fas fa-shopping-bag"></i> Min order ৳${restaurant.min_order}</span>
                                        <span class="text-muted">${restaurant.product_count} items</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                })
                .catch(error => {
                    console.error('Error loading restaurants:', error);
                    document.getElementById('restaurantsGrid').innerHTML = '<div class="col-12 text-center"><p class="text-muted">Unable to load restaurants</p></div>';
                });
        }

        // Search restaurants
        function searchRestaurants() {
            const query = document.getElementById('searchInput').value.trim();
            if (query) {
                // Implement search functionality
                console.log('Searching for:', query);
                // You can add AJAX search functionality here
            }
        }

        // Load cart count
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            document.getElementById('cartCount').textContent = cart.length;
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadFeaturedDelivery();
            loadDeliveryRestaurants();
            updateCartCount();
            
            // Search on Enter key
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchRestaurants();
                }
            });
        });

        // Location functionality
        let selectedLocation = '';

        function loadSavedLocation() {
            const savedLocation = localStorage.getItem('ordivo_location');
            if (savedLocation) {
                document.getElementById('currentLocation').textContent = savedLocation;
            }
        }

        function selectLocation(location) {
            selectedLocation = location;
            document.getElementById('locationSearch').value = location;
            
            // Highlight selected option
            document.querySelectorAll('.location-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.target.closest('.location-option').classList.add('selected');
        }

        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        // For demo purposes, we'll use a mock location
                        // In a real app, you'd use reverse geocoding API
                        const mockLocation = 'Current Location, Dhaka, Bangladesh';
                        selectedLocation = mockLocation;
                        document.getElementById('locationSearch').value = mockLocation;
                        
                        // Show success message
                        showLocationMessage('Location detected successfully!', 'success');
                    },
                    function(error) {
                        showLocationMessage('Unable to detect location. Please enter manually.', 'error');
                    }
                );
            } else {
                showLocationMessage('Geolocation is not supported by this browser.', 'error');
            }
        }

        function confirmLocation() {
            const locationInput = document.getElementById('locationSearch').value.trim();
            const finalLocation = selectedLocation || locationInput;
            
            if (!finalLocation) {
                showLocationMessage('Please select or enter a location.', 'error');
                return;
            }
            
            // Update the display
            document.getElementById('currentLocation').textContent = finalLocation;
            
            // Save to localStorage
            localStorage.setItem('ordivo_location', finalLocation);
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('locationModal'));
            modal.hide();
            
            // Show success message
            showLocationMessage('Location updated successfully!', 'success');
            
            // Reset form
            selectedLocation = '';
            document.getElementById('locationSearch').value = '';
            document.querySelectorAll('.location-option').forEach(option => {
                option.classList.remove('selected');
            });
        }

        function showLocationMessage(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed" style="top: 120px; right: 20px; z-index: 9999; min-width: 300px;">
                    <i class="fas ${iconClass} me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 4000);
        }

        // Load saved location on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadSavedLocation();
            
            const locationSearch = document.getElementById('locationSearch');
            if (locationSearch) {
                locationSearch.addEventListener('input', function() {
                    selectedLocation = this.value;
                });
            }
        });
    </script>
    
    <!-- Scroll Header Behavior -->
    <script src="../assets/js/scroll-header.js"></script>
</body>
</html>