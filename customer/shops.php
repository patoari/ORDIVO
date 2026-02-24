<?php
/**
 * ORDIVO - Shops Page
 * All types of shops and stores
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shops - ORDIVO</title>
    
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
            /* Green Theme - Solid Colors */
            --green-light: #a7f3d0;
            --green-regular: #10b981;
            --green-dark: #059669;
            --orange-regular: #f97316;
            --ash-light: #e5e7eb;
            --ash-regular: #6b7280;
            --ash-dark: #374151;
            --white: #ffffff;
            
            /* Legacy compatibility */
            --ordivo-pink: #10b981;
            --ordivo-primary: #10b981;
            --ordivo-light-pink: #f0fdf4;
            --ordivo-dark: #374151;
            --ordivo-gray: #6b7280;
            --ordivo-light-gray: #f3f4f6;
            --ordivo-border: #e5e7eb;
            --shops-purple: #10b981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            line-height: 1.6;
            margin: 0;
            padding-top: 160px; /* Header (100px) + Nav tabs (60px) */
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--ordivo-pink) !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            height: fit-content;
            margin-right: 2rem;
        }

        .navbar-brand:hover {
            color: var(--ordivo-pink) !important;
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

        .navbar-brand i.fa-shopping-bag {
            animation: logoPulse 2s ease-in-out infinite, logoColorShift 6s ease-in-out infinite;
            font-size: 2.5rem !important;
            color: var(--shops-purple);
        }

        @keyframes logoPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .location-display {
            display: flex;
            align-items: center;
            color: var(--ordivo-gray);
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

        /* Header and navigation handled by header_with_nav.php */

        .shops-hero {
            background: #10b981;
            color: white;
            padding: 3rem 0;
            text-align: center;
            margin-top: 2rem; /* Add space from navigation tabs */
        }

        .shops-hero h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .shops-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .shop-category {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 15px #e5e7eb;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            cursor: pointer;
        }

        .shop-category:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .category-icon {
            font-size: 3rem;
            color: var(--shops-purple);
            margin-bottom: 1rem;
        }

        .category-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--ordivo-dark);
            margin-bottom: 0.5rem;
        }

        .category-count {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .shop-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px #e5e7eb;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            cursor: pointer;
        }

        .shop-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .shop-image {
            height: 150px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .shop-logo {
            position: absolute;
            bottom: 10px;
            left: 10px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid white;
            box-shadow: 0 2px 8px #e5e7eb;
        }

        .shop-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .shop-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--shops-purple);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .shop-info {
            padding: 1.5rem;
        }

        .shop-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin-bottom: 0.5rem;
        }

        .shop-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .shop-type {
            background: var(--ordivo-light);
            color: var(--shops-purple);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            display: inline-block;
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

        /* Mobile styles handled by header_with_nav.php */
    </style>
</head>
<body>
    <?php 
    $userLocation = $_SESSION['user_location'] ?? 'Dhaka, Bangladesh';
    include 'includes/header_with_nav.php'; 
    ?>

    <!-- Hero Section -->
    <section class="shops-hero">
        <div class="container">
            <h1><i class="fas fa-shopping-bag me-3"></i>All Shops</h1>
            <p>Discover all types of shops and stores in your area</p>
        </div>
    </section>

    <!-- Shop Categories -->
    <section class="py-5">
        <div class="container">
            <h2 class="mb-4">Shop Categories</h2>
            <div class="row">
                <div class="col-lg-3 col-md-6 col-6 mb-4">
                    <div class="shop-category">
                        <div class="category-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="category-name">Restaurants</div>
                        <div class="category-count">50+ shops</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-6 mb-4">
                    <div class="shop-category">
                        <div class="category-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="category-name">Grocery</div>
                        <div class="category-count">25+ shops</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-6 mb-4">
                    <div class="shop-category">
                        <div class="category-icon">
                            <i class="fas fa-coffee"></i>
                        </div>
                        <div class="category-name">Cafes</div>
                        <div class="category-count">30+ shops</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-6 mb-4">
                    <div class="shop-category">
                        <div class="category-icon">
                            <i class="fas fa-birthday-cake"></i>
                        </div>
                        <div class="category-name">Bakery</div>
                        <div class="category-count">15+ shops</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- All Shops -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="mb-4">All Shops</h2>
            <div class="row" id="allShops">
                <div class="col-12 text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Loading shops...</p>
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
        document.addEventListener('DOMContentLoaded', function() {
            loadAllShops();
        });

        async function loadAllShops() {
            try {
                const response = await fetch('index.php?ajax=restaurants&filter=shops');
                const shops = await response.json();
                
                const container = document.getElementById('allShops');
                
                if (shops.length === 0) {
                    container.innerHTML = '<div class="col-12 text-center text-muted">No shops available at the moment.</div>';
                    return;
                }
                
                const shopCards = shops.map(shop => `
                    <div class="col-lg-4 col-md-6 col-6 mb-4">
                        <div class="shop-card" onclick="window.location.href='vendor_profile.php?id=${shop.id}'">
                            <div class="shop-image" style="background-image: url('${shop.image}')">
                                <div class="shop-badge">${shop.badge}</div>
                                ${shop.logo ? `<div class="shop-logo"><img src="${shop.logo}" alt="${shop.name}" /></div>` : ''}
                            </div>
                            <div class="shop-info">
                                <div class="shop-name">${shop.name}</div>
                                <div class="shop-meta">
                                    <div class="rating">
                                        <i class="fas fa-star text-warning"></i>
                                        <span>${shop.rating}</span>
                                    </div>
                                    <span>${shop.reviews}+ reviews</span>
                                </div>
                                <div class="shop-type">${shop.category}</div>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                container.innerHTML = shopCards;
                
            } catch (error) {
                console.error('Failed to load shops:', error);
                document.getElementById('allShops').innerHTML = 
                    '<div class="col-12 text-center text-danger">Failed to load shops. Please try again.</div>';
            }
        }

        // Set Shops tab as active
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('#mainNavTabs .nav-link');
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.href.includes('shops.php')) {
                    link.classList.add('active');
                }
            });
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>