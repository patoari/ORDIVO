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
                    SELECT DISTINCT u.id, u.name as vendor_name, u.avatar, u.cover_photo,
                           COUNT(p.id) as product_count,
                           AVG(p.rating) as avg_rating,
                           MIN(p.price) as min_price
                    FROM users u 
                    LEFT JOIN products p ON u.id = p.vendor_id AND p.is_available = 1
                    WHERE u.role = 'vendor' AND u.status = 'active'
                    GROUP BY u.id, u.name, u.avatar, u.cover_photo
                    HAVING product_count > 0
                    ORDER BY avg_rating DESC, product_count DESC
                    LIMIT 20
                ");
                
                // Check if we got any results
                if (empty($restaurants)) {
                    echo json_encode([]);
                    exit;
                }
                
                // Format for frontend
                $deliveryRestaurants = array_map(function($restaurant) {
                    // Use cover photo if available, otherwise use avatar, otherwise use placeholder
                    $image = 'https://images.pexels.com/photos/958545/pexels-photo-958545.jpeg?w=400&h=300&fit=crop';
                    if (!empty($restaurant['cover_photo'])) {
                        $image = '../' . $restaurant['cover_photo'];
                    } elseif (!empty($restaurant['avatar'])) {
                        $image = '../' . $restaurant['avatar'];
                    }
                    
                    return [
                        'id' => $restaurant['id'],
                        'name' => $restaurant['vendor_name'],
                        'address' => 'Dhaka, Bangladesh',
                        'rating' => round((float)($restaurant['avg_rating'] ?? 4.0) + (rand(1, 9) / 10), 1),
                        'reviews' => rand(100, 2000),
                        'delivery_time' => rand(15, 45) . '-' . rand(45, 60) . ' min',
                        'delivery_fee' => rand(0, 50),
                        'min_order' => rand(100, 300),
                        'image' => $image,
                        'badge' => rand(0, 1) ? 'Free Delivery' : 'Fast Delivery',
                        'cuisine_types' => ['Fast Food', 'Asian', 'Italian', 'Bangladeshi'][rand(0, 3)],
                        'product_count' => (int)$restaurant['product_count']
                    ];
                }, $restaurants);
                
                echo json_encode($deliveryRestaurants);
            } catch (Exception $e) {
                error_log("Delivery restaurants error: " . $e->getMessage());
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
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Logo Animations CSS -->
    <link href="../assets/logo-animations.css" rel="stylesheet">
    <!-- Homepage CSS (includes footer styles) -->
    <link href="../assets/css/homepage.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-pink: #f97316;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            line-height: 1.6;
            margin: 0;
            padding-top: 114px; /* Header height from header_with_nav.php */
        }

        /* Main Content */
        .main-content {
            padding: 2rem 0;
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }

        .hero-section {
            background: #10b981;
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
                padding-top: 114px; /* Row1(44px) + Row2(70px) only, no nav bar */
            }
            
            /* Mobile navigation handled by header_with_nav.php - no custom styles needed */
            
            .hero-section {
                padding: 2rem 0;
                margin-bottom: 2rem;
            }
            
            .search-section {
                padding: 1.5rem;
                margin-bottom: 2rem;
            }
            
            .featured-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            
            .featured-card {
                width: 100%;
            }
            
            .featured-image {
                height: 120px;
            }
            
            .featured-info {
                padding: 0.75rem;
            }
            
            .featured-name {
                font-size: 0.9rem;
                margin-bottom: 0.4rem;
            }
            
            .featured-price {
                font-size: 0.95rem;
            }
            
            /* Restaurant cards mobile */
            .restaurant-card {
                height: auto;
            }
            
            .restaurant-image {
                height: 120px;
            }
            
            .restaurant-info {
                padding: 0.75rem;
            }
            
            .restaurant-name {
                font-size: 0.9rem;
                margin-bottom: 0.4rem;
            }
            
            .restaurant-details {
                font-size: 0.75rem;
                margin-bottom: 0.5rem;
            }
            
            .delivery-info {
                font-size: 0.7rem;
                gap: 0.5rem;
            }
            
            .delivery-info span {
                display: flex;
                align-items: center;
                gap: 0.25rem;
            }
            
            .restaurant-badge {
                font-size: 0.65rem;
                padding: 0.25rem 0.5rem;
            }
            
            /* Pagination mobile */
            .pagination {
                flex-wrap: wrap;
                gap: 0.25rem;
            }
            
            .page-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
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
    <?php include 'includes/header_with_nav.php'; ?>

    <!-- Main Content -->
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
                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4" id="restaurantsPagination" style="display: none !important;">
                    <nav>
                        <ul class="pagination" id="paginationList"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/modals.php'; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Location Tracker -->
    <script src="../assets/js/location-tracker.js"></script>
    
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

        // Load delivery restaurants with pagination
        let allRestaurants = [];
        let currentPage = 1;
        const itemsPerPage = 10;

        function loadDeliveryRestaurants() {
            fetch('delivery.php?ajax=delivery_restaurants')
                .then(response => response.json())
                .then(data => {
                    console.log('Delivery restaurants response:', data);
                    const grid = document.getElementById('restaurantsGrid');
                    
                    if (data.error) {
                        console.error('Error from server:', data.error);
                        grid.innerHTML = '<div class="col-12 text-center"><p class="text-muted">Error: ' + data.error + '</p></div>';
                        return;
                    }
                    
                    if (!Array.isArray(data) || data.length === 0) {
                        console.warn('No restaurants found');
                        grid.innerHTML = '<div class="col-12 text-center"><p class="text-muted">No restaurants available at the moment</p></div>';
                        return;
                    }
                    
                    allRestaurants = data;
                    displayRestaurants(1);
                    setupPagination();
                })
                .catch(error => {
                    console.error('Error loading restaurants:', error);
                    document.getElementById('restaurantsGrid').innerHTML = '<div class="col-12 text-center"><p class="text-muted">Unable to load restaurants</p></div>';
                });
        }

        function displayRestaurants(page) {
            currentPage = page;
            const startIndex = (page - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const restaurantsToShow = allRestaurants.slice(startIndex, endIndex);
            
            const grid = document.getElementById('restaurantsGrid');
            grid.innerHTML = restaurantsToShow.map(restaurant => `
                <div class="col-lg-4 col-md-6 col-6 mb-4">
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
        }

        function setupPagination() {
            const totalPages = Math.ceil(allRestaurants.length / itemsPerPage);
            const paginationContainer = document.getElementById('restaurantsPagination');
            const paginationList = document.getElementById('paginationList');
            
            if (totalPages <= 1) {
                paginationContainer.style.display = 'none';
                return;
            }
            
            paginationContainer.style.display = 'flex';
            
            let paginationHTML = '';
            
            // Previous button
            paginationHTML += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Previous</a>
                </li>
            `;
            
            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                paginationHTML += `
                    <li class="page-item ${currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
                    </li>
                `;
            }
            
            // Next button
            paginationHTML += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Next</a>
                </li>
            `;
            
            paginationList.innerHTML = paginationHTML;
        }

        function changePage(page) {
            const totalPages = Math.ceil(allRestaurants.length / itemsPerPage);
            if (page < 1 || page > totalPages) return;
            
            displayRestaurants(page);
            setupPagination();
            
            // Scroll to top of restaurants section
            document.querySelector('.restaurants-section').scrollIntoView({ behavior: 'smooth' });
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

        // Remove invalid notifications
        function removeInvalidNotifications() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.textContent.includes('${message}')) {
                    console.warn('Removing invalid notification');
                    alert.closest('.toast-notification')?.remove() || alert.remove();
                }
            });
        }

        // Watch for new notifications and remove invalid ones
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    removeInvalidNotifications();
                }
            });
        });

        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Load cart count
        function updateCartCount() {
            try {
                const cart = JSON.parse(localStorage.getItem('cart') || '[]');
                const cartCountEl = document.getElementById('cartCount');
                if (cartCountEl) {
                    cartCountEl.textContent = cart.length;
                }
            } catch (e) {
                console.error('Error updating cart count:', e);
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded');
            
            loadFeaturedDelivery();
            loadDeliveryRestaurants();
            updateCartCount();
            
            // Search on Enter key
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchRestaurants();
                    }
                });
            }
        });
    </script>
    
    <script>
        // Set active navigation tab for delivery page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== Delivery Page: Setting active tab ===');
            const navLinks = document.querySelectorAll('#mainNavTabs .nav-link');
            console.log('Found nav links:', navLinks.length);
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.href.includes('delivery.php')) {
                    link.classList.add('active');
                    console.log('Set delivery tab as active');
                }
            });
            
            // Debug: Check if hamburger elements exist
            setTimeout(() => {
                const hamburger = document.getElementById('mobileNavToggle');
                const navTabs = document.getElementById('mainNavTabs');
                console.log('=== Delivery Page: Hamburger Debug ===');
                console.log('Hamburger button:', hamburger);
                console.log('Nav tabs:', navTabs);
                console.log('Hamburger has click listener:', hamburger ? 'checking...' : 'NO ELEMENT');
            }, 500);
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>