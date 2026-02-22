<?php
/**
 * ORDIVO - Customer Homepage
 * Clean, refactored version with external CSS/JS
 */

require_once '../config/db_connection.php';

// Get site settings
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
    error_log("Error loading site settings: " . $e->getMessage());
    $siteLogo = '';
    $siteName = 'ORDIVO';
}

// Get active banners
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

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    require_once 'includes/ajax_handlers.php';
    exit;
}

$userLocation = $_SESSION['user_location'] ?? 'Dhaka, Bangladesh';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?> - Food & Grocery Delivery</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="../assets/logo-animations.css" rel="stylesheet">
    <link href="../assets/css/homepage.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/navigation.php'; ?>

    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="container-fluid">
                <!-- Search Bar -->
                <div class="search-container">
                    <input type="text" class="search-input" id="searchInput" placeholder="Search for restaurants, cuisines, or dishes...">
                    <button class="clear-search-btn" id="clearSearchBtn" style="display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                    <i class="fas fa-search search-icon"></i>
                    <div class="search-suggestions" id="searchSuggestions"></div>
                </div>

                <!-- Promotional Banner Carousel -->
                <?php include 'includes/promo_carousel.php'; ?>

                <!-- Featured Restaurants -->
                <div class="restaurant-carousel">
                    <div class="carousel-container">
                        <div class="carousel-nav prev" onclick="scrollCarousel('prev')">
                            <i class="fas fa-chevron-left"></i>
                        </div>
                        <div class="restaurant-cards" id="featuredRestaurants">
                            <div class="loading"><div class="spinner"></div><p>Loading restaurants...</p></div>
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
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-wrapper" id="cuisinesContainer">
                            <div class="loading"><div class="spinner"></div><p>Loading cuisines...</p></div>
                        </div>
                        <div class="swiper-button-next"></div>
                    </div>
                </div>

                <!-- Featured Products -->
                <div class="products-section">
                    <h2 class="section-title">Featured Products</h2>
                    <div class="swiper featuredProductsSwiper">
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-wrapper" id="featuredProductsContainer">
                            <div class="loading"><div class="spinner"></div><p>Loading products...</p></div>
                        </div>
                        <div class="swiper-button-next"></div>
                    </div>
                </div>

                <!-- Top Choices -->
                <div class="products-section">
                    <h2 class="section-title">Top Choices</h2>
                    <div class="swiper topChoiceProductsSwiper">
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-wrapper" id="topChoiceProductsContainer">
                            <div class="loading"><div class="spinner"></div><p>Loading products...</p></div>
                        </div>
                        <div class="swiper-button-next"></div>
                    </div>
                </div>

                <!-- Daily Deals -->
                <div class="deals-section">
                    <h2 class="section-title">Daily Deals</h2>
                    <div class="deals-grid" id="dealsGrid">
                        <div class="deal-banner">
                            <div class="deal-content">
                                <h3>Weekend Special</h3>
                                <p>Up to 40% off</p>
                                <button class="deal-btn">Order Now</button>
                            </div>
                        </div>
                        <div class="deal-banner" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <div class="deal-content">
                                <h3>Free Delivery</h3>
                                <p>On orders above ৳500</p>
                                <button class="deal-btn">Get Started</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All Restaurants -->
                <div class="restaurants-section">
                    <h2 class="section-title">All Restaurants</h2>
                    <div class="row" id="restaurantsGrid">
                        <div class="col-12"><div class="loading"><div class="spinner"></div><p>Loading restaurants...</p></div></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/homepage.js"></script>
</body>
</html>
