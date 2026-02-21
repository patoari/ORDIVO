<?php
/**
 * ORDIVO - Product Browsing & Search
 * Phase 3: Customer Experience - Page 10
 * Product catalog browsing and search functionality
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
    
} catch (Exception $e) {
    error_log("Error loading site settings: " . $e->getMessage());
    $siteLogo = '';
    $siteName = 'ORDIVO';
}

$vendorId = (int)($_GET['vendor'] ?? 0);
$searchQuery = sanitizeInput($_GET['search'] ?? '');
$categoryFilter = sanitizeInput($_GET['category'] ?? '');
$sortBy = sanitizeInput($_GET['sort'] ?? 'popular');
$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 1000);
$filterType = sanitizeInput($_GET['filter'] ?? ''); // featured, top-choice

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'products':
            try {
                $whereConditions = ["p.is_available = 1"];
                $params = [];
                
                // Pagination parameters
                $page = max(1, (int)($_GET['page'] ?? 1));
                $perPage = 18; // Products per page
                $offset = ($page - 1) * $perPage;
                
                if ($vendorId) {
                    $whereConditions[] = "p.vendor_id = ?";
                    $params[] = $vendorId;
                }
                
                if ($searchQuery) {
                    $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
                    $params[] = "%$searchQuery%";
                    $params[] = "%$searchQuery%";
                }
                
                if ($categoryFilter) {
                    $whereConditions[] = "p.category = ?";
                    $params[] = $categoryFilter;
                }
                
                if ($minPrice > 0) {
                    $whereConditions[] = "p.price >= ?";
                    $params[] = $minPrice;
                }
                
                if ($maxPrice < 1000) {
                    $whereConditions[] = "p.price <= ?";
                    $params[] = $maxPrice;
                }
                
                // Handle filter type from homepage
                $ajaxFilterType = sanitizeInput($_GET['filter'] ?? '');
                if ($ajaxFilterType === 'featured') {
                    $whereConditions[] = "p.is_featured = 1";
                } elseif ($ajaxFilterType === 'top-choice') {
                    $whereConditions[] = "p.is_top_choice = 1";
                }
                
                $whereClause = implode(' AND ', $whereConditions);
                
                $orderBy = match($sortBy) {
                    'price_low' => 'p.price ASC',
                    'price_high' => 'p.price DESC',
                    'rating' => 'p.rating DESC',
                    'newest' => 'p.created_at DESC',
                    default => 'p.is_featured DESC, p.rating DESC'
                };
                
                // Get total count for pagination
                $totalCount = fetchValue("
                    SELECT COUNT(*)
                    FROM products p
                    INNER JOIN users u ON p.vendor_id = u.id AND u.role = 'vendor' AND u.status = 'active'
                    WHERE $whereClause
                ", $params);
                
                // Get products for current page
                $products = fetchAll("
                    SELECT p.*, u.name as vendor_name
                    FROM products p
                    INNER JOIN users u ON p.vendor_id = u.id AND u.role = 'vendor' AND u.status = 'active'
                    WHERE $whereClause
                    ORDER BY $orderBy
                    LIMIT $perPage OFFSET $offset
                ", $params);
                
                // Calculate pagination info
                $totalPages = ceil($totalCount / $perPage);
                
                echo json_encode([
                    'products' => $products,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total_count' => $totalCount,
                        'total_pages' => $totalPages,
                        'has_prev' => $page > 1,
                        'has_next' => $page < $totalPages
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'product_details':
            $productId = (int)($_GET['id'] ?? 0);
            try {
                $product = fetchRow("
                    SELECT p.*, u.name as vendor_name, u.address as vendor_address
                    FROM products p
                    INNER JOIN users u ON p.vendor_id = u.id AND u.role = 'vendor' AND u.status = 'active'
                    WHERE p.id = ?
                ", [$productId]);
                
                if ($product) {
                    // Set default values for missing review data
                    $product['avg_rating'] = $product['rating'] ?? 4.5;
                    $product['review_count'] = $product['total_reviews'] ?? 0;
                    $product['reviews'] = []; // No reviews for now
                }
                
                echo json_encode($product);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
            
        case 'categories':
            try {
                $categories = fetchAll("
                    SELECT c.name as category, COUNT(*) as product_count
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.is_available = 1
                    " . ($vendorId ? "AND p.vendor_id = $vendorId" : "") . "
                    GROUP BY c.name
                    ORDER BY product_count DESC, c.name ASC
                ");
                echo json_encode($categories);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
    }
}

// Get vendor info if specified
$vendor = null;
if ($vendorId) {
    try {
        $vendor = fetchRow("
            SELECT id, name, business_name, business_category, address, phone, 
                   rating, total_reviews, avg_delivery_time, delivery_fee, min_order_amount,
                   is_open, banner_image, logo_image
            FROM users 
            WHERE id = ? AND role = 'vendor' AND status = 'active'
        ", [$vendorId]);
    } catch (Exception $e) {
        $vendor = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $vendor ? htmlspecialchars($vendor['business_name'] ?? $vendor['name']) . ' - ' : '' ?>Products - ORDIVO</title>
    
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
            --ordivo-success: #28a745;
            --ordivo-warning: #ffc107;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            line-height: 1.6;
            margin: 0;
            padding-top: 180px; /* Header (100px) + Search/Filters (80px) */
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
            padding: 0 2rem; /* Add horizontal padding for better spacing */
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

        /* Logo styling */
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

        .navbar-brand i.fa-utensils {
            font-size: 2.5rem !important;
            color: var(--ordivo-primary);
            animation: logoPulse 2s ease-in-out infinite, logoColorShift 6s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .location-display {
            display: flex;
            align-items: center;
            color: var(--ordivo-dark);
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



        /* Header buttons styling */
        .header .btn-outline-light {
            border-color: #e9ecef;
            color: var(--ordivo-dark);
            background: white;
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .header .btn-outline-light:hover {
            background: #f8f9fa;
            color: var(--ordivo-dark);
            border-color: #e9ecef;
        }

        .header .btn-light {
            background: white;
            color: var(--ordivo-dark);
            border: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .header .btn-light:hover {
            background: #f8f9fa;
            color: var(--ordivo-dark);
            border-color: #e9ecef;
        }

        .header .badge {
            background: var(--ordivo-primary) !important;
            color: white !important;
        }

        .header .ms-auto {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Vendor Header */
        .vendor-header {
            background: white;
            padding: 2rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .vendor-banner {
            height: 200px;
            background: linear-gradient(135deg, var(--ordivo-light) 0%, #f8f9fa 100%);
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }

        .vendor-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .vendor-info {
            padding: 1.5rem 0;
        }

        .vendor-name {
            font-size: 2rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin-bottom: 0.5rem;
        }

        .vendor-meta {
            display: flex;
            gap: 2rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .vendor-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .vendor-status.open {
            background: var(--ordivo-success);
            color: white;
        }

        .vendor-status.closed {
            background: #dc3545;
            color: white;
        }

        /* Search & Filters */
        .search-filters {
            background: white;
            padding: 1.25rem 0;
            border-bottom: 1px solid #e9ecef;
            position: fixed;
            top: 100px; /* Right below header (100px) */
            left: 0;
            right: 0;
            z-index: 999;
            box-shadow: 0 2px 4px #e5e7eb;
            height: 80px;
            border-top: 2px solid transparent;
            border-bottom: 2px solid transparent;
            background: #10b981;
            background-origin: border-box;
            background-clip: padding-box, border-box;
            animation: searchBorderPulse 3s ease-in-out infinite;
        }

        @keyframes searchBorderPulse {
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

        .search-box {
            position: relative;
        }

        .search-input {
            border: 2px solid #e9ecef;
            border-radius: 25px;
            padding: 0.75rem 1rem;
            padding-left: 3rem;
            font-size: 1rem;
            width: 100%;
        }

        .search-input:focus {
            border-color: var(--ordivo-primary);
            box-shadow: 0 0 0 0.2rem #f97316;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .filter-btn {
            background: none;
            border: 1px solid #dee2e6;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            color: var(--ordivo-dark);
            transition: all 0.3s ease;
        }

        .filter-btn.active, .filter-btn:hover {
            background: var(--ordivo-primary);
            border-color: var(--ordivo-primary);
            color: white;
        }

        /* Product Cards */
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid var(--ordivo-primary);
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px #e5e7eb;
            border-color: var(--ordivo-secondary);
        }

        .product-image {
            height: 200px;
            background: linear-gradient(135deg, var(--ordivo-light) 0%, #f8f9fa 100%);
            position: relative;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .product-badge.featured {
            background: var(--ordivo-primary);
            color: white;
        }

        .product-badge.new {
            background: var(--ordivo-success);
            color: white;
        }

        .product-info {
            padding: 1rem;
        }

        .product-name {
            font-weight: 700;
            color: var(--ordivo-dark);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            line-height: 1.3;
        }

        .product-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--ordivo-primary);
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .rating-star {
            color: var(--ordivo-warning);
        }

        .add-to-cart-btn {
            background: var(--ordivo-primary);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: var(--ordivo-secondary);
            transform: translateY(-2px);
        }

        /* Sidebar */
        .sidebar {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 190px; /* Header (100px) + Search/Filters (80px) + margin (10px) */
        }

        .sidebar h5 {
            color: var(--ordivo-dark);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .category-item:hover {
            color: var(--ordivo-primary);
        }

        .category-item.active {
            color: var(--ordivo-primary);
            font-weight: 600;
        }

        .price-range {
            margin-top: 1rem;
        }

        .price-inputs {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .price-input {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Pagination */
        .pagination {
            margin-bottom: 0;
        }

        .pagination .page-link {
            color: var(--ordivo-primary);
            border: 1px solid #dee2e6;
            padding: 0.5rem 0.75rem;
            margin: 0 0.125rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .pagination .page-link:hover {
            color: white;
            background-color: var(--ordivo-primary);
            border-color: var(--ordivo-primary);
            transform: translateY(-1px);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--ordivo-primary);
            border-color: var(--ordivo-primary);
            color: white;
            box-shadow: 0 2px 4px #f97316;
        }

        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            background-color: #fff;
            border-color: #dee2e6;
            cursor: not-allowed;
        }

        .pagination .page-item.disabled .page-link:hover {
            transform: none;
            background-color: #fff;
            color: #6c757d;
        }

        #paginationInfo {
            font-size: 0.9rem;
            color: #6c757d;
        }

        /* Image error handling */
        .product-image img, .cuisine-icon {
            transition: opacity 0.3s ease;
        }
        
        .product-image img[data-error="true"], .cuisine-icon[data-error="true"] {
            opacity: 0.7;
            filter: grayscale(20%);
        }
        
        .image-fallback {
            background: linear-gradient(135deg, var(--ordivo-light) 0%, #f8f9fa 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 2rem;
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
            background: var(--ordivo-light);
            border-color: var(--ordivo-primary);
        }

        .location-option.selected {
            background: var(--ordivo-primary);
            color: white;
            border-color: var(--ordivo-primary);
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
            border-color: var(--ordivo-primary);
            box-shadow: 0 0 0 0.2rem #f97316;
        }

        .modal-header {
            border-bottom: 2px solid var(--ordivo-light);
        }

        .modal-footer {
            border-top: 2px solid var(--ordivo-light);
        }

        /* Mobile Header Styles */
        .mobile-only {
            display: none;
        }

        .desktop-only {
            display: block;
        }

        /* Hide mobile sort panel on desktop */
        .mobile-sort-panel {
            display: none;
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

        @media (max-width: 768px) {
            .mobile-only {
                display: block;
            }

            .desktop-only {
                display: none !important;
            }

            body {
                padding-top: 184px; /* Row1(44px) + Row2(70px) + Search(70px) */
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

            .mobile-header-middle .navbar-brand i.fa-utensils {
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

            .cart-badge,
            #cartCount {
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
            
            .search-filters {
                height: 70px;
                top: 114px;
                padding: 1rem 0;
                background: white !important;
                border-top: none;
                border-bottom: 1px solid #e9ecef;
                animation: none;
            }

            /* Hide sort buttons on mobile - they're in the sort panel now */
            .search-filters .col-md-6:last-child {
                display: none !important;
            }
            
            .sidebar {
                position: fixed;
                top: 114px;
                left: -280px;
                width: 280px;
                height: calc(100vh - 114px);
                background: white;
                padding: 1.5rem 1rem;
                box-shadow: 2px 0 10px rgba(0,0,0,0.3);
                overflow-y: auto;
                z-index: 10000;
                transition: left 0.3s ease-in-out;
                border-radius: 0;
                margin-bottom: 0;
            }

            .sidebar.show {
                left: 0 !important;
            }

            /* Overlay backdrop for sidebar */
            .sidebar::before {
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

            .sidebar.show::before {
                width: calc(100vw - 280px);
                background: rgba(0, 0, 0, 0.5);
                pointer-events: auto;
            }

            /* Mobile Sort Panel */
            .mobile-sort-panel {
                display: block;
                position: fixed;
                top: 114px;
                right: -300px;
                width: 300px;
                height: calc(100vh - 114px);
                background: white;
                box-shadow: -2px 0 10px rgba(0,0,0,0.3);
                z-index: 10001;
                transition: right 0.3s ease-in-out;
                overflow-y: auto;
            }

            .mobile-sort-panel.show {
                right: 0 !important;
            }

            /* Overlay backdrop for sort panel */
            .mobile-sort-panel::before {
                content: '';
                position: fixed;
                top: 114px;
                right: 300px;
                width: 0;
                height: calc(100vh - 114px);
                background: rgba(0, 0, 0, 0);
                transition: all 0.3s ease-in-out;
                pointer-events: none;
                z-index: -1;
            }

            .mobile-sort-panel.show::before {
                width: calc(100vw - 300px);
                background: rgba(0, 0, 0, 0.5);
                pointer-events: auto;
            }

            .sort-panel-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 1.5rem;
                border-bottom: 2px solid #e9ecef;
                background: #10b981;
                color: white;
            }

            .sort-panel-header h5 {
                margin: 0;
                font-weight: 700;
            }

            .btn-close-sort {
                background: transparent;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .sort-panel-content {
                padding: 1rem;
            }

            .sort-option {
                display: flex;
                align-items: center;
                width: 100%;
                padding: 1rem;
                margin-bottom: 0.5rem;
                background: white;
                border: 2px solid #e9ecef;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 500;
                color: #333;
                cursor: pointer;
                transition: all 0.3s ease;
                text-align: left;
            }

            .sort-option:hover {
                background: #f0fdf4;
                border-color: #10b981;
                color: #10b981;
            }

            .sort-option.active {
                background: #10b981;
                border-color: #10b981;
                color: white;
            }

            .sort-option i {
                font-size: 1.1rem;
            }

            /* Product Cards - 2 per line on mobile */
            .product-card {
                margin-bottom: 1rem;
            }

            .product-image {
                height: 140px;
            }

            .product-badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.5rem;
                top: 8px;
                left: 8px;
            }

            .product-info {
                padding: 0.75rem;
            }

            .product-name {
                font-size: 0.9rem;
                margin-bottom: 0.4rem;
            }

            .product-description {
                font-size: 0.75rem;
                margin-bottom: 0.5rem;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .product-meta {
                font-size: 0.8rem;
                margin-bottom: 0.5rem;
            }

            .product-price {
                font-size: 1rem;
            }

            .product-rating {
                font-size: 0.75rem;
            }

            .add-to-cart-btn {
                font-size: 0.85rem;
                padding: 0.5rem 0.75rem;
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
                        <i class="fas fa-utensils" style="display: none;"></i>
                    <?php else: ?>
                        <i class="fas fa-utensils"></i>
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
                        <i class="fas fa-shopping-cart me-1"></i>Cart <span class="badge bg-primary text-white ms-2" id="cartCount">0</span>
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
                            <i class="fas fa-utensils logo-icon" style="display: none; color: var(--ordivo-primary);"></i>
                        <?php else: ?>
                            <i class="fas fa-utensils logo-icon" style="color: var(--ordivo-primary);"></i>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Hamburger Icon -->
                    <button class="mobile-nav-toggle" id="navHamburgerMobile">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <!-- Categories Button -->
                    <button class="mobile-nav-toggle" id="navCategoriesMobile">
                        <i class="fas fa-th-large"></i>
                    </button>
                    
                    <!-- Sort Button -->
                    <button class="mobile-nav-toggle" id="navSortMobile">
                        <i class="fas fa-sort-amount-down"></i>
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

   

    <?php if ($vendor): ?>
    <!-- Vendor Header -->
    <section class="vendor-header">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="vendor-banner">
                        <?php if ($vendor['banner_image']): ?>
                            <?php if (strpos($vendor['banner_image'], 'http') === 0): ?>
                                <img src="<?= htmlspecialchars($vendor['banner_image']) ?>" 
                                     alt="<?= htmlspecialchars($vendor['business_name'] ?? $vendor['name']) ?>">
                            <?php else: ?>
                                <img src="../uploads/images/<?= htmlspecialchars($vendor['banner_image']) ?>" 
                                     alt="<?= htmlspecialchars($vendor['business_name'] ?? $vendor['name']) ?>">
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <i class="fas fa-store fa-4x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="vendor-info">
                        <h1 class="vendor-name"><?= htmlspecialchars($vendor['business_name'] ?? $vendor['name']) ?></h1>
                        <div class="vendor-meta">
                            <div><i class="fas fa-star text-warning me-1"></i><?= $vendor['rating'] ?? '4.5' ?> (<?= $vendor['total_reviews'] ?? '100' ?>+ reviews)</div>
                            <div><i class="fas fa-clock me-1"></i><?= $vendor['avg_delivery_time'] ?? '30' ?> min delivery</div>
                            <div><i class="fas fa-shopping-bag me-1"></i>Min order: ৳<?= number_format($vendor['min_order_amount'] ?? 150, 0) ?></div>
                        </div>
                        <div class="vendor-status <?= $vendor['is_open'] ? 'open' : 'closed' ?>">
                            <?= $vendor['is_open'] ? 'Open Now' : 'Closed' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Search & Filters -->
    <section class="search-filters">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="form-control search-input" id="searchInput" 
                               placeholder="Search products..." value="<?= htmlspecialchars($searchQuery) ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="me-3 fw-bold">Sort:</span>
                        <button class="filter-btn active" data-sort="popular">Popular</button>
                        <button class="filter-btn" data-sort="price_low">Price: Low</button>
                        <button class="filter-btn" data-sort="price_high">Price: High</button>
                        <button class="filter-btn" data-sort="rating">Rating</button>
                        <button class="filter-btn" data-sort="newest">Newest</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mobile Sort Panel -->
    <div class="mobile-sort-panel" id="mobileSortPanel">
        <div class="sort-panel-header">
            <h5>Sort By</h5>
            <button class="btn-close-sort" id="closeSortPanel">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="sort-panel-content">
            <button class="sort-option active" data-sort="popular">
                <i class="fas fa-fire me-2"></i>Popular
            </button>
            <button class="sort-option" data-sort="price_low">
                <i class="fas fa-arrow-down me-2"></i>Price: Low to High
            </button>
            <button class="sort-option" data-sort="price_high">
                <i class="fas fa-arrow-up me-2"></i>Price: High to Low
            </button>
            <button class="sort-option" data-sort="rating">
                <i class="fas fa-star me-2"></i>Rating
            </button>
            <button class="sort-option" data-sort="newest">
                <i class="fas fa-clock me-2"></i>Newest
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <section class="py-4">
        <div class="container">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-3">
                    <div class="sidebar">
                        <h5>Categories</h5>
                        <div id="categoriesList">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                        
                        <div class="price-range">
                            <h5>Price Range</h5>
                            <div class="price-inputs">
                                <input type="number" class="form-control price-input" id="minPrice" 
                                       placeholder="Min" value="<?= $minPrice > 0 ? $minPrice : '' ?>">
                                <input type="number" class="form-control price-input" id="maxPrice" 
                                       placeholder="Max" value="<?= $maxPrice < 1000 ? $maxPrice : '' ?>">
                            </div>
                            <button class="btn btn-primary btn-sm mt-2 w-100" onclick="applyPriceFilter()">
                                Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="col-lg-9">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3>
                            Products
                            <?php if ($filterType === 'featured'): ?>
                                <span class="badge bg-warning text-dark ms-2">Featured</span>
                            <?php elseif ($filterType === 'top-choice'): ?>
                                <span class="badge bg-success ms-2">Top Choice</span>
                            <?php endif; ?>
                        </h3>
                        <span class="text-muted" id="productCount">Loading...</span>
                    </div>
                    
                    <div class="row" id="productsGrid">
                        <div class="col-12 text-center">
                            <div class="loading">
                                <i class="fas fa-spinner"></i>
                                <p class="mt-2">Loading products...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="row mt-4" id="paginationContainer" style="display: none;">
                        <div class="col-12">
                            <nav aria-label="Products pagination">
                                <ul class="pagination justify-content-center" id="paginationList">
                                    <!-- Pagination items will be generated here -->
                                </ul>
                            </nav>
                            <div class="text-center mt-2">
                                <small class="text-muted" id="paginationInfo">
                                    <!-- Pagination info will be displayed here -->
                                </small>
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
        let currentSort = 'popular';
        let currentCategory = '<?= htmlspecialchars($categoryFilter) ?>';
        let currentVendor = <?= $vendorId ?>;
        let currentFilter = '<?= htmlspecialchars($filterType) ?>';
        let currentPage = 1; // Add current page tracking
        let cart = JSON.parse(localStorage.getItem('ordivo_cart') || '[]');
        
        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
            loadProducts();
            updateCartCount();
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchProducts();
                }
            });
            
            // Sort filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentSort = this.dataset.sort;
                    currentPage = 1; // Reset to first page on sort
                    loadProducts();
                });
            });
        });

        async function loadCategories() {
            try {
                const params = new URLSearchParams({
                    ajax: 'categories'
                });
                if (currentVendor) params.append('vendor', currentVendor);
                
                const response = await fetch('?' + params);
                const categories = await response.json();
                
                if (categories.error) {
                    console.error('Categories error:', categories.error);
                    return;
                }
                
                const list = document.getElementById('categoriesList');
                const categoryItems = categories.map(category => `
                    <div class="category-item ${currentCategory === category.category ? 'active' : ''}" 
                         onclick="filterByCategory('${category.category}')">
                        <span>${category.category}</span>
                        <span class="badge bg-light text-dark">${category.product_count}</span>
                    </div>
                `).join('');
                
                list.innerHTML = `
                    <div class="category-item ${!currentCategory ? 'active' : ''}" onclick="filterByCategory('')">
                        <span>All Categories</span>
                    </div>
                    ${categoryItems}
                `;
                
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        }

        async function loadProducts(page = 1) {
            currentPage = page;
            const grid = document.getElementById('productsGrid');
            grid.innerHTML = '<div class="col-12 text-center"><div class="loading"><i class="fas fa-spinner"></i><p class="mt-2">Loading products...</p></div></div>';
            
            try {
                const searchQuery = document.getElementById('searchInput').value;
                const minPrice = document.getElementById('minPrice').value;
                const maxPrice = document.getElementById('maxPrice').value;
                
                const params = new URLSearchParams({
                    ajax: 'products',
                    search: searchQuery,
                    category: currentCategory,
                    sort: currentSort,
                    filter: currentFilter,
                    page: page
                });
                
                if (currentVendor) params.append('vendor', currentVendor);
                if (minPrice) params.append('min_price', minPrice);
                if (maxPrice) params.append('max_price', maxPrice);
                
                const response = await fetch('?' + params);
                const data = await response.json();
                
                if (data.error) {
                    console.error('Products error:', data.error);
                    return;
                }
                
                // Handle both old format (direct products array) and new format (with pagination)
                const products = data.products || data;
                const pagination = data.pagination;
                
                // Update product count
                if (pagination) {
                    document.getElementById('productCount').textContent = `${pagination.total_count} products found`;
                } else {
                    document.getElementById('productCount').textContent = `${products.length} products found`;
                }
                
                const productCards = products.map(product => `
                    <div class="col-lg-4 col-md-6 col-6 mb-4">
                        <div class="product-card" onclick="viewProduct(${product.id})">
                            <div class="product-image">
                                ${product.image ? 
                                    (product.image.startsWith('http') ? 
                                        `<img src="${product.image}" alt="${product.name}" onerror="this.src='../uploads/images/placeholder-food.svg'">` :
                                        `<img src="../uploads/images/${product.image}" alt="${product.name}" onerror="this.src='../uploads/images/placeholder-food.svg'">`) :
                                    `<img src="../uploads/images/placeholder-food.svg" alt="${product.name}">`
                                }
                                ${product.featured ? '<div class="product-badge featured">Featured</div>' : ''}
                            </div>
                            <div class="product-info">
                                <div class="product-name">${product.name}</div>
                                <div class="product-description">${product.description || 'No description available'}</div>
                                <div class="product-meta">
                                    <div class="product-price">৳${parseFloat(product.price).toFixed(0)}</div>
                                    <div class="product-rating">
                                        <i class="fas fa-star rating-star"></i>
                                        <span>${product.avg_rating || '4.5'}</span>
                                        <span>(${product.review_count || '0'})</span>
                                    </div>
                                </div>
                                <button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(${product.id}, '${product.name}', ${product.price})">
                                    <i class="fas fa-plus me-2"></i>Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                grid.innerHTML = productCards || '<div class="col-12 text-center text-muted">No products found</div>';
                
                // Render pagination if available
                const paginationContainer = document.getElementById('paginationContainer');
                if (pagination && pagination.total_pages > 1) {
                    renderPagination(pagination);
                    paginationContainer.style.display = 'block';
                } else {
                    paginationContainer.style.display = 'none';
                }
                
            } catch (error) {
                console.error('Failed to load products:', error);
                grid.innerHTML = '<div class="col-12 text-center text-danger">Failed to load products</div>';
            }
        }

        function searchProducts() {
            currentPage = 1; // Reset to first page on search
            loadProducts();
        }

        function filterByCategory(category) {
            currentCategory = category;
            currentPage = 1; // Reset to first page on filter
            document.querySelectorAll('.category-item').forEach(item => item.classList.remove('active'));
            event.target.closest('.category-item').classList.add('active');
            loadProducts();
        }

        function applyPriceFilter() {
            currentPage = 1; // Reset to first page on filter
            loadProducts();
        }

        function renderPagination(pagination) {
            const paginationList = document.getElementById('paginationList');
            const paginationInfo = document.getElementById('paginationInfo');
            
            // Update pagination info
            const startItem = ((pagination.current_page - 1) * pagination.per_page) + 1;
            const endItem = Math.min(pagination.current_page * pagination.per_page, pagination.total_count);
            paginationInfo.textContent = `Showing ${startItem}-${endItem} of ${pagination.total_count} products`;
            
            let paginationHTML = '';
            
            // Previous button
            paginationHTML += `
                <li class="page-item ${!pagination.has_prev ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1}); return false;">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                </li>
            `;
            
            // Page numbers
            const maxVisiblePages = 5;
            let startPage = Math.max(1, pagination.current_page - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(pagination.total_pages, startPage + maxVisiblePages - 1);
            
            // Adjust start page if we're near the end
            if (endPage - startPage + 1 < maxVisiblePages) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }
            
            // First page and ellipsis
            if (startPage > 1) {
                paginationHTML += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="changePage(1); return false;">1</a>
                    </li>
                `;
                if (startPage > 2) {
                    paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }
            
            // Page numbers
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
                    </li>
                `;
            }
            
            // Last page and ellipsis
            if (endPage < pagination.total_pages) {
                if (endPage < pagination.total_pages - 1) {
                    paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                paginationHTML += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="changePage(${pagination.total_pages}); return false;">${pagination.total_pages}</a>
                    </li>
                `;
            }
            
            // Next button
            paginationHTML += `
                <li class="page-item ${!pagination.has_next ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1}); return false;">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;
            
            paginationList.innerHTML = paginationHTML;
        }

        function changePage(page) {
            if (page < 1) return;
            loadProducts(page);
            // Scroll to top of products section
            document.getElementById('productsGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        async function viewProduct(productId) {
            // Redirect to product details page
            window.location.href = `product_details.php?id=${productId}`;
        }

        function addToCart(productId, productName, price) {
            const existingItem = cart.find(item => item.id === productId);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: productId,
                    name: productName,
                    price: parseFloat(price),
                    quantity: 1
                });
            }
            
            localStorage.setItem('ordivo_cart', JSON.stringify(cart));
            updateCartCount();
            
            // Show success message
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 100px; right: 20px; z-index: 9999;">
                    <i class="fas fa-check-circle me-2"></i>Added to cart: ${productName}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        function updateCartCount() {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cartCount').textContent = totalItems;
        }

        // Image error handling
        function handleImageError(img, fallbackIcon = 'fas fa-image') {
            if (img.dataset.error === 'true') return; // Already handled
            
            img.dataset.error = 'true';
            const parent = img.parentElement;
            
            // Create fallback div
            const fallback = document.createElement('div');
            fallback.className = 'image-fallback w-100 h-100';
            fallback.innerHTML = `<i class="${fallbackIcon} fa-3x text-muted"></i>`;
            
            // Replace image with fallback
            parent.replaceChild(fallback, img);
        }

        // Add error handlers to all images when they're loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Handle existing images
            document.querySelectorAll('img').forEach(img => {
                img.addEventListener('error', () => handleImageError(img));
            });
            
            // Load saved location
            loadSavedLocation();
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

        // Add search functionality for location
        document.addEventListener('DOMContentLoaded', function() {
            const locationSearch = document.getElementById('locationSearch');
            if (locationSearch) {
                locationSearch.addEventListener('input', function() {
                    selectedLocation = this.value;
                });
            }

            // Mobile navigation toggle - Hamburger menu
            setTimeout(function() {
                const navHamburgerMobile = document.getElementById('navHamburgerMobile');
                
                if (navHamburgerMobile) {
                    // Toggle sidebar when hamburger is clicked
                    navHamburgerMobile.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const sidebar = document.querySelector('.sidebar');
                        if (sidebar) {
                            sidebar.classList.toggle('show');
                            
                            // Lock/unlock body scroll
                            if (sidebar.classList.contains('show')) {
                                document.body.style.overflow = 'hidden';
                            } else {
                                document.body.style.overflow = '';
                            }
                            
                            // Change icon
                            const icon = this.querySelector('i');
                            if (icon) {
                                if (sidebar.classList.contains('show')) {
                                    icon.classList.remove('fa-bars');
                                    icon.classList.add('fa-times');
                                } else {
                                    icon.classList.remove('fa-times');
                                    icon.classList.add('fa-bars');
                                }
                            }
                        }
                    });
                }

                // Categories button functionality
                const navCategoriesMobile = document.getElementById('navCategoriesMobile');
                if (navCategoriesMobile) {
                    navCategoriesMobile.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const sidebar = document.querySelector('.sidebar');
                        if (sidebar) {
                            sidebar.classList.toggle('show');
                            
                            // Lock/unlock body scroll
                            if (sidebar.classList.contains('show')) {
                                document.body.style.overflow = 'hidden';
                            } else {
                                document.body.style.overflow = '';
                            }
                            
                            // Change icon
                            const icon = this.querySelector('i');
                            if (icon) {
                                if (sidebar.classList.contains('show')) {
                                    icon.classList.remove('fa-th-large');
                                    icon.classList.add('fa-times');
                                } else {
                                    icon.classList.remove('fa-times');
                                    icon.classList.add('fa-th-large');
                                }
                            }
                        }
                    });

                    // Close sidebar when clicking on a category
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) {
                        const categoryItems = sidebar.querySelectorAll('.category-item');
                        categoryItems.forEach(item => {
                            item.addEventListener('click', function() {
                                if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                                    sidebar.classList.remove('show');
                                    document.body.style.overflow = '';
                                    const icon = navCategoriesMobile.querySelector('i');
                                    if (icon) {
                                        icon.classList.remove('fa-times');
                                        icon.classList.add('fa-th-large');
                                    }
                                }
                            });
                        });

                        // Close sidebar when clicking on backdrop
                        document.addEventListener('click', function(e) {
                            if (sidebar.classList.contains('show') && window.innerWidth <= 768) {
                                const rect = sidebar.getBoundingClientRect();
                                // Check if click is outside the sidebar
                                if (e.clientX > rect.right || e.clientX < rect.left) {
                                    sidebar.classList.remove('show');
                                    document.body.style.overflow = '';
                                    const icon = navCategoriesMobile.querySelector('i');
                                    if (icon) {
                                        icon.classList.remove('fa-times');
                                        icon.classList.add('fa-th-large');
                                    }
                                }
                            }
                        });
                    }
                }

                // Sort button functionality
                const navSortMobile = document.getElementById('navSortMobile');
                const mobileSortPanel = document.getElementById('mobileSortPanel');
                const closeSortPanel = document.getElementById('closeSortPanel');
                
                if (navSortMobile && mobileSortPanel) {
                    // Open sort panel
                    navSortMobile.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        mobileSortPanel.classList.toggle('show');
                        
                        // Lock/unlock body scroll
                        if (mobileSortPanel.classList.contains('show')) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                        
                        // Change icon
                        const icon = this.querySelector('i');
                        if (icon) {
                            if (mobileSortPanel.classList.contains('show')) {
                                icon.classList.remove('fa-sort-amount-down');
                                icon.classList.add('fa-times');
                            } else {
                                icon.classList.remove('fa-times');
                                icon.classList.add('fa-sort-amount-down');
                            }
                        }
                    });

                    // Close button
                    if (closeSortPanel) {
                        closeSortPanel.addEventListener('click', function() {
                            mobileSortPanel.classList.remove('show');
                            document.body.style.overflow = '';
                            const icon = navSortMobile.querySelector('i');
                            if (icon) {
                                icon.classList.remove('fa-times');
                                icon.classList.add('fa-sort-amount-down');
                            }
                        });
                    }

                    // Handle sort option clicks
                    const sortOptions = mobileSortPanel.querySelectorAll('.sort-option');
                    sortOptions.forEach(option => {
                        option.addEventListener('click', function() {
                            const sortValue = this.getAttribute('data-sort');
                            
                            // Update active state
                            sortOptions.forEach(opt => opt.classList.remove('active'));
                            this.classList.add('active');
                            
                            // Update desktop sort buttons too
                            const desktopSortButtons = document.querySelectorAll('.filter-btn');
                            desktopSortButtons.forEach(btn => {
                                btn.classList.remove('active');
                                if (btn.getAttribute('data-sort') === sortValue) {
                                    btn.classList.add('active');
                                }
                            });
                            
                            // Trigger sort
                            currentSort = sortValue;
                            currentPage = 1;
                            loadProducts();
                            
                            // Close panel
                            mobileSortPanel.classList.remove('show');
                            document.body.style.overflow = '';
                            const icon = navSortMobile.querySelector('i');
                            if (icon) {
                                icon.classList.remove('fa-times');
                                icon.classList.add('fa-sort-amount-down');
                            }
                        });
                    });

                    // Close when clicking on backdrop
                    document.addEventListener('click', function(e) {
                        if (mobileSortPanel.classList.contains('show') && window.innerWidth <= 768) {
                            const rect = mobileSortPanel.getBoundingClientRect();
                            // Check if click is outside the panel
                            if (e.clientX < rect.left || e.clientX > rect.right) {
                                mobileSortPanel.classList.remove('show');
                                document.body.style.overflow = '';
                                const icon = navSortMobile.querySelector('i');
                                if (icon) {
                                    icon.classList.remove('fa-times');
                                    icon.classList.add('fa-sort-amount-down');
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