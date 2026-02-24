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
    <?php include 'includes/header_with_nav.php'; ?>

    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Mobile Filters Modal -->
        <div class="mobile-filters-modal" id="mobileFiltersModal">
            <div class="mobile-filters-content">
                <div class="mobile-filters-header">
                    <h5><i class="fas fa-filter me-2"></i>Filters</h5>
                    <button class="mobile-filters-close" id="mobileFiltersClose">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Filter Content (same as sidebar) -->
                <div class="filter-section">
                    <h6>Sort By</h6>
                    <div class="filter-option">
                        <input type="radio" name="sort-mobile" id="sort-relevance-mobile" value="relevance" checked>
                        <label for="sort-relevance-mobile">Relevance</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" name="sort-mobile" id="sort-fastest-mobile" value="fastest">
                        <label for="sort-fastest-mobile">Fastest Delivery</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" name="sort-mobile" id="sort-distance-mobile" value="distance">
                        <label for="sort-distance-mobile">Distance</label>
                    </div>
                    <div class="filter-option">
                        <input type="radio" name="sort-mobile" id="sort-rating-mobile" value="top-rated">
                        <label for="sort-rating-mobile">Top Rated</label>
                    </div>
                </div>

                <div class="filter-section">
                    <h6>Delivery Options</h6>
                    <div class="filter-option">
                        <input type="checkbox" id="filter-free-delivery-mobile">
                        <label for="filter-free-delivery-mobile">Free Delivery</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="filter-fast-delivery-mobile">
                        <label for="filter-fast-delivery-mobile">Fast Delivery (Under 30 min)</label>
                    </div>
                </div>

                <div class="filter-section">
                    <h6>Price Range</h6>
                    <div class="filter-option">
                        <input type="checkbox" id="price-budget-mobile">
                        <label for="price-budget-mobile">৳ Budget Friendly</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="price-mid-mobile">
                        <label for="price-mid-mobile">৳৳ Mid Range</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="price-premium-mobile">
                        <label for="price-premium-mobile">৳৳৳ Premium</label>
                    </div>
                </div>

                <div class="filter-section">
                    <h6>Cuisines</h6>
                    <div class="cuisine-item">
                        <input type="checkbox" id="cuisine-bangladeshi-mobile">
                        <label for="cuisine-bangladeshi-mobile">Bangladeshi</label>
                    </div>
                    <div class="cuisine-item">
                        <input type="checkbox" id="cuisine-indian-mobile">
                        <label for="cuisine-indian-mobile">Indian</label>
                    </div>
                    <div class="cuisine-item">
                        <input type="checkbox" id="cuisine-chinese-mobile">
                        <label for="cuisine-chinese-mobile">Chinese</label>
                    </div>
                    <div class="cuisine-item">
                        <input type="checkbox" id="cuisine-italian-mobile">
                        <label for="cuisine-italian-mobile">Italian</label>
                    </div>
                    <div class="cuisine-item">
                        <input type="checkbox" id="cuisine-fastfood-mobile">
                        <label for="cuisine-fastfood-mobile">Fast Food</label>
                    </div>
                </div>

                <div class="filter-section">
                    <h6>Dietary</h6>
                    <div class="filter-option">
                        <input type="checkbox" id="diet-vegetarian-mobile">
                        <label for="diet-vegetarian-mobile">Vegetarian</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="diet-vegan-mobile">
                        <label for="diet-vegan-mobile">Vegan</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="diet-halal-mobile">
                        <label for="diet-halal-mobile">Halal</label>
                    </div>
                </div>

                <div class="mobile-filters-footer">
                    <button class="btn-clear-filters" onclick="clearMobileFilters()">
                        <i class="fas fa-redo me-2"></i>Clear All
                    </button>
                    <button class="btn-apply-filters" onclick="applyMobileFilters()">
                        <i class="fas fa-check me-2"></i>Apply Filters
                    </button>
                </div>
            </div>
        </div>
        
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
                <div class="products-section">
                    <h2 class="section-title">Featured Restaurants</h2>
                    <div class="swiper featuredRestaurantsSwiper">
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-wrapper" id="featuredRestaurants">
                            <div class="loading"><div class="spinner"></div><p>Loading restaurants...</p></div>
                        </div>
                        <div class="swiper-button-next"></div>
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
                <div class="products-section">
                    <h2 class="section-title">All Restaurants</h2>
                    <div class="swiper allRestaurantsSwiper">
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-wrapper" id="restaurantsGrid">
                            <div class="loading"><div class="spinner"></div><p>Loading restaurants...</p></div>
                        </div>
                        <div class="swiper-button-next"></div>
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
    <script src="../assets/js/location-tracker.js"></script>
    <script src="../assets/js/homepage.js"></script>
    
    <script>
        // Mobile filters functions
        function clearMobileFilters() {
            // Clear all checkboxes and radio buttons in mobile filters
            document.querySelectorAll('#mobileFiltersModal input[type="checkbox"]').forEach(cb => cb.checked = false);
            document.querySelectorAll('#mobileFiltersModal input[type="radio"]').forEach(rb => {
                if (rb.value === 'relevance') rb.checked = true;
                else rb.checked = false;
            });
            
            // Also clear desktop filters
            document.querySelectorAll('.sidebar input[type="checkbox"]').forEach(cb => cb.checked = false);
            document.querySelectorAll('.sidebar input[type="radio"]').forEach(rb => {
                if (rb.value === 'relevance') rb.checked = true;
                else rb.checked = false;
            });
            
            // Trigger filter update
            if (typeof applyFilters === 'function') {
                applyFilters();
            }
        }
        
        function applyMobileFilters() {
            // Sync mobile filters to desktop filters
            syncFilters('mobile', 'desktop');
            
            // Close the modal
            const modal = document.getElementById('mobileFiltersModal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
            
            // Trigger filter update
            if (typeof applyFilters === 'function') {
                applyFilters();
            }
        }
        
        function syncFilters(from, to) {
            // Sync checkboxes
            const fromCheckboxes = document.querySelectorAll(`#mobileFiltersModal input[type="checkbox"]`);
            fromCheckboxes.forEach(cb => {
                const id = cb.id.replace('-mobile', '');
                const desktopCb = document.getElementById(id);
                if (desktopCb) {
                    desktopCb.checked = cb.checked;
                }
            });
            
            // Sync radio buttons
            const fromRadios = document.querySelectorAll(`#mobileFiltersModal input[type="radio"]:checked`);
            fromRadios.forEach(rb => {
                const name = rb.name.replace('-mobile', '');
                const value = rb.value;
                const desktopRb = document.querySelector(`.sidebar input[name="${name}"][value="${value}"]`);
                if (desktopRb) {
                    desktopRb.checked = true;
                }
            });
        }
    </script>
</body>
</html>
