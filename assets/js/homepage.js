/**
 * ORDIVO Homepage JavaScript
 * Handles all interactive features
 */

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeHomepage();
});

function initializeHomepage() {
    loadFeaturedRestaurants();
    loadCuisines();
    loadFeaturedProducts();
    loadAllRestaurants();
    initializeSearch();
    initializeMobileMenu();
    initializeFilters();
    // initializeLocationModal(); // Now handled by location-tracker.js
    initializeSwipers();
}

// Load Featured Restaurants
function loadFeaturedRestaurants() {
    fetch('index.php?ajax=featured_restaurants')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Featured restaurants data:', data);
            const container = document.getElementById('featuredRestaurants');
            
            if (!container) {
                console.error('Featured restaurants container not found');
                return;
            }
            
            if (data.error) {
                console.error('API error:', data.error);
                container.innerHTML = '<p class="text-center text-danger">Error: ' + data.error + '</p>';
                return;
            }
            
            if (!data || data.length === 0) {
                container.innerHTML = '<p class="text-center text-muted">No restaurants available</p>';
                return;
            }
            
            container.innerHTML = data.map(restaurant => `
                <div class="swiper-slide">
                    <div class="product-card" onclick="window.location.href='vendor_profile.php?id=${restaurant.id}'" style="cursor: pointer;" title="Click to view ${restaurant.name}">
                        <div class="product-image" style="background-image: url('${restaurant.image}')">
                            <div class="product-badge">${restaurant.badge}</div>
                        </div>
                        <div class="product-info">
                            <div class="product-name">${restaurant.name}</div>
                            <div class="product-vendor">${restaurant.category}</div>
                            <div class="product-footer">
                                <div class="product-rating">
                                    <i class="fas fa-star"></i> ${restaurant.rating}
                                    <span class="text-muted">(${restaurant.reviews})</span>
                                </div>
                                <div class="delivery-time">
                                    <i class="fas fa-clock"></i> ${restaurant.time}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Initialize Swiper after content is loaded
            setTimeout(() => {
                new Swiper('.featuredRestaurantsSwiper', {
                    slidesPerView: 'auto',
                    spaceBetween: 15,
                    freeMode: true,
                    navigation: {
                        nextEl: '.featuredRestaurantsSwiper .swiper-button-next',
                        prevEl: '.featuredRestaurantsSwiper .swiper-button-prev',
                    },
                    breakpoints: {
                        320: { slidesPerView: 2 },
                        769: { slidesPerView: 'auto' }
                    }
                });
            }, 100);
        })
        .catch(error => {
            console.error('Error loading featured restaurants:', error);
            const container = document.getElementById('featuredRestaurants');
            if (container) {
                container.innerHTML = '<p class="text-center text-danger">Failed to load restaurants. Please refresh the page.</p>';
            }
        });
}

// Load Cuisines
function loadCuisines() {
    fetch('index.php?ajax=categories')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Invalid JSON: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('Cuisines data:', data);
            const container = document.getElementById('cuisinesContainer');
            
            if (data.error) {
                console.error('Cuisines error:', data.error);
                if (data.trace) console.error('Trace:', data.trace);
                container.innerHTML = '<p class="text-center text-danger">Error: ' + data.error + '</p>';
                return;
            }
            
            if (data.length === 0) {
                container.innerHTML = '<p class="text-center text-muted">No cuisines available</p>';
                return;
            }
            
            container.innerHTML = data.map(cuisine => `
                <div class="swiper-slide">
                    <a href="products.php?category_id=${cuisine.id}&category_name=${encodeURIComponent(cuisine.name)}" class="cuisine-card-link" title="Browse ${cuisine.name}">
                        <div class="cuisine-card">
                            <div class="cuisine-icon">
                                ${cuisine.image ? 
                                    `<img src="${cuisine.image}" alt="${cuisine.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">` : 
                                    (cuisine.icon ? `<i class="${cuisine.icon}"></i>` : '<i class="fas fa-utensils"></i>')
                                }
                            </div>
                            <div class="cuisine-name">${cuisine.name}</div>
                        </div>
                    </a>
                </div>
            `).join('');
            
            // Re-initialize Swiper after content is loaded
            setTimeout(() => {
                new Swiper('.cuisinesSwiper', {
                    slidesPerView: 'auto',
                    spaceBetween: 15,
                    freeMode: true,
                    allowTouchMove: true,
                    simulateTouch: true,
                    touchRatio: 1,
                    touchAngle: 45,
                    shortSwipes: true,
                    longSwipes: true,
                    longSwipesRatio: 0.5,
                    longSwipesMs: 300,
                    followFinger: true,
                    threshold: 5,
                    preventClicks: false,
                    preventClicksPropagation: false,
                    navigation: {
                        nextEl: '.cuisinesSwiper .swiper-button-next',
                        prevEl: '.cuisinesSwiper .swiper-button-prev',
                    },
                    breakpoints: {
                        320: { slidesPerView: 4 },
                        769: { slidesPerView: 'auto' }
                    }
                });
            }, 100);
        })
        .catch(error => {
            console.error('Error loading cuisines:', error);
            const container = document.getElementById('cuisinesContainer');
            if (container) {
                container.innerHTML = '<p class="text-center text-danger">Failed to load cuisines: ' + error.message + '</p>';
            }
        });
}

// Load Featured Products
function loadFeaturedProducts() {
    // Load Featured Products
    fetch('index.php?ajax=featured_products')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Invalid JSON: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('Featured products data:', data);
            const container = document.getElementById('featuredProductsContainer');
            
            if (data.error) {
                console.error('Featured products error:', data.error);
                if (data.trace) console.error('Trace:', data.trace);
                container.innerHTML = '<p class="text-center text-danger">Error: ' + data.error + '</p>';
                return;
            }
            
            if (data.length === 0) {
                container.innerHTML = '<p class="text-center text-muted">No featured products available</p>';
                return;
            }
            
            const productHTML = data.map(product => `
                <div class="swiper-slide">
                    <div class="product-card" onclick="window.location.href='product_details.php?id=${product.id}'">
                        <div class="product-image" style="background-image: url('${product.image}')"></div>
                        <div class="product-info">
                            <div class="product-name">${product.name}</div>
                            <div class="product-vendor">${product.vendor_name}</div>
                            <div class="product-footer">
                                <div class="product-price">৳${product.price}</div>
                                <div class="product-rating">
                                    <i class="fas fa-star"></i> ${product.rating}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = productHTML;
        })
        .catch(error => {
            console.error('Error loading featured products:', error);
            const container = document.getElementById('featuredProductsContainer');
            if (container) {
                container.innerHTML = '<p class="text-center text-danger">Failed to load products: ' + error.message + '</p>';
            }
        });
    
    // Load Top Choice Products
    fetch('index.php?ajax=top_choice_products')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Invalid JSON: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('Top choice products data:', data);
            const topChoiceContainer = document.getElementById('topChoiceProductsContainer');
            
            if (data.error) {
                console.error('Top choice products error:', data.error);
                if (data.trace) console.error('Trace:', data.trace);
                topChoiceContainer.innerHTML = '<p class="text-center text-danger">Error: ' + data.error + '</p>';
                return;
            }
            
            if (data.length === 0) {
                topChoiceContainer.innerHTML = '<p class="text-center text-muted">No top choice products available</p>';
                return;
            }
            
            const productHTML = data.map(product => `
                <div class="swiper-slide">
                    <div class="product-card" onclick="window.location.href='product_details.php?id=${product.id}'">
                        <div class="product-image" style="background-image: url('${product.image}')"></div>
                        <div class="product-info">
                            <div class="product-name">${product.name}</div>
                            <div class="product-vendor">${product.vendor_name}</div>
                            <div class="product-footer">
                                <div class="product-price">৳${product.price}</div>
                                <div class="product-rating">
                                    <i class="fas fa-star"></i> ${product.rating}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            topChoiceContainer.innerHTML = productHTML;
        })
        .catch(error => {
            console.error('Error loading top choice products:', error);
            const topChoiceContainer = document.getElementById('topChoiceProductsContainer');
            if (topChoiceContainer) {
                topChoiceContainer.innerHTML = '<p class="text-center text-danger">Failed to load products: ' + error.message + '</p>';
            }
        });
}

// Load All Restaurants
function loadAllRestaurants() {
    // Collect all filter values
    const sort = document.querySelector('input[name="sort"]:checked')?.value || 'relevance';
    const freeDelivery = document.getElementById('filter-free-delivery')?.checked || false;
    const fastDelivery = document.getElementById('filter-fast-delivery')?.checked || false;
    
    // Price range filters
    const priceBudget = document.getElementById('price-budget')?.checked || false;
    const priceMid = document.getElementById('price-mid')?.checked || false;
    const pricePremium = document.getElementById('price-premium')?.checked || false;
    
    // Cuisine filters
    const cuisines = [];
    document.querySelectorAll('#cuisineFilters input[type="checkbox"]:checked').forEach(cb => {
        cuisines.push(cb.id.replace('cuisine-', ''));
    });
    
    // Dietary filters
    const dietary = [];
    if (document.getElementById('diet-vegetarian')?.checked) dietary.push('vegetarian');
    if (document.getElementById('diet-vegan')?.checked) dietary.push('vegan');
    if (document.getElementById('diet-halal')?.checked) dietary.push('halal');
    
    // Build query parameters
    const params = new URLSearchParams({
        ajax: 'restaurants',
        sort: sort
    });
    
    if (freeDelivery) params.append('free_delivery', '1');
    if (fastDelivery) params.append('fast_delivery', '1');
    if (priceBudget) params.append('price_budget', '1');
    if (priceMid) params.append('price_mid', '1');
    if (pricePremium) params.append('price_premium', '1');
    if (cuisines.length > 0) params.append('cuisines', cuisines.join(','));
    if (dietary.length > 0) params.append('dietary', dietary.join(','));
    
    fetch(`index.php?${params}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Invalid JSON: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('All restaurants data:', data);
            const container = document.getElementById('restaurantsGrid');
            
            if (data.error) {
                console.error('All restaurants error:', data.error);
                if (data.trace) console.error('Trace:', data.trace);
                container.innerHTML = '<p class="text-center text-danger">Error: ' + data.error + '</p>';
                return;
            }
            
            if (data.length === 0) {
                container.innerHTML = '<p class="text-center text-muted">No restaurants found matching your filters</p>';
                return;
            }
            
            container.innerHTML = data.map(restaurant => `
                <div class="swiper-slide">
                    <div class="product-card" onclick="window.location.href='vendor_profile.php?id=${restaurant.id}'" style="cursor: pointer;">
                        <div class="product-image" style="background-image: url('${restaurant.image}')">
                            <div class="product-badge">${restaurant.badge}</div>
                        </div>
                        <div class="product-info">
                            <div class="product-name">${restaurant.name}</div>
                            <div class="product-vendor">${restaurant.category}</div>
                            <div class="product-footer">
                                <div class="product-rating">
                                    <i class="fas fa-star"></i> ${restaurant.rating}
                                    <span class="text-muted">(${restaurant.reviews})</span>
                                </div>
                                <div class="delivery-time">
                                    <i class="fas fa-clock"></i> ${restaurant.time}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Initialize Swiper after content is loaded
            setTimeout(() => {
                new Swiper('.allRestaurantsSwiper', {
                    slidesPerView: 'auto',
                    spaceBetween: 15,
                    freeMode: true,
                    navigation: {
                        nextEl: '.allRestaurantsSwiper .swiper-button-next',
                        prevEl: '.allRestaurantsSwiper .swiper-button-prev',
                    },
                    breakpoints: {
                        320: { slidesPerView: 2 },
                        769: { slidesPerView: 'auto' }
                    }
                });
            }, 100);
        })
        .catch(error => {
            console.error('Error loading restaurants:', error);
            const container = document.getElementById('restaurantsGrid');
            if (container) {
                container.innerHTML = '<p class="text-center text-danger">Failed to load restaurants: ' + error.message + '</p>';
            }
        });
}

// Initialize Search
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    const suggestions = document.getElementById('searchSuggestions');
    
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        clearBtn.style.display = query ? 'flex' : 'none';
        
        if (query.length >= 2) {
            // Show suggestions (simplified version)
            suggestions.classList.add('show');
            suggestions.innerHTML = `
                <div class="suggestion-item">
                    <i class="fas fa-search suggestion-icon"></i>
                    <div class="suggestion-text">Search for "${query}"</div>
                </div>
            `;
        } else {
            suggestions.classList.remove('show');
        }
    });
    
    clearBtn?.addEventListener('click', function() {
        searchInput.value = '';
        clearBtn.style.display = 'none';
        suggestions.classList.remove('show');
    });
    
    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestions.contains(e.target)) {
            suggestions.classList.remove('show');
        }
    });
}

// Initialize Mobile Menu
function initializeMobileMenu() {
    const navToggle = document.getElementById('mobileNavToggle');
    const navTabs = document.getElementById('mainNavTabs');
    
    if (!navToggle || !navTabs) return;
    
    navToggle.addEventListener('click', function() {
        navTabs.classList.toggle('show');
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!navToggle.contains(e.target) && !navTabs.contains(e.target)) {
            navTabs.classList.remove('show');
        }
    });
}

// Initialize Filters
function initializeFilters() {
    // Desktop filters - Sort by radio buttons
    document.querySelectorAll('input[name="sort"]').forEach(radio => {
        radio.addEventListener('change', loadAllRestaurants);
    });
    
    // Desktop filters - All checkboxes
    document.querySelectorAll('.sidebar input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', loadAllRestaurants);
    });
    
    // Clear filters button
    document.getElementById('clearFilters')?.addEventListener('click', function() {
        document.querySelectorAll('.sidebar input[type="checkbox"]').forEach(cb => cb.checked = false);
        document.querySelector('input[name="sort"][value="relevance"]').checked = true;
        loadAllRestaurants();
    });
    
    // Mobile filters
    const filtersBtn = document.getElementById('mobileFiltersBtn');
    const filtersModal = document.getElementById('mobileFiltersModal');
    const closeFiltersBtn = document.getElementById('closeFiltersBtn');
    const applyFiltersBtn = document.getElementById('mobileApplyFilters');
    
    filtersBtn?.addEventListener('click', () => filtersModal?.classList.add('show'));
    closeFiltersBtn?.addEventListener('click', () => filtersModal?.classList.remove('show'));
    applyFiltersBtn?.addEventListener('click', () => {
        filtersModal?.classList.remove('show');
        loadAllRestaurants();
    });
    
    document.getElementById('mobileClearFilters')?.addEventListener('click', function() {
        document.querySelectorAll('.mobile-filters-modal input[type="checkbox"]').forEach(cb => cb.checked = false);
        document.querySelector('input[name="mobile-sort"][value="relevance"]').checked = true;
    });
}

// Note: Location modal initialization is now handled by location-tracker.js

// Initialize Swipers
function initializeSwipers() {
    // Cuisines Swiper - will be initialized after content loads in loadCuisines()
    
    // Products Swipers
    const productSwiperConfig = {
        slidesPerView: 'auto',
        spaceBetween: 15,
        freeMode: true,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        breakpoints: {
            320: { slidesPerView: 2 },
            769: { slidesPerView: 'auto' }
        }
    };
    
    new Swiper('.featuredProductsSwiper', productSwiperConfig);
    new Swiper('.topChoiceProductsSwiper', productSwiperConfig);
}

// Filter by Cuisine - Navigate to products page with category filter
function filterByCuisine(cuisineName) {
    // Encode the cuisine name for URL
    const encodedCuisine = encodeURIComponent(cuisineName);
    // Navigate to products page with category filter
    window.location.href = `products.php?category=${encodedCuisine}`;
}


// Language Change Function
function changeLanguage(lang) {
    // Store language preference
    localStorage.setItem('language', lang);
    
    // Show notification
    const langName = lang === 'en' ? 'English' : 'বাংলা';
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Language Changed',
            text: `Language changed to ${langName}`,
            timer: 1500,
            showConfirmButton: false
        });
    }
    
    // Reload page to apply language (in a real app, this would update translations)
    setTimeout(() => {
        location.reload();
    }, 1500);
}

// Load saved language on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedLang = localStorage.getItem('language') || 'en';
    // Update language display if needed
    console.log('Current language:', savedLang);
});
