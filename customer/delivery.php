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
            height: 100px;
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

        /* Mobile Header Styles */
        .mobile-only {
            display: none;
        }

        .desktop-only {
            display: block;
        }

        @media (max-width: 768px) {
            .mobile-only {
                display: block;
            }

            .desktop-only {
                display: none !important;
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
                order: 1;
            }

            .mobile-header-middle .navbar-brand img {
                height: 100px;
            }

            .mobile-header-middle .navbar-brand i.fa-motorcycle {
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

            body {
                padding-top: 174px; /* Row1(44px) + Row2(70px) + Nav(60px) */
            }
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
            display: flex;
            align-items: center;
        }

        .nav-tabs-container .container-fluid {
            display: flex;
            align-items: center;
            width: 100%;
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
            display: flex;
            flex-wrap: wrap;
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
                padding-top: 114px; /* Row1(44px) + Row2(70px) only, no nav bar */
            }
            
            /* Hide the green navigation bar on mobile but keep it in DOM */
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

            /* Sliding Sidebar Menu */
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

            /* Overlay backdrop */
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
    <!-- Header -->
    <header class="header">
        <!-- Desktop Header -->
        <div class="container-fluid desktop-only">
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
                            <i class="fas fa-motorcycle logo-icon" style="display: none; color: var(--foodpanda-pink);"></i>
                        <?php else: ?>
                            <i class="fas fa-motorcycle logo-icon" style="color: var(--foodpanda-pink);"></i>
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
                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4" id="restaurantsPagination" style="display: none !important;">
                        <nav>
                            <ul class="pagination" id="paginationList"></ul>
                        </nav>
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

            // Mobile navigation toggle - Hamburger in header
            setTimeout(function() {
                const navHamburgerCenter = document.getElementById('navHamburgerMobile');
                const navTabs = document.getElementById('navTabs');
                
                console.log('=== Hamburger Menu Debug ===');
                console.log('Hamburger button:', navHamburgerCenter);
                console.log('Nav tabs:', navTabs);
                console.log('Window width:', window.innerWidth);
                
                if (navHamburgerCenter && navTabs) {
                    console.log('✓ Hamburger menu elements found');
                    console.log('Nav tabs initial classes:', navTabs.className);
                    console.log('Nav tabs computed style display:', window.getComputedStyle(navTabs).display);
                    console.log('Nav tabs computed style left:', window.getComputedStyle(navTabs).left);
                    
                    // Toggle menu
                    navHamburgerCenter.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('>>> Hamburger clicked!');
                        
                        const wasShown = navTabs.classList.contains('show');
                        navTabs.classList.toggle('show');
                        
                        console.log('Was shown:', wasShown);
                        console.log('Now has show class:', navTabs.classList.contains('show'));
                        console.log('Nav tabs classes after toggle:', navTabs.className);
                        console.log('Nav tabs computed left after toggle:', window.getComputedStyle(navTabs).left);
                        
                        // Lock/unlock body scroll
                        if (navTabs.classList.contains('show')) {
                            document.body.style.overflow = 'hidden';
                            console.log('Body scroll locked');
                        } else {
                            document.body.style.overflow = '';
                            console.log('Body scroll unlocked');
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
                    console.log('Found', navLinks.length, 'nav links');
                    navLinks.forEach(link => {
                        link.addEventListener('click', function() {
                            if (window.innerWidth <= 768) {
                                navTabs.classList.remove('show');
                                document.body.style.overflow = '';
                                const icon = navHamburgerCenter.querySelector('i');
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
                                console.log('Clicked outside sidebar, closing');
                                navTabs.classList.remove('show');
                                document.body.style.overflow = '';
                                const icon = navHamburgerCenter.querySelector('i');
                                if (icon) {
                                    icon.classList.remove('fa-times');
                                    icon.classList.add('fa-bars');
                                }
                            }
                        }
                    });
                    
                    console.log('✓ Hamburger menu initialized successfully');
                } else {
                    console.error('✗ Hamburger menu elements NOT found!');
                    console.error('navHamburgerCenter:', navHamburgerCenter);
                    console.error('navTabs:', navTabs);
                }
            }, 100);
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
</body>
</html>