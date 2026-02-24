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
            --ordivo-light: #f0fdf4;
            --ordivo-dark: #1a1a1a;
            --ordivo-pink: #f97316;
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
            color: var(--ordivo-pink);
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
            border-color: var(--ordivo-pink);
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
            background: var(--ordivo-pink);
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
            color: var(--ordivo-pink);
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

        /* Mobile Header Styles */
        .mobile-only {
            display: none;
        }

        .desktop-only {
            display: block;
        }

        /* Mobile Menu Toggle Button */
        .mobile-nav-toggle {
            background: white;
            border: 2px solid #10b981;
            border-radius: 8px;
            color: #10b981;
            width: 40px;
            height: 40px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
            display: none;
        }

        .mobile-nav-toggle:hover {
            background: #10b981;
            color: white;
            transform: scale(1.05);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .mobile-only {
                display: block;
            }

            .desktop-only {
                display: none !important;
            }

            body {
                padding-top: 114px; /* Row1(44px) + Row2(70px) only, no nav bar */
            }

            .header {
                height: auto;
                min-height: auto;
                padding: 0;
            }

            /* Row 1: Top Utility Bar */
            .mobile-header-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
                height: 44px;
                padding: 0 0.75rem;
                background: #f8f9fa;
                border-bottom: 1px solid #e5e7eb;
            }

            .mobile-header-top .location-display {
                flex: 1;
                font-size: 0.75rem;
                padding: 0.4rem 0.75rem;
                border-radius: 20px;
                background: white;
                border: 1px solid #e5e7eb;
                max-width: calc(100% - 60px);
                height: 32px;
                display: flex;
                align-items: center;
            }

            .mobile-header-top .location-display i {
                font-size: 0.7rem;
                flex-shrink: 0;
            }

            .mobile-header-top .location-display span {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                flex: 1;
                font-size: 0.7rem;
            }

            .mobile-header-top .location-display:hover {
                background: #f8f9fa;
                border-color: #10b981;
            }

            .mobile-login-btn {
                flex-shrink: 0;
                margin-left: 0.5rem;
            }

            .mobile-login-btn .btn-user {
                padding: 0.4rem 0.75rem;
                height: 32px;
                min-width: 40px;
                border-radius: 20px;
                font-size: 0.75rem;
            }

            /* Row 2: Logo + Hamburger + Filters + Action Icons */
            .mobile-header-middle {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
                height: 70px;
                padding: 0 0.75rem;
                background: white;
                border-bottom: 2px solid #10b981;
            }

            .mobile-header-left {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                flex: 1;
                min-width: 0;
            }

            .mobile-header-middle .navbar-brand {
                margin: 0;
                padding: 0;
                flex: 0 0 auto;
            }

            .mobile-header-middle .navbar-brand img {
                height: 100px;
            }

            .mobile-header-middle .navbar-brand i.fa-walking {
                font-size: 3rem !important;
            }

            .mobile-header-middle .mobile-nav-toggle {
                display: block;
                margin: 0;
                background: white;
                border: 2px solid #10b981;
                color: #10b981;
                flex-shrink: 0;
            }

            .mobile-header-middle .mobile-nav-toggle:hover {
                background: #10b981;
                color: white;
            }

            .mobile-header-right {
                display: flex !important;
                align-items: center;
                gap: 0.5rem;
                flex-shrink: 0;
            }

            .mobile-header-right .btn-user,
            .mobile-header-right .dropdown button {
                width: 40px;
                height: 40px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                flex-shrink: 0;
                position: relative;
            }

            .mobile-header-right .btn-user i,
            .mobile-header-right .dropdown button i {
                font-size: 1.1rem;
                margin: 0;
            }

            .mobile-header-right .dropdown-toggle::after {
                display: none;
            }

            .cart-badge {
                position: absolute;
                top: -4px;
                right: -4px;
                background: #dc3545;
                color: white;
                font-size: 0.65rem;
                font-weight: 700;
                padding: 0.15rem 0.35rem;
                border-radius: 10px;
                min-width: 18px;
                height: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 2px solid white;
            }

            /* Mobile navigation handled by header_with_nav.php */

            /* Restaurant Cards - 2 per line on mobile */
            .restaurant-card {
                margin-bottom: 1rem;
            }

            .restaurant-image {
                height: 140px;
            }

            .restaurant-badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.5rem;
            }

            .restaurant-time {
                font-size: 0.7rem;
                padding: 0.2rem 0.4rem;
            }

            .restaurant-info {
                padding: 0.75rem;
            }

            .restaurant-info h6 {
                font-size: 0.9rem;
                margin-bottom: 0.4rem;
            }

            .restaurant-meta {
                font-size: 0.75rem;
                gap: 0.5rem;
            }

            .page-header {
                padding: 1.5rem 0;
                margin-top: 1rem;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .page-header p {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <?php 
    $userLocation = $_SESSION['user_location'] ?? 'Dhaka, Bangladesh';
    include 'includes/header_with_nav.php'; 
    ?>

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

    <?php include 'includes/modals.php'; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/location-tracker.js"></script>
    
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
                    <div class="col-lg-4 col-md-6 col-6 mb-4">
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

        // Mobile navigation toggle - Hamburger menu
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const navHamburgerMobile = document.getElementById('navHamburgerMobile');
                const navTabs = document.getElementById('navTabs');
                
                if (navHamburgerMobile && navTabs) {
                    // Toggle menu
                    navHamburgerMobile.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        navTabs.classList.toggle('show');
                        
                        // Lock/unlock body scroll
                        if (navTabs.classList.contains('show')) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                        
                        // Change icon
                        const icon = this.querySelector('i');
                        if (icon) {
                            if (navTabs.classList.contains('show')) {
                                icon.classList.remove('fa-bars');
                                icon.classList.add('fa-times');
                            } else {
                                icon.classList.remove('fa-times');
                                icon.classList.add('fa-bars');
                            }
                        }
                    });
                    
                    // Close menu when clicking on a nav link
                    const navLinks = navTabs.querySelectorAll('.nav-link');
                    navLinks.forEach(link => {
                        link.addEventListener('click', function() {
                            if (window.innerWidth <= 768) {
                                navTabs.classList.remove('show');
                                document.body.style.overflow = '';
                                const icon = navHamburgerMobile.querySelector('i');
                                if (icon) {
                                    icon.classList.remove('fa-times');
                                    icon.classList.add('fa-bars');
                                }
                            }
                        });
                    });
                    
                    // Close menu when clicking on backdrop
                    document.addEventListener('click', function(e) {
                        if (navTabs.classList.contains('show')) {
                            const rect = navTabs.getBoundingClientRect();
                            // Check if click is outside the sidebar
                            if (e.clientX > rect.right || e.clientX < rect.left) {
                                navTabs.classList.remove('show');
                                document.body.style.overflow = '';
                                const icon = navHamburgerMobile.querySelector('i');
                                if (icon) {
                                    icon.classList.remove('fa-times');
                                    icon.classList.add('fa-bars');
                                }
                            }
                        }
                    });
                }
            }, 100);
        });
        
        // Set Pickup tab as active
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('#mainNavTabs .nav-link');
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.href.includes('pickup.php')) {
                    link.classList.add('active');
                }
            });
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>