<?php
/**
 * ORDIVO - OrdivoMart Page
 * Grocery and mart shopping
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
    <title>OrdivoMart - ORDIVO</title>
    
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
            --mart-green: #10b981;
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
            color: var(--mart-green);
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

        .mart-hero {
            background: #10b981;
            color: white;
            padding: 3rem 0;
            text-align: center;
            margin-top: 2rem; /* Add space from navigation tabs */
        }

        .mart-hero h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .mart-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .category-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 15px #e5e7eb;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .category-icon {
            font-size: 3rem;
            color: var(--mart-green);
            margin-bottom: 1rem;
        }

        .category-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--ordivo-dark);
            margin-bottom: 0.5rem;
        }

        .category-desc {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .store-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px #e5e7eb;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .store-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .store-image {
            height: 150px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .mart-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--mart-green);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .store-info {
            padding: 1.5rem;
        }

        .store-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin-bottom: 0.5rem;
        }

        .store-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .delivery-time {
            background: var(--ordivo-light);
            color: var(--mart-green);
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

        /* Mobile styles handled by header_with_nav.php */
    </style>
</head>
<body>
    <?php 
    $userLocation = $_SESSION['user_location'] ?? 'Dhaka, Bangladesh';
    include 'includes/header_with_nav.php'; 
    ?>

    <!-- Hero Section -->
    <section class="mart-hero">
        <div class="container">
            <h1><i class="fas fa-store me-3"></i>OrdivoMart</h1>
            <p>Fresh groceries, daily essentials, and more delivered to your door</p>
        </div>
    </section>

    <!-- Categories -->
    <section class="py-5">
        <div class="container">
            <h2 class="mb-4">Shop by Category</h2>
            <div class="row">
                <div class="col-lg-3 col-md-6 col-6 mb-4">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-apple-alt"></i>
                        </div>
                        <div class="category-name">Fresh Produce</div>
                        <div class="category-desc">Fruits, vegetables, herbs</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-6 mb-4">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-bread-slice"></i>
                        </div>
                        <div class="category-name">Bakery</div>
                        <div class="category-desc">Fresh bread, pastries, cakes</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-6 mb-4">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-cheese"></i>
                        </div>
                        <div class="category-name">Dairy</div>
                        <div class="category-desc">Milk, cheese, yogurt</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-6 mb-4">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-drumstick-bite"></i>
                        </div>
                        <div class="category-name">Meat & Fish</div>
                        <div class="category-desc">Fresh meat, seafood</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stores -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="mb-4">Available Stores</h2>
            <div class="row" id="martStores">
                <div class="col-12 text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Loading stores...</p>
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
            loadMartStores();
        });

        async function loadMartStores() {
            try {
                const response = await fetch('index.php?ajax=restaurants&filter=grocery');
                const stores = await response.json();
                
                const container = document.getElementById('martStores');
                
                if (stores.length === 0) {
                    container.innerHTML = '<div class="col-12 text-center text-muted">No stores available at the moment.</div>';
                    return;
                }
                
                const storeCards = stores.map(store => `
                    <div class="col-lg-4 col-md-6 col-6 mb-4">
                        <div class="store-card">
                            <div class="store-image" style="background-image: url('${store.image}')">
                                <div class="mart-badge">🛒 Fresh & Fast</div>
                            </div>
                            <div class="store-info">
                                <div class="store-name">${store.name}</div>
                                <div class="store-meta">
                                    <div class="rating">
                                        <i class="fas fa-star text-warning"></i>
                                        <span>${store.rating}</span>
                                    </div>
                                    <span>${store.reviews}+ reviews</span>
                                </div>
                                <div class="delivery-time">${store.time}</div>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                container.innerHTML = storeCards;
                
            } catch (error) {
                console.error('Failed to load mart stores:', error);
                document.getElementById('martStores').innerHTML = 
                    '<div class="col-12 text-center text-danger">Failed to load stores. Please try again.</div>';
            }
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
        
        // Set OrdivoMart tab as active
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('#mainNavTabs .nav-link');
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.href.includes('ordivomart.php')) {
                    link.classList.add('active');
                }
            });
        });
        
        // Set OrdivoMart tab as active
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('#mainNavTabs .nav-link');
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.href.includes('ordivomart.php')) {
                    link.classList.add('active');
                }
            });
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>