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
    initializeLocationModal();
    initializeSwipers();
}

// Load Featured Restaurants
function loadFeaturedRestaurants() {
    fetch('index.php?ajax=featured_restaurants')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('featuredRestaurants');
            if (data.length === 0) {
                container.innerHTML = '<p class="text-center text-muted">No restaurants available</p>';
                return;
            }
            
            container.innerHTML = data.map(restaurant => `
                <div class="restaurant-card" onclick="window.location.href='vendor_profile.php?id=${restaurant.id}'">
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
                            <div class="text-muted">${restaurant.category}</div>
                        </div>
                        <div class="delivery-time">
                            <i class="fas fa-clock"></i> ${restaurant.time}
                        </div>
                    </div>
                </div>
            `).join('');
        })
        .catch(error => {
            console.error('Error loading restaurants:', error);
            document.getElementById('featuredRestaurants').innerHTML = 
                '<p class="text-center text-danger">Failed to load restaurants</p>';
        });
}

// Load Cuisines
function loadCuisines() {
    fetch('index.php?ajax=categories')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('cuisinesContainer');
            if (data.error || data.length === 0) {
                container.innerHTML = '<p class="text-center text-muted">No cuisines available</p>';
                return;
            }
            
            container.innerHTML = data.map(cuisine => `
                <div class="swiper-slide">
                    <div class="cuisine-card" onclick="filterByCuisine('${cuisine.name}')">
                        <div class="cuisine-icon">
                            ${cuisine.image ? 
                                `<img src="${cuisine.image}" alt="${cuisine.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">` : 
                                (cuisine.icon ? `<i class="${cuisine.icon}"></i>` : '<i class="fas fa-utensils"></i>')
                            }
                        </div>
                        <div class="cuisine-name">${cuisine.name}</div>
                    </div>
                </div>
            `).join('');
        })
        .catch(error => console.error('Error loading cuisines:', error));
}

// Load Featured Products
function loadFeaturedProducts() {
    fetch('index.php?ajax=featured_products')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('featuredProductsContainer');
            const topChoiceContainer = document.getElementById('topChoiceProductsContainer');
            
            if (data.error || data.length === 0) {
                container.innerHTML = '<p class="text-center text-muted">No products available</p>';
                topChoiceContainer.innerHTML = '<p class="text-center text-muted">No products available</p>';
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
            topChoiceContainer.innerHTML = productHTML;
        })
        .catch(error => console.error('Error loading products:', error));
}

// Load All Restaurants
function loadAllRestaurants() {
    const sort = document.querySelector('input[name="sort"]:checked')?.value || 'relevance';
    
    fetch(`index.php?ajax=restaurants&sort=${sort}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('restaurantsGrid');
            if (data.error || data.length === 0) {
                container.innerHTML = '<div class="col-12"><p class="text-center text-muted">No restaurants available</p></div>';
                return;
            }
            
            container.innerHTML = data.map(restaurant => `
                <div class="col-lg-4 col-md-6 col-6 mb-4">
                    <div class="restaurant-card" onclick="window.location.href='vendor_profile.php?id=${restaurant.id}'">
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
                                <div class="text-muted">${restaurant.category}</div>
                            </div>
                            <div class="delivery-time">
                                <i class="fas fa-clock"></i> ${restaurant.time}
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        })
        .catch(error => console.error('Error loading restaurants:', error));
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
    // Desktop filters
    document.querySelectorAll('input[name="sort"]').forEach(radio => {
        radio.addEventListener('change', loadAllRestaurants);
    });
    
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

// Initialize Location Modal
function initializeLocationModal() {
    const locationOptions = document.querySelectorAll('.location-option');
    const confirmBtn = document.getElementById('confirmLocation');
    let selectedLocation = 'Dhaka, Bangladesh';
    
    locationOptions.forEach(option => {
        option.addEventListener('click', function() {
            locationOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            selectedLocation = this.dataset.location;
        });
    });
    
    confirmBtn?.addEventListener('click', function() {
        document.getElementById('currentLocation').textContent = selectedLocation;
        document.getElementById('currentLocationMobile').textContent = selectedLocation;
        bootstrap.Modal.getInstance(document.getElementById('locationModal'))?.hide();
    });
}

// Initialize Swipers
function initializeSwipers() {
    // Cuisines Swiper
    new Swiper('.cuisinesSwiper', {
        slidesPerView: 'auto',
        spaceBetween: 15,
        freeMode: true,
        breakpoints: {
            320: { slidesPerView: 4 },
            769: { slidesPerView: 'auto' }
        }
    });
    
    // Products Swipers
    const productSwiperConfig = {
        slidesPerView: 'auto',
        spaceBetween: 15,
        freeMode: true,
        breakpoints: {
            320: { slidesPerView: 2 },
            769: { slidesPerView: 'auto' }
        }
    };
    
    new Swiper('.featuredProductsSwiper', productSwiperConfig);
    new Swiper('.topChoiceProductsSwiper', productSwiperConfig);
}

// Restaurant Carousel Navigation
function scrollCarousel(direction) {
    const container = document.querySelector('.restaurant-cards');
    const scrollAmount = 300;
    
    if (direction === 'prev') {
        container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    } else {
        container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
}

// Filter by Cuisine
function filterByCuisine(cuisine) {
    console.log('Filtering by cuisine:', cuisine);
    // Implement cuisine filtering logic
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
