<?php
/**
 * ORDIVO - Customer Homepage (Exact Foodpanda Layout)
 * Complete redesign to match Foodpanda's exact layout
 */

require_once '../config/db_connection.php';

// Get site settings for logo
try {
    $siteSettings = fetchRow("SELECT * FROM site_settings WHERE id = 1") ?? [];
    $siteLogo = $siteSettings['logo_url'] ?? '';
    $siteName = $siteSettings['site_name'] ?? 'ORDIVO';
    
    // Fix logo path for customer directory - add ../ prefix if it's a relative path
    if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️') {
        // If it's a relative path starting with uploads/, add ../
        if (strpos($siteLogo, 'uploads/') === 0) {
            $siteLogo = '../' . $siteLogo;
        }
        // If it doesn't start with http or https or ../, assume it needs ../
        elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $siteLogo)) {
            $siteLogo = '../' . $siteLogo;
        }
    }
    
    // Debug: Log logo loading
    error_log("Logo loading - Site Name: " . $siteName . ", Original Logo URL: " . ($siteSettings['logo_url'] ?? '') . ", Adjusted Logo URL: " . $siteLogo);
    
} catch (Exception $e) {
    error_log("Error loading site settings: " . $e->getMessage());
    $siteLogo = '';
    $siteName = 'ORDIVO';
}

// Get active banners for homepage
try {
    $banners = fetchAll("
        SELECT * FROM site_banners 
        WHERE is_active = 1 
        AND position = 'homepage_promo'
        AND (start_date IS NULL OR start_date <= NOW())
        AND (end_date IS NULL OR end_date >= NOW())
        ORDER BY display_order ASC
        LIMIT 5
    ");
} catch (Exception $e) {
    error_log("Error loading banners: " . $e->getMessage());
    $banners = [];
}

// Handle error messages
$errorMessage = '';
$successMessage = '';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'vendor_not_found':
            $errorMessage = 'Restaurant not found or currently unavailable.';
            break;
        default:
            $errorMessage = 'An error occurred. Please try again.';
    }
}

// Get parameters
$userLocation = $_SESSION['user_location'] ?? 'New address Road 71 Road 71, Dhaka, Bangladesh Dhaka';
$searchQuery = sanitizeInput($_GET['search'] ?? '');

// Handle AJAX requests for data
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'featured_restaurants':
            try {
                // Get featured products from database
                $featured = fetchAll("
                    SELECT p.*, c.name as category_name, u.name as vendor_name
                    FROM products p 
                    INNER JOIN users u ON p.vendor_id = u.id AND u.role = 'vendor' AND u.status = 'active'
                    LEFT JOIN categories c ON p.category_id = c.id 
                    ORDER BY p.created_at DESC 
                    LIMIT 8
                ");
                
                // Format for frontend
                $featuredFormatted = array_map(function($product) {
                    return [
                        'id' => $product['id'],
                        'name' => $product['vendor_name'] ?? 'Restaurant',
                        'rating' => 4.5, // Default rating
                        'reviews' => rand(100, 2000),
                        'time' => '15-45 min',
                        'category' => $product['category_name'] ?? 'Food',
                        'image' => !empty($product['image']) ? 
                            (strpos($product['image'], 'http') === 0 ? $product['image'] : '../uploads/images/' . $product['image']) : 
                            '../uploads/images/placeholder-food.svg',
                        'badge' => 'Get 25% off'
                    ];
                }, $featured);
                
                echo json_encode($featuredFormatted);
            } catch (Exception $e) {
                // Return empty array if database fails
                echo json_encode([]);
            }
            exit;
            
        case 'featured_products':
            try {
                // Get featured products
                $products = fetchAll("
                    SELECT p.*, c.name as category_name, v.name as vendor_name
                    FROM products p 
                    LEFT JOIN vendors v ON p.vendor_id = v.owner_id
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.is_featured = 1
                    ORDER BY p.created_at DESC
                    LIMIT 12
                ");
                
                // Format for frontend
                $featuredProducts = array_map(function($product) {
                    return [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'description' => $product['description'] ?? $product['short_description'] ?? '',
                        'price' => (float)$product['price'],
                        'image' => !empty($product['image']) ? 
                            (strpos($product['image'], 'http') === 0 ? $product['image'] : '../uploads/images/' . $product['image']) : 
                            '../uploads/images/placeholder-food.svg',
                        'category' => $product['category_name'] ?? 'Food',
                        'vendor_name' => $product['vendor_name'] ?? 'Restaurant',
                        'rating' => (float)($product['rating'] ?? 4.0) + (rand(1, 9) / 10),
                        'is_featured' => true
                    ];
                }, $products);
                
                echo json_encode($featuredProducts);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'restaurants':
            try {
                // Get filter and sort parameters
                $filter = sanitizeInput($_GET['filter'] ?? '');
                $sort = sanitizeInput($_GET['sort'] ?? 'relevance');
                
                // Build query based on filter
                $whereClause = "WHERE p.is_available = 1";
                $orderClause = "ORDER BY p.created_at DESC";
                
                // Apply filters
                switch ($filter) {
                    case 'pickup':
                        // For pickup, we'll show all restaurants but with pickup badge
                        break;
                    case 'grocery':
                        // Filter for grocery/mart categories
                        $whereClause .= " AND (c.name LIKE '%grocery%' OR c.name LIKE '%mart%' OR c.name LIKE '%store%')";
                        break;
                    case 'shops':
                        // Show all types of shops
                        break;
                }
                
                // Apply sorting
                switch ($sort) {
                    case 'fastest':
                        $orderClause = "ORDER BY RAND()"; // Random for demo, could be actual delivery time
                        break;
                    case 'distance':
                        $orderClause = "ORDER BY RAND()"; // Random for demo, could be actual distance
                        break;
                    case 'top-rated':
                        $orderClause = "ORDER BY p.rating DESC";
                        break;
                    case 'relevance':
                    default:
                        $orderClause = "ORDER BY p.is_featured DESC, p.rating DESC";
                        break;
                }
                
                // Get all products grouped by vendor - handle missing vendors table gracefully
                try {
                    $products = fetchAll("
                        SELECT p.*, c.name as category_name, u.name as vendor_name, v.logo as vendor_logo, v.banner_image as vendor_banner
                        FROM products p 
                        INNER JOIN users u ON p.vendor_id = u.id AND u.role = 'vendor' AND u.status = 'active'
                        LEFT JOIN categories c ON p.category_id = c.id 
                        LEFT JOIN vendors v ON u.id = v.owner_id
                        $whereClause
                        $orderClause
                        LIMIT 50
                    ");
                } catch (Exception $e) {
                    // If vendors table doesn't exist, fall back to basic query
                    error_log("Vendors table query failed, using fallback: " . $e->getMessage());
                    $products = fetchAll("
                        SELECT p.*, c.name as category_name, u.name as vendor_name, u.avatar as vendor_logo, NULL as vendor_banner
                        FROM products p 
                        INNER JOIN users u ON p.vendor_id = u.id AND u.role = 'vendor' AND u.status = 'active'
                        LEFT JOIN categories c ON p.category_id = c.id 
                        $whereClause
                        $orderClause
                        LIMIT 50
                    ");
                }
                
                if (empty($products)) {
                    echo json_encode([]);
                    exit;
                }
                
                // Group products by vendor and format for frontend
                $vendorGroups = [];
                foreach ($products as $product) {
                    $vendorId = $product['vendor_id'];
                    if (!isset($vendorGroups[$vendorId])) {
                        // Customize badge based on filter
                        $badge = 'Flat 15% off';
                        if ($filter === 'pickup') {
                            $badge = '🚶 Pickup Available';
                        } elseif ($filter === 'grocery') {
                            $badge = '🛒 Fresh & Fast';
                        } elseif ($filter === 'shops') {
                            $badge = '🏪 Shop Now';
                        }
                        
                        $vendorGroups[$vendorId] = [
                            'id' => $vendorId,
                            'name' => $product['vendor_name'] ?? 'Restaurant',
                            'rating' => 4.0 + (rand(1, 9) / 10), // Random rating between 4.1-4.9
                            'reviews' => rand(500, 2500),
                            'time' => $filter === 'pickup' ? 'Ready in ' . rand(10, 30) . ' min' : rand(10, 45) . '-' . rand(30, 60) . ' min',
                            'category' => $product['category_name'] ?? 'Food',
                            'image' => !empty($product['vendor_banner']) ? 
                                (strpos($product['vendor_banner'], 'http') === 0 ? $product['vendor_banner'] : 
                                    (strpos($product['vendor_banner'], 'uploads/') === 0 ? '../' . $product['vendor_banner'] : $product['vendor_banner'])) : 
                                (!empty($product['image']) ? 
                                    (strpos($product['image'], 'http') === 0 ? $product['image'] : 
                                        (strpos($product['image'], 'uploads/') === 0 ? '../' . $product['image'] : '../uploads/images/' . $product['image'])) : 
                                    '../uploads/images/placeholder-food.svg'),
                            'logo' => !empty($product['vendor_logo']) ? 
                                (strpos($product['vendor_logo'], 'http') === 0 ? $product['vendor_logo'] : 
                                    (strpos($product['vendor_logo'], 'uploads/') === 0 ? '../' . $product['vendor_logo'] : $product['vendor_logo'])) : 
                                null,
                            'badge' => $badge,
                            'offer' => 'Valid for first order',
                            'products' => []
                        ];
                    }
                    $vendorGroups[$vendorId]['products'][] = $product;
                }
                
                // Convert to indexed array
                $restaurants = array_values($vendorGroups);
                
                echo json_encode($restaurants);
            } catch (Exception $e) {
                // Return error information for debugging
                error_log("Homepage restaurants AJAX error: " . $e->getMessage());
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'categories':
            try {
                // Check if status column exists
                $statusColumnExists = fetchValue("SHOW COLUMNS FROM categories LIKE 'status'");
                
                if ($statusColumnExists) {
                    $categories = fetchAll("
                        SELECT id, name, image, description, icon
                        FROM categories 
                        WHERE status = 'active'
                        ORDER BY name ASC
                    ");
                } else {
                    // Fallback if status column doesn't exist
                    $categories = fetchAll("
                        SELECT id, name, image, description, icon
                        FROM categories 
                        ORDER BY name ASC
                    ");
                }
                
                // Fix image paths for customer directory
                foreach ($categories as &$category) {
                    if (!empty($category['image'])) {
                        // Add ../ prefix if path starts with uploads/
                        if (strpos($category['image'], 'uploads/') === 0) {
                            $category['image'] = '../' . $category['image'];
                        }
                    }
                }
                
                echo json_encode($categories);
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
    <title><?= htmlspecialchars($siteName) ?> - Food & Grocery Delivery</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Logo Animations -->
    <link href="../assets/logo-animations.css" rel="stylesheet">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* Green Colors - Solid */
            --green-light: #a7f3d0;
            --green-regular: #10b981;
            --green-dark: #059669;
            
            /* Orange Colors - Solid */
            --orange-light: #fdba74;
            --orange-regular: #f97316;
            --orange-dark: #ea580c;
            
            /* Ash/Gray Colors - Solid */
            --ash-light: #e5e7eb;
            --ash-regular: #6b7280;
            --ash-dark: #374151;
            
            /* White/Background Colors - Solid */
            --white: #ffffff;
            --bg-light: #f9fafb;
            --bg-lighter: #f3f4f6;
            
            /* Legacy variable names for compatibility */
            --foodpanda-primary: #10b981;
            --foodpanda-pink: #10b981;
            --foodpanda-secondary: #f97316;
            --foodpanda-orange: #f97316;
            --foodpanda-light-pink: #f0fdf4;
            --foodpanda-light-green: #dcfce7;
            --foodpanda-dark: #374151;
            --foodpanda-gray: #6b7280;
            --foodpanda-light-gray: #f3f4f6;
            --foodpanda-border: #e5e7eb;
            --foodpanda-success: #10b981;
            --foodpanda-warning: #f59e0b;
            --foodpanda-error: #ef4444;
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
            padding: 0; /* Remove padding to control height precisely */
            box-shadow: 0 2px 4px #e5e7eb;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 100px; /* Fixed header height */
        }

        .header .navbar {
            height: 100px; /* Match header height */
            padding: 0 1rem; /* Add horizontal padding */
        }

        .header .container-fluid {
            height: 100%; /* Full height of header */
        }

        .header .navbar-expand-lg {
            height: 100%; /* Full height */
            align-items: center; /* Center all items vertically */
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--foodpanda-pink) !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            height: fit-content;
            margin-right: 2rem; /* Add some spacing */
        }

        .navbar-brand:hover {
            color: var(--foodpanda-pink) !important;
            text-decoration: none;
        }

        .navbar-brand img {
            height: 100px; /* Increased from 80px for better visibility */
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
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            25% {
                transform: translateY(-4px) rotate(1deg);
            }
            50% {
                transform: translateY(-6px) rotate(0deg);
            }
            75% {
                transform: translateY(-4px) rotate(-1deg);
            }
        }

        @keyframes logoColorShift {
            0%, 100% {
                
            }
            25% {
                
            }
            50% {
                
            }
            75% {
                
            }
        }

        /* Logo pulse animation for fallback icon */
        .navbar-brand i.fa-utensils {
            animation: logoPulse 2s ease-in-out infinite, logoColorShift 6s ease-in-out infinite;
            font-size: 2.5rem !important; /* Increased size */
            color: var(--foodpanda-pink);
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

        .navbar-brand:hover {
            color: var(--foodpanda-pink) !important;
            text-decoration: none;
        }

        .location-display {
            display: flex;
            align-items: center;
            color: var(--foodpanda-gray);
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
            height: fit-content; /* Ensure proper height */
        }

        .btn-user {
            background: white;
            border: 2px solid #10b981;
            color: var(--foodpanda-dark);
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

        .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dropdown-toggle::after {
            margin-left: 0.5rem;
        }

        /* Mobile Header Layout */
        @media (max-width: 768px) {
            .header {
                height: auto;
                min-height: auto;
                padding: 0;
            }

            .header .navbar {
                height: auto;
                padding: 0;
                flex-direction: column;
                align-items: stretch !important;
            }

            .header .container-fluid {
                height: auto;
                padding: 0;
            }

            /* Row 1: Top Utility Bar - Address + Login (UNCHANGED) */
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

            .location-display {
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

            .location-display i {
                font-size: 0.7rem;
                flex-shrink: 0;
            }

            .location-display span {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                flex: 1;
                font-size: 0.7rem;
            }

            .location-display:hover {
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

            /* Row 2: Merged Row - Logo + Hamburger + Action Icons */
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

            /* Left side: Logo + Hamburger */
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

            /* Right side: Action Icons */
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

            /* Cart badge */
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

            /* Hide Row 3 (merged into Row 2) */
            .mobile-header-bottom {
                display: none !important;
            }

            .navbar-brand {
                margin-right: 0;
            }

            .navbar-brand img {
                height: 100px; /* Doubled from 50px to 100px */
            }

            .navbar-brand i.fa-utensils {
                font-size: 3rem !important; /* Doubled from 1.5rem to 3rem */
            }

            .user-menu {
                gap: 0.5rem;
            }

            .btn-user {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }

            .btn-user i {
                font-size: 1rem;
            }

            /* Hide text in buttons on mobile, show only icons */
            .btn-user .btn-text {
                display: none;
            }

            /* Desktop-only elements */
            .desktop-only {
                display: none !important;
            }
        }

        /* Desktop: Hide mobile-specific elements */
        @media (min-width: 769px) {
            .mobile-header-top,
            .mobile-header-middle,
            .mobile-header-bottom,
            .mobile-header-left,
            .mobile-header-right {
                display: none !important;
            }

            .mobile-only {
                display: none !important;
            }
        }

        /* Navigation Tabs */
        .nav-tabs-container {
            background: white;
            border-bottom: 1px solid var(--foodpanda-border);
            padding: 0 1rem;
            height: 60px; /* Fixed nav tabs height */
            position: fixed;
            top: 100px; /* Below header with proper spacing */
            left: 0;
            right: 0;
            z-index: 999;
            box-shadow: 0 2px 4px #e5e7eb; /* Add subtle shadow for separation */
            border-top: 2px solid transparent;
            border-bottom: 2px solid transparent;
            background: #10b981;
            background-origin: border-box;
            background-clip: padding-box, border-box;
            animation: navbarBorderPulse 3s ease-in-out infinite;
            display: flex;
            align-items: center;
            justify-content: center;
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
            display: none; /* Hidden by default, shown on mobile */
        }

        .mobile-nav-toggle:hover {
            background: #10b981;
            color: white;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .nav-tabs-container {
                top: 114px; /* Row1(44px) + Row2(70px) = 114px */
                background: transparent;
                border: none;
                box-shadow: none;
                padding: 0.5rem 0.75rem;
                height: auto;
                animation: none;
                display: flex;
                gap: 0.5rem;
                align-items: center;
            }

            body {
                padding-top: 174px; /* Header (114px) + Nav tabs (60px) */
            }

            .main-layout {
                min-height: calc(100vh - 174px);
            }

            .sidebar {
                top: 174px;
                height: calc(100vh - 174px);
                display: none; /* Hide sidebar on mobile */
            }

            /* Mobile Filters Modal */
            .mobile-filters-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 2000;
                animation: fadeIn 0.3s ease;
            }

            .mobile-filters-modal.show {
                display: block;
            }

            .mobile-filters-content {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                max-height: 80vh;
                background: white;
                border-radius: 20px 20px 0 0;
                padding: 1.5rem;
                overflow-y: auto;
                animation: slideUp 0.3s ease;
            }

            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            @keyframes slideUp {
                from { transform: translateY(100%); }
                to { transform: translateY(0); }
            }

            .mobile-filters-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1.5rem;
                padding-bottom: 1rem;
                border-bottom: 2px solid #e5e7eb;
            }

            .mobile-filters-header h5 {
                margin: 0;
                font-size: 1.2rem;
                font-weight: 700;
                color: #333;
            }

            .mobile-filters-close {
                background: none;
                border: none;
                font-size: 1.5rem;
                color: #666;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mobile-filters-footer {
                position: sticky;
                bottom: 0;
                background: white;
                padding: 1rem 0 0;
                margin-top: 1rem;
                border-top: 2px solid #e5e7eb;
                display: flex;
                gap: 0.5rem;
            }

            .mobile-filters-footer button {
                flex: 1;
                height: 45px;
                border-radius: 10px;
                font-weight: 600;
                border: none;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .btn-clear-filters {
                background: white;
                border: 2px solid #10b981 !important;
                color: #10b981;
            }

            .btn-apply-filters {
                background: #10b981;
                color: white;
            }

            .btn-apply-filters:hover {
                background: #059669;
            }
        }

        @keyframes navbarBorderPulse {
            0%, 100% {
                background: #10b981;
                background-size: 100% 100%, 200% 200%;
                background-position: 0 0, 0 0;
            }
            25% {
                background: #10b981;
                background-size: 100% 100%, 200% 200%;
                background-position: 0 0, -50% -50%;
            }
            50% {
                background: #10b981;
                background-size: 100% 100%, 200% 200%;
                background-position: 0 0, -100% -100%;
            }
            75% {
                background: #10b981;
                background-size: 100% 100%, 200% 200%;
                background-position: 0 0, -150% -150%;
            }
        }

        .nav-tabs {
            border-bottom: none;
            flex: 1;
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

        /* Mobile Navigation Styles */
        @media (max-width: 768px) {
            .nav-tabs-container .container-fluid {
                width: 100%;
                display: block !important; /* Override the display:none */
            }

            /* Sliding Sidebar Menu */
            .nav-tabs {
                display: flex;
                position: fixed;
                top: 114px; /* Below mobile header (44px + 70px) */
                left: -280px; /* Hidden off-screen to the left */
                width: 280px;
                height: calc(100vh - 114px); /* Full height minus header */
                background: #10b981;
                flex-direction: column;
                padding: 1.5rem 1rem;
                box-shadow: 2px 0 10px rgba(0,0,0,0.3);
                overflow-y: auto;
                z-index: 9999;
                transition: left 0.3s ease-in-out;
            }

            .nav-tabs.show {
                left: 0; /* Slide in from left */
            }

            /* Overlay backdrop */
            .nav-tabs::before {
                content: '';
                position: fixed;
                top: 114px; /* Below mobile header */
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

            /* Sidebar header */
            .nav-tabs .nav-item:first-child {
                margin-top: 0; /* No extra space needed */
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
        }

        /* Main Layout */
        .main-layout {
            display: flex;
            margin: 0;
            padding: 0;
            min-height: calc(100vh - 160px); /* Header (100px) + Nav tabs (60px) */
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            flex-shrink: 0;
            background: white;
            border-right: 1px solid var(--foodpanda-border);
            position: fixed;
            top: 160px; /* Header (100px) + Nav tabs (60px) */
            left: 0;
            height: calc(100vh - 160px);
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1.5rem;
            z-index: 100;
        }

        /* Custom scrollbar for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--foodpanda-border);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--foodpanda-gray);
        }

        /* Ensure sidebar doesn't interfere with main content scrolling */
        .sidebar {
            scrollbar-width: thin;
            scrollbar-color: var(--foodpanda-border) transparent;
        }

        /* Prevent horizontal overflow */
        html, body {
            overflow-x: hidden;
        }

        .filter-section {
            margin-bottom: 2rem;
        }

        .filter-section h6 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--foodpanda-dark);
        }

        .filter-option {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            cursor: pointer;
        }

        .filter-option input {
            margin-right: 0.5rem;
            accent-color: var(--foodpanda-pink);
        }

        .filter-option label {
            cursor: pointer;
            font-size: 0.9rem;
        }

        .cuisine-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .cuisine-item input {
            margin-right: 0.5rem;
        }

        .show-more {
            color: var(--foodpanda-pink);
            background: none;
            border: none;
            font-size: 0.9rem;
            cursor: pointer;
            padding: 0.5rem 0;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px; /* Space for fixed sidebar */
            padding: 2rem;
            max-width: calc(100vw - 280px);
            min-height: calc(100vh - 160px);
        }

        /* Search Bar */
        .search-container {
            margin-bottom: 2rem;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1rem 3rem 1rem 1rem;
            border: 1px solid var(--foodpanda-border);
            border-radius: 8px;
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--foodpanda-pink);
            box-shadow: 0 0 0 3px #10b981;
        }

        .search-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            pointer-events: none;
        }

        .clear-search-btn {
            position: absolute;
            right: 2.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .clear-search-btn:hover {
            background-color: #f0f0f0;
            color: var(--foodpanda-pink);
        }

        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--foodpanda-border);
            border-radius: 8px;
            box-shadow: 0 4px 20px #e5e7eb;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .search-suggestions.show {
            display: block;
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            transition: background-color 0.2s ease;
        }

        .suggestion-item:hover,
        .suggestion-item.active {
            background-color: #f8f9fa;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-icon {
            margin-right: 0.75rem;
            color: var(--foodpanda-pink);
            width: 16px;
            flex-shrink: 0;
        }

        .suggestion-text {
            flex: 1;
            min-width: 0;
        }

        .suggestion-text strong {
            color: var(--foodpanda-pink);
            font-weight: 600;
        }

        .suggestion-type {
            font-size: 0.8rem;
            color: #666;
            margin-left: 0.5rem;
            white-space: nowrap;
        }

        .no-suggestions {
            padding: 1rem;
            text-align: center;
            color: #666;
            font-style: italic;
        }

        /* Mobile responsiveness for search suggestions */
        @media (max-width: 768px) {
            .search-suggestions {
                max-height: 250px;
                border-radius: 6px;
            }
            
            .suggestion-item {
                padding: 0.6rem 0.8rem;
            }
            
            .suggestion-icon {
                margin-right: 0.5rem;
                width: 14px;
            }
            
            .suggestion-type {
                font-size: 0.75rem;
            }
        }

        /* Promotional Banner Carousel */
        .promo-carousel-container {
            margin-bottom: 2rem;
        }

        .promo-banner {
            border-radius: 12px;
            padding: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
            min-height: 200px;
            display: flex;
            align-items: center;
        }

        .promo-content h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .promo-content p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .promo-btn {
            background: white;
            color: #f97316;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .promo-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .carousel-control-prev,
        .carousel-control-next {
            width: 40px;
            height: 40px;
            background: rgba(0,0,0,0.3);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
        }

        .carousel-control-prev {
            left: 10px;
        }

        .carousel-control-next {
            right: 10px;
        }

        .carousel-indicators {
            margin-bottom: 0.5rem;
        }

        .carousel-indicators button {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.5);
        }

        .carousel-indicators button.active {
            background-color: white;
        }

        /* Restaurant Cards */
        .restaurant-carousel {
            margin-bottom: 3rem;
            padding: 0 30px;
        }

        .carousel-container {
            position: relative;
            overflow: visible;
        }

        .restaurant-cards {
            display: flex;
            gap: 1rem;
            transition: transform 0.3s ease;
            margin-top: 1.5rem;
        }

        .restaurant-card {
            min-width: 280px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px #e5e7eb;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #10b981;
        }

        /* Mobile: 2 restaurant cards per line */
        @media (max-width: 768px) {
            /* Promo carousel mobile */
            .promo-banner {
                padding: 1.5rem;
                min-height: 160px;
            }

            .promo-content h2 {
                font-size: 1.5rem;
            }

            .promo-content p {
                font-size: 0.9rem;
            }

            .promo-btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }

            .carousel-control-prev,
            .carousel-control-next {
                width: 32px;
                height: 32px;
            }

            .carousel-indicators {
                margin-bottom: 0.25rem;
            }

            .carousel-indicators button {
                width: 8px;
                height: 8px;
            }

            .restaurant-card {
                min-width: calc(50% - 10px);
            }

            .restaurant-image {
                height: 140px;
            }

            .restaurant-info {
                padding: 0.75rem;
            }

            .restaurant-name {
                font-size: 0.9rem;
                margin-bottom: 0.4rem;
            }

            .restaurant-meta {
                font-size: 0.75rem;
                gap: 0.5rem;
            }

            .restaurant-badge {
                font-size: 0.65rem;
                padding: 0.2rem 0.4rem;
            }

            .restaurant-time {
                font-size: 0.65rem;
                padding: 0.2rem 0.4rem;
            }
        }

        .restaurant-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px #e5e7eb;
            border-color: #f97316;
        }

        .restaurant-image {
            height: 160px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .restaurant-logo {
            position: absolute;
            bottom: 8px;
            left: 8px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid white;
            box-shadow: 0 2px 8px #e5e7eb;
        }

        .restaurant-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .restaurant-listing-logo {
            position: absolute;
            bottom: 8px;
            left: 8px;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid white;
            box-shadow: 0 2px 8px #e5e7eb;
        }

        .restaurant-listing-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .restaurant-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: var(--foodpanda-pink);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .restaurant-time {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: #e5e7eb;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
        }

        .restaurant-info {
            padding: 1rem;
        }

        .restaurant-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--foodpanda-dark);
        }

        .restaurant-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.85rem;
            color: var(--foodpanda-gray);
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .rating-star {
            color: #ffc107;
        }

        /* Carousel Navigation */
        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: white;
            border: 2px solid #10b981;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            z-index: 10;
            color: #10b981;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .carousel-nav:hover {
            background: #10b981;
            color: white;
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-nav.prev {
            left: -10px;
        }

        .carousel-nav.next {
            right: -10px;
        }

        /* Cuisines Section */
        .cuisines-section {
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--foodpanda-dark);
        }

        .cuisines-section {
            margin-bottom: 3rem;
        }

        .cuisinesSwiper {
            position: relative;
            padding: 10px 40px;
            overflow: visible;
        }

        .cuisinesSwiper .swiper-wrapper {
            display: flex;
            align-items: center;
            padding: 5px 0;
        }

        .cuisinesSwiper .swiper-slide {
            width: auto !important;
            margin-right: 1rem;
        }

        .cuisinesSwiper .swiper-button-next,
        .cuisinesSwiper .swiper-button-prev {
            color: #10b981;
            width: 44px;
            height: 44px;
            background: white;
            border: 2px solid #10b981;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }

        .cuisinesSwiper .swiper-button-next:hover,
        .cuisinesSwiper .swiper-button-prev:hover {
            background: #10b981;
            color: white;
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
            transform: scale(1.1);
        }

        .cuisinesSwiper .swiper-button-next:after,
        .cuisinesSwiper .swiper-button-prev:after {
            font-size: 18px;
            font-weight: bold;
        }

        .cuisine-card {
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 100px;
            padding: 1rem;
            border-radius: 12px;
            background: #f0fdf4;
            border: 2px solid transparent;
        }

        .cuisine-card:hover {
            transform: translateY(-5px);
            border-color: var(--foodpanda-primary);
            box-shadow: 0 8px 20px #10b981;
            background: #dcfce7;
        }

        .cuisine-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 0.5rem;
            background-size: cover;
            background-position: center;
            border: 3px solid white;
            box-shadow: 0 4px 12px #f97316;
        }

        .cuisine-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: #ea580c;
            
        }

        /* Mobile: 4 cuisine cards per line */
        @media (max-width: 768px) {
            .cuisinesSwiper {
                padding: 10px 5px;
            }

            .cuisinesSwiper .swiper-slide {
                width: calc(25% - 8px) !important;
                min-width: calc(25% - 8px);
                max-width: calc(25% - 8px);
                margin-right: 8px;
            }

            .cuisinesSwiper .swiper-button-next,
            .cuisinesSwiper .swiper-button-prev {
                display: none;
            }

            .cuisine-card {
                min-width: auto;
                padding: 0.5rem;
            }

            .cuisine-icon {
                width: 60px;
                height: 60px;
                margin-bottom: 0.3rem;
            }

            .cuisine-name {
                font-size: 0.7rem;
                line-height: 1.2;
            }
        }

        /* Featured Products Section */
        .featured-products-section {
            margin-bottom: 3rem;
        }

        .featuredProductsSwiper,
        .topChoiceProductsSwiper {
            position: relative;
            padding: 10px 50px;
            overflow: visible;
        }

        .featuredProductsSwiper .swiper-wrapper,
        .topChoiceProductsSwiper .swiper-wrapper {
            display: flex;
            align-items: stretch;
            padding: 5px 0;
        }

        .featuredProductsSwiper .swiper-slide,
        .topChoiceProductsSwiper .swiper-slide {
            width: 280px !important;
            min-width: 280px;
            max-width: 280px;
            height: auto;
            margin-right: 16px;
            flex-shrink: 0;
        }

        /* Mobile: 2 product cards per line */
        @media (max-width: 768px) {
            .featuredProductsSwiper .swiper-slide,
            .topChoiceProductsSwiper .swiper-slide {
                width: calc(50% - 8px) !important;
                min-width: calc(50% - 8px);
                max-width: calc(50% - 8px);
                margin-right: 8px;
            }

            .featuredProductsSwiper,
            .topChoiceProductsSwiper {
                padding: 10px 5px;
            }

            .featuredProductsSwiper .swiper-button-next,
            .featuredProductsSwiper .swiper-button-prev,
            .topChoiceProductsSwiper .swiper-button-next,
            .topChoiceProductsSwiper .swiper-button-prev {
                display: none;
            }

            /* Optimize product card content for mobile 2-column layout */
            .product-card .product-image {
                height: 120px;
            }

            .product-card .product-info {
                padding: 0.6rem;
            }

            .product-card .product-name {
                font-size: 0.8rem;
                margin-bottom: 0.3rem;
                -webkit-line-clamp: 2;
            }

            .product-card .product-vendor {
                font-size: 0.7rem;
                margin-bottom: 0.3rem;
            }

            .product-card .product-price {
                font-size: 0.9rem;
                margin-bottom: 0.3rem;
            }

            .product-card .product-rating {
                font-size: 0.7rem;
                margin-bottom: 0.5rem;
            }

            .product-card .product-badge {
                font-size: 0.65rem;
                padding: 0.25rem 0.5rem;
                top: 6px;
                left: 6px;
            }

            .product-card .btn {
                padding: 0.4rem;
                font-size: 0.75rem;
            }
        }

        .featuredProductsSwiper .swiper-button-next,
        .featuredProductsSwiper .swiper-button-prev,
        .topChoiceProductsSwiper .swiper-button-next,
        .topChoiceProductsSwiper .swiper-button-prev {
            color: #10b981;
            width: 44px;
            height: 44px;
            background: white;
            border: 2px solid #10b981;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }

        .featuredProductsSwiper .swiper-button-next:hover,
        .featuredProductsSwiper .swiper-button-prev:hover,
        .topChoiceProductsSwiper .swiper-button-next:hover,
        .topChoiceProductsSwiper .swiper-button-prev:hover {
            background: #10b981;
            color: white;
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
            transform: scale(1.1);
        }

        .featuredProductsSwiper .swiper-button-next:after,
        .featuredProductsSwiper .swiper-button-prev:after,
        .topChoiceProductsSwiper .swiper-button-next:after,
        .topChoiceProductsSwiper .swiper-button-prev:after {
            font-size: 18px;
            font-weight: bold;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px #e5e7eb;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid #10b981;
            height: 100%;
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px #e5e7eb;
            border-color: #f97316;
        }

        .product-image {
            height: 160px;
            width: 100%;
            background-size: cover;
            background-position: center;
            position: relative;
            flex-shrink: 0;
        }

        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
        }

        .badge-featured {
            background: #10b981;
        }

        .badge-top-choice {
            background: #f97316;
        }

        .product-info {
            padding: 0.875rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.4rem;
            color: #374151;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-vendor {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 0.4rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-price {
            font-size: 1.05rem;
            font-weight: 700;
            color: #10b981;
            margin-bottom: 0.4rem;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            color: #ffc107;
            margin-bottom: 0.75rem;
        }

        .product-info .btn {
            margin-top: auto;
            padding: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 8px;
            background: #10b981;
            border: none;
            color: white;
            transition: all 0.3s ease;
        }

        .product-info .btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .view-all-btn {
            background: var(--foodpanda-light-pink);
            color: var(--foodpanda-pink);
            border: 2px solid var(--foodpanda-pink);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .view-all-btn:hover {
            background: var(--foodpanda-pink);
            color: white;
        }

        /* Daily Deals */
        .deals-section {
            margin-bottom: 3rem;
        }

        .deals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        /* Mobile: 2 deal banners per line */
        @media (max-width: 768px) {
            .deals-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }

            .deal-card {
                padding: 1rem;
            }

            .deal-title {
                font-size: 1rem;
                margin-bottom: 0.3rem;
            }

            .deal-subtitle {
                font-size: 0.8rem;
            }
        }

        .deal-card {
            background: #f97316;
            border-radius: 12px;
            padding: 1.5rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .deal-card:hover {
            transform: translateY(-2px);
        }

        .deal-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .deal-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Restaurant Listings */
        .restaurants-section {
            margin-bottom: 3rem;
        }

        .restaurants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        /* Swiper Container Styles */
        .swiper-container {
            width: 100%;
            padding: 0;
            margin: 0;
        }

        .swiper-wrapper {
            display: flex;
            align-items: stretch;
        }

        .swiper-slide {
            height: auto;
            display: flex;
        }

        .swiper-pagination {
            bottom: -40px !important;
        }

        .swiper-pagination-bullet {
            background: var(--foodpanda-pink);
            opacity: 0.3;
        }

        .swiper-pagination-bullet-active {
            opacity: 1;
        }

        .swiper-button-next,
        .swiper-button-prev {
            color: #10b981;
            background: white;
            border: 2px solid #10b981;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }

        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            background: #10b981;
            color: white;
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
            transform: scale(1.1);
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 18px;
            font-weight: 600;
        }

        .restaurant-listing {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px #e5e7eb;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .restaurant-listing:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px #e5e7eb;
        }

        .restaurant-listing-image {
            height: 180px;
            background-size: cover;
            background-position: center;
            position: relative;
            flex-shrink: 0;
        }

        .restaurant-listing-info {
            padding: 1rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .restaurant-listing-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--foodpanda-dark);
        }

        .restaurant-listing-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: var(--foodpanda-gray);
        }

        .restaurant-offer {
            background: var(--foodpanda-light-pink);
            color: var(--foodpanda-pink);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: auto;
            align-self: flex-start;
        }

        /* Swiper specific adjustments */
        .swiper-container {
            padding-bottom: 60px; /* Space for pagination */
        }

        .swiper-slide .restaurant-listing {
            margin: 0; /* Remove any default margins */
        }

        /* Responsive adjustments for Swiper */
        @media (max-width: 768px) {
            .swiper-button-next,
            .swiper-button-prev {
                display: none; /* Hide navigation arrows on mobile */
            }
            
            .swiper-container {
                padding-bottom: 40px;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding-top: 180px; /* Adjusted for mobile */
            }
            
            .main-layout {
                flex-direction: column;
                padding: 0;
                margin: 0;
            }
            
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
                max-height: 300px;
                top: auto;
                left: auto;
                border-right: none;
                border-bottom: 1px solid var(--foodpanda-border);
                padding: 1rem;
            }
            
            .main-content {
                margin-left: 0;
                max-width: 100vw;
                padding: 1rem;
            }
            
            .restaurant-cards {
                flex-wrap: nowrap;
                overflow-x: auto;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }
            
            .restaurant-cards::-webkit-scrollbar {
                display: none;
            }
            
            .carousel-nav {
                display: none;
            }
        }

        @media (max-width: 1024px) and (min-width: 769px) {
            .main-content {
                padding: 1.5rem;
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
            background: var(--foodpanda-light-pink);
            border-color: var(--foodpanda-pink);
        }

        .location-option.selected {
            background: var(--foodpanda-pink);
            color: white;
            border-color: var(--foodpanda-pink);
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
            border-color: var(--foodpanda-pink);
            box-shadow: 0 0 0 0.2rem #10b981;
        }

        .modal-header {
            border-bottom: 2px solid var(--foodpanda-light-pink);
        }

        .modal-footer {
            border-top: 2px solid var(--foodpanda-light-pink);
        }

        /* Footer Styles */
        .footer-section {
            background: #10b981;
            color: #ffffff;
            margin-top: 4rem;
            position: relative;
            overflow: hidden;
        }

        .footer-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: transparent; 
                #ffffff 100%);
            pointer-events: none;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .footer-logo {
            height: 80px;
            width: auto;
            margin-right: 0;
            filter: brightness(0) invert(1);
        }

        .footer-logo-icon {
            font-size: 4rem;
            color: #ffffff;
            margin-right: 0;
            
        }

        .footer-description {
            color: #ffffff;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border-radius: 50%;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .social-link:hover {
            background: #ffffff;
            color: #10b981;
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border-color: #ffffff;
        }

        .social-link i {
            font-size: 1rem;
        }

        .footer-title {
            color: #ffffff;
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
            
        }

        .footer-title::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 30px;
            height: 2px;
            background: #ffffff;
            border-radius: 1px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            position: relative;
            z-index: 1;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: #ffffff;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            position: relative;
        }

        .footer-links a:hover {
            color: #ffffff;
            padding-left: 8px;
            
        }

        .footer-links a::before {
            content: '';
            position: absolute;
            left: -15px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 2px;
            background: #ffffff;
            transition: width 0.3s ease;
        }

        .footer-links a:hover::before {
            width: 10px;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #ffffff;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .contact-item:hover {
            color: #ffffff;
            transform: translateX(5px);
        }

        .contact-item i {
            color: #10b981;
            width: 16px;
            text-align: center;
            background: #ffffff;
            padding: 8px;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .newsletter-form {
            position: relative;
            z-index: 1;
        }

        .newsletter-form .input-group {
            max-width: 500px;
        }

        .newsletter-form .form-control {
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            backdrop-filter: blur(10px);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
        }

        .newsletter-form .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .newsletter-form .form-control:focus {
            border-color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
            outline: none;
        }

        .newsletter-form .btn-primary {
            background: #ffffff;
            border-color: #ffffff;
            color: #10b981;
            backdrop-filter: blur(10px);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .newsletter-form .btn-primary:hover {
            background: #f0fdf4;
            border-color: #f0fdf4;
            color: #059669;
            transform: scale(1.05);
        }

        .newsletter-form .btn-primary i {
            font-size: 1rem;
        }

        .footer-section .border-top {
            border-color: #ffffff !important;
        }

        .footer-section .border-bottom {
            border-color: #ffffff !important;
        }

        .footer-copyright {
            color: #ffffff;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }

        .footer-legal {
            display: flex;
            gap: 1.5rem;
            justify-content: flex-end;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .footer-legal a {
            color: #ffffff;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .footer-legal a:hover {
            color: #ffffff;
            
        }

        .footer-legal a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: #ffffff;
            transition: width 0.3s ease;
        }

        .footer-legal a:hover::after {
            width: 100%;
        }

        /* Footer Responsive */
        @media (max-width: 768px) {
            .footer-section .container-fluid {
                margin-left: 0 !important;
                max-width: 100vw !important;
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            /* Company info - full width */
            .footer-section .row > div:first-child {
                order: 1;
                margin-bottom: 2rem;
            }

            /* Footer links sections - 2 columns grid */
            .footer-section .row > div:nth-child(2),
            .footer-section .row > div:nth-child(3),
            .footer-section .row > div:nth-child(4),
            .footer-section .row > div:nth-child(5) {
                flex: 0 0 50%;
                max-width: 50%;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            /* Reorder for better mobile layout */
            .footer-section .row > div:nth-child(2) { order: 2; }
            .footer-section .row > div:nth-child(3) { order: 3; }
            .footer-section .row > div:nth-child(4) { order: 4; }
            .footer-section .row > div:nth-child(5) { order: 5; }

            /* Reduce spacing */
            .footer-section .row {
                display: flex;
                flex-wrap: wrap;
            }

            .footer-title {
                font-size: 1rem;
                margin-bottom: 1rem;
            }

            .footer-title::after {
                width: 30px;
            }

            .footer-links {
                margin-bottom: 0;
            }

            .footer-links li {
                margin-bottom: 0.5rem;
            }

            .footer-links a {
                font-size: 0.85rem;
            }

            .footer-description {
                font-size: 0.85rem;
                margin-bottom: 1rem;
            }

            .contact-info {
                align-items: flex-start;
                text-align: left;
            }

            .contact-item {
                font-size: 0.85rem;
                margin-bottom: 0.5rem;
            }

            .contact-item i {
                font-size: 0.9rem;
                min-width: 20px;
            }

            /* App download and newsletter - stack vertically */
            .footer-section .border-top.border-bottom .row > div {
                flex: 0 0 100%;
                max-width: 100%;
            }

            /* Bottom footer */
            .footer-legal {
                justify-content: flex-start;
                flex-wrap: wrap;
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .footer-legal a {
                font-size: 0.8rem;
            }

            .footer-copyright {
                font-size: 0.8rem;
            }

            .social-links {
                justify-content: flex-start;
                margin-top: 1rem;
            }

            .footer-brand {
                justify-content: flex-start;
                text-align: left;
            }

            /* Reduce padding */
            .footer-section .py-5 {
                padding-top: 2rem !important;
                padding-bottom: 2rem !important;
            }

            .footer-section .py-4 {
                padding-top: 1.5rem !important;
                padding-bottom: 1.5rem !important;
            }

            .footer-section .py-3 {
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }
        }

        @media (min-width: 1025px) {
            .footer-section .container-fluid {
                margin-left: 280px;
                max-width: calc(100vw - 280px);
                padding-left: 2rem;
                padding-right: 2rem;
            }
        }

        @media (max-width: 1024px) and (min-width: 769px) {
            .footer-section .container-fluid {
                margin-left: 280px;
                max-width: calc(100vw - 280px);
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container-fluid">
            <nav class="navbar d-flex justify-content-between align-items-center">
                <!-- Desktop Layout (hidden on mobile) -->
                <a class="navbar-brand desktop-only" href="index.php">
                    <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                        <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" 
                             class="logo-img logo-sparkle"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                        <i class="fas fa-utensils logo-icon" style="display: none; font-size: 2.5rem; color: var(--foodpanda-pink);"></i>
                    <?php else: ?>
                        <i class="fas fa-utensils logo-icon" style="font-size: 2.5rem; color: var(--foodpanda-pink);"></i>
                    <?php endif; ?>
                </a>
                
                <div class="location-display desktop-only" data-bs-toggle="modal" data-bs-target="#locationModal">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <span id="currentLocation"><?= htmlspecialchars($userLocation) ?></span>
                    <i class="fas fa-chevron-down ms-2"></i>
                </div>
                
                <div class="user-menu desktop-only">
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <div class="dropdown">
                            <button class="btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'Login') ?>
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
                        <a href="../auth/login.php" class="btn-user">Login</a>
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
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                </div>

                <!-- Mobile Layout (hidden on desktop) -->
                <!-- Row 1: Top Utility Bar - Address + Login (UNCHANGED) -->
                <div class="mobile-header-top mobile-only">
                    <div class="location-display" data-bs-toggle="modal" data-bs-target="#locationModal">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        <span id="currentLocationMobile"><?= htmlspecialchars($userLocation) ?></span>
                    </div>
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <div class="dropdown mobile-login-btn">
                            <button class="btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                                <li><a class="dropdown-item" href="orders.php"><i class="fas fa-shopping-bag me-2"></i>My Orders</a></li>
                                <li><a class="dropdown-item" href="favorites.php"><i class="fas fa-heart me-2"></i>Favorites</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="../auth/login.php" class="btn-user mobile-login-btn">
                            <i class="fas fa-user"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Row 2: Merged Row - Logo + Action Icons (No Hamburger) -->
                <div class="mobile-header-middle mobile-only">
                    <!-- Left Side: Logo + Hamburger + Filters -->
                    <div class="mobile-header-left">
                        <a class="navbar-brand" href="index.php">
                            <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                                <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" 
                                     class="logo-img logo-sparkle"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                <i class="fas fa-utensils logo-icon" style="display: none; color: var(--foodpanda-pink);"></i>
                            <?php else: ?>
                                <i class="fas fa-utensils logo-icon" style="color: var(--foodpanda-pink);"></i>
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
            </nav>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <div class="nav-tabs-container">
        <div class="container-fluid">
            <ul class="nav nav-tabs" id="navTabs">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
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

    <!-- Mobile Filters Modal -->
    <div class="mobile-filters-modal" id="mobileFiltersModal">
        <div class="mobile-filters-content">
            <div class="mobile-filters-header">
                <h5><i class="fas fa-filter me-2"></i>Filters</h5>
                <button class="mobile-filters-close" id="closeFiltersModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="filter-section">
                <h6>Sort by</h6>
                <div class="filter-option">
                    <input type="radio" name="sort-mobile" id="relevance-mobile" checked>
                    <label for="relevance-mobile">Relevance</label>
                </div>
                <div class="filter-option">
                    <input type="radio" name="sort-mobile" id="fastest-mobile">
                    <label for="fastest-mobile">Fastest delivery</label>
                </div>
                <div class="filter-option">
                    <input type="radio" name="sort-mobile" id="distance-mobile">
                    <label for="distance-mobile">Distance</label>
                </div>
                <div class="filter-option">
                    <input type="radio" name="sort-mobile" id="top-rated-mobile">
                    <label for="top-rated-mobile">Top rated</label>
                </div>
            </div>

            <div class="filter-section">
                <h6>Quick filters</h6>
                <div class="filter-option">
                    <input type="checkbox" id="ratings-4-mobile">
                    <label for="ratings-4-mobile">Ratings 4+</label>
                </div>
                <div class="filter-option">
                    <input type="checkbox" id="super-restaurant-mobile">
                    <label for="super-restaurant-mobile">🔥 Super restaurant</label>
                </div>
            </div>

            <div class="filter-section">
                <h6>Offers</h6>
                <div class="filter-option">
                    <input type="checkbox" id="free-delivery-mobile">
                    <label for="free-delivery-mobile">Free delivery</label>
                </div>
                <div class="filter-option">
                    <input type="checkbox" id="accepts-vouchers-mobile">
                    <label for="accepts-vouchers-mobile">Accepts vouchers</label>
                </div>
                <div class="filter-option">
                    <input type="checkbox" id="deals-mobile">
                    <label for="deals-mobile">Deals</label>
                </div>
            </div>

            <div class="filter-section">
                <h6>Cuisines</h6>
                <div class="cuisine-item">
                    <input type="checkbox" id="american-mobile">
                    <label for="american-mobile">American</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="asian-mobile">
                    <label for="asian-mobile">Asian</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="bakery-mobile">
                    <label for="bakery-mobile">Bakery</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="bangladeshi-mobile">
                    <label for="bangladeshi-mobile">Bangladeshi</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="biryani-mobile">
                    <label for="biryani-mobile">Biryani</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="burgers-mobile">
                    <label for="burgers-mobile">Burgers</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="cafe-mobile">
                    <label for="cafe-mobile">Cafe</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="cakes-mobile">
                    <label for="cakes-mobile">Cakes</label>
                </div>
            </div>

            <div class="filter-section">
                <h6>Price</h6>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm">৳</button>
                    <button class="btn btn-outline-secondary btn-sm">৳৳</button>
                    <button class="btn btn-outline-secondary btn-sm">৳৳৳</button>
                </div>
            </div>

            <div class="mobile-filters-footer">
                <button class="btn-clear-filters" id="clearFiltersBtn">Clear All</button>
                <button class="btn-apply-filters" id="applyFiltersBtn">Apply Filters</button>
            </div>
        </div>
    </div>

    <!-- Main Layout -->
    <div class="main-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="filter-section">
                <h6>Filters</h6>
            </div>

            <div class="filter-section">
                <h6>Sort by</h6>
                <div class="filter-option">
                    <input type="radio" name="sort" id="relevance" checked>
                    <label for="relevance">Relevance</label>
                </div>
                <div class="filter-option">
                    <input type="radio" name="sort" id="fastest">
                    <label for="fastest">Fastest delivery</label>
                </div>
                <div class="filter-option">
                    <input type="radio" name="sort" id="distance">
                    <label for="distance">Distance</label>
                </div>
                <div class="filter-option">
                    <input type="radio" name="sort" id="top-rated">
                    <label for="top-rated">Top rated</label>
                </div>
            </div>

            <div class="filter-section">
                <h6>Quick filters</h6>
                <div class="filter-option">
                    <input type="checkbox" id="ratings-4">
                    <label for="ratings-4">Ratings 4+</label>
                </div>
                <div class="filter-option">
                    <input type="checkbox" id="super-restaurant">
                    <label for="super-restaurant">🔥 Super restaurant</label>
                </div>
            </div>

            <div class="filter-section">
                <h6>Offers</h6>
                <div class="filter-option">
                    <input type="checkbox" id="free-delivery">
                    <label for="free-delivery">Free delivery</label>
                </div>
                <div class="filter-option">
                    <input type="checkbox" id="accepts-vouchers">
                    <label for="accepts-vouchers">Accepts vouchers</label>
                </div>
                <div class="filter-option">
                    <input type="checkbox" id="deals">
                    <label for="deals">Deals</label>
                </div>
            </div>

            <div class="filter-section">
                <h6>Cuisines</h6>
                <div class="cuisine-item">
                    <input type="checkbox" id="american">
                    <label for="american">American</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="asian">
                    <label for="asian">Asian</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="bakery">
                    <label for="bakery">Bakery</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="bangladeshi">
                    <label for="bangladeshi">Bangladeshi</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="biryani">
                    <label for="biryani">Biryani</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="burgers">
                    <label for="burgers">Burgers</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="cafe">
                    <label for="cafe">Cafe</label>
                </div>
                <div class="cuisine-item">
                    <input type="checkbox" id="cakes">
                    <label for="cakes">Cakes</label>
                </div>
                <button class="show-more">Show more ▼</button>
            </div>

            <div class="filter-section">
                <h6>Price</h6>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm">৳</button>
                    <button class="btn btn-outline-secondary btn-sm">৳৳</button>
                    <button class="btn btn-outline-secondary btn-sm">৳৳৳</button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Search Bar -->
            <div class="search-container">
                <input type="text" class="search-input" placeholder="Search for restaurants, cuisines, and dishes" id="searchInput" autocomplete="off">
                <i class="fas fa-search search-icon"></i>
                <button class="clear-search-btn" id="clearSearchBtn" style="display: none;" onclick="clearSearch()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="search-suggestions" id="searchSuggestions">
                    <!-- Suggestions will be populated here -->
                </div>
            </div>

            <!-- Promotional Banner Carousel -->
            <div class="promo-carousel-container">
                <div id="promoCarousel" class="carousel slide" data-bs-ride="carousel">
                    <?php if (count($banners) > 1): ?>
                    <div class="carousel-indicators">
                        <?php foreach ($banners as $index => $banner): ?>
                        <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="<?= $index ?>" 
                                class="<?= $index === 0 ? 'active' : '' ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="carousel-inner">
                        <?php if (empty($banners)): ?>
                            <!-- Default banner if no banners in database -->
                            <div class="carousel-item active">
                                <div class="promo-banner" style="background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);">
                                    <div class="promo-content">
                                        <h2>Get 25% off</h2>
                                        <p>Min order Tk 250</p>
                                        <button class="promo-btn">Get it</button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($banners as $index => $banner): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <div class="promo-banner" style="
                                    <?php if (!empty($banner['background_image'])): ?>
                                        background-image: url('../<?= htmlspecialchars($banner['background_image']) ?>');
                                        background-size: cover;
                                        background-position: center;
                                    <?php endif; ?>
                                    background-color: <?= htmlspecialchars($banner['background_color'] ?? '#f97316') ?>;
                                ">
                                    <div class="promo-content" style="color: <?= htmlspecialchars($banner['text_color'] ?? '#ffffff') ?>">
                                        <h2><?= htmlspecialchars($banner['title']) ?></h2>
                                        <?php if (!empty($banner['subtitle'])): ?>
                                        <p><?= htmlspecialchars($banner['subtitle']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($banner['button_text'])): ?>
                                        <button class="promo-btn" onclick="window.location.href='<?= htmlspecialchars($banner['button_link'] ?? '#') ?>'"><?= htmlspecialchars($banner['button_text']) ?></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($banners) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Featured Restaurants Carousel -->
            <div class="restaurant-carousel">
                <div class="carousel-container">
                    <div class="carousel-nav prev" onclick="scrollCarousel('prev')">
                        <i class="fas fa-chevron-left"></i>
                    </div>
                    <div class="restaurant-cards" id="featuredRestaurants">
                        <!-- Featured restaurants will be loaded here -->
                    </div>
                    <div class="carousel-nav next" onclick="scrollCarousel('next')">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </div>

            <!-- Cuisines Section -->
            <div class="cuisines-section">
                <h2 class="section-title">Cuisines</h2>
                <div class="swiper cuisinesSwiper">
                    <div class="swiper-wrapper" id="cuisinesGrid">
                        <!-- Categories will be loaded dynamically -->
                        <div class="text-center w-100">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p class="mt-2">Loading cuisines...</p>
                        </div>
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>

            <!-- Featured Products Section -->
            <div class="featured-products-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="section-title mb-0">Featured Products & Top Choices</h2>
                    <a href="products.php" class="view-all-btn">
                        <i class="fas fa-arrow-right me-2"></i>View All Products
                    </a>
                </div>
                
                <!-- Featured Products Swiper -->
                <div class="mb-4">
                    <h4 class="mb-3">
                        <i class="fas fa-star text-warning me-2"></i>Featured Products
                    </h4>
                    <div class="swiper featuredProductsSwiper">
                        <div class="swiper-wrapper" id="featuredProductsGrid">
                            <!-- Featured products will be loaded here -->
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>
                
                <!-- Top Choice Products Swiper -->
                <div class="mb-4">
                    <h4 class="mb-3">
                        <i class="fas fa-trophy text-success me-2"></i>Top Choices
                    </h4>
                    <div class="swiper topChoiceProductsSwiper">
                        <div class="swiper-wrapper" id="topChoiceProductsGrid">
                            <!-- Top choice products will be loaded here -->
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>
            </div>

            <!-- Daily Deals Section -->
            <div class="deals-section">
                <h2 class="section-title">Your daily deals</h2>
                <div class="deals-grid">
                    <div class="deal-card" style="background: #ea580c;">
                        <div class="deal-title">Flat 50% off</div>
                        <div class="deal-subtitle">on <?= htmlspecialchars($siteName) ?></div>
                    </div>
                    <div class="deal-card" style="background: #10b981;">
                        <div class="deal-title">Late-night cravings?</div>
                        <div class="deal-subtitle">Order now</div>
                    </div>
                    <div class="deal-card" style="background: #059669;">
                        <div class="deal-title">15% off</div>
                        <div class="deal-subtitle">entire menu</div>
                    </div>
                    <div class="deal-card" style="background: #f97316;">
                        <div class="deal-title">Exclusive treats</div>
                        <div class="deal-subtitle">Just for you</div>
                    </div>
                </div>
            </div>

            <!-- Restaurant Listings -->
            <div class="restaurants-section">
                <h2 class="section-title">Flat 15% off entire menu</h2>
                <div class="swiper-container swiper-container-initialized swiper-container-horizontal swiper-container-pointer-events" id="restaurantsSwiper">
                    <div class="swiper-wrapper" id="restaurantsGrid">
                        <!-- Restaurants will be loaded here -->
                    </div>
                    <!-- Add Pagination -->
                    <div class="swiper-pagination"></div>
                    <!-- Add Navigation -->
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Professional Footer -->
    <footer class="footer-section">
        <div class="container-fluid" style="margin-left: 280px; max-width: calc(100vw - 280px); padding-left: 2rem; padding-right: 2rem;">
            <!-- Main Footer Content -->
            <div class="row py-5">
                <!-- Company Info -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-brand mb-3">
                        <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" 
                                 class="footer-logo" style="filter: brightness(0) invert(1);"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                            <i class="fas fa-utensils footer-logo-icon" style="display: none;"></i>
                        <?php else: ?>
                            <i class="fas fa-utensils footer-logo-icon"></i>
                        <?php endif; ?>
                    </div>
                    <p class="footer-description">
                        Your favorite food delivery service bringing delicious meals from the best restaurants right to your doorstep. Fast, fresh, and reliable.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="footer-title">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">All Products</a></li>
                        <li><a href="shops.php">Restaurants</a></li>
                        <li><a href="ordivomart.php">OrdivoMart</a></li>
                        <li><a href="favorites.php">Favorites</a></li>
                        <li><a href="orders.php">My Orders</a></li>
                    </ul>
                </div>

                <!-- Services -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="footer-title">Services</h5>
                    <ul class="footer-links">
                        <li><a href="delivery.php">Food Delivery</a></li>
                        <li><a href="pickup.php">Pickup</a></li>
                        <li><a href="#">Catering</a></li>
                        <li><a href="#">Corporate Orders</a></li>
                        <li><a href="#">Gift Cards</a></li>
                        <li><a href="#">Loyalty Program</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="footer-title">Support</h5>
                    <ul class="footer-links">
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">Track Order</a></li>
                        <li><a href="#">Report Issue</a></li>
                        <li><a href="#">Safety</a></li>
                        <li><a href="#">Accessibility</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="footer-title">Contact</h5>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+880 1234-567890</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>support@ordivo.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Dhaka, Bangladesh</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <span>24/7 Service</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Newsletter Section -->
            <div class="row py-4 border-top border-bottom">
                <div class="col-md-12 mb-3">
                    <h6 class="mb-3">Newsletter</h6>
                    <p class="text-white small mb-3">Subscribe to get special offers and updates</p>
                    <div class="newsletter-form">
                        <div class="input-group" style="max-width: 500px;">
                            <input type="email" class="form-control" placeholder="Enter your email">
                            <button class="btn btn-primary" type="button">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Footer -->
            <div class="row py-3">
                <div class="col-md-6">
                    <p class="footer-copyright mb-0">
                        &copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="footer-legal">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                        <a href="#">Cookie Policy</a>
                        <a href="#">Refund Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

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
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Universal SweetAlert Configuration -->
    <script src="../assets/js/sweet-alerts.js"></script>
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    
    <script>
        let currentCarouselIndex = 0;
        const carouselItemsPerView = 3;

        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            loadFeaturedRestaurants();
            loadFeaturedProducts();
            loadRestaurants();
            loadCategories();
            
            // Initialize filters only
            initializeFilters();
            
            // Mobile Filters Modal
            const navFiltersBtn = document.getElementById('navFiltersMobile');
            const mobileFiltersModal = document.getElementById('mobileFiltersModal');
            const closeFiltersModal = document.getElementById('closeFiltersModal');
            const applyFiltersBtn = document.getElementById('applyFiltersBtn');
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');

            if (navFiltersBtn && mobileFiltersModal) {
                // Open filters modal
                navFiltersBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    mobileFiltersModal.classList.add('show');
                    document.body.style.overflow = 'hidden';
                });

                // Close filters modal
                closeFiltersModal.addEventListener('click', function() {
                    mobileFiltersModal.classList.remove('show');
                    document.body.style.overflow = '';
                });

                // Close when clicking outside
                mobileFiltersModal.addEventListener('click', function(e) {
                    if (e.target === mobileFiltersModal) {
                        mobileFiltersModal.classList.remove('show');
                        document.body.style.overflow = '';
                    }
                });

                // Apply filters
                applyFiltersBtn.addEventListener('click', function() {
                    mobileFiltersModal.classList.remove('show');
                    document.body.style.overflow = '';
                    // Add your filter logic here
                    console.log('Filters applied');
                });

                // Clear filters
                clearFiltersBtn.addEventListener('click', function() {
                    // Clear all checkboxes and radio buttons
                    const checkboxes = mobileFiltersModal.querySelectorAll('input[type="checkbox"]');
                    const radios = mobileFiltersModal.querySelectorAll('input[type="radio"]');
                    
                    checkboxes.forEach(cb => cb.checked = false);
                    radios.forEach(radio => {
                        if (radio.id.includes('relevance')) {
                            radio.checked = true;
                        } else {
                            radio.checked = false;
                        }
                    });
                    
                    console.log('Filters cleared');
                });
            }
            
            // Mobile navigation toggle - Hamburger in header
            const navHamburgerCenter = document.getElementById('navHamburgerMobile');
            const navTabs = document.getElementById('navTabs');
            
            if (navHamburgerCenter && navTabs) {
                // Toggle menu
                navHamburgerCenter.addEventListener('click', function(e) {
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
                    if (navTabs.classList.contains('show')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
                
                // Close menu when clicking on a nav link
                const navLinks = navTabs.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 768) {
                            navTabs.classList.remove('show');
                            document.body.style.overflow = '';
                            const icon = navHamburgerCenter.querySelector('i');
                            icon.classList.remove('fa-times');
                            icon.classList.add('fa-bars');
                        }
                    });
                });
                
                // Close menu when clicking on backdrop (::before pseudo-element area)
                navTabs.addEventListener('click', function(e) {
                    // Check if click is on the backdrop area (outside the sidebar)
                    const rect = navTabs.getBoundingClientRect();
                    if (e.clientX > rect.right) {
                        navTabs.classList.remove('show');
                        document.body.style.overflow = '';
                        const icon = navHamburgerCenter.querySelector('i');
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
                
                // Close menu when clicking outside (for safety)
                document.addEventListener('click', function(e) {
                    if (navTabs.classList.contains('show') && 
                        !navTabs.contains(e.target) && 
                        !navHamburgerCenter.contains(e.target)) {
                        navTabs.classList.remove('show');
                        document.body.style.overflow = '';
                        const icon = navHamburgerCenter.querySelector('i');
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
            }
            
            // Debug logo loading
            const logoImg = document.querySelector('.navbar-brand img');
            if (logoImg) {
                console.log('Logo element found:', logoImg.src);
                logoImg.addEventListener('load', function() {
                    console.log('✓ Logo loaded successfully:', this.src);
                });
                logoImg.addEventListener('error', function() {
                    console.error('✗ Logo failed to load:', this.src);
                });
            } else {
                console.log('No logo image element found in navbar');
            }
            
            // Add special logo effects
            initLogoEffects();

            // Update cart badge count
            updateCartBadge();
        });

        // Function to update cart badge
        function updateCartBadge() {
            // Get cart count from localStorage or session
            const cartCount = localStorage.getItem('cartCount') || 0;
            const cartBadge = document.getElementById('cartBadge');
            
            if (cartBadge) {
                if (cartCount > 0) {
                    cartBadge.textContent = cartCount > 99 ? '99+' : cartCount;
                    cartBadge.style.display = 'flex';
                } else {
                    cartBadge.style.display = 'none';
                }
            }
        }

        // Load featured products
        async function loadFeaturedProducts() {
            try {
                const response = await fetch('?ajax=featured_products');
                const products = await response.json();
                
                // Separate featured and top choice products
                const featuredProducts = products.filter(product => product.is_featured);
                const topChoiceProducts = products.filter(product => product.is_top_choice);
                
                // Load featured products
                const featuredContainer = document.getElementById('featuredProductsGrid');
                const featuredCards = featuredProducts.map(product => `
                    <div class="swiper-slide">
                        <div class="product-card" onclick="viewProduct(${product.id})">
                            <div class="product-image" style="background-image: url('${product.image}')" 
                                 onError="this.style.backgroundImage = 'url(../uploads/images/placeholder-food.svg)'">
                                <div class="product-badge badge-featured">Featured</div>
                            </div>
                            <div class="product-info">
                                <div class="product-name">${product.name}</div>
                                <div class="product-vendor">
                                    <i class="fas fa-store me-1"></i>${product.vendor_name}
                                </div>
                                <div class="product-price">৳${product.price.toFixed(0)}</div>
                                <div class="product-rating">
                                    <i class="fas fa-star"></i>
                                    <span>${product.rating.toFixed(1)}</span>
                                </div>
                                <button class="btn btn-primary btn-sm w-100" onclick="event.stopPropagation(); addToCartFromHomepage(${product.id})">
                                    <i class="fas fa-plus me-1"></i>Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                featuredContainer.innerHTML = featuredCards || '<div class="swiper-slide"><div class="text-center text-muted">No featured products available</div></div>';
                
                // Initialize Featured Products Swiper
                new Swiper('.featuredProductsSwiper', {
                    slidesPerView: 'auto',
                    spaceBetween: 16,
                    navigation: {
                        nextEl: '.featuredProductsSwiper .swiper-button-next',
                        prevEl: '.featuredProductsSwiper .swiper-button-prev',
                    },
                    freeMode: true,
                    grabCursor: true,
                    breakpoints: {
                        // Mobile: 2 cards per view
                        320: {
                            slidesPerView: 2,
                            spaceBetween: 8,
                            freeMode: false,
                        },
                        // Tablet and up: auto width
                        769: {
                            slidesPerView: 'auto',
                            spaceBetween: 16,
                            freeMode: true,
                        }
                    }
                });
                
                // Load top choice products
                const topChoiceContainer = document.getElementById('topChoiceProductsGrid');
                const topChoiceCards = topChoiceProducts.map(product => `
                    <div class="swiper-slide">
                        <div class="product-card" onclick="viewProduct(${product.id})">
                            <div class="product-image" style="background-image: url('${product.image}')" 
                                 onError="this.style.backgroundImage = 'url(../uploads/images/placeholder-food.svg)'">
                                <div class="product-badge badge-top-choice">Top Choice</div>
                            </div>
                            <div class="product-info">
                                <div class="product-name">${product.name}</div>
                                <div class="product-vendor">
                                    <i class="fas fa-store me-1"></i>${product.vendor_name}
                                </div>
                                <div class="product-price">৳${product.price.toFixed(0)}</div>
                                <div class="product-rating">
                                    <i class="fas fa-star"></i>
                                    <span>${product.rating.toFixed(1)}</span>
                                </div>
                                <button class="btn btn-primary btn-sm w-100" onclick="event.stopPropagation(); addToCartFromHomepage(${product.id})">
                                    <i class="fas fa-plus me-1"></i>Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                topChoiceContainer.innerHTML = topChoiceCards || '<div class="swiper-slide"><div class="text-center text-muted">No top choice products available</div></div>';
                
                // Initialize Top Choice Products Swiper
                new Swiper('.topChoiceProductsSwiper', {
                    slidesPerView: 'auto',
                    spaceBetween: 16,
                    navigation: {
                        nextEl: '.topChoiceProductsSwiper .swiper-button-next',
                        prevEl: '.topChoiceProductsSwiper .swiper-button-prev',
                    },
                    freeMode: true,
                    grabCursor: true,
                    breakpoints: {
                        // Mobile: 2 cards per view
                        320: {
                            slidesPerView: 2,
                            spaceBetween: 8,
                            freeMode: false,
                        },
                        // Tablet and up: auto width
                        769: {
                            slidesPerView: 'auto',
                            spaceBetween: 16,
                            freeMode: true,
                        }
                    }
                });
                
            } catch (error) {
                console.error('Failed to load featured products:', error);
                document.getElementById('featuredProductsGrid').innerHTML = '<div class="swiper-slide"><div class="text-center text-danger">Failed to load featured products</div></div>';
                document.getElementById('topChoiceProductsGrid').innerHTML = '<div class="swiper-slide"><div class="text-center text-danger">Failed to load top choice products</div></div>';
            }
        }

        // View product function
        function viewProduct(productId) {
            window.location.href = `product_details.php?id=${productId}`;
        }

        // Add to cart from homepage
        function addToCartFromHomepage(productId) {
            const formData = new FormData();
            formData.append('action', 'add_to_cart');
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            
            fetch('', {
                method: 'POST',
                body: formData
            }).then(() => {
                showNotification('Product added to cart!', 'success');
            }).catch(error => {
                showNotification('Failed to add product to cart', 'error');
            });
        }

        // Show notification function
        function showNotification(message, type) {
            if (type === 'success') {
                showToast(message, 'success');
            } else {
                showToast(message, 'error');
            }
        }

        // Enhanced logo effects
        function initLogoEffects() {
            const logoImg = document.querySelector('.navbar-brand img');
            const logoIcon = document.querySelector('.navbar-brand i.logo-icon');
            
            const logoElement = logoImg || logoIcon;
            
            if (logoElement) {
                // Add special glow effect on hover
                logoElement.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.2) rotate(10deg)';
                    this.style.transition = 'all 0.3s ease';
                });
                
                logoElement.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
                
                // Add periodic attention-grabbing effect
                setInterval(function() {
                    if (Math.random() < 0.1) { // 10% chance every interval
                        logoElement.classList.add('logo-sparkle');
                        setTimeout(() => {
                            logoElement.classList.remove('logo-sparkle');
                        }, 2000);
                    }
                }, 10000); // Check every 10 seconds
            }
        }

        async function loadFeaturedRestaurants() {
            try {
                const response = await fetch('?ajax=featured_restaurants');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const restaurants = await response.json();
                
                const container = document.getElementById('featuredRestaurants');
                if (!container) return;
                
                if (!restaurants || restaurants.length === 0) {
                    container.innerHTML = '<div class="text-center text-muted">No featured restaurants available</div>';
                    return;
                }
                
                const restaurantCards = restaurants.map(restaurant => `
                    <div class="restaurant-card" onclick="viewRestaurant(${restaurant.id})">
                        <div class="restaurant-image" style="background-image: url('${restaurant.image}')">
                            ${restaurant.badge ? `<div class="restaurant-badge">${restaurant.badge}</div>` : ''}
                            ${restaurant.logo ? `<div class="restaurant-logo"><img src="${restaurant.logo}" alt="${restaurant.name}" /></div>` : ''}
                            <div class="restaurant-time">${restaurant.time}</div>
                        </div>
                        <div class="restaurant-info">
                            <div class="restaurant-name">${restaurant.name}</div>
                            <div class="restaurant-meta">
                                <div class="rating">
                                    <i class="fas fa-star rating-star"></i>
                                    <span>${restaurant.rating}</span>
                                    <span>(${restaurant.reviews}+)</span>
                                </div>
                                <div>${restaurant.category}</div>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                container.innerHTML = restaurantCards;
            } catch (error) {
                console.error('Failed to load featured restaurants:', error);
                const container = document.getElementById('featuredRestaurants');
                if (container) {
                    container.innerHTML = '<div class="text-center text-muted">Unable to load featured restaurants</div>';
                }
            }
        }

        async function loadRestaurants() {
            try {
                const response = await fetch('?ajax=restaurants');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const restaurants = await response.json();
                
                const container = document.getElementById('restaurantsGrid');
                if (!container) return;
                
                if (!restaurants || restaurants.length === 0) {
                    container.innerHTML = '<div class="swiper-slide"><div class="text-center text-muted p-4">No restaurants available</div></div>';
                    return;
                }
                
                const restaurantCards = restaurants.map(restaurant => `
                    <div class="swiper-slide">
                        <div class="restaurant-listing" onclick="viewRestaurant(${restaurant.id})" style="width: 100%; height: 100%;">
                            <div class="restaurant-listing-image" style="background-image: url('${restaurant.image}')">
                                <div class="restaurant-badge">${restaurant.badge}</div>
                                ${restaurant.logo ? `<div class="restaurant-listing-logo"><img src="${restaurant.logo}" alt="${restaurant.name}" /></div>` : ''}
                            </div>
                            <div class="restaurant-listing-info">
                                <div class="restaurant-listing-name">${restaurant.name}</div>
                                <div class="restaurant-listing-meta">
                                    <div class="rating">
                                        <i class="fas fa-star rating-star"></i>
                                        <span>${restaurant.rating}</span>
                                        <span>(${restaurant.reviews})</span>
                                    </div>
                                    <div>${restaurant.time} • ${restaurant.category}</div>
                                </div>
                                <div class="restaurant-offer">${restaurant.offer}</div>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                container.innerHTML = restaurantCards;
                
                // Initialize Swiper after content is loaded
                initRestaurantsSwiper();
                
            } catch (error) {
                console.error('Failed to load restaurants:', error);
            }
        }

        // Load categories dynamically
        async function loadCategories() {
            try {
                const response = await fetch('?ajax=categories');
                const categories = await response.json();
                
                if (categories.error) {
                    console.error('Categories error:', categories.error);
                    return;
                }
                
                const container = document.getElementById('cuisinesGrid');
                
                const categoryCards = categories.map(category => {
                    // Use image if available, otherwise use icon
                    let backgroundStyle = '';
                    if (category.image) {
                        backgroundStyle = `background-image: url('${category.image}'); background-size: cover; background-position: center;`;
                    } else if (category.icon) {
                        // If no image but has icon, show icon instead
                        return `
                            <div class="swiper-slide">
                                <div class="cuisine-card" onclick="viewCategory('${category.name}')">
                                    <div class="cuisine-icon d-flex align-items-center justify-content-center" style="background: #f0fdf4;">
                                        <i class="${category.icon}" style="font-size: 2rem; color: #10b981;"></i>
                                    </div>
                                    <div class="cuisine-name">${category.name}</div>
                                </div>
                            </div>
                        `;
                    }
                    
                    return `
                        <div class="swiper-slide">
                            <div class="cuisine-card" onclick="viewCategory('${category.name}')">
                                <div class="cuisine-icon" style="${backgroundStyle}"></div>
                                <div class="cuisine-name">${category.name}</div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                container.innerHTML = categoryCards;
                
                // Initialize Swiper after loading categories
                new Swiper('.cuisinesSwiper', {
                    slidesPerView: 'auto',
                    spaceBetween: 0,
                    navigation: {
                        nextEl: '.cuisinesSwiper .swiper-button-next',
                        prevEl: '.cuisinesSwiper .swiper-button-prev',
                    },
                    freeMode: true,
                    grabCursor: true,
                    breakpoints: {
                        // Mobile: 4 cards per view
                        320: {
                            slidesPerView: 4,
                            spaceBetween: 8,
                            freeMode: false,
                        },
                        // Tablet and up: auto width
                        769: {
                            slidesPerView: 'auto',
                            spaceBetween: 0,
                            freeMode: true,
                        }
                    }
                });
                
            } catch (error) {
                console.error('Failed to load categories:', error);
                // Fallback to show message
                const container = document.getElementById('cuisinesGrid');
                container.innerHTML = '<div class="text-center w-100"><p class="text-muted">Unable to load cuisines</p></div>';
            }
        }

        // View category function
        function viewCategory(categoryName) {
            // Redirect to products page with category filter
            window.location.href = `products.php?category=${encodeURIComponent(categoryName)}`;
        }

        // Image error handling
        function handleImageError(img, fallbackIcon = 'fas fa-image') {
            if (img.dataset.error === 'true') return; // Already handled
            
            img.dataset.error = 'true';
            const parent = img.parentElement;
            
            // Create fallback div
            const fallback = document.createElement('div');
            fallback.className = 'image-fallback w-100 h-100 d-flex align-items-center justify-content-center';
            fallback.innerHTML = `<i class="${fallbackIcon} fa-3x text-muted"></i>`;
            
            // Replace image with fallback
            parent.replaceChild(fallback, img);
        }

        // Initialize navigation functionality
        function initializeNavigation() {
            // Get all nav links
            const navLinks = document.querySelectorAll('.nav-tabs .nav-link');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const mode = this.dataset.mode;
                    
                    // Skip if it's a regular link (All Products, ordivomart, Shops)
                    if (!mode) return;
                    
                    e.preventDefault();
                    
                    // Handle different navigation items
                    switch(mode) {
                        case 'pickup':
                            handlePickupMode();
                            break;
                        case 'delivery':
                            handleDeliveryMode();
                            break;
                    }
                    
                    // Update active state
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        }

        // Handle delivery mode
        function handleDeliveryMode() {
            showNotification('🚚 Delivery mode activated', 'Showing restaurants that deliver to your area');
            // Reload normal restaurants
            loadFeaturedRestaurants();
            loadRestaurants();
        }

        // Handle pickup mode
        function handlePickupMode() {
            window.location.href = 'pickup.php';
        }

        // Handle ordivomart
        function handleOrdivomart() {
            window.location.href = 'ordivomart.php';
        }

        // Handle shops
        function handleShops() {
            window.location.href = 'shops.php';
        }

        // Load restaurants with specific filter
        async function loadRestaurantsWithFilter(filterType) {
            try {
                const response = await fetch(`?ajax=restaurants&filter=${filterType}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const restaurants = await response.json();
                
                if (!restaurants || restaurants.length === 0) {
                    showNotification('ℹ️ No Results', 'No restaurants found for this filter');
                    return;
                }
                
                // Update featured restaurants section
                updateRestaurantDisplay(restaurants);
                
            } catch (error) {
                console.error('Failed to load filtered restaurants:', error);
                showNotification('❌ Error', 'Failed to load restaurants. Please try again.');
            }
        }

        // Update restaurant display
        function updateRestaurantDisplay(restaurants) {
            // Update featured restaurants section
            const featuredContainer = document.getElementById('featuredRestaurants');
            if (featuredContainer && restaurants.length > 0) {
                const featuredCards = restaurants.slice(0, 6).map(restaurant => `
                    <div class="restaurant-card" onclick="viewRestaurant(${restaurant.id})">
                        <div class="restaurant-image" style="background-image: url('${restaurant.image}')">
                            <div class="restaurant-badge">${restaurant.badge}</div>
                            ${restaurant.logo ? `<div class="restaurant-logo"><img src="${restaurant.logo}" alt="${restaurant.name}" /></div>` : ''}
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
                        </div>
                    </div>
                `).join('');
                
                featuredContainer.innerHTML = featuredCards;
            }
            
            // Update restaurants swiper
            const swiperContainer = document.querySelector('#restaurantsSwiper .swiper-wrapper');
            if (swiperContainer && restaurants.length > 0) {
                const swiperSlides = restaurants.map(restaurant => `
                    <div class="swiper-slide">
                        <div class="restaurant-listing" onclick="viewRestaurant(${restaurant.id})" style="width: 100%; height: 100%;">
                            <div class="restaurant-listing-image" style="background-image: url('${restaurant.image}')">
                                <div class="restaurant-badge">${restaurant.badge}</div>
                            </div>
                            <div class="restaurant-listing-info">
                                <h6>${restaurant.name}</h6>
                                <div class="restaurant-listing-meta">
                                    <span class="rating">
                                        <i class="fas fa-star"></i> ${restaurant.rating}
                                    </span>
                                    <span class="time">${restaurant.time}</span>
                                </div>
                                <p class="restaurant-listing-category">${restaurant.category}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                swiperContainer.innerHTML = swiperSlides;
                
                // Reinitialize Swiper if it exists
                if (window.restaurantsSwiper) {
                    window.restaurantsSwiper.update();
                }
            }
        }

        // Initialize filters functionality
        function initializeFilters() {
            // Sort filters
            const sortFilters = document.querySelectorAll('input[name="sort"]');
            sortFilters.forEach(filter => {
                filter.addEventListener('change', function() {
                    if (this.checked) {
                        applySortFilter(this.id);
                    }
                });
            });
            
            // Quick filters
            const quickFilters = document.querySelectorAll('#ratings-4, #super-restaurant');
            quickFilters.forEach(filter => {
                filter.addEventListener('change', function() {
                    applyQuickFilter(this.id, this.checked);
                });
            });
            
            // Offer filters
            const offerFilters = document.querySelectorAll('#free-delivery, #accepts-vouchers, #deals');
            offerFilters.forEach(filter => {
                filter.addEventListener('change', function() {
                    applyOfferFilter(this.id, this.checked);
                });
            });
            
            // Cuisine filters
            const cuisineFilters = document.querySelectorAll('#american, #asian');
            cuisineFilters.forEach(filter => {
                filter.addEventListener('change', function() {
                    applyCuisineFilter(this.id, this.checked);
                });
            });
        }

        // Apply sort filter
        function applySortFilter(sortType) {
            let message = '';
            switch(sortType) {
                case 'relevance':
                    message = 'Sorted by relevance';
                    break;
                case 'fastest':
                    message = 'Sorted by fastest delivery';
                    break;
                case 'distance':
                    message = 'Sorted by distance';
                    break;
                case 'top-rated':
                    message = 'Sorted by top rated';
                    break;
            }
            showNotification('🔄 Filter Applied', message);
            // Reload restaurants with sort
            loadRestaurantsWithSort(sortType);
        }

        // Apply quick filter
        function applyQuickFilter(filterType, isChecked) {
            const message = isChecked ? 'Filter applied' : 'Filter removed';
            if (filterType === 'ratings-4') {
                showNotification('⭐ Rating Filter', `${message}: Ratings 4+`);
            } else if (filterType === 'super-restaurant') {
                showNotification('🔥 Super Restaurant', `${message}: Super restaurants only`);
            }
        }

        // Apply offer filter
        function applyOfferFilter(filterType, isChecked) {
            const message = isChecked ? 'Filter applied' : 'Filter removed';
            let filterName = '';
            switch(filterType) {
                case 'free-delivery':
                    filterName = 'Free delivery';
                    break;
                case 'accepts-vouchers':
                    filterName = 'Accepts vouchers';
                    break;
                case 'deals':
                    filterName = 'Deals';
                    break;
            }
            showNotification('💰 Offer Filter', `${message}: ${filterName}`);
        }

        // Apply cuisine filter
        function applyCuisineFilter(cuisineType, isChecked) {
            const message = isChecked ? 'Filter applied' : 'Filter removed';
            const cuisineName = cuisineType.charAt(0).toUpperCase() + cuisineType.slice(1);
            showNotification('🍽️ Cuisine Filter', `${message}: ${cuisineName} cuisine`);
        }

        // Load restaurants with sort
        async function loadRestaurantsWithSort(sortType) {
            try {
                const response = await fetch(`?ajax=restaurants&sort=${sortType}`);
                const restaurants = await response.json();
                
                if (restaurants.error) {
                    console.error('Sorted restaurants error:', restaurants.error);
                    return;
                }
                
                // Update displays (similar to loadRestaurantsWithFilter)
                // Implementation would be similar to above
                
            } catch (error) {
                console.error('Failed to load sorted restaurants:', error);
            }
        }

        // Show notification
        function showNotification(title, message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'notification-toast';
            notification.innerHTML = `
                <div class="alert alert-info alert-dismissible fade show position-fixed" style="top: 120px; right: 20px; z-index: 9999; min-width: 300px;">
                    <strong>${title}</strong><br>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }

        // Initialize Swiper for restaurants section
        function initRestaurantsSwiper() {
            const restaurantsSwiper = new Swiper('#restaurantsSwiper', {
                slidesPerView: 2,
                spaceBetween: 12,
                loop: true,
                autoplay: {
                    delay: 4000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                breakpoints: {
                    // Mobile: 2 cards per view
                    320: {
                        slidesPerView: 2,
                        spaceBetween: 12,
                    },
                    640: {
                        slidesPerView: 2,
                        spaceBetween: 20,
                    },
                    768: {
                        slidesPerView: 2,
                        spaceBetween: 30,
                    },
                    1024: {
                        slidesPerView: 3,
                        spaceBetween: 30,
                    },
                    1200: {
                        slidesPerView: 4,
                        spaceBetween: 30,
                    }
                }
            });
        }

        function scrollCarousel(direction) {
            const container = document.getElementById('featuredRestaurants');
            const cards = container.querySelectorAll('.restaurant-card');
            const cardWidth = cards[0].offsetWidth + 16; // Card width + gap
            
            if (direction === 'prev') {
                currentCarouselIndex = Math.max(0, currentCarouselIndex - 1);
            } else {
                const maxIndex = Math.max(0, cards.length - carouselItemsPerView);
                currentCarouselIndex = Math.min(maxIndex, currentCarouselIndex + 1);
            }
            
            const translateX = -(currentCarouselIndex * cardWidth);
            container.style.transform = `translateX(${translateX}px)`;
        }

        function viewRestaurant(restaurantId) {
            window.location.href = `vendor_profile.php?id=${restaurantId}`;
        }

        // Enhanced Search functionality with autocomplete
        let searchTimeout;
        let currentSuggestionIndex = -1;
        let suggestions = [];
        let isLoading = false;

        const searchInput = document.getElementById('searchInput');
        const searchSuggestions = document.getElementById('searchSuggestions');
        const clearSearchBtn = document.getElementById('clearSearchBtn');

        // Search input event listeners
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Show/hide clear button
            clearSearchBtn.style.display = query.length > 0 ? 'flex' : 'none';
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                hideSuggestions();
                return;
            }

            // Show loading state
            showLoadingState();

            // Debounce search requests
            searchTimeout = setTimeout(() => {
                fetchSuggestions(query);
            }, 300);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (!searchSuggestions.classList.contains('show')) return;

            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    currentSuggestionIndex = Math.min(currentSuggestionIndex + 1, suggestions.length - 1);
                    updateSuggestionHighlight();
                    break;
                
                case 'ArrowUp':
                    e.preventDefault();
                    currentSuggestionIndex = Math.max(currentSuggestionIndex - 1, -1);
                    updateSuggestionHighlight();
                    break;
                
                case 'Enter':
                    e.preventDefault();
                    if (currentSuggestionIndex >= 0) {
                        selectSuggestion(suggestions[currentSuggestionIndex]);
                    } else {
                        performSearch(this.value);
                    }
                    break;
                
                case 'Escape':
                    hideSuggestions();
                    break;
            }
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                hideSuggestions();
            }
        });

        // Clear search function
        function clearSearch() {
            searchInput.value = '';
            clearSearchBtn.style.display = 'none';
            hideSuggestions();
            searchInput.focus();
        }

        // Show loading state
        function showLoadingState() {
            searchSuggestions.innerHTML = `
                <div class="suggestion-item">
                    <i class="fas fa-spinner fa-spin suggestion-icon"></i>
                    <div class="suggestion-text">Searching...</div>
                </div>
            `;
            searchSuggestions.classList.add('show');
            isLoading = true;
        }

        // Fetch suggestions from server
        async function fetchSuggestions(query) {
            if (isLoading) return;
            
            try {
                isLoading = true;
                const response = await fetch(`search_suggestions.php?q=${encodeURIComponent(query)}&limit=8`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                suggestions = await response.json();
                displaySuggestions(suggestions);
                
            } catch (error) {
                console.error('Error fetching suggestions:', error);
                showErrorState();
            } finally {
                isLoading = false;
            }
        }

        // Show error state
        function showErrorState() {
            searchSuggestions.innerHTML = `
                <div class="suggestion-item">
                    <i class="fas fa-exclamation-triangle suggestion-icon" style="color: #dc3545;"></i>
                    <div class="suggestion-text">Unable to load suggestions</div>
                </div>
            `;
            searchSuggestions.classList.add('show');
        }

        // Display suggestions in dropdown
        function displaySuggestions(suggestionList) {
            if (suggestionList.length === 0) {
                searchSuggestions.innerHTML = '<div class="no-suggestions">No suggestions found</div>';
                searchSuggestions.classList.add('show');
                return;
            }

            const suggestionHTML = suggestionList.map((suggestion, index) => `
                <div class="suggestion-item" data-index="${index}" onclick="selectSuggestion(suggestions[${index}])">
                    <i class="${suggestion.icon} suggestion-icon"></i>
                    <div class="suggestion-text">
                        <div>${highlightMatch(suggestion.text, searchInput.value)}</div>
                        ${suggestion.subtitle ? `<small class="suggestion-type">${suggestion.subtitle}</small>` : ''}
                    </div>
                </div>
            `).join('');

            searchSuggestions.innerHTML = suggestionHTML;
            searchSuggestions.classList.add('show');
            currentSuggestionIndex = -1;
        }

        // Highlight matching text in suggestions
        function highlightMatch(text, query) {
            if (!query) return text;
            
            const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            return text.replace(regex, '<strong>$1</strong>');
        }

        // Update suggestion highlight
        function updateSuggestionHighlight() {
            const suggestionItems = searchSuggestions.querySelectorAll('.suggestion-item');
            
            suggestionItems.forEach((item, index) => {
                item.classList.toggle('active', index === currentSuggestionIndex);
            });

            // Scroll highlighted item into view
            if (currentSuggestionIndex >= 0) {
                const activeItem = suggestionItems[currentSuggestionIndex];
                if (activeItem) {
                    activeItem.scrollIntoView({ block: 'nearest' });
                }
            }
        }

        // Select a suggestion
        function selectSuggestion(suggestion) {
            searchInput.value = suggestion.text;
            hideSuggestions();
            
            // Add to recent searches (localStorage)
            addToRecentSearches(suggestion.text);
            
            // Show loading toast
            if (typeof showToast !== 'undefined') {
                showToast('Searching for ' + suggestion.text + '...', 'info');
            }
            
            // Perform search based on suggestion type
            switch(suggestion.type) {
                case 'product':
                    // Redirect to product page or filter products
                    window.location.href = `products.php?search=${encodeURIComponent(suggestion.text)}`;
                    break;
                
                case 'category':
                    // Redirect to category page
                    window.location.href = `products.php?category=${encodeURIComponent(suggestion.text)}`;
                    break;
                
                case 'vendor':
                    // Redirect to vendor page
                    window.location.href = `vendor_profile.php?vendor=${suggestion.id}`;
                    break;
                
                default:
                    // General search
                    performSearch(suggestion.text);
                    break;
            }
        }

        // Add to recent searches
        function addToRecentSearches(searchTerm) {
            try {
                let recentSearches = JSON.parse(localStorage.getItem('recentSearches') || '[]');
                
                // Remove if already exists
                recentSearches = recentSearches.filter(term => term !== searchTerm);
                
                // Add to beginning
                recentSearches.unshift(searchTerm);
                
                // Keep only last 10 searches
                recentSearches = recentSearches.slice(0, 10);
                
                localStorage.setItem('recentSearches', JSON.stringify(recentSearches));
            } catch (error) {
                console.error('Error saving recent search:', error);
            }
        }

        // Hide suggestions dropdown
        function hideSuggestions() {
            searchSuggestions.classList.remove('show');
            currentSuggestionIndex = -1;
            isLoading = false;
        }

        // Perform search
        function performSearch(query) {
            if (query.trim()) {
                addToRecentSearches(query.trim());
                
                // Show loading toast
                if (typeof showToast !== 'undefined') {
                    showToast('Searching for ' + query.trim() + '...', 'info');
                }
                
                window.location.href = `products.php?search=${encodeURIComponent(query.trim())}`;
            }
        }

        // Legacy search functionality (keeping for compatibility)
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    performSearch(query);
                }
            }
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