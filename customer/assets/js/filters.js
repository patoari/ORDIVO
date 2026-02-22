/**
 * ORDIVO - Filter Functionality
 * Handles all filter interactions and restaurant filtering
 */

// Global filter state
const filterState = {
    sort: 'relevance',
    freeDelivery: false,
    fastDelivery: false,
    priceRange: [],
    cuisines: [],
    dietary: []
};

// Initialize filters when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
});

function initializeFilters() {
    // Sort filters
    document.querySelectorAll('input[name="sort"]').forEach(radio => {
        radio.addEventListener('change', function() {
            filterState.sort = this.value;
            applyFilters();
        });
    });

    // Delivery options
    document.getElementById('filter-free-delivery')?.addEventListener('change', function() {
        filterState.freeDelivery = this.checked;
        applyFilters();
    });

    document.getElementById('filter-fast-delivery')?.addEventListener('change', function() {
        filterState.fastDelivery = this.checked;
        applyFilters();
    });

    // Price range filters
    document.getElementById('price-budget')?.addEventListener('change', function() {
        updateArrayFilter(filterState.priceRange, 'budget', this.checked);
        applyFilters();
    });

    document.getElementById('price-mid')?.addEventListener('change', function() {
        updateArrayFilter(filterState.priceRange, 'mid', this.checked);
        applyFilters();
    });

    document.getElementById('price-premium')?.addEventListener('change', function() {
        updateArrayFilter(filterState.priceRange, 'premium', this.checked);
        applyFilters();
    });

    // Cuisine filters
    const cuisineCheckboxes = [
        'cuisine-bangladeshi',
        'cuisine-indian',
        'cuisine-chinese',
        'cuisine-italian',
        'cuisine-fastfood'
    ];

    cuisineCheckboxes.forEach(id => {
        document.getElementById(id)?.addEventListener('change', function() {
            const cuisine = this.id.replace('cuisine-', '');
            updateArrayFilter(filterState.cuisines, cuisine, this.checked);
            applyFilters();
        });
    });

    // Dietary filters
    document.getElementById('diet-vegetarian')?.addEventListener('change', function() {
        updateArrayFilter(filterState.dietary, 'vegetarian', this.checked);
        applyFilters();
    });

    document.getElementById('diet-vegan')?.addEventListener('change', function() {
        updateArrayFilter(filterState.dietary, 'vegan', this.checked);
        applyFilters();
    });

    document.getElementById('diet-halal')?.addEventListener('change', function() {
        updateArrayFilter(filterState.dietary, 'halal', this.checked);
        applyFilters();
    });

    // Clear filters button
    document.getElementById('clearFilters')?.addEventListener('click', clearAllFilters);

    // Show more cuisines button
    document.getElementById('showMoreCuisines')?.addEventListener('click', function() {
        // Toggle show more cuisines
        const cuisineFilters = document.getElementById('cuisineFilters');
        cuisineFilters.classList.toggle('expanded');
        this.textContent = cuisineFilters.classList.contains('expanded') ? 'Show Less' : 'Show More';
    });
}

function updateArrayFilter(array, value, add) {
    const index = array.indexOf(value);
    if (add && index === -1) {
        array.push(value);
    } else if (!add && index !== -1) {
        array.splice(index, 1);
    }
}

function applyFilters() {
    console.log('Applying filters:', filterState);
    
    // Show loading state
    const restaurantsContainer = document.getElementById('featuredRestaurants');
    if (restaurantsContainer) {
        restaurantsContainer.innerHTML = '<div class="loading"><div class="spinner"></div><p>Filtering restaurants...</p></div>';
    }

    // Build query parameters
    const params = new URLSearchParams({
        ajax: 'restaurants',
        sort: filterState.sort,
        free_delivery: filterState.freeDelivery ? '1' : '0',
        fast_delivery: filterState.fastDelivery ? '1' : '0',
        price_range: filterState.priceRange.join(','),
        cuisines: filterState.cuisines.join(','),
        dietary: filterState.dietary.join(',')
    });

    // Fetch filtered restaurants
    fetch(`?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            displayFilteredRestaurants(data);
        })
        .catch(error => {
            console.error('Error applying filters:', error);
            restaurantsContainer.innerHTML = '<div class="error-message">Error loading restaurants. Please try again.</div>';
        });
}

function displayFilteredRestaurants(restaurants) {
    const container = document.getElementById('featuredRestaurants');
    
    if (!restaurants || restaurants.length === 0) {
        container.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search fa-3x mb-3"></i>
                <h4>No restaurants found</h4>
                <p>Try adjusting your filters to see more results</p>
            </div>
        `;
        return;
    }

    container.innerHTML = restaurants.map(restaurant => `
        <div class="restaurant-card" onclick="window.location.href='restaurant.php?id=${restaurant.id}'">
            <div class="restaurant-image">
                <img src="${restaurant.image}" alt="${restaurant.name}" onerror="this.src='../uploads/images/placeholder-food.svg'">
                ${restaurant.badge ? `<span class="badge">${restaurant.badge}</span>` : ''}
                ${restaurant.freeDelivery ? '<span class="free-delivery-badge"><i class="fas fa-shipping-fast"></i> Free Delivery</span>' : ''}
            </div>
            <div class="restaurant-info">
                <h3>${restaurant.name}</h3>
                <p class="category">${restaurant.category}</p>
                <div class="restaurant-meta">
                    <span class="rating">
                        <i class="fas fa-star"></i> ${restaurant.rating}
                    </span>
                    <span class="reviews">(${restaurant.reviews})</span>
                    <span class="time">
                        <i class="far fa-clock"></i> ${restaurant.time}
                    </span>
                </div>
                ${restaurant.priceRange ? `<div class="price-range">${restaurant.priceRange}</div>` : ''}
            </div>
        </div>
    `).join('');
}

function clearAllFilters() {
    // Reset filter state
    filterState.sort = 'relevance';
    filterState.freeDelivery = false;
    filterState.fastDelivery = false;
    filterState.priceRange = [];
    filterState.cuisines = [];
    filterState.dietary = [];

    // Reset UI
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.checked = radio.value === 'relevance';
    });

    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Reload restaurants
    applyFilters();
}

// Export for use in other scripts
window.filterState = filterState;
window.applyFilters = applyFilters;
window.clearAllFilters = clearAllFilters;
