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

        /* Navigation Tabs */
        .nav-tabs-container {
            background: white;
            border-bottom: 1px solid var(--ordivo-border);
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

        /* Responsive */
        @media (max-width: 768px) {
            .mobile-only {
                display: block;
            }

            .desktop-only {
                display: none !important;
            }

            body {
                padding-top: 114px;
            }

            .header {
                height: auto;
                min-height: auto;
                padding: 0;
            }

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
                order: 1;
            }

            .mobile-header-middle .navbar-brand img {
                height: 100px;
            }

            .mobile-header-middle .navbar-brand i.fa-shopping-bag {
                font-size: 3rem !important;
            }

            .mobile-header-middle .mobile-nav-toggle {
                display: block;
                margin: 0;
                background: white;
                border: 2px solid #10b981;
                color: #10b981;
                flex-shrink: 0;
                order: 2;
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

            .nav-tabs-container {
                display: block !important;
                position: fixed;
                top: 114px;
                left: 0;
                right: 0;
                height: 0;
                overflow: visible;
                background: transparent;
                border: none;
                box-shadow: none;
                padding: 0;
                z-index: 9999;
            }

            .nav-tabs-container .container-fluid {
                width: 100%;
                display: block !important;
                height: 0;
                overflow: visible;
            }

            .nav-tabs {
                display: flex !important;
                position: fixed;
                top: 114px;
                left: -280px;
                width: 280px;
                height: calc(100vh - 114px);
                background: #10b981;
                flex-direction: column;
                padding: 1.5rem 1rem;
                box-shadow: 2px 0 10px rgba(0,0,0,0.3);
                overflow-y: auto;
                z-index: 10000;
                transition: left 0.3s ease-in-out;
                border: none;
            }

            .nav-tabs.show {
                left: 0 !important;
            }

            .nav-tabs::before {
                content: '';
                position: fixed;
                top: 114px;
                left: 280px;
                width: 0;
                height: calc(100vh - 114px);
                background: rgba(0, 0, 0, 0);
                transition: all 0.3s ease-in-out;
                pointer-events: none;
                z-index: -1;
            }

            .nav-tabs.show::before {
                width: calc(100vw - 280px);
                background: rgba(0, 0, 0, 0.5);
                pointer-events: auto;
            }

            .nav-tabs .nav-item:first-child {
                margin-top: 0;
            }

            .nav-tabs .nav-item {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .nav-tabs .nav-link {
                width: 100%;
                margin-right: 0;
                text-align: left;
                padding: 1rem;
                border-radius: 8px;
                font-size: 1rem;
                transition: all 0.3s ease;
            }

            .nav-tabs .nav-link:hover {
                background: rgba(255, 255, 255, 0.2);
                transform: translateX(5px);
            }

            .nav-tabs .nav-link.active {
                background: rgba(255, 255, 255, 0.3);
            }

            .shops-hero {
                padding: 2rem 0;
                margin-top: 1rem;
            }

            .shops-hero h1 {
                font-size: 1.8rem;
            }

            .shops-hero p {
                font-size: 1rem;
            }

            /* Shop Categories - 2 per line */
            .shop-category {
                padding: 1.25rem;
                margin-bottom: 1rem;
            }

            .category-icon {
                font-size: 2.2rem;
                margin-bottom: 0.75rem;
            }

            .category-name {
                font-size: 1rem;
                margin-bottom: 0.4rem;
            }

            .category-desc {
                font-size: 0.8rem;
            }

            /* Shop Cards - 2 per line */
            .shop-card {
                margin-bottom: 1rem;
            }

            .shop-image {
                height: 120px;
            }

            .shop-badge {
                font-size: 0.75rem;
                padding: 0.35rem 0.75rem;
                top: 10px;
                left: 10px;
            }

            .shop-info {
                padding: 1rem;
            }

            .shop-name {
                font-size: 1rem;
                margin-bottom: 0.4rem;
            }

            .shop-meta {
                font-size: 0.8rem;
                gap: 0.5rem;
                margin-bottom: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <!-- Desktop Header -->
        <div class="container-fluid desktop-only">
            <nav class="navbar navbar-expand-lg d-flex justify-content-between align-items-center">
                <a class="navbar-brand" href="index.php">
                    <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                        <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                        <i class="fas fa-shopping-bag" style="display: none;"></i>
                    <?php else: ?>
                        <i class="fas fa-shopping-bag"></i>
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

        <!-- Mobile Header -->
        <div class="mobile-only">
            <!-- Row 1: Top Utility Bar - Address + Login -->
            <div class="mobile-header-top">
                <div class="location-display" data-bs-toggle="modal" data-bs-target="#locationModal">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    <span id="currentLocationMobile">Dhaka, Bangladesh</span>
                </div>
                <div class="mobile-login-btn">
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <a href="profile.php" class="btn-user" title="Profile">
                            <i class="fas fa-user"></i>
                        </a>
                    <?php else: ?>
                        <a href="../auth/login.php" class="btn-user" title="Login">
                            <i class="fas fa-user"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Row 2: Logo + Hamburger + Filters + Action Icons -->
            <div class="mobile-header-middle">
                <!-- Left Side: Logo + Hamburger + Filters -->
                <div class="mobile-header-left">
                    <a class="navbar-brand" href="index.php">
                        <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" 
                                 class="logo-img logo-sparkle"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                            <i class="fas fa-shopping-bag logo-icon" style="display: none; color: var(--shops-purple);"></i>
                        <?php else: ?>
                            <i class="fas fa-shopping-bag logo-icon" style="color: var(--shops-purple);"></i>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Hamburger Icon -->
                    <button class="mobile-nav-toggle" id="navHamburgerMobile">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <!-- Filters Button -->
                    <button class="mobile-nav-toggle" id="navFiltersMobile">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>

                <!-- Right Side: Action Icons -->
                <div class="mobile-header-right">
                    <div class="dropdown">
                        <button class="btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Language">
                            <i class="fas fa-globe"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-check me-2 text-success"></i>English</a></li>
                            <li><a class="dropdown-item" href="#">বাংলা</a></li>
                        </ul>
                    </div>
                    <a href="favorites.php" class="btn-user" title="Favorites">
                        <i class="fas fa-heart"></i>
                    </a>
                    <a href="cart.php" class="btn-user" title="Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge" id="cartBadgeMobile" style="display: none;">0</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <div class="nav-tabs-container">
        <div class="container-fluid">
            <ul class="nav nav-tabs" id="navTabs">
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
                    <a class="nav-link active" href="shops.php">
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

            // Mobile navigation toggle - Hamburger menu
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
    </script>
</body>
</html>