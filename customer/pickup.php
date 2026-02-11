<?php
/**
 * ORDIVO - Pickup Page
 * Restaurants available for pickup
 */

require_once '../config/db_connection.php';

// Get site settings for logo
try {
    $siteSettings = fetchRow("SELECT * FROM site_settings WHERE id = 1") ?? [];
    $siteLogo = $siteSettings['logo_url'] ?? '';
    $siteName = $siteSettings['site_name'] ?? 'ORDIVO';
    
    if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️') {
        if (strpos($siteLogo, 'uploads/') === 0) {
            $siteLogo = '../' . $siteLogo;
        } elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $siteLogo)) {
            $siteLogo = '../' . $siteLogo;
        }
    }
} catch (Exception $e) {
    $siteLogo = '';
    $siteName = 'ORDIVO';
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'pickup_restaurants':
            try {
                // Get all products grouped by vendor for pickup
                $products = fetchAll("
                    SELECT p.*, c.name as category_name, u.name as vendor_name
                    FROM products p 
                    INNER JOIN users u ON p.vendor_id = u.id AND u.role = 'vendor' AND u.status = 'active'
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.is_available = 1
                    ORDER BY p.is_featured DESC, p.rating DESC
                    LIMIT 50
                ");
                
                // Group products by vendor and format for frontend
                $vendorGroups = [];
                foreach ($products as $product) {
                    $vendorId = $product['vendor_id'];
                    if (!isset($vendorGroups[$vendorId])) {
                        $vendorGroups[$vendorId] = [
                            'id' => $vendorId,
                            'name' => $product['vendor_name'] ?? 'Restaurant',
                            'rating' => 4.0 + (rand(1, 9) / 10),
                            'reviews' => rand(500, 2500),
                            'time' => 'Ready in ' . rand(10, 30) . ' min',
                            'category' => $product['category_name'] ?? 'Food',
                            'image' => !empty($product['image']) ? 
                                (strpos($product['image'], 'http') === 0 ? $product['image'] : '../uploads/images/' . $product['image']) : 
                                'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?w=400&h=300&fit=crop',
                            'badge' => '🚶 Pickup Available',
                            'offer' => 'No delivery fee',
                            'products' => []
                        ];
                    }
                    $vendorGroups[$vendorId]['products'][] = $product;
                }
                
                $restaurants = array_values($vendorGroups);
                echo json_encode($restaurants);
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
    <title>Pickup - ORDIVO</title>
    
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
            --foodpanda-pink: #f97316;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            padding-top: 160px;
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
            color: var(--ordivo-primary) !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            height: fit-content;
            margin-right: 2rem;
        }

        .navbar-brand:hover {
            color: var(--ordivo-primary) !important;
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

        .navbar-brand i.fa-walking {
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

        /* Restaurant Cards */
        .restaurant-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
            height: 100%;
        }

        .restaurant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px #e5e7eb;
            border-color: var(--foodpanda-pink);
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
            background: var(--foodpanda-pink);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .restaurant-time {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #e5e7eb;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-size: 0.8rem;
        }

        .restaurant-info {
            padding: 1rem;
        }

        .restaurant-info h6 {
            font-weight: 700;
            color: var(--ordivo-dark);
            margin-bottom: 0.5rem;
        }

        .restaurant-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .rating {
            color: var(--foodpanda-pink);
        }

        /* Page Header */
        .page-header {
            background: #10b981;
            color: white;
            padding: 2rem 0;
            text-align: center;
            margin-top: 2rem; /* Add space from navigation tabs */
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
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
                        <i class="fas fa-walking" style="display: none;"></i>
                    <?php else: ?>
                        <i class="fas fa-walking"></i>
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
                    <a class="nav-link" href="delivery.php">
                        <i class="fas fa-motorcycle me-2"></i>Delivery
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="pickup.php">
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1><i class="fas fa-walking me-3"></i>Pickup</h1>
            <p>Order ahead and pick up your food - no delivery fees!</p>
        </div>
    </section>

    <!-- Restaurants -->
    <section class="py-4">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h3 class="mb-4">Restaurants Available for Pickup</h3>
                    <div class="row" id="restaurantsGrid">
                        <div class="col-12 text-center">
                            <div class="loading">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p class="mt-2">Loading pickup restaurants...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
        let cart = JSON.parse(localStorage.getItem('ordivo_cart') || '[]');
        
        document.addEventListener('DOMContentLoaded', function() {
            loadPickupRestaurants();
            updateCartCount();
        });

        async function loadPickupRestaurants() {
            try {
                const response = await fetch('?ajax=pickup_restaurants');
                const restaurants = await response.json();
                
                if (restaurants.error) {
                    console.error('Pickup restaurants error:', restaurants.error);
                    return;
                }
                
                const grid = document.getElementById('restaurantsGrid');
                
                if (restaurants.length === 0) {
                    grid.innerHTML = '<div class="col-12 text-center text-muted">No pickup restaurants available</div>';
                    return;
                }
                
                const restaurantCards = restaurants.map(restaurant => `
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="restaurant-card" onclick="viewRestaurant(${restaurant.id})">
                            <div class="restaurant-image" style="background-image: url('${restaurant.image}')">
                                <div class="restaurant-badge">${restaurant.badge}</div>
                                <div class="restaurant-time">${restaurant.time}</div>
                            </div>
                            <div class="restaurant-info">
                                <h6>${restaurant.name}</h6>
                                <div class="restaurant-meta">
                                    <span class="rating">
                                        <i class="fas fa-star"></i> ${restaurant.rating}
                                    </span>
                                    <span class="reviews">(${restaurant.reviews}+)</span>
                                    <span class="category">${restaurant.category}</span>
                                </div>
                                <p class="text-muted small mt-2">${restaurant.offer}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                grid.innerHTML = restaurantCards;
                
            } catch (error) {
                console.error('Failed to load pickup restaurants:', error);
                document.getElementById('restaurantsGrid').innerHTML = 
                    '<div class="col-12 text-center text-danger">Failed to load restaurants</div>';
            }
        }

        function viewRestaurant(restaurantId) {
            window.location.href = `vendor_profile.php?id=${restaurantId}`;
        }

        function updateCartCount() {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cartCount').textContent = totalItems;
        }

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
</body>
</html>