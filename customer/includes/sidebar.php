<!-- Sidebar Filters -->
<aside class="sidebar">
    <div class="filter-section">
        <h6>Sort By</h6>
        <div class="filter-option">
            <input type="radio" name="sort" id="sort-relevance" value="relevance" checked>
            <label for="sort-relevance">Relevance</label>
        </div>
        <div class="filter-option">
            <input type="radio" name="sort" id="sort-fastest" value="fastest">
            <label for="sort-fastest">Fastest Delivery</label>
        </div>
        <div class="filter-option">
            <input type="radio" name="sort" id="sort-distance" value="distance">
            <label for="sort-distance">Distance</label>
        </div>
        <div class="filter-option">
            <input type="radio" name="sort" id="sort-rating" value="top-rated">
            <label for="sort-rating">Top Rated</label>
        </div>
    </div>

    <div class="filter-section">
        <h6>Delivery Options</h6>
        <div class="filter-option">
            <input type="checkbox" id="filter-free-delivery">
            <label for="filter-free-delivery">Free Delivery</label>
        </div>
        <div class="filter-option">
            <input type="checkbox" id="filter-fast-delivery">
            <label for="filter-fast-delivery">Fast Delivery (Under 30 min)</label>
        </div>
    </div>

    <div class="filter-section">
        <h6>Price Range</h6>
        <div class="filter-option">
            <input type="checkbox" id="price-budget">
            <label for="price-budget">৳ Budget Friendly</label>
        </div>
        <div class="filter-option">
            <input type="checkbox" id="price-mid">
            <label for="price-mid">৳৳ Mid Range</label>
        </div>
        <div class="filter-option">
            <input type="checkbox" id="price-premium">
            <label for="price-premium">৳৳৳ Premium</label>
        </div>
    </div>

    <div class="filter-section">
        <h6>Cuisines</h6>
        <div id="cuisineFilters">
            <div class="cuisine-item">
                <input type="checkbox" id="cuisine-bangladeshi">
                <label for="cuisine-bangladeshi">Bangladeshi</label>
            </div>
            <div class="cuisine-item">
                <input type="checkbox" id="cuisine-indian">
                <label for="cuisine-indian">Indian</label>
            </div>
            <div class="cuisine-item">
                <input type="checkbox" id="cuisine-chinese">
                <label for="cuisine-chinese">Chinese</label>
            </div>
            <div class="cuisine-item">
                <input type="checkbox" id="cuisine-italian">
                <label for="cuisine-italian">Italian</label>
            </div>
            <div class="cuisine-item">
                <input type="checkbox" id="cuisine-fastfood">
                <label for="cuisine-fastfood">Fast Food</label>
            </div>
        </div>
        <button class="show-more" id="showMoreCuisines">Show More</button>
    </div>

    <div class="filter-section">
        <h6>Dietary</h6>
        <div class="filter-option">
            <input type="checkbox" id="diet-vegetarian">
            <label for="diet-vegetarian">Vegetarian</label>
        </div>
        <div class="filter-option">
            <input type="checkbox" id="diet-vegan">
            <label for="diet-vegan">Vegan</label>
        </div>
        <div class="filter-option">
            <input type="checkbox" id="diet-halal">
            <label for="diet-halal">Halal</label>
        </div>
    </div>

    <button class="btn btn-outline-primary w-100 mt-3" id="clearFilters">
        <i class="fas fa-redo me-2"></i>Clear All Filters
    </button>
</aside>
